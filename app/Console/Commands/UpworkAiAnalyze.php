<?php

namespace App\Console\Commands;

use App\Models\UpworkOpportunity;
use App\Services\Upwork\Ai\Qualification\QualificationService;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Console\Command;

/**
 * Run AI qualification for one opportunity now (no queue). Same budget guard,
 * limits and idempotency as the automatic path; never touches Upwork.
 */
class UpworkAiAnalyze extends Command
{
    protected $signature = 'upwork:ai-analyze {opportunity : PMS id or Upwork job id} {--force : ask again even if this input was already answered}';
    protected $description = 'Run AI qualification for one Upwork opportunity (budget-guarded; no Upwork calls)';

    public function handle(QualificationService $service)
    {
        $key = (string) $this->argument('opportunity');
        $o = UpworkOpportunity::where('upwork_job_id', UpworkOpportunityService::normalizeJobId($key))->first()
            ?? (ctype_digit($key) ? UpworkOpportunity::find((int) $key) : null);
        if (!$o) {
            $this->error("No opportunity '{$key}'.");
            return 1;
        }

        $out = $service->analyze($o, 'qualify', (bool) $this->option('force'));
        $o->refresh();

        $this->line("#{$o->id} {$o->title}");
        $this->line("Result: {$out['status']}" . ($out['reused'] ? ' (reused stored answer, no AI call)' : '') . ($out['message'] ? " — {$out['message']}" : ''));
        if ($a = $out['analysis']) {
            if ($a->result) {
                $this->line(sprintf('AI: fit %d, %s, confidence %.2f, %s/%s', $a->fit_score, strtoupper($a->recommendation), $a->confidence, $a->provider, $a->model));
                $this->line('Summary: ' . $a->result['summary']);
            }
        }
        $this->line(sprintf('Local score now: %s (%s)', $o->score, strtoupper((string) $o->recommendation)));

        return in_array($out['status'], ['analyzed'], true) ? 0 : 1;
    }
}
