<?php

namespace App\Console\Commands;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Console\Command;

/**
 * Backfill the best-fit profile (agency / individual / both) for jobs scored
 * before dual-track targeting existed. Dry run by default; --apply writes only
 * the target fields. Scores, recommendations, review deadlines, reminders,
 * snoozes and status are never changed, and nothing calls Upwork.
 */
class UpworkClassifyTargets extends Command
{
    protected $signature = 'upwork:classify-targets
        {--apply : Write the target fields (default: dry run, saves nothing)}
        {--all : Also re-classify jobs that already have a target}
        {--limit=0 : Stop after this many jobs (0 = no limit)}';

    protected $description = 'Classify stored Upwork opportunities as agency / individual / both (dry run unless --apply)';

    public function handle(UpworkOpportunityService $service)
    {
        if (!UpworkRuleset::current()) {
            $this->error('No published ruleset. Seed with: php artisan db:seed --class=UpworkIntelligenceSeeder');
            return 1;
        }

        $apply = (bool) $this->option('apply');
        $limit = max(0, (int) $this->option('limit'));
        $query = UpworkOpportunity::whereNotNull('ruleset_version')->whereNotNull('score_explanation')
            ->when(!$this->option('all'), fn($q) => $q->whereNull('target_profile'))
            ->orderBy('id');

        $counts = ['agency' => 0, 'individual' => 0, 'both' => 0, 'ambiguous' => 0, 'skipped' => 0];
        $done = 0;
        foreach ($query->cursor() as $opportunity) {
            if ($limit && $done >= $limit) {
                break;
            }
            $target = $service->classifyTarget($opportunity, $apply);
            $done++;
            if (!$target) {
                $counts['skipped']++;
                continue;
            }
            $counts[$target['target']]++;
            $counts['ambiguous'] += $target['ambiguous'] ? 1 : 0;
        }

        $this->table(['Agency', 'Individual', 'Either', 'of which ambiguous', 'Skipped'], [[
            $counts['agency'], $counts['individual'], $counts['both'], $counts['ambiguous'], $counts['skipped'],
        ]]);
        $this->info($apply
            ? "Classified {$done} job(s). Scores and recommendations were not changed."
            : "Dry run: {$done} job(s) examined, nothing saved. Run with --apply to write the target fields.");

        return 0;
    }
}
