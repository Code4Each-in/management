<?php

namespace App\Http\Controllers;
use App\Models\Projects;
use App\Models\Holidays;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Users;
use App\Models\Quote;
use App\Models\Tickets;
use App\Models\TodoList;
use App\Models\Sprint;
use App\Models\Notification;
use Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;


class SprintController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
{
    $projectFilter = request()->input('project_filter');
    $clients = Client::orderBy('name', 'asc')->get();
    $user = Auth::user();
    $clientId = $user->client_id;
    if (!is_null($clientId)) {
        $projects = Projects::where('client_id', $clientId)->get();
    } else {
        $projects = Projects::all();
    }
    
    $sprints = Sprint::with('projectDetails')
    ->withCount([
        'tickets',
        'tickets as completed_tickets_count' => function ($query) {
            $query->where('status', 'complete');
        },
        'tickets as todo_tickets_count' => function ($query) {
            $query->where('status', 'to_do');
        },
        'tickets as in_progress_tickets_count' => function ($query) {
            $query->where('status', 'in_progress');
        },
        'tickets as deployed_tickets_count' => function ($query) {
            $query->where('status', 'deployed');
        },
        'tickets as ready_tickets_count' => function ($query) {
            $query->where('status', 'ready');
        },
    ])
    ->where('sprints.status', 1)
    ->when(!is_null($clientId), function ($query) use ($clientId) {
        $query->where('sprints.client', $clientId);
    })
    ->when($projectFilter, function ($query) use ($projectFilter) {
        $query->where('project', $projectFilter);
    })
    ->havingRaw('tickets_count != completed_tickets_count OR tickets_count = 0 OR completed_tickets_count = 0')
    ->get();


    $inactivesprints = Sprint::with('projectDetails')
    ->withCount([
        'tickets',
        'tickets as completed_tickets_count' => function ($query) {
            $query->where('status', 'complete');
        },
        'tickets as todo_tickets_count' => function ($query) {
            $query->where('status', 'to_do');
        },
        'tickets as in_progress_tickets_count' => function ($query) {
            $query->where('status', 'in_progress');
        },
        'tickets as deployed_tickets_count' => function ($query) {
            $query->where('status', 'deployed');
        },
        'tickets as ready_tickets_count' => function ($query) {
            $query->where('status', 'ready');
        },
    ])
    ->where('sprints.status', 0)
    ->when(!is_null($clientId), function ($query) use ($clientId) {
        $query->where('sprints.client', $clientId);
    })
    ->when($projectFilter, function ($query) use ($projectFilter) {
        $query->where('project', $projectFilter);
    })
    ->get();

    $completedsprints = Sprint::with('projectDetails')
    ->withCount([
        'tickets',
        'tickets as completed_tickets_count' => function ($query) {
            $query->where('status', 'complete');
        },
        'tickets as todo_tickets_count' => function ($query) {
            $query->where('status', 'to_do');
        },
        'tickets as in_progress_tickets_count' => function ($query) {
            $query->where('status', 'in_progress');
        },
        'tickets as deployed_tickets_count' => function ($query) {
            $query->where('status', 'deployed');
        },
        'tickets as ready_tickets_count' => function ($query) {
            $query->where('status', 'ready');
        },
    ])
    ->when(!is_null($clientId), function ($query) use ($clientId) {
        $query->where('sprints.client', $clientId); 
    })
    ->when($projectFilter, function ($query) use ($projectFilter) {
        $query->where('project', $projectFilter);
    })
    ->having('tickets_count', '>', 0)
    ->havingRaw('tickets_count = completed_tickets_count')
    ->get();

