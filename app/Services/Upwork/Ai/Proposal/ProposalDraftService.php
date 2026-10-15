<?php

namespace App\Services\Upwork\Ai\Proposal;

use App\Jobs\Upwork\GenerateProposalDraft;
use App\Models\UpworkAiAnalysis;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\UpworkProposalService;
use Illuminate\Support\Facades\Cache;

/**
 * AI proposal drafts, queued only when a person explicitly approves a job and
 * only while AI and proposal drafts are both switched on (both off by default).
 *
 * Cost controls: AiGateway's worst-case reservation (overall daily/monthly
 * limits plus the proposal sub-budget and per-job retry limit), one queued
 * request per job at a time, and the same input never paid for twice.
 *
 * The draft lands in the normal PROPOSAL_DRAFT flow: editable, needs human
 * approval, never overwrites a human edit, and is never submitted anywhere.
 */
class ProposalDraftService
{
    const TYPE = ProposalDraftPrompt::TYPE;

    /** @var AiGateway|null */
    private $gateway;

    public function __construct(?AiGateway $gateway = null)
    {
        $this->gateway = $gateway;
    }

    private function gateway(): AiGateway
    {
        return $this->gateway = $this->gateway ?? AiGateway::fromSettings();
    }

    public static function enabled(): bool
    {
        return UpworkSetting::get('ai.enabled') && UpworkSetting::get('ai.proposal_drafts_enabled');
    }

    /** Called right after a person approves a job. Silent no-op while switched off. */
    public function queueAfterApproval(UpworkOpportunity $o, int $userId): bool
    {
        if (!self::enabled()) {
            return false;
        }

        return $this->queue($o, $userId, false) === 'queued';
    }

    /** "Draft with AI" / "Retry" from the job page. Returns 'queued' or why not. */
    public function request(UpworkOpportunity $o, int $userId): string
    {
        if (!self::enabled()) {
            return 'AI proposal drafts are switched off (Upwork → Configuration → AI).';
        }

        return $this->queue($o, $userId, true);
    }

    private function queue(UpworkOpportunity $o, int $userId, bool $manual): string
    {
        if (!in_array($o->status, [UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_PROPOSAL_DRAFT], true)) {
            return 'Approve the job before drafting a proposal.';
        }
        if ($o->proposal_edited_at !== null) {
            return 'A person has already edited this draft; AI will not replace it.';
        }
        if ($o->proposal_ai_status === UpworkOpportunity::PROPOSAL_AI_QUEUED && $o->updated_at && $o->updated_at->gt(now()->subMinutes(10))) {
            return 'An AI draft is already queued.';
        }
        if (!$manual && $o->proposal_ai_status === UpworkOpportunity::PROPOSAL_AI_READY) {
            return 'An AI draft already exists.'; // re-approval after a rejection does not pay again
        }
        if (!$this->gateway()->provider()->isConfigured()) {
            $this->mark($o, UpworkOpportunity::PROPOSAL_AI_BLOCKED, 'AI provider is not configured.');
            return 'AI provider is not configured.';
        }
        if ($blocked = $this->gateway()->status()['blocked'] ?? null) {
            $this->mark($o, UpworkOpportunity::PROPOSAL_AI_BLOCKED, $blocked);
            return $blocked;
        }

        $this->mark($o, UpworkOpportunity::PROPOSAL_AI_QUEUED, null);
        $o->log('proposal_ai_queued', $userId, null, null, $manual ? 'AI proposal draft requested' : 'AI proposal draft queued after approval');
        GenerateProposalDraft::dispatch($o->id, $userId);

        return 'queued';
    }

