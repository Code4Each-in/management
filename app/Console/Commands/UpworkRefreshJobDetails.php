<?php

namespace App\Console\Commands;

use App\Models\UpworkOpportunity;
use App\Services\Upwork\Api\UpworkClientFactory;
use App\Services\Upwork\Eligibility\LocationEligibility;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Re-read facts Upwork only gives in a job's full details: the freelancer
 * location list (preferred_locations) and the posting's activity (invites
 * sent, interviews, hires, offers). Search results carry neither, and
 * discovery reads details once per new job, so stored jobs can lack them or
 * hold old counts. One read-only find_jobs get per job; then a re-score (the
 * invite penalty and location eligibility follow).
 *
 * Without --fetch it calls nothing and only lists the jobs it would read.
 * By default: active jobs (new, approved, proposal draft) whose details were
 * never re-read; --all re-reads every active job (fresh invite counts).
 * No search is run; status, deadlines and history are not changed; nothing is sent.
 */
class UpworkRefreshJobDetails extends Command
{
    protected $signature = 'upwork:refresh-job-details
        {--fetch : Read each job\'s details from Upwork (read-only) and save them (default: list only, no Upwork calls)}
        {--id=* : Only these opportunity ids}
        {--limit=25 : At most this many jobs per run}
        {--all : Every active job, not only those never re-read (refreshes invite counts)}
        {--pause=1500 : Milliseconds between Upwork calls}
        {--force : Skip the confirmation prompt in production}';

    protected $description = 'Re-read invites sent and location requirements from Upwork for stored jobs (lists only unless --fetch)';

    const STATUSES = [UpworkOpportunity::STATUS_NEW, UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_PROPOSAL_DRAFT];

    public function handle(UpworkOpportunityService $service)
    {
        $fetch = (bool) $this->option('fetch');
        $limit = max(1, (int) $this->option('limit'));

        $candidates = [];
        $query = UpworkOpportunity::query()
            ->when($this->option('id'), fn($q, $ids) => $q->whereIn('id', array_map('intval', $ids)),
                fn($q) => $q->whereIn('status', self::STATUSES))
            ->orderByDesc('id');
        foreach ($query->cursor() as $o) {
            // Every details read since this fix stores preferred_locations (possibly []): its absence means "never re-read".
            if ($this->option('all') || $this->option('id') || !array_key_exists(LocationEligibility::LIST_SOURCE, (array) $o->raw_data)) {
                $candidates[] = $o;
            }
            if (count($candidates) >= $limit) {
                break;
            }
        }

        if (!$candidates) {
            $this->info('No jobs need their details re-read.');
            return 0;
        }
        if (!$fetch) {
            $this->table(['ID', 'Upwork job', 'Title', 'Status', 'Invites sent now', 'Location now'], array_map(fn($o) => [
                $o->id, $o->upwork_job_id, Str::limit((string) $o->title, 50), $o->status,
                $o->invites_sent ?? 'Not available', LocationEligibility::describe($o),
            ], $candidates));
            $this->info('Listed ' . count($candidates) . ' job(s); nothing was fetched. Re-run with --fetch to read their details from Upwork ('
                . count($candidates) . ' read-only call(s)).');
            return 0;
        }

        if (app()->environment('production') && !$this->option('force')
            && !$this->confirm('This makes ' . count($candidates) . ' read-only Upwork call(s) and updates invite counts, location fields and scores in PRODUCTION. Continue?')) {
            $this->warn('Nothing fetched.');
            return 1;
        }

        $client = UpworkClientFactory::make();
        if (!$client->isConfigured()) {
            $this->error('The Upwork connection is not configured; nothing fetched.');
            return 1;
        }

        $fmt = fn($v) => $v === null ? 'n/a' : rtrim(rtrim(number_format($v, 1), '0'), '.');
        $rows = [];
        $stopped = null;
        foreach ($candidates as $i => $o) {
            if ($i > 0) {
                usleep(max(0, (int) $this->option('pause')) * 1000);
            }
            $detail = $client->job($o->upwork_job_id);
            if ($detail->rateLimited() || $detail->errorCode === 'auth') {
                $stopped = "{$detail->errorCode}: {$detail->error}";
                break;
            }
            if (!$detail->ok) {
                $rows[] = [$o->id, $o->upwork_job_id, Str::limit((string) $o->title, 40), '—', '—', "not read ({$detail->errorCode})"];
                continue;
            }
            $r = $service->refreshFromDetail($o, $detail->rows[0]);
            $rows[] = [
                $o->id, $o->upwork_job_id, Str::limit((string) $o->title, 40),
                ($r['invites_before'] ?? 'n/a') . ' -> ' . ($r['invites'] ?? 'Not available'),
                $fmt($r['score_before']) . ' -> ' . $fmt($r['score']),
                $r['location']['eligibility'] . " [{$r['location']['rule']}]",
            ];
        }

        $this->table(['ID', 'Upwork job', 'Title', 'Invites sent', 'Score', 'Location [rule]'], $rows);
        if ($stopped) {
            $this->warn("Stopped early ({$stopped}). Re-run later; jobs already done are skipped.");
        }
        $this->info('Read ' . count($rows) . ' job(s). No status was changed and nothing was sent.');

        return $stopped ? 1 : 0;
    }
}
