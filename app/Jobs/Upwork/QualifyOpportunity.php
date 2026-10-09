<?php

namespace App\Jobs\Upwork;

use App\Models\UpworkOpportunity;
use App\Services\Upwork\Ai\Qualification\QualificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * AI qualification of one opportunity, on the module's own queue connection
 * (config/queue.php "upwork"), processed by the scheduled worker.
 *
 * Safe to run twice: QualificationService reuses an answer for identical
 * input and holds a per-opportunity lock. It never retries on its own;
 * AiGateway already made its bounded retry inside the budget.
 */
class QualifyOpportunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 1;
    /** @var int seconds; below the connection's retry_after (300) */
    public $timeout = 240;

    /** @var int */
    public $opportunityId;
    /** @var string */
    public $type;
    /** @var bool */
    public $force;
    /** @var int|null */
    public $userId;

    public function __construct(int $opportunityId, string $type = 'qualify', bool $force = false, ?int $userId = null)
    {
        $this->opportunityId = $opportunityId;
        $this->type = $type;
        $this->force = $force;
        $this->userId = $userId;
        $this->onConnection(config('upwork.ai.queue.connection', 'upwork'));
        $this->onQueue(config('upwork.ai.queue.name', 'upwork'));
    }

    public function handle(QualificationService $service): void
    {
        $opportunity = UpworkOpportunity::find($this->opportunityId);
        if (!$opportunity) {
            return;
        }

        // Automatic requests re-check eligibility: a person may have rejected the job meanwhile.
        if ($this->userId === null && !$this->force && ($reason = $service->skipReason($opportunity))) {
            $opportunity->forceFill(['ai_status' => UpworkOpportunity::AI_SKIPPED, 'ai_status_reason' => $reason])->save();
            return;
        }

        $service->analyze($opportunity, $this->type, $this->force, $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        // Leave the job usable: local scoring stands, a person can retry from the UI.
        UpworkOpportunity::whereKey($this->opportunityId)->where('ai_status', UpworkOpportunity::AI_QUEUED)
            ->update(['ai_status' => UpworkOpportunity::AI_FAILED, 'ai_status_reason' => mb_substr('Worker error: ' . $e->getMessage(), 0, 255)]);
    }
}
