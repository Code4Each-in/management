<?php

namespace App\Console\Commands;

use App\Models\UpworkOpportunity;
use App\Services\Upwork\Eligibility\LocationEligibility;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Classify stored Upwork jobs by location eligibility (can India-based
 * freelancers apply?). Dry run by default: lists what would change and saves
 * nothing. --apply writes only the location fields, plus recommendation /
 * hard_filters for jobs held at (or released from) SKIP. Status (including
 * archived, approved, rejected), score, review deadline, snooze and history
 * never change; no record is created or deleted, nothing is sent, nothing
 * calls Upwork. Safe to run again: a second run finds nothing to change.
 */
class UpworkClassifyLocations extends Command
{
    protected $signature = 'upwork:classify-locations
        {--apply : Write the changes (default: dry run, saves nothing)}
        {--id=* : Only these opportunity ids}
        {--limit=0 : Stop after this many jobs (0 = no limit)}
        {--all-rows : List every job examined, not only the ones that would change}
        {--force : Skip the confirmation prompt in production}';

    protected $description = 'Classify stored Upwork opportunities by location eligibility (dry run unless --apply)';

    public function handle(UpworkOpportunityService $service)
    {
        $apply = (bool) $this->option('apply');
        if ($apply && app()->environment('production') && !$this->option('force')
            && !$this->confirm('This updates location fields and holds restricted jobs at SKIP in PRODUCTION (statuses are not changed). Run the dry run first. Continue?')) {
            $this->warn('Nothing changed.');
            return 1;
        }

        $limit = max(0, (int) $this->option('limit'));
        $query = UpworkOpportunity::query()
            ->when($this->option('id'), fn($q, $ids) => $q->whereIn('id', array_map('intval', $ids)))
            ->orderBy('id');

        $rows = [];
        $classes = [LocationEligibility::ELIGIBLE => 0, LocationEligibility::RESTRICTED => 0, LocationEligibility::UNKNOWN => 0];
        $actions = [];
        $examined = $changed = 0;

        foreach ($query->cursor() as $opportunity) {
            if ($limit && $examined >= $limit) {
                break;
            }
            $examined++;
            $result = $service->classifyLocation($opportunity, $apply);
            $classes[$result['eligibility']]++;
            $kind = Str::before($result['action'], ':');
            $actions[$kind] = ($actions[$kind] ?? 0) + 1;
            if ($result['changes']) {
                $changed++;
            }
            if ($result['changes'] || $this->option('all-rows')) {
                $rows[] = [
                    $opportunity->id,
                    $opportunity->upwork_job_id,
                    Str::limit((string) $opportunity->title, 45),
                    $opportunity->status,
                    $result['text'] !== null ? Str::limit($result['text'], 40) : '(none)',
                    $result['eligibility'] . ($result['eligibility'] !== $result['auto'] ? " (override; rules: {$result['auto']})" : '') . " [{$result['rule']}]",
                    $result['action'],
                    $result['reason'],
                ];
            }
        }

        if ($rows) {
            $this->table(['ID', 'Upwork job', 'Title', 'Status', 'Location', 'Classification [rule]', ($apply ? 'Action taken' : 'Proposed action'), 'Reason'], $rows);
        }
        $this->table(['Eligible', 'Restricted (SKIP)', 'Unclear (manual review)', 'Would change', 'Examined'], [[
            $classes[LocationEligibility::ELIGIBLE], $classes[LocationEligibility::RESTRICTED], $classes[LocationEligibility::UNKNOWN], $changed, $examined,
        ]]);
        ksort($actions);
        $this->line('Actions: ' . collect($actions)->map(fn($n, $a) => "{$a} {$n}")->implode(', '));

        $this->info($apply
            ? "Applied: {$changed} of {$examined} job(s) updated. No status was changed and nothing was sent."
            : "Dry run: {$changed} of {$examined} job(s) would change; nothing saved. Re-run with --apply to write.");

        return 0;
    }
}
