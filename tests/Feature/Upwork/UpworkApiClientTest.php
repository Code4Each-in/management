<?php

namespace Tests\Feature\Upwork;

use App\Console\UpworkSchedule;
use App\Models\UpworkApiCredential;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Api\Contracts\UpworkClientInterface;
use App\Services\Upwork\Api\FixtureUpworkClient;
use App\Services\Upwork\Api\GraphQlUpworkClient;
use App\Services\Upwork\Api\ReadOnlyGraphQl;
use App\Services\Upwork\Api\UpworkClientFactory;
use App\Services\Upwork\Api\UpworkOAuth;
use App\Services\Upwork\Api\UpworkWriteRefused;
use App\Services\Upwork\Discovery\ServerDiscoveryRunner;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * AI redesign phase 5: server-side discovery through a read-only Upwork client
 * (official GraphQL API, or fixture data in development), OAuth token storage,
 * scheduler entries. No request ever leaves the machine: HTTP is faked.
 */
class UpworkApiClientTest extends UpworkTestCase
{
    const GRAPHQL = 'https://api.upwork.com/graphql';
    const TOKEN = 'https://www.upwork.com/api/v3/oauth2/token';

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::swap(new Factory());
        config(['upwork.api.client_id' => 'test-client', 'upwork.api.client_secret' => 'test-secret']);
    }

    private function connect(array $overrides = []): UpworkApiCredential
    {
        return UpworkApiCredential::create(array_merge([
            'label' => 'default', 'access_token' => 'access-1', 'refresh_token' => 'refresh-1', 'tenant_id' => 'org-42',
            'expires_at' => now()->addHours(12), 'refresh_expires_at' => now()->addDays(10),
        ], $overrides));
    }

    private function searchResponse(array $nodes, bool $hasNext = false): array
    {
        return ['data' => ['marketplaceJobPostingsSearch' => [
            'totalCount' => count($nodes), 'pageInfo' => ['hasNextPage' => $hasNext, 'endCursor' => $hasNext ? 'c2' : null],
            'edges' => array_map(fn($n) => ['node' => $n], $nodes),
        ]]];
    }

    private function node(string $id = '2107561771980939922'): array
    {
        return ['id' => $id, 'ciphertext' => "~02{$id}", 'title' => 'Laravel developer for SaaS dashboard',
            'description' => 'Extend our Laravel SaaS platform with Stripe billing.', 'publishedDateTime' => '2026-10-08T06:00:00Z',
            'experienceLevel' => 'EXPERT', 'durationLabel' => '3 to 6 months', 'engagement' => 'Part-time',
            'hourlyBudgetMin' => ['rawValue' => '40'], 'hourlyBudgetMax' => ['rawValue' => '70'], 'amount' => ['rawValue' => '0'],
            'skills' => [['name' => 'laravel', 'prettyName' => 'Laravel']], 'totalApplicants' => 7,
            'client' => ['totalHires' => 30, 'totalPostedJobs' => 40, 'totalReviews' => 25, 'totalFeedback' => 4.9,
                'verificationStatus' => 'VERIFIED', 'location' => ['country' => 'United States'], 'totalSpent' => ['rawValue' => '80000']]];
    }

    // ---- Read-only guarantee ----------------------------------------------------------------

    public function test_only_allowlisted_named_queries_pass_the_read_only_gate(): void
    {
        $allowed = array_keys(GraphQlUpworkClient::OPERATIONS);
        foreach (GraphQlUpworkClient::OPERATIONS as $name => $document) {
            $this->assertSame($name, ReadOnlyGraphQl::assert($document, $allowed));
        }

        $refused = [
            'mutation { submitProposal(input: {}) { id } }',
            'query JobSearch { x } mutation Save { saveJob(id: 1) }',
            'subscription S { jobs { id } }',
            '{ marketplaceJobPostingsSearch { totalCount } }',              // anonymous
            'query Other { marketplaceJobPosting(id: "1") { id } }',       // not allowlisted
            'query JobSearch { a } query JobDetail { b }',                 // two operations
            "# harmless\nmutation M { sendMessage(text: \"hi\") { id } }",
            'query JobSearch($m: String = "mutation") { a } MUTATION X { y }',
        ];
        foreach ($refused as $doc) {
            try {
                ReadOnlyGraphQl::assert($doc, $allowed);
                $this->fail("Should be refused: {$doc}");
            } catch (UpworkWriteRefused $e) {
                $this->assertStringStartsWith('Refused', $e->getMessage());
            }
        }
    }

    public function test_no_upwork_write_can_be_invoked(): void
    {
        // The client interface has exactly two read operations.
        $methods = array_map(fn($m) => $m->name, (new \ReflectionClass(UpworkClientInterface::class))->getMethods());
        $this->assertEqualsCanonicalizing(['name', 'isConfigured', 'search', 'job'], $methods);

        // No public way to send an arbitrary document.
        $public = array_map(fn($m) => $m->name, (new \ReflectionClass(GraphQlUpworkClient::class))->getMethods(\ReflectionMethod::IS_PUBLIC));
        $this->assertEqualsCanonicalizing(['__construct', 'name', 'isConfigured', 'search', 'job', 'searchFilter', 'mapSearchNode', 'mapJobDetail'], $public);

        // An unknown operation is refused before anything is sent.
        Http::fake();
        $this->connect();
        $execute = (new \ReflectionClass(GraphQlUpworkClient::class))->getMethod('execute');
        $execute->setAccessible(true);
        try {
            $execute->invoke(new GraphQlUpworkClient(), 'SubmitProposal', []);
            $this->fail('Unknown operation must be refused');
        } catch (UpworkWriteRefused $e) {
            Http::assertNothingSent();
        }

        // No GraphQL mutation text anywhere in the module's code.
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path('Services/Upwork')));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php' || $file->getFilename() === 'ReadOnlyGraphQl.php') {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('/\bmutation\s*[A-Za-z_({]/i', file_get_contents($file->getPathname()), $file->getFilename());
        }
    }

    public function test_every_request_sent_is_a_read_only_query(): void
    {
        Http::fake([self::GRAPHQL => Http::sequence()->push($this->searchResponse([$this->node()]))->push(['data' => ['marketplaceJobPosting' => ['id' => '2107561771980939922']]])]);
        $this->connect();
        $client = new GraphQlUpworkClient();

        $client->search(['query' => 'Laravel', 'limit' => 10]);
        $client->job('~022107561771980939922');

        Http::assertSentCount(2);
        Http::assertSent(function (Request $r) {
            $allowed = array_keys(GraphQlUpworkClient::OPERATIONS);
            return $r->url() === self::GRAPHQL && in_array(ReadOnlyGraphQl::assert($r['query'], $allowed), $allowed, true)
                && $r->hasHeader('Authorization', 'Bearer access-1') && $r->hasHeader('X-Upwork-API-TenantId', 'org-42');
        });
    }

    // ---- GraphQL client ---------------------------------------------------------------------

    public function test_search_maps_upwork_nodes_to_pms_rows(): void
    {
        Http::fake([self::GRAPHQL => Http::response($this->searchResponse([$this->node(), ['title' => 'no id']], true))]);
        $this->connect();

        $result = (new GraphQlUpworkClient())->search(['query' => 'Laravel', 'limit' => 5, 'verified_payment_only' => true, 'experience_level' => 'expert', 'rate_min' => 25], 'c1');

        $this->assertTrue($result->ok);
        $this->assertSame('c2', $result->nextCursor);
        $this->assertCount(1, $result->rows);
        $row = $result->rows[0];
        $this->assertSame('2107561771980939922', $row['upwork_job_id']);
        $this->assertSame('https://www.upwork.com/jobs/~022107561771980939922', $row['upwork_url']);
        $this->assertSame(['hourly', 40.0, 70.0, 'expert', 'part_time'], [$row['job_type'], $row['hourly_min'], $row['hourly_max'], $row['experience_level'], $row['workload']]);
        $this->assertSame([80000.0, 75.0, true, 7.0], [$row['client_total_spent'], $row['client_hire_rate'], $row['payment_verified'], $row['proposals_count']]);
        $this->assertSame(['Laravel'], $row['raw_data']['skills']);

        Http::assertSent(function (Request $r) {
            $f = $r['variables']['filter'];
            return $f['searchExpression_eq'] === 'Laravel' && $f['verifiedPaymentOnly_eq'] === true && $f['experienceLevel_eq'] === 'EXPERT'
                && $f['hourlyRate_eq'] === ['rangeStart' => 25.0] && $f['pagination_eq'] === ['first' => 5, 'after' => 'c1'];
        });
    }

    public function test_job_detail_maps_contractor_type_and_client_id(): void
    {
        Http::fake([self::GRAPHQL => Http::response(['data' => ['marketplaceJobPosting' => [
            'id' => '2107561771980939922', 'content' => ['title' => 'Laravel', 'description' => 'Full description'],
            'contractTerms' => ['contractType' => 'HOURLY', 'experienceLevel' => 'EXPERT', 'hourlyContractTerms' => ['hourlyBudgetMin' => 40, 'hourlyBudgetMax' => 70, 'engagementDuration' => ['label' => '3 to 6 months']]],
            'contractorSelection' => ['qualification' => ['contractorType' => 'INDEPENDENT']],
            'activityStat' => ['jobActivity' => ['totalInvitedToInterview' => 2, 'invitesSent' => 5, 'totalHired' => 0]],
            'clientCompanyPublic' => ['id' => '1234567890', 'country' => ['name' => 'Canada'], 'paymentVerification' => ['paymentVerified' => true]],
        ]]])]);
        $this->connect();

        $row = (new GraphQlUpworkClient())->job('2107561771980939922')->rows[0];

        $this->assertSame(['Individuals only', '1234567890', 'Canada', 2.0, 'hourly'], [$row['contractor_type'], $row['upwork_client_id'], $row['client_country'], $row['interviews_count'], $row['job_type']]);
        $this->assertSame(5, $row['job_activity']['invites_sent']);
    }

    public function test_rate_limits_errors_and_missing_connection_are_classified(): void
    {
        $client = new GraphQlUpworkClient();
        $this->assertSame('not_configured', $client->search(['query' => 'x'])->errorCode);

        $this->connect();
        Http::fake([self::GRAPHQL => Http::sequence()
            ->push([], 429)
            ->push(['errors' => [['message' => 'Rate limit exceeded']]])
            ->push([], 503)
            ->push(['errors' => [['message' => 'Field not found']]])]);

        $this->assertTrue($client->search(['query' => 'x'])->rateLimited());
        $this->assertTrue($client->search(['query' => 'x'])->rateLimited());
        $this->assertSame('server_error', $client->search(['query' => 'x'])->errorCode);
        $this->assertSame('invalid_response', $client->search(['query' => 'x'])->errorCode);
    }

    // ---- OAuth tokens -----------------------------------------------------------------------

    public function test_expired_tokens_are_refreshed_and_stored_encrypted(): void
    {
        $this->connect(['expires_at' => now()->subMinute()]);
        Http::fake([
            self::TOKEN => Http::response(['access_token' => 'access-2', 'refresh_token' => 'refresh-2', 'expires_in' => 86400, 'token_type' => 'Bearer']),
            self::GRAPHQL => Http::response($this->searchResponse([])),
        ]);

        (new GraphQlUpworkClient())->search(['query' => 'x']);

        $credential = UpworkApiCredential::current();
        $this->assertSame(['access-2', 'refresh-2'], [$credential->access_token, $credential->refresh_token]);
        $raw = DB::table('upwork_api_credentials')->first();
        $this->assertStringNotContainsString('access-2', $raw->access_token, 'encrypted at rest');
        $this->assertStringNotContainsString('refresh-2', $raw->refresh_token);
        $this->assertTrue($credential->refresh_expires_at->gt(now()->addDays(13)));
        $this->assertArrayNotHasKey('access_token', $credential->toArray(), 'hidden from serialization');
        Http::assertSent(fn(Request $r) => $r->url() === self::TOKEN && $r['grant_type'] === 'refresh_token' && $r->hasHeader('Authorization', 'Basic ' . base64_encode('test-client:test-secret')));
        Http::assertSent(fn(Request $r) => $r->url() === self::GRAPHQL && $r->hasHeader('Authorization', 'Bearer access-2'));
    }

    public function test_a_401_triggers_one_refresh_and_one_retry(): void
    {
        $this->connect();
        Http::fake([
            self::TOKEN => Http::response(['access_token' => 'access-2', 'expires_in' => 86400]),
            self::GRAPHQL => Http::sequence()->push([], 401)->push($this->searchResponse([$this->node()])),
        ]);

        $result = (new GraphQlUpworkClient())->search(['query' => 'x']);

        $this->assertTrue($result->ok);
        $this->assertSame('refresh-1', UpworkApiCredential::current()->refresh_token, 'kept when Upwork does not rotate it');
    }

    public function test_oauth_connect_callback_and_disconnect(): void
    {
        $admin = $this->superAdmin();
        Http::fake([self::TOKEN => Http::response(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 86400])]);

        $redirect = $this->actingAs($admin)->get(route('upwork.config.api.connect'))->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://www.upwork.com/ab/account-security/oauth2/authorize?', $redirect);
        parse_str(parse_url($redirect, PHP_URL_QUERY), $q);

        $this->actingAs($admin)->get(route('upwork.config.api.callback', ['state' => 'forged', 'code' => 'c']))->assertSessionHas('error');
        $this->assertNull(UpworkApiCredential::current());

        $this->actingAs($admin)->get(route('upwork.config.api.connect'));
        $state = session('upwork_oauth_state');
        $this->actingAs($admin)->get(route('upwork.config.api.callback', ['state' => $state, 'code' => 'c']))->assertSessionHas('success', 'Upwork API connected (read-only).');
        $this->assertTrue(UpworkApiCredential::current()->isConnected());

        $this->actingAs($admin)->get(route('upwork.config.settings.edit'))->assertOk()->assertSee('Connected')->assertDontSee('>a<', false);
        $this->actingAs($admin)->post(route('upwork.config.api.disconnect'))->assertSessionHas('success');
        $this->assertNull(UpworkApiCredential::current());

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->bde())->get(route('upwork.config.api.connect'))->assertForbidden();
    }

    // ---- Server-side discovery --------------------------------------------------------------

    public function test_server_cycle_discovers_enriches_and_scores_from_fixture_data(): void
    {
        Http::fake();
        UpworkSetting::set('agent.enrich_limit_per_run', 5);

        $out = app(ServerDiscoveryRunner::class, ['pause' => function () {}])->run('manual', new FixtureUpworkClient());

        $this->assertSame('completed', $out['status'], $out['message']);
        $run = UpworkDiscoveryRun::where('uuid', $out['run'])->first();
        $this->assertSame(UpworkDiscoveryRun::STATUS_COMPLETED, $run->status);
        $this->assertSame('server:fixture', $run->agent_host);
        $this->assertGreaterThan(0, $out['stats']['searches']);
        $this->assertGreaterThan(0, UpworkOpportunity::count());
        $this->assertLessThanOrEqual(5, $out['stats']['enriched']);
        $this->assertSame($out['stats']['enriched'], UpworkOpportunity::where('stage', UpworkOpportunity::STAGE_ENRICHED)->count());
        $this->assertSame(0, UpworkOpportunity::whereNull('ruleset_version')->count(), 'everything went through local scoring');
        $this->assertSame(0, UpworkOpportunity::whereNull('score')->whereNull('hard_filters')->count());
        Http::assertNothingSent();
    }

    public function test_scheduled_runs_respect_the_switch_and_the_interval(): void
    {
        $runner = app(ServerDiscoveryRunner::class, ['pause' => function () {}]);

        $this->assertStringContainsString('switched off', $runner->run('scheduled', new FixtureUpworkClient())['message']);
        $this->assertSame(0, UpworkDiscoveryRun::count());

        UpworkSetting::set('agent.enabled', true);
        $this->assertSame('completed', $runner->run('scheduled', new FixtureUpworkClient())['status']);
        $this->assertStringContainsString('Not due', $runner->run('scheduled', new FixtureUpworkClient())['message']);

        $this->travel(21)->minutes();
        $this->assertSame('completed', $runner->run('scheduled', new FixtureUpworkClient())['status']);
        $this->assertStringContainsString('not connected', $runner->run('manual', new GraphQlUpworkClient())['message']);

        // Nothing due: skipped without creating an empty run record.
        $runs = UpworkDiscoveryRun::count();
        \App\Models\UpworkSearchProfile::query()->update(['last_run_at' => now()]);
        $this->assertSame('No search profile is due yet.', $runner->run('manual', new FixtureUpworkClient())['message']);
        $this->assertSame($runs, UpworkDiscoveryRun::count());
    }

    public function test_rate_limit_stops_the_run_and_backs_off(): void
    {
        $client = new FixtureUpworkClient(null, 'rate_limited');

        $out = app(ServerDiscoveryRunner::class, ['pause' => function () {}])->run('manual', $client);

        $this->assertSame('completed', $out['status']);
        $this->assertSame('rate_limited', $out['stats']['stopped']);
        $this->assertSame(1, $client->calls, 'no further Upwork calls after a rate limit');
        $this->assertSame(1, \App\Models\UpworkSearchProfile::whereNotNull('backoff_until')->count());
    }

    public function test_fixture_client_is_refused_in_production(): void
    {
        $this->assertInstanceOf(FixtureUpworkClient::class, UpworkClientFactory::make('fixture'));
        $this->app['env'] = 'production';
        $this->assertInstanceOf(GraphQlUpworkClient::class, UpworkClientFactory::make('fixture'));
        $this->assertSame(['graphql', 'mcp'], UpworkClientFactory::available());
    }

    // ---- Scheduler --------------------------------------------------------------------------

    public function test_scheduler_entries_are_off_by_default_and_guarded_when_on(): void
    {
        $schedule = new Schedule();
        UpworkSchedule::register($schedule);
        $this->assertSame([], $schedule->events());

        config(['upwork.scheduler.enabled' => true]);
        UpworkSchedule::register($schedule);
        $events = collect($schedule->events());

        $commands = $events->map(fn($e) => preg_replace('/^.*artisan[\'"]?\s+/', '', $e->command))->all();
        $this->assertSame(['upwork:discover', 'queue:work upwork --queue=upwork --stop-when-empty --max-time=50 --tries=1 --timeout=240', 'upwork:token-refresh'], $commands);
        $this->assertTrue($events->every(fn($e) => $e->withoutOverlapping), 'no overlapping runs');
        $this->assertSame(['*/5 * * * *', '* * * * *', '15 3 * * *'], $events->pluck('expression')->all());
    }
}
