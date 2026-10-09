<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkClient;
use App\Models\UpworkOpportunity;
use App\Services\Upwork\Clients\ClientIntelligenceService;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 6: a search row (no Upwork client id) from a client the PMS already
 * knows is matched by a strict fingerprint, so stage-1 scoring can use the
 * client's history and cached facts. Only full details ever link a client.
 */
class UpworkClientFingerprintTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
    }

    /** An enriched job from client "c-1" that a person rejected. */
    private function knownClient(array $overrides = []): UpworkOpportunity
    {
        $payload = array_merge($this->payload(['upwork_client_id' => 'c-1']), $overrides);
        unset($payload['score'], $payload['recommendation']);
        [$o] = app(UpworkOpportunityService::class)->ingest($payload);
        app(UpworkOpportunityService::class)->reject($o, $this->bde()->id, 'Unrealistic expectations');

        return $o->fresh();
    }

    private function searchRow(array $overrides = []): UpworkOpportunity
    {
        [$o] = app(UpworkOpportunityService::class)->ingestSighting(array_merge([
            'upwork_job_id' => '2107000000000000001', 'title' => 'Laravel API for our SaaS', 'description' => 'Laravel REST API work.',
            'job_type' => 'hourly', 'hourly_min' => 40, 'hourly_max' => 70, 'client_country' => 'United States',
            'client_total_spent' => 125000, 'client_jobs_posted' => 40, 'client_jobs_hired' => 31,
        ], $overrides));

        return $o->fresh();
    }

    public function test_fingerprint_needs_distinctive_figures(): void
    {
        $base = ['client_country' => 'United States', 'client_jobs_posted' => 40, 'client_jobs_hired' => 31, 'client_total_spent' => 125000.4];

        $this->assertSame(40, strlen(ClientIntelligenceService::fingerprint($base)));
        $this->assertSame(ClientIntelligenceService::fingerprint($base), ClientIntelligenceService::fingerprint(['client_country' => ' united states', 'client_total_spent' => '125000'] + $base));
        $this->assertNull(ClientIntelligenceService::fingerprint(['client_country' => null] + $base));
        $this->assertNull(ClientIntelligenceService::fingerprint(['client_total_spent' => 200] + $base), 'new clients look alike');
        $this->assertNull(ClientIntelligenceService::fingerprint(['client_jobs_posted' => 1] + $base));
    }

    public function test_enrichment_stores_the_clients_fingerprint(): void
    {
        $this->knownClient();

        $this->assertNotNull(UpworkClient::where('external_id', 'c-1')->value('fingerprint'));
    }

    public function test_search_row_from_a_known_client_uses_its_history_without_linking(): void
    {
        $this->knownClient();

        $o = $this->searchRow();

        $this->assertNull($o->upwork_client_id, 'only full details link a job to a client');
        $match = $o->score_explanation['client']['match'];
        $this->assertSame(['fingerprint', UpworkClient::where('external_id', 'c-1')->value('id')], [$match['by'], $match['client_id']]);
        $this->assertSame(1, $o->score_explanation['client']['history']['previous_rejections']);
        $this->assertContains('client_hire_rate', $match['filled'], 'cached fact used for scoring');
        $this->assertNull($o->client_hire_rate, 'the job record itself is not changed');
    }

    public function test_no_match_when_figures_differ_are_ambiguous_or_stale(): void
    {
        $this->knownClient();

        $this->assertArrayNotHasKey('match', $this->searchRow(['client_jobs_posted' => 41])->score_explanation['client']);

        UpworkClient::create(['external_id' => 'c-2', 'fingerprint' => UpworkClient::where('external_id', 'c-1')->value('fingerprint'), 'last_seen_at' => now()]);
        $this->assertArrayNotHasKey('match', $this->searchRow(['upwork_job_id' => '2107000000000000002'])->score_explanation['client'], 'ambiguous');

        UpworkClient::where('external_id', 'c-2')->delete();
        UpworkClient::query()->update(['last_seen_at' => now()->subDays(31)]);
        $this->assertArrayNotHasKey('match', $this->searchRow(['upwork_job_id' => '2107000000000000003'])->score_explanation['client'], 'stale');
    }

    public function test_full_details_replace_the_fingerprint_match_with_the_real_link(): void
    {
        $this->knownClient();
        $this->searchRow();

        $payload = $this->payload(['upwork_job_id' => '2107000000000000001', 'upwork_url' => 'https://www.upwork.com/jobs/~022107000000000000001', 'upwork_client_id' => 'c-1']);
        unset($payload['score'], $payload['recommendation']);
        app(UpworkOpportunityService::class)->ingest($payload);

        $o = UpworkOpportunity::where('upwork_job_id', '2107000000000000001')->first();
        $this->assertSame(UpworkClient::where('external_id', 'c-1')->value('id'), $o->upwork_client_id);
        $this->assertArrayNotHasKey('match', $o->score_explanation['client']);
    }
}
