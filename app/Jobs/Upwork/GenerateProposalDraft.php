<?php

namespace App\Jobs\Upwork;

use App\Models\UpworkOpportunity;
use App\Services\Upwork\Ai\Proposal\ProposalDraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * AI proposal draft for one approved opportunity, on the module's own queue
 * (config/queue.php "upwork"). Safe to run twice (see ProposalDraftService).
 * Never retries on its own: AiGateway already made its bounded retry inside
 * the budget, and a person can retry from the job page.
 */
class GenerateProposalDraft implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 1;
    /** @var int seconds; below the connection's retry_after (300) */
    public $timeout = 240;

    /** @var int */
    public $opportunityId;
    /** @var int|null */
    public $userId;

    public function __construct(int $opportunityId, ?int $userId = null)
    {
        $this->opportunityId = $opportunityId;
        $this->userId = $userId;
        $this->onConnection(config('upwork.ai.queue.connection', 'upwork'));
        $this->onQueue(config('upwork.ai.queue.name', 'upwork'));
    }

    public function handle(ProposalDraftService $service): void
    {
        if ($opportunity = UpworkOpportunity::find($this->opportunityId)) {
            $service->generate($opportunity, $this->userId);
        }
    }

    public function failed(\Throwable $e): void
    {
        UpworkOpportunity::whereKey($this->opportunityId)->where('proposal_ai_status', UpworkOpportunity::PROPOSAL_AI_QUEUED)
            ->update(['proposal_ai_status' => UpworkOpportunity::PROPOSAL_AI_FAILED, 'proposal_ai_status_reason' => mb_substr('Worker error: ' . \App\Services\Upwork\Notifications\AlertService::scrub($e->getMessage()), 0, 255)]);
    }
}
