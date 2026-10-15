<?php

namespace App\Http\Controllers;

use App\Http\Requests\Upwork\UpdateProposalDraftRequest;
use App\Models\UpworkOpportunity;
use App\Services\Upwork\Ai\Proposal\ProposalDraftService;
use App\Services\Upwork\Review\ReviewSlaService;
use App\Services\Upwork\Scoring\TargetProfileScorer;
use App\Services\Upwork\Targeting\TargetProfiles;
use App\Services\Upwork\UpworkOpportunityService;
use App\Services\Upwork\UpworkProposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Human review of Upwork opportunities. No action here calls Upwork.
 */
class UpworkOpportunityController extends Controller
{
    private $opportunities;
    private $proposals;

    public function __construct(UpworkOpportunityService $opportunities, UpworkProposalService $proposals)
    {
        $this->opportunities = $opportunities;
        $this->proposals = $proposals;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', UpworkOpportunity::class);

        // Invalid filter values are dropped rather than redirecting away from the list.
        $filters = $this->listFilters($request->query());
        $view = $filters['view'] ?? null;
        $sort = $filters['sort'] ?? ($view === 'archived' ? 'archived_at' : 'score');
        $query = $this->listQuery($filters);
        if ($sort === 'score') {
            $query->orderByRaw('score IS NULL')->orderByDesc('score')->latest('id');
        } elseif ($sort === 'expected_value') {
            $query->orderByRaw('expected_value IS NULL')->orderByDesc('expected_value')->latest('id');
        } elseif ($sort === 'strategic') {
            $query->orderByRaw('strategic_score IS NULL')->orderByDesc('strategic_score')->latest('id');
        } elseif ($sort === 'agency_fit' || $sort === 'individual_fit') {
            $column = $sort === 'agency_fit' ? 'agency_fit_score' : 'individual_fit_score';
            $query->orderByRaw("{$column} IS NULL")->orderByDesc($column)->latest('id');
        } elseif ($sort === 'posted_at') {
            $query->orderByRaw('posted_at IS NULL')->orderByDesc('posted_at');
        } elseif ($sort === 'archived_at') {
            $query->orderByRaw('archived_at IS NULL')->orderByDesc('archived_at')->latest('id');
        } else {
            $query->latest('id');
        }

        $opportunities = $query->with('aiAnalysis:id,fit_score,recommendation,confidence')->paginate(config('upwork.per_page', 25))->withQueryString();
        $stats = $this->opportunities->stats();
        $catalog = \App\Models\UpworkClassification::catalog();
        $viewCounts = [];
        foreach (array_keys(\App\Services\Upwork\RadarService::VIEWS) as $name) {
            $viewCounts[$name] = \App\Services\Upwork\RadarService::view($name)->count();
        }

        $matching = $opportunities->total();
        // Query string that defines this selection, for "select all matching" (no paging or sorting).
        $filterParams = \Illuminate\Support\Arr::except($filters, ['sort']);

        return view('upwork.opportunities.index', compact('opportunities', 'stats', 'filters', 'sort', 'catalog', 'view', 'viewCounts', 'matching', 'filterParams'));
    }