    /**
     * Generate and store the draft (runs in the queue). Idempotent: one worker
     * per job, identical input reuses the stored answer, a human-edited draft
     * is never replaced.
     *
     * @return array{status: string, message: ?string}
     */
    public function generate(UpworkOpportunity $o, ?int $userId = null): array
    {
        $lock = Cache::lock("upwork:ai:proposal:{$o->id}", 300);
        if (!$lock->get()) {
            return ['status' => 'busy', 'message' => 'Another worker is drafting this proposal.'];
        }

        try {
            $o->refresh();
            if (!self::enabled()) {
                $this->mark($o, UpworkOpportunity::PROPOSAL_AI_BLOCKED, 'AI proposal drafts are switched off.');
                return ['status' => 'blocked', 'message' => 'AI proposal drafts are switched off.'];
            }
            if (!in_array($o->status, [UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_PROPOSAL_DRAFT], true) || $o->proposal_edited_at !== null) {
                $this->mark($o, UpworkOpportunity::PROPOSAL_AI_SKIPPED, 'The job moved on or the draft was edited by a person.');
                return ['status' => 'skipped', 'message' => 'The job moved on or the draft was edited by a person.'];
            }

            $model = (string) UpworkSetting::get('ai.model');
            $request = (new ProposalDraftPrompt())->build($o, (int) UpworkSetting::get('ai.proposal_max_input_tokens'), (int) UpworkSetting::get('ai.proposal_max_output_tokens'));
            // The verified sources are in the static part, so they are part of "the same question".
            $hash = hash('sha256', implode("\n", [$request->type, $request->promptVersion, $model, $request->staticContext, $request->dynamicContext]));

            $analysis = UpworkAiAnalysis::where('opportunity_id', $o->id)->where('request_type', self::TYPE)
                ->where('input_hash', $hash)->where('status', UpworkAiAnalysis::STATUS_SUCCEEDED)->latest('id')->first();

            if (!$analysis) {
                $result = $this->gateway()->run($request, $o->id);
                if ($result['blocked']) {
                    $this->mark($o, UpworkOpportunity::PROPOSAL_AI_BLOCKED, $result['error']);
                    return ['status' => 'blocked', 'message' => $result['error']];
                }

                $analysis = new UpworkAiAnalysis([
                    'opportunity_id' => $o->id,
                    'usage_id' => $result['usage_ids'] ? end($result['usage_ids']) : null,
                    'request_type' => self::TYPE,
                    'provider' => $this->gateway()->provider()->name(),
                    'model' => $model,
                    'prompt_version' => $request->promptVersion,
                    'input_hash' => $hash,
                    'requested_by' => $userId,
                ]);

                if (!$result['ok']) {
                    $analysis->fill(['status' => UpworkAiAnalysis::STATUS_FAILED, 'error_code' => $result['error_code'], 'error' => mb_substr((string) $result['error'], 0, 2000)])->save();
                    $this->mark($o, UpworkOpportunity::PROPOSAL_AI_FAILED, 'AI draft failed (' . $result['error_code'] . '). Write the draft manually or retry later.');
                    $o->log('proposal_ai_failed', $userId, null, null, "AI proposal draft failed: {$result['error_code']}");
                    return ['status' => 'failed', 'message' => $result['error']];
                }

                $analysis->fill(['status' => UpworkAiAnalysis::STATUS_SUCCEEDED, 'result' => ProposalDraftPrompt::verify($result['data'], $request->dynamicContext, $o)])->save();
            }

            try {
                app(UpworkProposalService::class)->storeAgentDraft($o, ['proposal_draft' => trim((string) $analysis->result['draft'])], $userId,
                    "AI draft ({$analysis->model}, analysis #{$analysis->id}); edit and approve before sending it on Upwork yourself");
            } catch (\DomainException $e) {
                $this->mark($o, UpworkOpportunity::PROPOSAL_AI_SKIPPED, $e->getMessage());
                return ['status' => 'skipped', 'message' => $e->getMessage()];
            }

            $warnings = array_merge($analysis->result['verification']['unverified_experience'] ?? [], $analysis->result['verification']['unverified_figures'] ?? []);
            $this->mark($o->refresh(), UpworkOpportunity::PROPOSAL_AI_READY, $warnings ? 'Check before use: ' . implode(', ', $warnings) : null);

            return ['status' => 'ready', 'message' => null];
        } finally {
            $lock->release();
        }
    }

    /** The AI draft currently in use (for the warnings panel), if any. */
    public static function latest(UpworkOpportunity $o): ?UpworkAiAnalysis
    {
        return UpworkAiAnalysis::where('opportunity_id', $o->id)->where('request_type', self::TYPE)
            ->where('status', UpworkAiAnalysis::STATUS_SUCCEEDED)->latest('id')->first();
    }

    public function mark(UpworkOpportunity $o, string $status, ?string $reason): void
    {
        $o->forceFill([
            'proposal_ai_status' => $status,
            'proposal_ai_status_reason' => $reason === null ? null : mb_substr($reason, 0, 255),
        ])->save();
    }
}
