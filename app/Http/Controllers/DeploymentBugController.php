<?php

namespace App\Http\Controllers;

use App\Models\DeploymentBug;
use App\Models\DeploymentBugHistory;
use App\Models\DeploymentTicket;
use App\Services\DeploymentActivityLogger;
use App\Services\DeploymentCodeGenerator;
use App\Services\DeploymentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DeploymentBugController extends Controller
{
    /**
     * Create a bug task directly from a deployment ticket (QA action).
     */
    public function store(Request $request, DeploymentTicket $ticket)
    {
        abort_unless($ticket->isAssignedQaTester(), 403, 'Only the assigned QA tester can report a bug on this deployment.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'assigned_developer_id' => 'nullable|exists:users,id',
            'steps_to_reproduce' => 'nullable|string',
            'screenshot' => 'nullable|image|max:10240',
        ]);

        $bug = DB::transaction(function () use ($validated, $request, $ticket) {
            $screenshotPath = null;
            if ($request->hasFile('screenshot')) {
                $screenshotPath = $request->file('screenshot')->store('deployment-bugs/' . $ticket->id, 'public');
            }

            $bug = DeploymentBug::create([
                'bug_code' => DeploymentCodeGenerator::nextBugCode(),
                'deployment_ticket_id' => $ticket->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'severity' => $validated['severity'],
                'assigned_developer_id' => $validated['assigned_developer_id'] ?? $ticket->assigned_developer_id,
                'reported_by' => $request->user()->id,
                'steps_to_reproduce' => $validated['steps_to_reproduce'] ?? null,
                'screenshot_path' => $screenshotPath,
                'status' => DeploymentBug::STATUS_OPEN,
            ]);

            DeploymentBugHistory::create([
                'deployment_bug_id' => $bug->id,
                'changed_by' => $request->user()->id,
                'old_status' => null,
                'new_status' => DeploymentBug::STATUS_OPEN,
            ]);

            // A new open bug means QA can no longer be considered approved.
            $ticket->update([
                'qa_approved' => false,
                'status' => DeploymentTicket::STATUS_TESTING_IN_PROGRESS,
            ]);

            DeploymentActivityLogger::log($ticket, 'Bug Created', null, $bug->bug_code, $validated['title'], $bug);

            DeploymentNotifier::send(
                $bug->assigned_developer_id,
                'bug_assigned',
                "Bug {$bug->bug_code} assigned to you",
                $validated['title'],
                $ticket,
                $bug
            );

            return $bug;
        });

        return back()->with('success', "Bug {$bug->bug_code} created.");
    }

    public function show(DeploymentBug $bug)
    {
        $bug->load(['ticket', 'developer', 'reporter', 'history.changedBy', 'comments.user']);

        return view('deployment.bugs.show', compact('bug'));
    }

    public function edit(DeploymentBug $bug)
    {
        $users = \App\Models\Users::where('role_id', 3)->where('status', 1)->orderBy('first_name')->get();

        return view('deployment.bugs.edit', compact('bug', 'users'));
    }

    public function update(Request $request, DeploymentBug $bug)
    {
        abort_unless(
            $bug->isAssignedDeveloper() || $bug->isReporter(),
            403,
            'Only the assigned developer or the QA tester who reported this bug can edit it.'
        );

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'assigned_developer_id' => 'nullable|exists:users,id',
            'steps_to_reproduce' => 'nullable|string',
        ]);

        $bug->update($validated);

        DeploymentActivityLogger::log($bug->ticket, 'Bug Updated', null, null, $bug->title, $bug);

        return redirect()->route('deployment.bugs.show', $bug)->with('success', 'Bug updated.');
    }

    /**
     * Developer changes bug status, e.g. In Progress -> Fixed -> Ready For Retest.
     */
    public function changeStatus(Request $request, DeploymentBug $bug)
    {
        abort_unless($bug->isAssignedDeveloper(), 403, 'Only the assigned developer can update this bug\'s status.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(DeploymentBug::statusOptions())],
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $bug, $request) {
            $oldStatus = $bug->status;
            $bug->update(['status' => $validated['status']]);

            DeploymentBugHistory::create([
                'deployment_bug_id' => $bug->id,
                'changed_by' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $actionLabel = match ($validated['status']) {
                'Fixed' => 'Bug Fixed',
                'Ready For Retest' => 'Bug Ready For Retest',
                'Closed' => 'Bug Closed',
                'Reopened' => 'Bug Reopened',
                default => 'Bug Status Changed',
            };

            DeploymentActivityLogger::log($bug->ticket, $actionLabel, $oldStatus, $validated['status'], $validated['remarks'] ?? null, $bug);

            // Notifications based on transition
            if ($validated['status'] === 'Ready For Retest') {
                DeploymentNotifier::send(
                    $bug->reported_by,
                    'bug_ready_for_retest',
                    "Bug {$bug->bug_code} ready for retest",
                    $validated['remarks'] ?? null,
                    $bug->ticket,
                    $bug
                );
            }

            if ($validated['status'] === 'Reopened') {
                DeploymentNotifier::send(
                    $bug->assigned_developer_id,
                    'bug_reopened',
                    "Bug {$bug->bug_code} reopened",
                    $validated['remarks'] ?? null,
                    $bug->ticket,
                    $bug
                );
            }

            $this->syncTicketBugGate($bug->ticket);
        });

        return back()->with('success', "Bug status updated to {$validated['status']}.");
    }

    /**
     * QA verifies a "Ready For Retest" bug: Close it or Reopen it.
     */
    public function verify(Request $request, DeploymentBug $bug)
    {
        abort_unless($bug->isReporter(), 403, 'Only the QA tester who reported this bug can verify and close/reopen it.');

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['Closed', 'Reopened'])],
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $bug, $request) {
            $oldStatus = $bug->status;
            $bug->update(['status' => $validated['decision']]);

            DeploymentBugHistory::create([
                'deployment_bug_id' => $bug->id,
                'changed_by' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['decision'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            DeploymentActivityLogger::log(
                $bug->ticket,
                $validated['decision'] === 'Closed' ? 'Bug Closed' : 'Bug Reopened',
                $oldStatus,
                $validated['decision'],
                $validated['remarks'] ?? null,
                $bug
            );

            if ($validated['decision'] === 'Reopened') {
                DeploymentNotifier::send(
                    $bug->assigned_developer_id,
                    'bug_reopened',
                    "Bug {$bug->bug_code} reopened by QA",
                    $validated['remarks'] ?? null,
                    $bug->ticket,
                    $bug
                );
            }

            $this->syncTicketBugGate($bug->ticket);
        });

        return back()->with('success', "Bug {$validated['decision']}.");
    }

    public function addComment(Request $request, DeploymentBug $bug)
    {
        abort_unless(
            $bug->isAssignedDeveloper() || $bug->isReporter(),
            403,
            'Only the assigned developer or the QA tester who reported this bug can comment on it.'
        );

        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $bug->comments()->create([
            'deployment_ticket_id' => $bug->deployment_ticket_id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
        ]);

        DeploymentActivityLogger::log($bug->ticket, 'Bug Comment Added', null, null, $validated['comment'], $bug);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(DeploymentBug $bug)
    {
        abort_unless($bug->isSuperAdmin(), 403, 'Only an admin can delete a bug.');

        $ticket = $bug->ticket;

        if ($bug->screenshot_path) {
            Storage::disk('public')->delete($bug->screenshot_path);
        }

        $bug->delete(); // soft delete

        DeploymentActivityLogger::log($ticket, 'Bug Deleted', $bug->status, null, $bug->title, $bug);

        return redirect()->route('deployment.tickets.show', $ticket)->with('success', 'Bug deleted.');
    }

    /**
     * Re-check whether the parent ticket's QA gate should flip back to true
     * once all bugs are closed.
     */
    private function syncTicketBugGate(DeploymentTicket $ticket): void
    {
        if (! $ticket->hasOpenBugs() && $ticket->status === DeploymentTicket::STATUS_TESTING_IN_PROGRESS) {
            $ticket->update([
                'qa_approved' => true,
                'status' => DeploymentTicket::STATUS_TESTING_PASSED,
            ]);

            DeploymentActivityLogger::log($ticket, 'Testing Approved', DeploymentTicket::STATUS_TESTING_IN_PROGRESS, DeploymentTicket::STATUS_TESTING_PASSED, 'All bugs closed.');
        }
    }
}
