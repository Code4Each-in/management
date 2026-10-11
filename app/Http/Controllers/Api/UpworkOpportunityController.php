<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upwork\StoreUpworkOpportunityRequest;
use App\Http\Requests\Upwork\UpdateProposalDraftRequest;
use App\Models\UpworkOpportunity;
use App\Services\Upwork\UpworkOpportunityService;
use App\Services\Upwork\UpworkProposalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Endpoints for the Claude Upwork agent (Sanctum token with the upwork:ingest ability).
 * These only read/write PMS data; the PMS never calls Upwork.
 */
class UpworkOpportunityController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('ingest', UpworkOpportunity::class);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(UpworkOpportunity::STATUSES))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $opportunities = UpworkOpportunity::filter($validated)
            ->latest('id')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'success' => true,
            'data' => $opportunities->getCollection()->map(fn($o) => $this->summary($o)),
            'meta' => [
                'current_page' => $opportunities->currentPage(),
                'last_page' => $opportunities->lastPage(),
                'total' => $opportunities->total(),
            ],
        ]);
    }

    public function store(StoreUpworkOpportunityRequest $request, UpworkOpportunityService $service)
    {
        [$opportunity, $created] = $service->ingest($request->validated(), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => $created ? 'Opportunity created.' : 'Opportunity updated.',
            'created' => $created,
            'data' => $this->summary($opportunity),
        ], $created ? 201 : 200);
    }

    public function storeProposalDraft(UpdateProposalDraftRequest $request, string $upworkJobId, UpworkProposalService $service)
    {
        $this->authorize('ingest', UpworkOpportunity::class);

        $opportunity = UpworkOpportunity::where('upwork_job_id', UpworkOpportunityService::normalizeJobId($upworkJobId))->first();
        if (!$opportunity) {
            return response()->json(['success' => false, 'message' => 'Opportunity not found.'], 404);
        }

        if (!in_array($opportunity->status, [UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_PROPOSAL_DRAFT], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Proposal drafts can only be stored for approved opportunities.',
            ], 409);
        }

        try {
            $service->storeAgentDraft($opportunity, $request->validated(), $request->user()->id);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Proposal draft stored.',
            'data' => $this->summary($opportunity->fresh()),
        ]);
    }

    private function summary(UpworkOpportunity $o): array
    {
        return [
            'id' => $o->id,
            'upwork_job_id' => $o->upwork_job_id,
            'upwork_url' => $o->upwork_url,
            'title' => $o->title,
            'status' => $o->status,
            'score' => $o->score,
            'recommendation' => $o->recommendation,
            'has_proposal_draft' => $o->proposal_draft !== null,
            'pms_url' => route('upwork.opportunities.show', $o),
            'updated_at' => optional($o->updated_at)->toIso8601String(),
        ];
    }
}
