<?php

namespace App\Http\Controllers;

use App\Models\DeploymentAttachment;
use App\Models\DeploymentTicket;
use App\Models\DeploymentBug;
use App\Models\Projects;
use App\Models\User;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeploymentTicketController extends Controller
{
    // public function index()
    // {
    //     $tickets = DeploymentTicket::with(['project', 'developers', 'qa'])->latest()->paginate(20);

    //     $stats = [
    //         'total' => DeploymentTicket::count(),
    //         'deplyoment_pending' => DeploymentTicket::where('status', 'deplyoment_pending')->count(),
    //         'needs_fix' => DeploymentTicket::where('status', 'needs_fix')->count(),
    //         'approved' => DeploymentTicket::where('status', 'approved')->count(),
    //         'deployed' => DeploymentTicket::where('status', 'deployed')->count(),
    //         'open_bugs' => DeploymentBug::whereIn('status', ['open', 'fixed'])->count(),
    //         'rolled_back' => DeploymentTicket::where('status', 'rolled_back')->count(),
    //     ];

    //     return view('deployment.index', compact('tickets', 'stats'));
    // }
public function index(Request $request)
{
    $search = $request->input('search');
    $filterText = $request->input('filter_text');
    $projectId = $request->input('project_id');

    $query = DeploymentTicket::with(['project', 'developers', 'qa'])
        ->when($projectId, function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        })
        ->when($search, function ($q) use ($search) {
            $q->where(function ($qq) use ($search) {
                $qq->where('deployment_code', 'like', "%{$search}%")
                   ->orWhere('deployment_name', 'like', "%{$search}%")
                   ->orWhereHas('project', fn($pq) => $pq->where('project_name', 'like', "%{$search}%"))
                   ->orWhereHas('developers', fn($dq) => $dq->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                   ->orWhereHas('qa', fn($qaq) => $qaq->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
            });
        })
        ->when($filterText, function ($q) use ($filterText) {
            $q->where(function ($qq) use ($filterText) {
                $qq->where('modules_affected', 'like', "%{$filterText}%")
                   ->orWhere('files_modified', 'like', "%{$filterText}%")
                   ->orWhere('migration_details', 'like', "%{$filterText}%");
            });
        });

    $filteredIds = (clone $query)->pluck('id');

    $stats = [
        'total'       => $filteredIds->count(),
        'deplyoment_pending'   => (clone $query)->where('status', 'deplyoment_pending')->count(),
        'needs_fix'   => (clone $query)->where('status', 'needs_fix')->count(),
        'approved'    => (clone $query)->where('status', 'approved')->count(),
        'deployed'    => (clone $query)->where('status', 'deployed')->count(),
        'rolled_back' => (clone $query)->where('status', 'rolled_back')->count(),
        'open_bugs'   => DeploymentBug::whereIn('deployment_ticket_id', $filteredIds)
                            ->whereIn('status', ['open', 'fixed'])->count(),
    ];

    $tickets = $query->latest()->paginate(20)
        ->appends(['search' => $search, 'filter_text' => $filterText, 'project_id' => $projectId]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'stats' => $stats,
            'tickets' => $tickets->map(function ($t) {
                return [
                    'code' => $t->deployment_code,
                    'name' => $t->deployment_name,
                    'project' => $t->project->project_name ?? '—',
                    'developers' => $t->developers->pluck('first_name')->implode(', ') ?: '—',
                    'qa' => $t->qa->first_name ?? '—',
                    'status' => $t->status,
                    'status_label' => str_replace('_', ' ', $t->status),
                    'priority' => $t->priority,
                    'show_url' => route('deployment.tickets.show', $t),
                ];
            }),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'first_item' => $tickets->firstItem(),
                'last_item' => $tickets->lastItem(),
                'total' => $tickets->total(),
                'links' => collect($tickets->linkCollection())->map(fn($l) => [
                    'url' => $l['url'], 'label' => $l['label'], 'active' => $l['active'],
                ]),
            ],
        ]);
    }

    $projects = Projects::orderBy('project_name')->get();

    return view('deployment.index', compact('tickets', 'stats', 'search', 'filterText', 'projectId', 'projects'));
}
    public function create()
    {
        $projects = Projects::where('status', 'active')->orderBy('project_name')->get();
        $users = Users::whereIn('role_id', [1, 3])
            ->where('status', 1)
            ->orderBy('first_name')
            ->get();

        return view('deployment.create', compact('projects', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'deployment_name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'assigned_developer_ids' => 'nullable|array',
            'assigned_developer_ids.*' => 'exists:users,id',
            'qa_id' => 'nullable|exists:users,id',
        ]);

        $nextId = DeploymentTicket::max('id') + 1;
        $validated['deployment_code'] = 'DEP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'deplyoment_pending';
        $validated['db_changes_required'] = $request->boolean('db_changes_required');

        $validated += $request->only([
            'related_ticket_ids','changes_done','files_modified','modules_affected',
            'testing_done','deployment_notes','migration_details',
            'current_version','new_version','deployment_date',
        ]);

        $developerIds = $validated['assigned_developer_ids'] ?? [];
        unset($validated['assigned_developer_ids']); // not a column, goes to pivot instead

        $ticket = DeploymentTicket::create($validated);
        $ticket->developers()->sync($developerIds);

        $ticket->logs()->create([ 
            'user_id' => auth()->id(),
            'old_status' => null,
            'new_status' => 'deplyoment_pending',
        ]);

            // if ($request->hasFile('attachments')) {
            //     foreach ($request->file('attachments') as $i => $file) {
            //         if (!$file) continue;
            //         $path = $file->store('deployment_attachments', 'public');
            //         $ticket->attachments()->create([
            //             'type' => $request->attachment_types[$i] ?? 'Other',
            //             'file_path' => $path,
            //             'original_name' => $file->getClientOriginalName(),
            //         ]);
            //     }
            // }

               if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $i => $file) {
                        if (!$file) {
                            continue;
                        }

                        $filename = time() . '_' . $file->getClientOriginalName();

                        // Ensure the directory exists
                        if (!file_exists(public_path('storage/deployment_attachments'))) {
                            mkdir(public_path('storage/deployment_attachments'), 0755, true);
                        }

                        // Move the file
                        $file->move(public_path('storage/deployment_attachments'), $filename);

                        $ticket->attachments()->create([
                            'type' => $request->attachment_types[$i] ?? 'Other',
                            'file_path' => 'deployment_attachments/' . $filename,
                            'original_name' => $file->getClientOriginalName(),
                        ]);
                    }
                }

        return redirect()->route('deployment.tickets.show', $ticket)
            ->with('success', 'Deployment ticket created.');
    }

    public function show(DeploymentTicket $ticket)
    {
        $ticket->load(['project', 'developers', 'qa', 'bugs', 'attachments', 'logs.user']);
        return view('deployment.show', compact('ticket'));
    }

    public function submitForQA(DeploymentTicket $ticket)
    {
        $ticket->changeStatus('deplyoment_pending', auth()->id());
        return back()->with('success', 'Submitted for QA.');
    }

    public function approve(DeploymentTicket $ticket)
    {
        if ($ticket->openBugsCount() > 0) {
            return back()->with('error', 'Cannot approve — open bugs still exist.');
        }
        $ticket->changeStatus('approved', auth()->id());
        return back()->with('success', 'Ticket approved.');
    }
    public function markDeployed(DeploymentTicket $ticket)
    {
        $ticket->changeStatus('deployed', auth()->id());
        return back()->with('success', 'Marked as deployed.');
    }

    public function addBug(Request $request, DeploymentTicket $ticket)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'required|in:Low,Medium,High',
            'screenshot' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('screenshot')) {
            $validated['screenshot'] = $request->file('screenshot')->store('deployment_bugs', 'public');
        }

        $ticket->bugs()->create($validated + ['status' => 'open']);
        $ticket->changeStatus('needs_fix', auth()->id());

        return back()->with('success', 'Bug raised.');
    }

    public function markBugFixed(DeploymentBug $bug)
    {
        $bug->update(['status' => 'fixed']);
        return back()->with('success', 'Bug marked fixed — awaiting QA verification.');
    }

    public function closeBug(DeploymentBug $bug)
    {
        $bug->update(['status' => 'closed']);
        return back()->with('success', 'Bug closed.');
    }
    public function projectTickets(Projects $project)
    {
        $tickets = $project->projectOnTicket()
            ->where('status', 'in_progress')
            ->get();

        $titleColumn = collect(['title', 'ticket_title', 'subject', 'name'])
            ->first(fn ($col) => $tickets->isNotEmpty() && array_key_exists($col, $tickets->first()->getAttributes()));

        $result = $tickets->map(function ($t) use ($titleColumn) {
            return [
                'id' => $t->id,
                'title' => $titleColumn ? $t->{$titleColumn} : ('Ticket #' . $t->id),
            ];
        });

        return response()->json($result);
    }
    public function reports(Request $request)
    {
        $from = $request->from ? \Carbon\Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->to ? \Carbon\Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        $query = DeploymentTicket::with(['project', 'developers', 'qa', 'bugs'])
            ->whereBetween('created_at', [$from, $to]);

        // metrics always calculated on the full date-range set
        $allTickets = $query->get();

        $filteredTickets = $request->deployment_id
            ? $allTickets->where('id', $request->deployment_id)->values()
            : $allTickets;

        $reportTickets = $filteredTickets;

        $developerMetrics = [];

        foreach ($reportTickets as $ticket) {
            foreach ($ticket->developers as $dev) {

                $key = $dev->id;

                if (!isset($developerMetrics[$key])) {
                    $developerMetrics[$key] = [
                        'name' => $dev->first_name.' '.$dev->last_name,
                        'deployments' => 0,
                        'fixes_requested' => 0,
                        'bugs_raised' => 0,
                        'bugs_fixed' => 0,
                    ];
                }

                $developerMetrics[$key]['deployments']++;
                $developerMetrics[$key]['fixes_requested'] += $ticket->fix_attempts;
                $developerMetrics[$key]['bugs_raised'] += $ticket->bugs->count();
                $developerMetrics[$key]['bugs_fixed'] += $ticket->bugs->whereIn('status', ['fixed', 'closed'])->count();
            }
        }

        foreach ($developerMetrics as &$d) {
            $d['pass_rate'] = $d['bugs_raised'] > 0
                ? round(($d['bugs_fixed'] / $d['bugs_raised']) * 100).'%'
                : '100%';
        }

        $qaMetrics = [];

        foreach ($reportTickets as $ticket) {

            if (!$ticket->qa) {
                continue;
            }

            $key = $ticket->qa->id;

            if (!isset($qaMetrics[$key])) {
                $qaMetrics[$key] = [
                    'name' => $ticket->qa->first_name.' '.$ticket->qa->last_name,
                    'bugs_found' => 0,
                    'testing_approvals' => 0,
                ];
            }

            $qaMetrics[$key]['bugs_found'] += $ticket->bugs->count();

            if ($ticket->qa_approved) {
                $qaMetrics[$key]['testing_approvals']++;
            }
        }
        return view('deployment.reports', [
            'tickets' => $filteredTickets,
            'allTicketsForDropdown' => $allTickets, // for the filter dropdown options
            'developerMetrics' => $developerMetrics,
            'qaMetrics' => $qaMetrics,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'selectedDeploymentId' => $request->deployment_id,
        ]);
    }
    public function rollback(DeploymentTicket $ticket)
    {
        if (auth()->user()->role_id != 1) {
            abort(403, 'Only Admin can rollback a deployment.');
        }

        if ($ticket->status !== 'deployed') {
            return back()->with('error', 'Only deployed tickets can be rolled back.');
        }

        $ticket->changeStatus('deplyoment_pending', auth()->id());

        return back()->with('success', 'Deployment rolled back — sent  for re-review.');
    }
    public function edit(DeploymentTicket $ticket)
    {
        $isAssignedDeveloper = $ticket->developers->pluck('id')->contains(auth()->id());
        // if (!in_array($ticket->status, ['draft', 'needs_fix', 'deplyoment_pending'])
        //     || !($ticket->created_by == auth()->id() || $isAssignedDeveloper)) {
        //     abort(403, 'This ticket can no longer be edited.');
        // }

        $projects = Projects::all();
        $users = Users::all();

        return view('deployment.edit', compact('ticket', 'projects', 'users'));
    }

    public function update(Request $request, DeploymentTicket $ticket)
    {
        $validated = $request->validate([
            'deployment_name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'assigned_developer_ids' => 'nullable|array',
            'assigned_developer_ids.*' => 'exists:users,id',
            'qa_id' => 'nullable|exists:users,id',
        ]);

        $validated['db_changes_required'] = $request->boolean('db_changes_required');

        $validated += $request->only([
            'related_ticket_ids', 'changes_done', 'files_modified', 'modules_affected',
            'testing_done', 'deployment_notes', 'migration_details',
            'current_version', 'new_version', 'deployment_date',
        ]);

        $developerIds = $validated['assigned_developer_ids'] ?? [];
        unset($validated['assigned_developer_ids']);

        $ticket->update($validated);
        $ticket->developers()->sync($developerIds);

        if ($request->filled('remove_attachments')) {
            $toRemove = $ticket->attachments()->whereIn('id', $request->remove_attachments)->get();
            foreach ($toRemove as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }
        }

        // if ($request->hasFile('attachments')) {
        //     foreach ($request->file('attachments') as $i => $file) {
        //         if (!$file) continue;
        //         $path = $file->store('deployment_attachments', 'public');
        //         $ticket->attachments()->create([
        //             'type' => $request->attachment_types[$i] ?? 'Other',
        //             'file_path' => $path,
        //             'original_name' => $file->getClientOriginalName(),
        //         ]);
        //     }
        // }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $i => $file) {
                if (!$file) {
                    continue;
                }

                $filename = time() . '_' . $file->getClientOriginalName();

                // Ensure directory exists
                if (!file_exists(public_path('storage/deployment_attachments'))) {
                    mkdir(public_path('storage/deployment_attachments'), 0755, true);
                }

                // Move file (same as store)
                $file->move(public_path('storage/deployment_attachments'), $filename);

                $ticket->attachments()->create([
                    'type' => $request->attachment_types[$i] ?? 'Other',
                    'file_path' => 'deployment_attachments/' . $filename,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return redirect()->route('deployment.tickets.show', $ticket)
            ->with('success', 'Deployment ticket updated.');
    }

    public function deleteAttachment(DeploymentAttachment $attachment)
    {
        // get related ticket id
        $ticketId = $attachment->deployment_ticket_id;

        // delete file from storage
        if ($attachment->file_path && Storage::exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        }

        // delete DB record
        $attachment->delete();

        // redirect back to edit page
        return redirect()
            ->route('deployment.tickets.edit', $ticketId)
            ->with('success', 'Attachment deleted successfully.');
    }
}
