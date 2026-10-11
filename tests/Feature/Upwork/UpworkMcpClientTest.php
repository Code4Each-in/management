<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkApiCredential;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Api\McpUpworkClient;
use App\Services\Upwork\Api\UpworkClientFactory;
use App\Services\Upwork\Api\UpworkWriteRefused;
use App\Services\Upwork\Discovery\RowValidator;
use App\Services\Upwork\Discovery\ServerDiscoveryRunner;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Upwork MCP called directly by the PMS (no AI model). Read-only: only
 * find_jobs (search, get) and list_accounts can ever be sent. Every request
 * is faked; responses are real (sanitized) Upwork MCP answers from 9 Oct 2026.
 */
class UpworkMcpClientTest extends UpworkTestCase
{
    const MCP = 'https://mcp.upwork.com/mcp';
    const TOKEN = 'https://www.upwork.com/api/v3/oauth2/token';
    const REGISTER = 'https://www.upwork.com/register';
    const ORG = '1000000000000000002'; // fictional agency org_uid

    /** @var array tool calls the fake MCP received */
    private $calls = [];
    /** @var array scripted HTTP statuses for upcoming tools/call requests */
    private $toolStatuses = [];

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::swap(new Factory());
    }

    private function fixture(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/Fixtures/Upwork/{$name}.json")), true);
    }

    private function connect(array $overrides = []): UpworkApiCredential
    {
        UpworkSetting::set('mcp.client', ['client_id' => 'pms-client', 'redirect_uri' => route('upwork.config.mcp.callback'), 'registered_at' => now()->toIso8601String()]);
        UpworkSetting::set('mcp.accounts', [['org_uid' => self::ORG, 'name' => 'Code4Each', 'role' => 'FL_AGENCY', 'role_label' => 'Agency']]);

        return UpworkApiCredential::create(array_merge([
            'label' => 'mcp', 'access_token' => 'mcp-access-1', 'refresh_token' => 'mcp-refresh-1', 'tenant_id' => self::ORG,
            'expires_at' => now()->addHour(), 'refresh_expires_at' => now()->addDays(10),
        ], $overrides));
    }

    private function toolResult(int $id, $data, bool $isError = false): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['content' => [['type' => 'text', 'text' => is_string($data) ? $data : json_encode($data)]]] + ($isError ? ['isError' => true] : [])];
    }

    /** A fake Upwork MCP + OAuth server that records every tool call. */
    private function fakeUpwork(array $overrides = []): void
    {
        Http::fake(function (Request $r) use ($overrides) {
            if ($r->url() === self::REGISTER) {
                return Http::response(['client_id' => 'pms-client', 'registration_access_token' => 'secret-mgmt', 'token_endpoint_auth_method' => 'none'], 201);
            }
            if ($r->url() === self::TOKEN) {
                return Http::response(['access_token' => 'mcp-access-2', 'refresh_token' => 'mcp-refresh-2', 'token_type' => 'Bearer', 'expires_in' => 3600]);
            }
            if ($r->url() !== self::MCP) {
                return Http::response('unexpected host', 500);
            }

            $msg = json_decode($r->body(), true);
            switch ($msg['method']) {
                case 'initialize':
                    return Http::response(['jsonrpc' => '2.0', 'id' => $msg['id'], 'result' => ['serverInfo' => ['name' => 'fake-upwork-mcp']]], 200, ['Mcp-Session-Id' => 'sess-1']);
                case 'notifications/initialized':
                    return Http::response('', 202);
                case 'tools/call':
                    $this->calls[] = $msg['params'] + ['session' => $r->header('Mcp-Session-Id')[0] ?? null, 'auth' => $r->header('Authorization')[0] ?? null];
                    if ($status = array_shift($this->toolStatuses)) {
                        return Http::response('', $status);
                    }
                    $name = $msg['params']['name'];
                    $action = $msg['params']['arguments']['action'] ?? null;
                    $key = $action ? "{$name}:{$action}" : $name;
                    if (isset($overrides[$key])) {
                        return Http::response($overrides[$key]($msg['id']), 200);
                    }
                    if ($key === 'upwork__find_jobs:search') {
                        return Http::response($this->toolResult($msg['id'], $this->fixture('mcp_search')));
                    }
                    if ($key === 'upwork__find_jobs:get') {
                        return Http::response($this->toolResult($msg['id'], $this->fixture('mcp_get')));
                    }
                    if ($key === 'upwork__list_accounts') {
                        return Http::response($this->toolResult($msg['id'], ['accounts' => [
                            ['name' => 'Test Freelancer', 'org_uid' => '1000000000000000001', 'role' => 'TALENT', 'role_label' => 'Freelancer'],
                            ['name' => 'Code4Each', 'org_uid' => self::ORG, 'role' => 'FL_AGENCY', 'role_label' => 'Agency'],
                        ]]));
                    }
                    return Http::response(['jsonrpc' => '2.0', 'id' => $msg['id'], 'error' => ['message' => 'unknown tool']]);
            }

            return Http::response('', 400);
        });
    }

    // ---- Read-only guarantee ----------------------------------------------------------------

    public function test_write_tools_and_write_actions_are_refused_before_sending(): void
    {
        $this->connect();
        $this->fakeUpwork();
        $call = (new \ReflectionClass(McpUpworkClient::class))->getMethod('callTool');
        $call->setAccessible(true);

        foreach ([
            ['send_message', []], ['save_job', []], ['manage_proposals', ['action' => 'submit']], ['respond_to_offer', []],
            ['update_profile', []], ['submit_milestones', []], ['set_tool_permission', []],
            ['find_jobs', ['action' => 'save']], ['find_jobs', ['action' => 'smart_search']], ['find_jobs', []],
        ] as [$tool, $args]) {
            try {
                $call->invoke(new McpUpworkClient(), $tool, $args);
                $this->fail("{$tool} must be refused");
            } catch (UpworkWriteRefused $e) {
                $this->assertStringStartsWith('Refused', $e->getMessage());
            }
        }

        Http::assertNothingSent();
        $this->assertSame(['find_jobs' => ['search', 'get'], 'list_accounts' => null], McpUpworkClient::TOOLS);
    }

    public function test_no_public_method_takes_a_tool_name(): void
    {
        $public = array_map(fn($m) => $m->name, (new \ReflectionClass(McpUpworkClient::class))->getMethods(\ReflectionMethod::IS_PUBLIC));

        $this->assertEqualsCanonicalizing(['__construct', 'name', 'isConfigured', 'search', 'job', 'accounts', 'mapSearchJob', 'mapJobDetail', 'text', 'decode'], $public);
    }

    public function test_module_code_never_names_an_mcp_write_tool_in_a_string(): void
    {
        $writeTools = ['send_message', 'save_job', 'manage_proposals', 'respond_to_offer', 'update_profile', 'submit_milestones',
            'update_agency', 'boost_profile', 'confirm_draft', 'manage_meetings', 'set_tool_permission', 'set_tool_mode', 'start_attachment_upload'];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
                if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                    foreach ($writeTools as $tool) {
                        $this->assertStringNotContainsString($tool, $token[1], "{$file->getFilename()} names {$tool}");
                    }
                }
            }
        }
    }

    public function test_every_tool_call_sent_is_read_only_and_runs_as_the_chosen_account(): void
    {
        $this->connect();
        $this->fakeUpwork();
        $client = new McpUpworkClient();

        $client->search(['query' => 'Laravel', 'limit' => 25, 'sort' => 'recency', 'include_full_details' => true, 'pages' => 2, 'unknown' => 'x'], '5');
        $client->job('~022108471673182427072');

        $this->assertCount(2, $this->calls);
        foreach ($this->calls as $c) {
            $this->assertContains($c['name'], ['upwork__find_jobs']);
            $this->assertContains($c['arguments']['action'], ['search', 'get']);
            $this->assertSame(self::ORG, $c['arguments']['org_uid']);
            $this->assertSame('sess-1', $c['session'], 'session from initialize is reused');
            $this->assertSame('Bearer mcp-access-1', $c['auth']);
        }
        $this->assertSame(['query' => 'Laravel', 'limit' => 10, 'sort' => 'recency', 'include_full_details' => true, 'cursor' => '5'], $this->calls[0]['arguments']['params'], 'limit capped at 10; PMS-only and unknown params dropped');
        $this->assertSame(['id' => '2108471673182427072'], $this->calls[1]['arguments']['params']);
        Http::assertSentCount(4); // initialize, initialized, two tool calls: one session for the run
    }

    // ---- Mapping (real responses) -----------------------------------------------------------

    public function test_search_results_map_to_valid_pms_rows(): void
    {
        $this->connect();
        $this->fakeUpwork();

        $result = (new McpUpworkClient())->search(['query' => 'Laravel']);

        $this->assertTrue($result->ok);
        $this->assertSame('5', $result->nextCursor);
        $this->assertCount(5, $result->rows);
        [$valid, $invalid] = RowValidator::split($result->rows);
        $this->assertCount(5, $valid);
        $this->assertSame([], $invalid);

        $hourly = $result->rows[0];
        $this->assertSame('2108471673182427072', $hourly['upwork_job_id']);
        $this->assertSame('https://www.upwork.com/jobs/~022108471673182427072', $hourly['upwork_url'], 'tracking parameters removed');
        $this->assertStringNotContainsString('untrusted_participant_content', $hourly['description']);
        $this->assertSame(['hourly', 'expert', 'part_time', 'Less than 1 month'], [$hourly['job_type'], $hourly['experience_level'], $hourly['workload'], $hourly['duration']]);
        $this->assertSame([84216.98, 47.0, 39.0, 5.0, true], [$hourly['client_total_spent'], $hourly['client_jobs_posted'], $hourly['client_reviews_count'], $hourly['client_rating'], $hourly['payment_verified']]);
        $this->assertSame('20 to 50', $hourly['proposals_tier']);
        $this->assertSame(['Laravel', 'PHP', 'Git', 'Linux System Administration'], $hourly['raw_data']['skills']);
        $this->assertSame(['fixed', 500.0], [$result->rows[1]['job_type'], $result->rows[1]['fixed_budget']]);
    }

    public function test_job_detail_maps_client_record_and_never_reviews(): void
    {
        $row = McpUpworkClient::mapJobDetail($this->fixture('mcp_get'));

        $this->assertSame(['9000001', 'Asia/Dubai', 100.0, 49.0, 2, 14], [$row['upwork_client_id'], $row['client_timezone'], $row['client_hire_rate'], $row['client_jobs_hired'], $row['client_open_jobs'], $row['connects_required']]);
        $this->assertSame(0, $row['job_activity']['total_hired']);
        $this->assertCount(9, $row['client_work_history']);
        $this->assertSame(['title', 'type', 'status', 'started'], array_keys($row['client_work_history'][0]));
        $this->assertStringNotContainsString('untrusted_participant_content', json_encode($row));
        $this->assertArrayNotHasKey('client_feedback', $row['raw_data']);
        [$valid] = RowValidator::split([$row]);
        $this->assertCount(1, $valid);
    }

    public function test_server_sent_event_responses_are_decoded(): void
    {
        $body = "event: message\ndata: {\"jsonrpc\":\"2.0\",\"method\":\"notifications/progress\"}\n\nevent: message\ndata: {\"jsonrpc\":\"2.0\",\"id\":3,\"result\":{\"ok\":1}}\n\n";

        $this->assertSame(['jsonrpc' => '2.0', 'id' => 3, 'result' => ['ok' => 1]], McpUpworkClient::decode($body, 'text/event-stream'));
        $this->assertNull(McpUpworkClient::decode('not json', 'application/json'));
    }

    // ---- Failures ---------------------------------------------------------------------------

    public function test_rate_limits_errors_and_missing_connection_are_classified(): void
    {
        $client = new McpUpworkClient();
        $this->assertSame('not_configured', $client->search(['query' => 'x'])->errorCode);

        $this->connect();
        $this->fakeUpwork(['upwork__find_jobs:get' => fn($id) => $this->toolResult($id, 'Rate limit exceeded, retry later', true)]);
        $this->toolStatuses = [429, 503];

        $this->assertTrue($client->search(['query' => 'x'])->rateLimited());
        $this->assertSame('server_error', $client->search(['query' => 'x'])->errorCode);
        $this->assertTrue($client->job('2108471673182427072')->rateLimited());
        $this->assertTrue($client->search(['query' => 'x'])->ok);
    }

    public function test_an_expired_token_is_refreshed_once_and_stored_encrypted(): void
    {
        $this->connect(['expires_at' => now()->subMinute()]);
        $this->fakeUpwork();

        $this->assertTrue((new McpUpworkClient())->search(['query' => 'x'])->ok);

        $credential = UpworkApiCredential::mcp();
        $this->assertSame(['mcp-access-2', 'mcp-refresh-2'], [$credential->access_token, $credential->refresh_token]);
        $this->assertStringNotContainsString('mcp-access-2', DB::table('upwork_api_credentials')->where('label', 'mcp')->value('access_token'));
        Http::assertSent(fn(Request $r) => $r->url() === self::TOKEN && $r['grant_type'] === 'refresh_token' && $r['client_id'] === 'pms-client'
            && $r['resource'] === self::MCP && !$r->hasHeader('Authorization'));
        $this->assertSame('Bearer mcp-access-2', $this->calls[0]['auth']);
    }

    public function test_a_401_triggers_one_refresh_and_a_new_session(): void
    {
        $this->connect();
        $this->fakeUpwork();
        $this->toolStatuses = [401];

        $result = (new McpUpworkClient())->search(['query' => 'x']);

        $this->assertTrue($result->ok);
        $this->assertCount(2, $this->calls);
        $this->assertSame('Bearer mcp-access-2', $this->calls[1]['auth']);
    }

    // ---- Connecting -------------------------------------------------------------------------

    public function test_connect_registers_once_runs_pkce_login_and_picks_the_agency(): void
    {
        $admin = $this->superAdmin();
        $this->fakeUpwork();

        $location = $this->actingAs($admin)->get(route('upwork.config.mcp.connect'))->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://www.upwork.com/ab/account-security/oauth2/authorize?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $q);
        $this->assertSame(['code', 'pms-client', 'S256', self::MCP, route('upwork.config.mcp.callback')], [$q['response_type'], $q['client_id'], $q['code_challenge_method'], $q['resource'], $q['redirect_uri']]);
        $this->assertSame(['client_id', 'redirect_uri', 'registered_at'], array_keys(UpworkSetting::get('mcp.client')), 'registration management token is not kept');

        $this->actingAs($admin)->get(route('upwork.config.mcp.callback', ['state' => 'forged', 'code' => 'c']))->assertSessionHas('error');
        $this->assertNull(UpworkApiCredential::mcp());

        $this->actingAs($admin)->get(route('upwork.config.mcp.connect'));
        $pending = session('upwork_mcp_oauth');
        $this->actingAs($admin)->get(route('upwork.config.mcp.callback', ['state' => $pending['state'], 'code' => 'c']))
            ->assertSessionHas('success', 'Upwork MCP connected (read-only), searching as Code4Each.');

        Http::assertSentCount(5); // register, token exchange, initialize, initialized, list_accounts
        $this->assertSame(1, collect(Http::recorded())->filter(fn($pair) => $pair[0]->url() === self::REGISTER)->count(), 'registered once');
        Http::assertSent(fn(Request $r) => $r->url() === self::TOKEN && $r['grant_type'] === 'authorization_code' && $r['code_verifier'] === $pending['verifier']);
        $this->assertSame(self::ORG, UpworkApiCredential::mcp()->tenant_id);
        $this->assertSame(['list_accounts'], array_map(fn($c) => str_replace('upwork__', '', $c['name']), $this->calls));

        $this->actingAs($admin)->get(route('upwork.config.settings.edit'))->assertOk()
            ->assertSee('Upwork MCP, direct')->assertSee('Searching as')->assertSee('Code4Each')->assertDontSee('mcp-access-2');

        $this->actingAs($admin)->post(route('upwork.config.mcp.account'), ['org_uid' => '1000000000000000001'])->assertSessionHas('success', 'Searching as Test Freelancer.');
        $this->actingAs($admin)->post(route('upwork.config.mcp.account'), ['org_uid' => '999'])->assertSessionHasErrors('org_uid');

        $this->actingAs($admin)->post(route('upwork.config.mcp.disconnect'))->assertSessionHas('success');
        $this->assertNull(UpworkApiCredential::mcp());

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->bde())->get(route('upwork.config.mcp.connect'))->assertForbidden();
    }

    // ---- Discovery --------------------------------------------------------------------------

    public function test_server_discovery_through_the_mcp_enriches_and_links_the_client(): void
    {
        $this->connect();
        $this->fakeUpwork();
        UpworkSetting::set('discovery.enrich_min_score', 0);
        UpworkSetting::set('discovery.max_queries_per_run', 1);
        UpworkSetting::set('agent.enrich_limit_per_run', 1);

        $out = app(ServerDiscoveryRunner::class, ['pause' => function () {}])->run('manual', new McpUpworkClient());

        $this->assertSame('completed', $out['status'], $out['message']);
        $this->assertSame('server:upwork-mcp', UpworkDiscoveryRun::where('uuid', $out['run'])->value('agent_host'));
        $this->assertSame(5, UpworkOpportunity::count());
        $enriched = UpworkOpportunity::where('stage', UpworkOpportunity::STAGE_ENRICHED)->first();
        $this->assertNotNull($enriched);
        $this->assertNotNull($enriched->upwork_client_id, 'client linked from the job detail');
        $this->assertNotNull($enriched->duration, 'search-only facts kept when details are merged');
        $this->assertSame(['find_jobs:search', 'find_jobs:get'], array_map(fn($c) => str_replace('upwork__', '', $c['name']) . ':' . $c['arguments']['action'], $this->calls));
    }

    public function test_factory_offers_the_mcp_source_in_production_too(): void
    {
        $this->assertInstanceOf(McpUpworkClient::class, UpworkClientFactory::make('mcp'));
        $this->app['env'] = 'production';
        $this->assertSame(['graphql', 'mcp'], UpworkClientFactory::available());
    }
}