    private function listFilters(array $input): array
    {
        return Validator::make($input, [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(UpworkOpportunity::STATUSES))],
            'recommendation' => ['nullable', Rule::in(array_keys(UpworkOpportunity::RECOMMENDATIONS))],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort' => ['nullable', Rule::in(['score', 'posted_at', 'latest', 'expected_value', 'strategic', 'agency_fit', 'individual_fit', 'archived_at'])],
            'target' => ['nullable', Rule::in(array_keys(TargetProfiles::TRACKS))],
            'bid_as' => ['nullable', Rule::in([TargetProfiles::TRACK_AGENCY, TargetProfiles::TRACK_INDIVIDUAL])],
            'location' => ['nullable', Rule::in(['eligible', 'restricted', 'unknown', 'unclassified'])],
            'show' => ['nullable', Rule::in(['all'])],
            'class' => ['nullable', 'string', 'regex:/^[A-Z0-9_]{2,50}$/'],
            'view' => ['nullable', Rule::in(array_keys(\App\Services\Upwork\RadarService::VIEWS))],
        ])->valid();
    }

    /**
     * The list's WHERE clause (no sorting). The page and "select all matching
     * this filter" both use it, so a bulk action affects exactly what is listed.
     */
    private function listQuery(array $filters)
    {
        $view = $filters['view'] ?? null;
        if ($view) {
            // Named views (New Matches, Review, Archived, ...) define their own scope.
            $query = \App\Services\Upwork\RadarService::view($view)->filter($filters);
        } else {
            $query = UpworkOpportunity::filter($filters);
            // Discovery stores every job; untouched SKIP / excluded noise is hidden unless asked for.
            $showAll = ($filters['show'] ?? null) === 'all' || ($filters['recommendation'] ?? null) === UpworkOpportunity::RECOMMENDATION_SKIP
                || ($filters['location'] ?? null) === \App\Services\Upwork\Eligibility\LocationEligibility::RESTRICTED;
            if (!$showAll) {
                $query->visible();
            }
            // Archived jobs live in their own tab; "show all" means all active jobs.
            if (($filters['status'] ?? null) !== UpworkOpportunity::STATUS_ARCHIVED) {
                $query->active();
            }
        }
        if (!empty($filters['class'])) {
            // Codes are A-Z0-9_ only (validated), stored as JSON strings: portable across MySQL, MariaDB and SQLite.
            $query->where('classifications', 'like', '%"' . $filters['class'] . '"%');
        }

        return $query;
    }

    public function archive(UpworkOpportunity $opportunity)
    {
        $this->authorize('archive', $opportunity);

        return $this->attempt(fn() => $this->opportunities->archive($opportunity, auth()->id()),
            'Archived. It is out of the review queue and will send no reminders. Restore it from the Archived tab.');
    }

    public function restore(UpworkOpportunity $opportunity)
    {
        $this->authorize('archive', $opportunity);

        try {
            $status = $this->opportunities->restore($opportunity, auth()->id());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Restored to ' . UpworkOpportunity::STATUSES[$status] . '.'
            . ($status === UpworkOpportunity::STATUS_NEW && $opportunity->fresh()->review_due_at ? ' It has a fresh 30-minute review deadline; no old reminders are re-sent.' : ''));
    }

    /**
     * Archive / restore many jobs: either the ticked ids, or every job matching
     * the list filter. For a filter, the number the person confirmed must still
     * match, otherwise nothing changes (new jobs may have arrived meanwhile).
     */
    public function bulk(Request $request)
    {
        $this->authorize('archive', UpworkOpportunity::class);

        $v = $request->validate([
            'action' => ['required', Rule::in(['archive', 'restore'])],
            'mode' => ['required', Rule::in(['ids', 'filter'])],
            'ids' => ['required_if:mode,ids', 'array', 'max:1000'],
            'ids.*' => ['integer', 'min:1'],
            'filter' => ['nullable', 'array'],
            'expected' => ['required_if:mode,filter', 'integer', 'min:1'],
        ]);

        if ($v['mode'] === 'filter') {
            $filters = $this->listFilters((array) ($v['filter'] ?? []));
            $ids = $this->listQuery($filters)->orderBy('id')->pluck('id');
            if ($ids->count() !== (int) $v['expected']) {
                return back()->with('error', "The filter now matches {$ids->count()} jobs, not the {$v['expected']} you confirmed. Nothing was changed; check the list and try again.");
            }
        } else {
            $ids = collect($v['ids'])->map(fn($id) => (int) $id)->unique()->values();
        }

        $summary = $this->opportunities->bulk($v['action'], $ids, auth()->id());
        $verb = $v['action'] === 'archive' ? 'Archived' : 'Restored';
        $message = "{$verb} {$summary['done']} of {$ids->count()} selected job(s).";
        if (!$summary['failed']) {
            return back()->with('success', $message);
        }
        $reasons = collect($summary['failed'])->map(fn($reason, $id) => "#{$id}: {$reason}")->take(5)->implode(' ');

        return back()->with($summary['done'] ? 'success' : 'error', $message)
            ->with('bulk_failures', count($summary['failed']) . ' not changed. ' . $reasons . (count($summary['failed']) > 5 ? ' …' : ''));
    }

    public function show(UpworkOpportunity $opportunity)
    {
        $this->authorize('view', $opportunity);

        $opportunity->load(['approver', 'rejecter', 'proposalEditor', 'proposalApprover', 'reviewSnoozer', 'profileSelector', 'archiver', 'locationOverrider', 'logs.user', 'client', 'aiAnalysis']);

        $client = $opportunity->client;
        $clientHistory = $client ? app(\App\Services\Upwork\Clients\ClientIntelligenceService::class)->history($client, $opportunity->id) : null;

        $catalog = \App\Models\UpworkClassification::catalog();

        $ai = [
            'qualify' => $opportunity->aiAnalyses()->where('request_type', 'qualify')->where('status', 'succeeded')->first(),
            'deep' => $opportunity->aiAnalyses()->where('request_type', 'deep')->where('status', 'succeeded')->first(),
            'last_failure' => $opportunity->aiAnalyses()->whereIn('request_type', ['qualify', 'deep'])->where('status', 'failed')->first(),
            'usage' => \App\Models\UpworkAiUsage::where('opportunity_id', $opportunity->id)->latest('id')->get(),
            'status' => \App\Services\Upwork\Ai\AiGateway::fromSettings()->status(),
            'min_confidence' => (float) \App\Models\UpworkSetting::get('ai.min_confidence'),
            'proposal_enabled' => ProposalDraftService::enabled(),
            'proposal' => ProposalDraftService::latest($opportunity),
        ];
        $review = $this->reviewState($opportunity);
        $targeting = $this->targetingState($opportunity, $ai['deep']);

        return view('upwork.opportunities.show', compact('opportunity', 'client', 'clientHistory', 'catalog', 'ai', 'review', 'targeting'));
    }

    /** Profile and angle choices for the approval / proposal forms, prefilled from the best-fit suggestion. */
    private function targetingState(UpworkOpportunity $o, ?\App\Models\UpworkAiAnalysis $deep): array
    {
        $fit = $o->score_explanation['target'] ?? null;

        return [
            'fit' => $fit,
            'options' => TargetProfiles::options(),
            'angles' => TargetProfileScorer::ANGLES,
            'profile' => $o->selected_profile ?? ($fit['suggested_profile'] ?? TargetProfiles::TRACK_AGENCY),
            'angle' => $o->proposal_angle ?? ($fit['suggested_angle'] ?? null),
            // Reuse the deep analysis's free-text angle as a starting note.
            'note' => $o->proposal_angle_note ?? ($deep->result['proposal_angle'] ?? null),
            'editable' => in_array($o->status, [UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_PROPOSAL_DRAFT], true) && !$o->proposal_edited_at,
        ];
    }

    /**
     * Email snooze links land here. A GET only shows the choices; nothing
     * changes until the person confirms with the POST form (CSRF-protected).
     */
    public function snoozeConfirm(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('snooze', $opportunity);

        $preset = in_array($request->query('preset'), ReviewSlaService::SNOOZE_PRESETS, true) ? $request->query('preset') : '1h';
        $review = $this->reviewState($opportunity);

        return view('upwork.opportunities.snooze', ['opportunity' => $opportunity, 'preset' => $preset, 'review' => $review]);
    }

    public function snooze(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('snooze', $opportunity);

        $validated = $request->validate([
            'preset' => ['required', Rule::in(ReviewSlaService::SNOOZE_PRESETS)],
            'until' => ['nullable', 'required_if:preset,custom', 'string', 'max:20'],
        ]);

        $sla = app(ReviewSlaService::class);
        try {
            $until = $sla->snoozeUntil($validated['preset'], $validated['until'] ?? null);
            $sla->snooze($opportunity, $until, auth()->id(), $validated['preset']);
        } catch (\DomainException $e) {
            return redirect()->route('upwork.opportunities.show', $opportunity)->with('error', $e->getMessage());
        }

        return redirect()->route('upwork.opportunities.show', $opportunity)
            ->with('success', 'Reminders for this job are snoozed until ' . ReviewSlaService::format($until) . '. Other jobs are not affected; the review deadline is unchanged.');
    }

    public function unsnooze(UpworkOpportunity $opportunity)
    {
        $this->authorize('snooze', $opportunity);

        return $this->attempt(fn() => app(ReviewSlaService::class)->unsnooze($opportunity, auth()->id()), 'Reminders for this job are back on.');
    }

    /** Draft with AI / Retry: queued, budget-guarded; the draft is never submitted anywhere. */
    public function draftProposalWithAi(UpworkOpportunity $opportunity)
    {
        $this->authorize('manageProposal', $opportunity);

        $outcome = app(ProposalDraftService::class)->request($opportunity, auth()->id());
        if ($outcome !== 'queued') {
            return back()->with('error', $outcome);
        }

        return back()->with('success', 'AI proposal draft queued. It usually takes a minute or two; refresh this page, then edit it before approving.');
    }

    private function reviewState(UpworkOpportunity $o): array
    {
        $sla = app(ReviewSlaService::class);

        return [
            'tracked' => $o->review_due_at !== null,
            'open' => $o->review_due_at !== null && $o->status === UpworkOpportunity::STATUS_NEW,
            'overdue' => $o->review_due_at !== null && $o->status === UpworkOpportunity::STATUS_NEW && $o->review_due_at->isPast(),
            'snoozed' => $sla->isSnoozed($o),
            'timezone' => ReviewSlaService::timezone(),
            'now_local' => now()->setTimezone(ReviewSlaService::timezone()),
            'presets' => [
                '1h' => $sla->snoozeUntil('1h'),
                '4h' => $sla->snoozeUntil('4h'),
                'tomorrow' => $sla->snoozeUntil('tomorrow'),
            ],
        ];
    }

    /**
     * Correct the location classification (eligible / restricted), or go back
     * to the rules. The original text and rule are kept; the change is logged.
     */
    public function overrideLocation(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('overrideLocation', $opportunity);

        $validated = $request->validate([
            'classification' => ['required', Rule::in(['eligible', 'restricted', 'rules'])],
            'note' => ['nullable', 'required_unless:classification,rules', 'string', 'max:500'],
        ]);
        $classification = $validated['classification'] === 'rules' ? null : $validated['classification'];

        return $this->attempt(
            fn() => $this->opportunities->overrideLocation($opportunity, $classification, auth()->id(), $validated['note'] ?? null),
            $classification ? 'Location classification corrected and recorded in the activity log.' : 'Manual location override removed; the rules apply again.'
        );
    }

    public function approve(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('approve', $opportunity);

        // Optional: without a choice the best-fit suggestion is recorded (and labelled as such).
        $targeting = $request->validate($this->targetingRules(false));

        return $this->attempt(fn() => $this->opportunities->approve($opportunity, auth()->id(), $targeting), 'Opportunity approved.');
    }

    /** Change the profile / angle before the draft is written. Never touches Upwork. */
    public function selectTargeting(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('manageProposal', $opportunity);

        $targeting = $request->validate($this->targetingRules(true));

        return $this->attempt(fn() => $this->opportunities->selectTargeting($opportunity, $targeting, auth()->id()),
            'Profile and angle saved. Generate or redraft the proposal to use them.');
    }

    private function targetingRules(bool $required): array
    {
        return [
            'selected_profile' => [$required ? 'required' : 'nullable', Rule::in(array_keys(TargetProfiles::options()))],
            'proposal_angle' => ['nullable', Rule::in(array_keys(TargetProfileScorer::ANGLES))],
            'proposal_angle_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function reject(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('reject', $opportunity);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->attempt(
            fn() => $this->opportunities->reject($opportunity, auth()->id(), $validated['rejection_reason'] ?? null),
            'Opportunity rejected.'
        );
    }

    public function generateProposal(UpworkOpportunity $opportunity)
    {
        $this->authorize('manageProposal', $opportunity);

        return $this->attempt(fn() => $this->proposals->generateDraft($opportunity, auth()->id()), 'Proposal draft created. Review and edit it below.');
    }

    public function updateProposal(UpdateProposalDraftRequest $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('manageProposal', $opportunity);

        return $this->attempt(fn() => $this->proposals->updateDraft($opportunity, $request->validated(), auth()->id()), 'Proposal draft saved.');
    }

    public function approveProposal(UpworkOpportunity $opportunity)
    {
        $this->authorize('manageProposal', $opportunity);

        return $this->attempt(fn() => $this->proposals->approveDraft($opportunity, auth()->id()), 'Proposal draft approved.');
    }

    public function markSubmitted(UpworkOpportunity $opportunity)
    {
        $this->authorize('manageProposal', $opportunity);

        return $this->attempt(fn() => $this->proposals->markSubmitted($opportunity, auth()->id()), 'Marked as submitted.');
    }

    public function recordOutcome(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('manageProposal', $opportunity);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                UpworkOpportunity::STATUS_CLIENT_RESPONSE,
                UpworkOpportunity::STATUS_WON,
                UpworkOpportunity::STATUS_LOST,
            ])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->attempt(
            fn() => $this->proposals->recordOutcome($opportunity, $validated['status'], auth()->id(), $validated['note'] ?? null),
            'Status updated.'
        );
    }

    /** Analyze with AI / Deep analyze / Regenerate: queued, budget-guarded, read-only. */
    public function analyze(Request $request, UpworkOpportunity $opportunity)
    {
        $this->authorize('analyze', $opportunity);

        $validated = $request->validate([
            'type' => ['required', Rule::in([\App\Models\UpworkAiAnalysis::TYPE_QUALIFY, \App\Models\UpworkAiAnalysis::TYPE_DEEP])],
            'regenerate' => ['nullable', 'boolean'],
        ]);

        $outcome = app(\App\Services\Upwork\Ai\Qualification\QualificationService::class)
            ->request($opportunity, auth()->id(), $request->boolean('regenerate'), $validated['type']);

        if ($outcome !== 'queued') {
            return back()->with('error', $outcome);
        }
        $label = $validated['type'] === 'deep' ? 'Deep analysis' : 'AI analysis';

        return back()->with('success', "{$label} queued. It usually completes within a minute or two; refresh this page.");
    }

    private function attempt(callable $action, string $successMessage)
    {
        try {
            $action();
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $successMessage);
    }
}
