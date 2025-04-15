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
        $projects = Projects::all();
        $clients = Client::orderBy('name', 'asc')  
        ->get();
        $sprints = Sprint::with('projectDetails')
        ->withCount([
            'tickets', 
            'tickets as completed_tickets_count' => function ($query) {
                $query->where('status', 'complete');
            }
        ])
        ->where('sprints.status', 1)
        ->havingRaw('tickets_count != completed_tickets_count OR tickets_count = 0 OR completed_tickets_count = 0')
        ->get();
    


        $totalSprintCount = $sprints->count();
        $inactivesprints = Sprint::with('projectDetails')
        ->withCount([
            'tickets', 
            'tickets as completed_tickets_count' => function ($query) {
                $query->where('status', 'complete');
            }
        ])
        ->where('sprints.status', 0)
        ->get();
         

        $completedsprints = Sprint::with(['projectDetails'])
        ->withCount([
            'tickets',
            'tickets as completed_tickets_count' => function ($query) {
                $query->where('status', 'complete');
            }
        ])
        ->having('tickets_count', '>', 0)
        ->havingRaw('tickets_count = completed_tickets_count')
        ->get();
    
    
        $totalinSprintCount = $inactivesprints->count();
        return view('sprintdash.index', compact('projects','clients', 'sprints', 'inactivesprints', 'totalSprintCount', 'totalinSprintCount', 'completedsprints'));

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
        'description' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error', 
            'message' => $validator->errors()->first()
        ]);
    }

    $validated = $validator->validated();

    $sprint = Sprint::create([
        'name' => $validated['name'],
        'eta' => date("Y-m-d H:i:s", strtotime($validated['eta'])),
        'start_date' => date("Y-m-d H:i:s", strtotime($validated['start_date'])),
        'client' => $validated['client'],
        'project' => $validated['project'],
        'description' => $validated['description'],
        'status' => $validated['status']
    ]);
    $request->session()->flash('message', 'Sprint added successfully.');

    return response()->json([
        'status' => 'success', 
        'message' => 'Sprint added successfully.'
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
        return view('sprintdash.edit', compact('sprint','clients','projects'));

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

        return view('sprintdash.view', compact('sprint','tickets', 'assignedUsers', 'doneTicketsCount', 'progressTicketsCount', 'totalTicketsCount', 'clients', 'sprints', 'progress', 'complete', 'todo', 'ready'));

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
            'status' => 'required'
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
    

    
    }
