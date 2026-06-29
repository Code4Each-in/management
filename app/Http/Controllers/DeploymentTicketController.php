<?php

namespace App\Http\Controllers;

use App\Models\DeploymentTicket;
use App\Models\DeploymentAttachment;
use App\Models\DeploymentReviewHistory;
use App\Models\DeploymentStatusHistory;
use App\Models\Users;
use App\Models\Projects;
use App\Models\Tickets;
use App\Services\DeploymentActivityLogger;
use App\Services\DeploymentCodeGenerator;
use App\Services\DeploymentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeploymentTicketController extends Controller
{
    /**
     * List all deployment tickets with filters.
     */
    public function index(Request $request)
    {
        $query = DeploymentTicket::with(['project', 'developer', 'reviewer', 'qaTester'])
            ->withCount(['bugs' => function ($q) {
                $q->whereNotIn('status', ['Closed']);
            }]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('developer_id')) {
            $query->where('assigned_developer_id', $request->developer_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('deployment_name', 'like', "%{$search}%")
                    ->orWhere('deployment_code', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $projects = Projects::where('status', 'active')->orderBy('project_name')->get();
        $developers = Users::whereIn('role_id', [1, 3])->where('status', 1)->orderBy('first_name')->get();
        $statusOptions = DeploymentTicket::statusOptions();

        return view('deployment.tickets.index', compact('tickets', 'projects', 'developers', 'statusOptions'));
    }

    public function create()
    {
        $projects =  Projects::where('status', 'active')->orderBy('project_name')->get();
        $users = Users::whereIn('role_id', [1, 3])->where('status', 1)->orderBy('first_name')->get();
        return view('deployment.tickets.create', compact('projects', 'users'));
    }

    /**
     * Step 1: Developer creates the deployment ticket (status: Draft).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'deployment_name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'related_ticket_ids' => 'nullable|string|max:255',
            'assigned_developer_id' => 'nullable|exists:users,id',
            'reviewer_id' => 'nullable|exists:users,id',
            'qa_tester_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'changes_done' => 'nullable|string',
            'files_modified' => 'nullable|string',
            'modules_affected' => 'nullable|string',
            'testing_done' => 'nullable|string',
            'deployment_notes' => 'nullable|string',
            'db_changes_required' => 'nullable|boolean',
            'migration_details' => 'nullable|string',
            'current_version' => 'nullable|string|max:50',
            'new_version' => 'nullable|string|max:50',
            'deployment_date' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:20480', // 20MB
            'attachment_types' => 'nullable|array',
        ]);

        $ticket = DB::transaction(function () use ($validated, $request) {
            $ticket = DeploymentTicket::create([
                'deployment_code' => DeploymentCodeGenerator::nextDeploymentCode(),
                'deployment_name' => $validated['deployment_name'],
                'project_id' => $validated['project_id'],
                'related_ticket_ids' => $validated['related_ticket_ids'] ?? null,
                'created_by' => $request->user()->id,
                'assigned_developer_id' => $validated['assigned_developer_id'] ?? null,
                'reviewer_id' => $validated['reviewer_id'] ?? null,
                'qa_tester_id' => $validated['qa_tester_id'] ?? null,
                'priority' => $validated['priority'],
                'changes_done' => $validated['changes_done'] ?? null,
                'files_modified' => $validated['files_modified'] ?? null,
                'modules_affected' => $validated['modules_affected'] ?? null,
                'testing_done' => $validated['testing_done'] ?? null,
                'deployment_notes' => $validated['deployment_notes'] ?? null,
                'db_changes_required' => $request->boolean('db_changes_required'),
                'migration_details' => $validated['migration_details'] ?? null,
                'current_version' => $validated['current_version'] ?? null,
                'new_version' => $validated['new_version'] ?? null,
                'deployment_date' => $validated['deployment_date'] ?? null,
                'status' => DeploymentTicket::STATUS_DRAFT,
            ]);

            $this->handleAttachmentUploads($request, $ticket);

            DeploymentActivityLogger::log($ticket, 'Deployment Created', null, $ticket->status, "Deployment ticket {$ticket->deployment_code} created.");

            return $ticket;
        });

        return redirect()->route('deployment.tickets.show', $ticket)
            ->with('success', "Deployment ticket {$ticket->deployment_code} created successfully.");
    }

    public function show(DeploymentTicket $ticket)
    {
        $ticket->load([
            'project', 'creator', 'developer', 'reviewer', 'qaTester',
            'attachments', 'reviewHistory.reviewer', 'bugs.developer', 'bugs.reporter',
            'statusHistory.changedBy', 'activityLogs.user', 'comments.user',
        ]);

        $users = Users::orderBy('first_name')->get();
        $relatedTicket = null;
            if ($ticket->related_ticket_ids) {
                $relatedTicket = Tickets::select('id', 'title')->find($ticket->related_ticket_ids);
            }
        return view('deployment.tickets.show', compact('ticket', 'users', 'relatedTicket'));
    }

    public function edit(DeploymentTicket $ticket)
    {
        $projects = Projects::where('status', 'active')->orderBy('project_name')->get();
        $users =    $users = Users::whereIn('role_id', [1, 3])->where('status', 1)->orderBy('first_name')->get();

        return view('deployment.tickets.edit', compact('ticket', 'projects', 'users'));
    }

    /**
     * Developer updates deployment details (allowed pre-approval, and when changes requested).
     */
    public function update(Request $request, DeploymentTicket $ticket)
    {
        abort_unless(
            $ticket->isAssignedDeveloper() || $ticket->isSuperAdmin(),
            403,
            'Only the assigned developer or an admin can edit this deployment ticket.'
        );

        $validated = $request->validate([
            'deployment_name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'related_ticket_ids' => 'nullable|string|max:255',
            'assigned_developer_id' => 'nullable|exists:users,id',
            'reviewer_id' => 'nullable|exists:users,id',
            'qa_tester_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'changes_done' => 'nullable|string',
            'files_modified' => 'nullable|string',
            'modules_affected' => 'nullable|string',
            'testing_done' => 'nullable|string',
            'deployment_notes' => 'nullable|string',
            'db_changes_required' => 'nullable|boolean',
            'migration_details' => 'nullable|string',
            'current_version' => 'nullable|string|max:50',
            'new_version' => 'nullable|string|max:50',
            'deployment_date' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:20480',
        ]);

        DB::transaction(function () use ($validated, $request, $ticket) {
            $trackedFields = ['assigned_developer_id', 'reviewer_id', 'qa_tester_id', 'priority'];
            foreach ($trackedFields as $field) {
                $newVal = $validated[$field] ?? null;
                if ((string) $ticket->{$field} !== (string) $newVal) {
                    DeploymentStatusHistory::create([
                        'deployment_ticket_id' => $ticket->id,
                        'changed_by' => $request->user()->id,
                        'field_changed' => $field,
                        'old_value' => $ticket->{$field},
                        'new_value' => $newVal,
                    ]);
                }
            }

            $validated['db_changes_required'] = $request->boolean('db_changes_required');
            $ticket->update($validated);

            $this->handleAttachmentUploads($request, $ticket);

            DeploymentActivityLogger::log($ticket, 'Deployment Updated', null, null, 'Deployment details updated.');
        });

        return redirect()->route('deployment.tickets.show', $ticket)
            ->with('success', 'Deployment ticket updated successfully.');
    }

    public function destroy(DeploymentTicket $ticket)
    {
        abort_unless($ticket->isSuperAdmin(), 403, 'Only an admin can delete a deployment ticket.');

        $ticket->delete(); // soft delete

        DeploymentActivityLogger::log($ticket, 'Deployment Deleted', $ticket->status, null, 'Deployment ticket soft-deleted.');

        return redirect()->route('deployment.tickets.index')
            ->with('success', 'Deployment ticket deleted.');
    }

    /**
     * Step 2: Developer submits the ticket for review.
     */
    public function submitForReview(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isAssignedDeveloper(), 403, 'Only the assigned developer can submit this deployment for review.');

        if (! $ticket->reviewer_id) {
            return back()->with('error', 'Please assign a reviewer before submitting for review.');
        }

        DB::transaction(function () use ($ticket, $request) {
            $oldStatus = $ticket->status;
            $isFirstSubmission = $ticket->first_submitted_for_review_at === null;

            $ticket->update([
                'status' => DeploymentTicket::STATUS_REVIEW_PENDING,
                'review_attempts' => $ticket->review_attempts + 1,
                'first_submitted_for_review_at' => $isFirstSubmission ? now() : $ticket->first_submitted_for_review_at,
            ]);

            DeploymentReviewHistory::create([
                'deployment_ticket_id' => $ticket->id,
                'reviewer_id' => $ticket->reviewer_id,
                'action' => 'Submitted',
                'attempt_number' => $ticket->review_attempts,
            ]);

            DeploymentActivityLogger::log($ticket, 'Submitted For Review', $oldStatus, $ticket->status);

            DeploymentNotifier::send(
                $ticket->reviewer_id,
                'review_requested',
                "Deployment {$ticket->deployment_code} submitted for review",
                "{$ticket->deployment_name} is ready for your review.",
                $ticket
            );
        });

        return back()->with('success', 'Deployment submitted for review.');
    }

    /**
     * Step 3: Reviewer takes action - Approve / Reject / Request Changes.
     */
    public function review(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isAssignedReviewer(), 403, 'Only the assigned reviewer can review this deployment.');

        $validated = $request->validate([
            'action' => ['required', Rule::in(['Approved', 'Rejected', 'Changes Requested'])],
            'comments' => 'nullable|string',
            'time_spent_minutes' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $ticket, $request) {
            $oldStatus = $ticket->status;

            $statusMap = [
                'Approved' => DeploymentTicket::STATUS_REVIEW_APPROVED,
                'Rejected' => DeploymentTicket::STATUS_REVIEW_REJECTED,
                'Changes Requested' => DeploymentTicket::STATUS_CHANGES_REQUESTED,
            ];

            $newStatus = $statusMap[$validated['action']];

            $ticket->update([
                'status' => $newStatus,
                'code_review_approved' => $validated['action'] === 'Approved',
                'review_completed_at' => in_array($validated['action'], ['Approved', 'Rejected']) ? now() : null,
            ]);

            DeploymentReviewHistory::create([
                'deployment_ticket_id' => $ticket->id,
                'reviewer_id' => $request->user()->id,
                'action' => $validated['action'],
                'comments' => $validated['comments'] ?? null,
                'attempt_number' => $ticket->review_attempts,
                'time_spent_minutes' => $validated['time_spent_minutes'] ?? null,
            ]);

            DeploymentActivityLogger::log(
                $ticket,
                "Review {$validated['action']}",
                $oldStatus,
                $newStatus,
                $validated['comments'] ?? null
            );

            $notifTitle = "Review {$validated['action']} - {$ticket->deployment_code}";
            DeploymentNotifier::send(
                $ticket->assigned_developer_id ?: $ticket->created_by,
                'review_' . str_replace(' ', '_', strtolower($validated['action'])),
                $notifTitle,
                $validated['comments'] ?? null,
                $ticket
            );
        });

        return back()->with('success', "Review action recorded: {$validated['action']}.");
    }

    /**
     * Step 4: Developer resubmits after fixing review comments - reuses submitForReview.
     * Kept as a distinct named route for clarity in the workflow / UI.
     */
    public function resubmit(Request $request, DeploymentTicket $ticket)
    {
        return $this->submitForReview($request, $ticket);
    }

    /**
     * Step 5: QA marks testing pass/fail.
     */
    public function testing(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isAssignedQaTester(), 403, 'Only the assigned QA tester can record a testing result.');

        $validated = $request->validate([
            'result' => ['required', Rule::in(['Pass', 'Fail'])],
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $ticket, $request) {
            $oldStatus = $ticket->status;

            if ($validated['result'] === 'Pass') {
                $newStatus = $ticket->hasOpenBugs()
                    ? DeploymentTicket::STATUS_TESTING_IN_PROGRESS
                    : DeploymentTicket::STATUS_TESTING_PASSED;

                $ticket->update([
                    'status' => $newStatus,
                    'qa_approved' => ! $ticket->hasOpenBugs(),
                ]);

                $action = 'Testing Approved';
            } else {
                $ticket->update([
                    'status' => DeploymentTicket::STATUS_TESTING_FAILED,
                    'qa_approved' => false,
                ]);

                $action = 'Testing Failed';
            }

            DeploymentActivityLogger::log($ticket, $action, $oldStatus, $ticket->status, $validated['notes'] ?? null);

            DeploymentNotifier::send(
                $ticket->assigned_developer_id ?: $ticket->created_by,
                'testing_' . strtolower($validated['result']),
                "Testing {$validated['result']} - {$ticket->deployment_code}",
                $validated['notes'] ?? null,
                $ticket
            );
        });

        return back()->with('success', "Testing result recorded: {$validated['result']}.");
    }

    /**
     * Move ticket into "Testing In Progress" (QA starts testing).
     */
    public function startTesting(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isAssignedQaTester(), 403, 'Only the assigned QA tester can start testing on this deployment.');

        $oldStatus = $ticket->status;
        $ticket->update(['status' => DeploymentTicket::STATUS_TESTING_IN_PROGRESS]);

        DeploymentActivityLogger::log($ticket, 'Testing Started', $oldStatus, $ticket->status);

        return back()->with('success', 'Testing started.');
    }

    /**
     * Mark ticket "Ready For Deployment" once all gates are satisfied.
     */
    public function markReadyForDeployment(Request $request, DeploymentTicket $ticket)
    {
        abort_unless(
            $ticket->isAssignedDeveloper() || $ticket->isAssignedQaTester(),
            403,
            'Only the assigned developer or QA tester can mark this deployment ready.'
        );

        if (! $ticket->isEligibleForDeploymentApproval()) {
            return back()->with('error', 'Ticket must have Code Review Approved, QA Approved, and zero open bugs before it can be marked Ready For Deployment.');
        }

        $oldStatus = $ticket->status;
        $ticket->update(['status' => DeploymentTicket::STATUS_READY_FOR_DEPLOYMENT]);

        DeploymentActivityLogger::log($ticket, 'Marked Ready For Deployment', $oldStatus, $ticket->status);

        return back()->with('success', 'Deployment marked as Ready For Deployment.');
    }

    /**
     * Admin approves or rejects the deployment for release.
     */
    public function approveDeployment(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isSuperAdmin(), 403, 'Only an admin can approve or reject a deployment for release.');

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'remarks' => 'nullable|string',
        ]);

        if ($validated['decision'] === 'Approved' && ! $ticket->isEligibleForDeploymentApproval()) {
            return back()->with('error', 'Cannot approve: review/QA gates not satisfied or open bugs remain.');
        }

        $oldStatus = $ticket->status;
        $newStatus = $validated['decision'] === 'Approved'
            ? DeploymentTicket::STATUS_DEPLOYMENT_APPROVED
            : DeploymentTicket::STATUS_DEPLOYMENT_REJECTED;

        $ticket->update(['status' => $newStatus]);

        DeploymentActivityLogger::log($ticket, "Deployment {$validated['decision']}", $oldStatus, $newStatus, $validated['remarks'] ?? null);

        DeploymentNotifier::send(
            $ticket->assigned_developer_id ?: $ticket->created_by,
            'deployment_' . strtolower($validated['decision']),
            "Deployment {$validated['decision']} - {$ticket->deployment_code}",
            $validated['remarks'] ?? null,
            $ticket
        );

        return back()->with('success', "Deployment {$validated['decision']}.");
    }

    /**
     * Mark as actually deployed to production.
     */
    public function markDeployed(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isSuperAdmin(), 403, 'Only an admin can mark a deployment as Deployed.');

        if ($ticket->status !== DeploymentTicket::STATUS_DEPLOYMENT_APPROVED) {
            return back()->with('error', 'Only an approved deployment can be marked as Deployed.');
        }

        $oldStatus = $ticket->status;
        $ticket->update([
            'status' => DeploymentTicket::STATUS_DEPLOYED,
            'deployment_date' => now()->toDateString(),
        ]);

        DeploymentActivityLogger::log($ticket, 'Deployment Deployed', $oldStatus, $ticket->status);

        return back()->with('success', 'Deployment marked as Deployed.');
    }

    /**
     * Mark rollback required / rolled back.
     */
    public function rollback(Request $request, DeploymentTicket $ticket)
    {
        abort_unless(
            $ticket->isAssignedDeveloper() || $ticket->isSuperAdmin(),
            403,
            'Only the assigned developer or an admin can manage rollback status.'
        );

        $validated = $request->validate([
            'stage' => ['required', Rule::in(['Rollback Required', 'Rolled Back'])],
            'reason' => 'nullable|string',
        ]);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => $validated['stage']]);

        DeploymentActivityLogger::log($ticket, $validated['stage'], $oldStatus, $validated['stage'], $validated['reason'] ?? null);

        return back()->with('success', "Status updated to {$validated['stage']}.");
    }

    /**
     * Admin override - force any status regardless of workflow gates.
     */
    public function overrideStatus(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isSuperAdmin(), 403, 'Only an admin can force-override a deployment status.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(DeploymentTicket::statusOptions())],
            'reason' => 'nullable|string',
        ]);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => $validated['status']]);

        DeploymentStatusHistory::create([
            'deployment_ticket_id' => $ticket->id,
            'changed_by' => $request->user()->id,
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $validated['status'],
        ]);

        DeploymentActivityLogger::log($ticket, 'Status Overridden By Admin', $oldStatus, $validated['status'], $validated['reason'] ?? null);

        return back()->with('success', 'Status overridden successfully.');
    }

    /**
     * Add a ticket-level comment.
     */
    public function addComment(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isInvolved(), 403, 'Only people assigned to this deployment can comment on it.');

        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
        ]);

        DeploymentActivityLogger::log($ticket, 'Comment Added', null, null, $validated['comment']);

        return back()->with('success', 'Comment added.');
    }

    private function handleAttachmentUploads(Request $request, DeploymentTicket $ticket): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $types = $request->input('attachment_types', []);

        foreach ($request->file('attachments') as $index => $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('deployment-attachments/' . $ticket->id, 'public');

            DeploymentAttachment::create([
                'deployment_ticket_id' => $ticket->id,
                'type' => $types[$index] ?? 'Other',
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'uploaded_by' => $request->user()->id,
            ]);
        }
    }

    public function deleteAttachment(DeploymentAttachment $attachment)
    {
        $ticket = $attachment->ticket;
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        DeploymentActivityLogger::log($ticket, 'Attachment Removed', $attachment->original_name, null);

        return back()->with('success', 'Attachment removed.');
    }
    /**
     * Get in-progress tickets for a given project (AJAX).
     */
    public function getTicketsByProject($projectId)
    {
        $tickets = Tickets::where('project_id', $projectId)
            ->where('status', 'in_progress')
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return response()->json($tickets);
    }
}
