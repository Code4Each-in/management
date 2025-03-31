<?php

namespace App\Http\Controllers;

use App\Models\Holidays;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Users;
use App\Models\Tickets;
use App\Models\TodoList;
use Auth;



class ScrumdashController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $tasks = Tickets::join('ticket_assigns', 'tickets.id', '=', 'ticket_assigns.ticket_id')
    ->join('users', 'ticket_assigns.user_id', '=', 'users.id')
    ->whereRaw("LOWER(tickets.status) != ?", ['complete'])
    ->where('users.status', 1)
    ->where('tickets.ticket_priority', 1)
    ->groupBy('tickets.title', 'tickets.eta', 'tickets.status', 'tickets.created_at') 
    ->select(
        'tickets.title',
        'tickets.eta',
        'tickets.status',
        DB::raw('GROUP_CONCAT(users.first_name ORDER BY users.first_name ASC) as assigned_user_names')
    )
    ->orderBy('tickets.created_at', 'desc')
    ->get();


         
    $activetasks = Tickets::join('ticket_assigns', 'tickets.id', '=', 'ticket_assigns.ticket_id')
    ->join('users', 'ticket_assigns.user_id', '=', 'users.id')
    ->whereRaw("LOWER(tickets.status) = ?", ['in_progress'])  
    ->where('users.status', 1) 
    ->where('tickets.ticket_priority', 1)
    ->groupBy('tickets.title', 'tickets.eta', 'tickets.status', 'tickets.created_at')
    ->select(
        'tickets.title',
        'tickets.eta',
        'tickets.status',
        DB::raw('GROUP_CONCAT(users.first_name ORDER BY users.first_name ASC) as assigned_user_names')
    )
    ->orderBy('tickets.created_at', 'desc') 
    ->get();


        $notasks = Users::leftJoin('ticket_assigns', 'users.id', '=', 'ticket_assigns.user_id')
        ->leftJoin('tickets', 'ticket_assigns.ticket_id', '=', 'tickets.id')
        ->where('users.status', 1)  
        ->where('tickets.ticket_priority', 1)
        ->whereNull('ticket_assigns.ticket_id')  
        ->orderBy('users.first_name', 'asc')
        ->select('users.*', 'users.first_name as assigned_user_name')
        ->get();
    

        $taskss = Tickets::join('ticket_assigns', 'tickets.id', '=', 'ticket_assigns.ticket_id')
        ->join('users', 'ticket_assigns.user_id', '=', 'users.id')
        ->whereRaw("LOWER(tickets.status) != ?", ['complete']) 
        ->where('users.status', 1) 
        ->where('tickets.ticket_priority', 1)
        ->orderBy('tickets.created_at', 'desc')
        ->select('tickets.*', 'users.first_name as assigned_user_name')
        ->get();
         
    return view('scrumdash.index', compact('tasks',
    'activetasks',
    'notasks',
    'taskss'


));
    }

}