    $totalSprintCount = $sprints->count();
    $totalinSprintCount = $inactivesprints->count();
    $role_id = auth()->user()->role_id;
    return view('sprintdash.index', compact(
        'projects', 
        'clients', 
        'sprints', 
        'inactivesprints', 
        'totalSprintCount', 
        'totalinSprintCount', 
        'completedsprints',
        'role_id',
        'user'
    ));
}

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'eta' => 'required|date',
        'client' => 'required|string|max:255',
        'project' => 'required|string|max:255',
        'start_date' => 'required|date',
        'status' => 'required',
        'description' => 'required',
        'add_document' => 'nullable|array', 
        'add_document.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ]);
    }

    $validated = $validator->validated();

    $documents = [];
    if ($request->hasFile('add_document')) {
        foreach ($request->file('add_document') as $file) {
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $dateString = date('YmdHis');
            $name = $dateString . '_' . $fileName . '.' . $file->extension();
            $file->move(public_path('assets/img/sprintAssets'), $name);
            $path = 'sprintAssets/' . $name;
            $documents[] = $path;
        }
    }
    $sprint = Sprint::create([
        'name' => $validated['name'],
        'eta' => date("Y-m-d H:i:s", strtotime($validated['eta'])),
        'start_date' => date("Y-m-d H:i:s", strtotime($validated['start_date'])),
        'client' => $validated['client'],
        'project' => $validated['project'],
        'description' => $validated['description'],
        'status' => $validated['status'],
        'document' => implode(',', $documents), 
    ]);

    $request->session()->flash('message', 'Sprint added successfully.');

    return response()->json([
        'status' => 'success',
        'message' => 'Sprint added successfully.',
    ]);
}
    
    public function destroy(Request $request)
     {
         $sprints = Sprint::where('id',$request->id)->delete(); 
         $request->session()->flash('message','Sprint deleted successfully.');
         return Response()->json($sprints);
     }
     public function editSprint($sprintId)
    {
        $sprint = Sprint::findOrFail($sprintId);
        $projects = Projects::all();
        $clients = Client::orderBy('name', 'asc')  
        ->get();
        $allDocs = explode(',', $sprint->document);
$existingDocs = array_filter($allDocs, function ($file) {
    return file_exists(public_path('assets/img/' . trim($file)));
});

$ProjectDocuments = collect($existingDocs)->map(function ($filename, $index) {
    return (object)[
        'id' => $index, // You need some unique key for DOM identification
        'document' => trim($filename),
    ];
});
        return view('sprintdash.edit', compact('sprint','clients','projects','ProjectDocuments'));

    }
    public function viewSprint($sprintId)
    {
        $sprint = Sprint::findOrFail($sprintId);
        $tickets = Tickets::where('sprint_id', $sprintId)->get();
        $doneTicketsCount = Tickets::where('sprint_id', $sprintId)
        ->whereRaw('LOWER(status) = ?', ['complete'])
        ->count();
        $clients = Sprint::select('sprints.*', 'projects.project_name as project_name', 'clients.name as client_name')
        ->join('projects', 'sprints.project', '=', 'projects.id')
        ->join('clients', 'sprints.client', '=', 'clients.id')
        ->where('sprints.id', $sprintId) 
        ->first();

        $sprints = Sprint::select('sprints.*', 'projects.project_name as project_name')
        ->join('projects', 'sprints.project', '=', 'projects.id')
        ->where('sprints.id', $sprintId) 
        ->first();

        $progressTicketsCount = Tickets::where('sprint_id', $sprintId)
        ->whereRaw('LOWER(status) IN (?, ?)', ['in_progress', 'to_do'])
        ->count();
        $progress = Tickets::where('sprint_id', $sprintId)
        ->whereRaw('LOWER(status) = ?', ['in_progress'])
        ->count();

        $todo = Tickets::where('sprint_id', $sprintId)
        ->whereRaw('LOWER(status) = ?', ['to_do'])
        ->count();
        $complete = Tickets::where('sprint_id', $sprintId)
        ->whereRaw('LOWER(status) = ?', ['complete'])
        ->count();
        $ready = Tickets::where('sprint_id', $sprintId)
        ->whereRaw('LOWER(status) = ?', ['ready'])
        ->count();
        $deployed = Tickets::where('sprint_id', $sprintId)
        ->whereRaw('LOWER(status) = ?', ['deployed'])
        ->count();
        $totalTicketsCount = Tickets::where('sprint_id', $sprintId)->count();

        $ticketFilterQuery = Tickets::with('ticketRelatedTo','ticketAssigns')->orderBy('id','desc');
        $assignedUsers = Tickets::join('ticket_assigns', 'tickets.id', '=', 'ticket_assigns.ticket_id')
        ->join('users', 'ticket_assigns.user_id', '=', 'users.id')
        ->where('users.status', 1)
        ->where('tickets.ticket_priority', 1)
        ->select(
            'tickets.id as ticket_id',
            'users.first_name as assigned_user_name',
            'users.designation'
        )
        ->get();
        $allDocs = explode(',', $sprint->document);
        $existingDocs = array_filter($allDocs, function ($file) {
            return file_exists(public_path('assets/img/' . trim($file)));
        });
        
        $ProjectDocuments = collect($existingDocs)->map(function ($filename, $index) {
            return (object)[
                'id' => $index, 
                'document' => trim($filename),
            ];
        });
        $role_id = auth()->user()->role_id;
        return view('sprintdash.view', compact('sprint','tickets', 'assignedUsers', 'doneTicketsCount', 'progressTicketsCount', 'totalTicketsCount', 'clients', 'sprints', 'progress', 'complete', 'todo', 'ready', 'role_id', 'deployed','ProjectDocuments'));

    }
    
            public function updateSprint(Request $request, $sprintId)
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'eta' => 'required|date',
                'start_date' => 'required|date',
                'client' => 'required|int|max:255',
                'project' => 'required|int|max:255',
                'description' => 'required',
                'status' => 'required',
                'edit_document' => 'nullable|array',
                'edit_document.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ]);
            }

            $validated = $validator->validated();

            $sprint = Sprint::findOrFail($sprintId);
            $sprint->name = $validated['name'];
            $sprint->eta = $validated['eta'];
            $sprint->start_date = $validated['start_date'];
            $sprint->client = $validated['client'];
            $sprint->project = $validated['project'];
            $sprint->status = $validated['status'];
            $sprint->description = $validated['description'];

            $documentPaths = [];
            if ($request->hasFile('edit_document')) {
                foreach ($request->file('edit_document') as $file) {

                    $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $dateString = date('YmdHis');
                    $name = $dateString . '_' . $fileName . '.' . $file->extension();
                    $file->move(public_path('assets/img/sprintAssets'), $name);
                    $documentPaths[] = 'sprintAssets/' . $name;
                }

                if (count($documentPaths) > 0) {
                    $existingDocuments = explode(',', $sprint->document); 
                    $updatedDocuments = array_merge($existingDocuments, $documentPaths); 
                    $sprint->document = implode(',', $updatedDocuments);
                }
            }

            $sprint->save();

            $request->session()->flash('message', 'Sprint updated successfully.');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sprint updated successfully.'
            ]);
        }

    
            public function getSprints($project_id)
        {
            $sprints = Sprint::where('project', $project_id)
                 ->where('status', 1)
                 ->get(['id', 'name']);
            return response()->json($sprints);
        }

            public function deleteSprintFile(Request $request)
        {
            $sprint = Sprint::findOrFail($request->sprintId);
            $documents = explode(',', $sprint->document);
            
            if (!isset($documents[$request->id])) {
                return response()->json(['status' => 'error', 'message' => 'Invalid file index'], 404);
            }

            $fileToDelete = trim($documents[$request->id]);
            $filePath = public_path('assets/img/' . $fileToDelete);

            if (file_exists($filePath)) {
                unlink($filePath);
            }
            unset($documents[$request->id]);
            $sprint->document = implode(',', array_values($documents)); 
            $sprint->save();

            $request->session()->flash('message', 'Sprint file deleted successfully.');
            return response()->json(['status' => 200]);
        }

        public function allNotifications()
        {
            $user = auth()->user();

            if ($user->role_id == 6) {
                $clientId = $user->client_id;
                $projectIds = Projects::where('client_id', $clientId)->pluck('id');
                $ticketIds = Tickets::whereIn('project_id', $projectIds)->pluck('id');

                $notifications = Notification::whereIn('ticket_id', $ticketIds)
                ->where('message', 'not like', '%assigned%') // partial match
                ->get()
                ->unique('created_at') 
                ->sortByDesc('created_at')
                ->values();            
          
            } else {
                $notifications = Notification::orderBy('created_at', 'desc')->get();
            }

            return view('developer.notification', compact('notifications'));
        }

    
    }
