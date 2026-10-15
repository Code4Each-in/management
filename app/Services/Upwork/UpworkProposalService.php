<?php

namespace App\Services\Upwork;

use App\Models\UpworkOpportunity;

/**
 * Proposal draft lifecycle: APPROVED -> PROPOSAL_DRAFT -> (human review) -> SUBMITTED.
 *
 * The PMS never submits anything to Upwork. "Submitted" only records that a
 * human sent the proposal on Upwork themselves.
 */
class UpworkProposalService
{
    /**
     * Create a starter draft from the stored analysis so a human can edit it.
     * The agent can replace this later via storeAgentDraft().
     */
    public function generateDraft(UpworkOpportunity $opportunity, int $userId): void
    {
        $opportunity->changeStatus(UpworkOpportunity::STATUS_PROPOSAL_DRAFT, $userId, [
            'proposal_draft' => $this->starterDraft($opportunity),
            'proposal_generated_at' => now(),
            'proposal_approved_at' => null,
            'proposal_approved_by' => null,
        ], 'Starter draft generated in PMS');
    }

    /**
     * Store a draft produced by the agent. Refuses to overwrite a draft a human has edited.
     */
    public function storeAgentDraft(UpworkOpportunity $opportunity, array $data, ?int $userId, ?string $source = null): void
    {
        if ($opportunity->proposal_edited_at !== null) {
            throw new \DomainException('This proposal draft has been edited by a human and will not be overwritten.');
        }

        $attributes = [
            'proposal_draft' => $data['proposal_draft'],
            'proposed_rate' => $data['proposed_rate'] ?? $opportunity->proposed_rate,
            'estimated_hours' => $data['estimated_hours'] ?? $opportunity->estimated_hours,
            'screening_answers' => $data['screening_answers'] ?? $opportunity->screening_answers,
            'proposal_generated_at' => now(),
            'proposal_approved_at' => null,
            'proposal_approved_by' => null,
        ];

        if ($opportunity->status === UpworkOpportunity::STATUS_PROPOSAL_DRAFT) {
            $opportunity->forceFill($attributes)->save();
            $opportunity->log('proposal_regenerated', $userId, null, null, 'Draft replaced by ' . ($source ?? 'agent'));
            return;
        }

        $opportunity->changeStatus(UpworkOpportunity::STATUS_PROPOSAL_DRAFT, $userId, $attributes, 'Draft stored by ' . ($source ?? 'agent'));
    }

    /**
     * Human edit. Any edit invalidates a previous proposal approval.
     */
    public function updateDraft(UpworkOpportunity $opportunity, array $data, int $userId): void
    {
        $this->assertDraft($opportunity);

        $opportunity->forceFill([
            'proposal_draft' => $data['proposal_draft'],
            'proposed_rate' => $data['proposed_rate'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'screening_answers' => $data['screening_answers'] ?? null,
        ]);

        if (!$opportunity->isDirty()) {
            return;
        }

        $opportunity->forceFill([
            'proposal_edited_at' => now(),
            'proposal_edited_by' => $userId,
            'proposal_approved_at' => null,
            'proposal_approved_by' => null,
        ])->save();

        $opportunity->log('proposal_edited', $userId);
    }

    public function approveDraft(UpworkOpportunity $opportunity, int $userId): void
    {
        $this->assertDraft($opportunity);

        if (trim((string) $opportunity->proposal_draft) === '') {
            throw new \DomainException('Cannot approve an empty proposal draft.');
        }

        $opportunity->forceFill([
            'proposal_approved_at' => now(),
            'proposal_approved_by' => $userId,
        ])->save();

        $opportunity->log('proposal_approved', $userId);
    }

    /**
     * Records that a human submitted the approved proposal on Upwork manually.
     */
    public function markSubmitted(UpworkOpportunity $opportunity, int $userId): void
    {
        $this->assertDraft($opportunity);

        if ($opportunity->proposal_approved_at === null) {
            throw new \DomainException('Approve the proposal draft before marking it as submitted.');
        }

        $opportunity->changeStatus(UpworkOpportunity::STATUS_SUBMITTED, $userId, [
            'submitted_at' => now(),
        ], 'Marked as submitted manually on Upwork');
    }

    /**
     * Post-submission outcome tracking (client_response / won / lost).
     */
    public function recordOutcome(UpworkOpportunity $opportunity, string $status, int $userId, ?string $note = null): void
    {
        if (!in_array($status, [
            UpworkOpportunity::STATUS_CLIENT_RESPONSE,
            UpworkOpportunity::STATUS_WON,
            UpworkOpportunity::STATUS_LOST,
        ], true)) {
            throw new \DomainException('Invalid outcome status.');
        }

        $opportunity->changeStatus($status, $userId, [], $note);
    }

    private function assertDraft(UpworkOpportunity $opportunity): void
    {
        if ($opportunity->status !== UpworkOpportunity::STATUS_PROPOSAL_DRAFT) {
            throw new \DomainException('Opportunity does not have a proposal draft in progress.');
        }
    }

    private function starterDraft(UpworkOpportunity $opportunity): string
    {
        $lines = ['Hi,', '', 'Thanks for posting "' . $opportunity->title . '".'];

        if ($opportunity->match_summary) {
            $lines[] = '';
            $lines[] = $opportunity->match_summary;
        }
        if ($opportunity->relevant_experience) {
            $lines[] = '';
            $lines[] = 'Relevant experience:';
            $lines[] = $opportunity->relevant_experience;
        }

        $angle = \App\Services\Upwork\Scoring\TargetProfileScorer::ANGLES[$opportunity->proposal_angle ?? ''] ?? null;
        $lines[] = '';
        $lines[] = $angle ? "[Proposed approach — angle: {$angle[0]}. {$angle[1]} Edit before approving]" : '[Proposed approach — edit before approving]';
        $lines[] = '';
        $lines[] = 'Best regards,';
        // Individual profiles sign as the person; the PMS does not know which Upwork account will send it.
        $profile = \App\Services\Upwork\Targeting\TargetProfiles::find($opportunity->selected_profile);
        if (($profile['track'] ?? null) === 'individual') {
            $lines[] = $profile['name'] ?: '[Your name]';
        } else {
            $lines[] = 'Code4Each';
        }

        return implode("\n", $lines);
    }
}
