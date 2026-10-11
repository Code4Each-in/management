<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkRuleset;
use App\Services\Upwork\Scoring\ScoringEngine;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * The default configuration must reproduce the Test 7 decisions for the 14
 * real jobs: the same recommendation band, and the matching hard filter for
 * every hard reject. Exact component points were human judgement in Test 7,
 * so only the decision is asserted.
 */
class UpworkTest7ReplayTest extends UpworkTestCase
{
    public function test_default_configuration_reproduces_test7_decisions(): void
    {
        (new UpworkIntelligenceSeeder)->run();
        $engine = new ScoringEngine();
        $ruleset = UpworkRuleset::current()->load('rules');
        $jobs = json_decode(file_get_contents(base_path('tests/Fixtures/Upwork/test7_jobs.json')), true)['jobs'];

        $this->assertCount(14, $jobs);

        $mismatches = [];
        foreach ($jobs as $job) {
            $result = $engine->score($job, $ruleset);
            $filters = array_column($result['hard_filters'], 'key');

            if ($result['recommendation'] !== $job['expect']['recommendation']) {
                $mismatches[] = sprintf('%s: expected %s, got %s (%s)', $job['title'], $job['expect']['recommendation'], $result['recommendation'], $result['score'] ?? 'hard: ' . implode(',', $filters));
            }
            if (isset($job['expect']['hard_filter']) && !in_array($job['expect']['hard_filter'], $filters, true)) {
                $mismatches[] = sprintf('%s: expected hard filter %s, got [%s]', $job['title'], $job['expect']['hard_filter'], implode(',', $filters));
            }
            if (!isset($job['expect']['hard_filter']) && $filters) {
                $mismatches[] = sprintf('%s: unexpected hard filter [%s]', $job['title'], implode(',', $filters));
            }
        }

        $this->assertSame([], $mismatches, "Test 7 replay differs:\n" . implode("\n", $mismatches));
    }
}
