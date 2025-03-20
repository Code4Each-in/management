<?php

namespace App\Http\Controllers;

use App\Models\Holidays;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        ->orderBy('tickets.created_at', 'desc')
        ->select('tickets.*', 'users.first_name as assigned_user_name')
        ->get();

        $activetasks = Tickets::join('ticket_assigns', 'tickets.id', '=', 'ticket_assigns.ticket_id')
        ->join('users', 'ticket_assigns.user_id', '=', 'users.id')
        ->whereRaw("LOWER(tickets.status) = ?", ['in_progress'])  
        ->where('users.status', 1) 
        ->orderBy('tickets.created_at', 'desc')
        ->select('tickets.*', 'users.first_name as assigned_user_name')
        ->get();

        $notasks = Tickets::leftJoin('ticket_assigns', 'tickets.id', '=', 'ticket_assigns.ticket_id')
        ->join('users', 'ticket_assigns.user_id', '=', 'users.id')
        ->where('users.status', 1)
        ->whereNull('ticket_assigns.ticket_id') 
        ->whereRaw("LOWER(tickets.status) != ?", ['complete']) 
        ->orderBy('tickets.created_at', 'desc')
        ->select('tickets.*', 'users.first_name as assigned_user_name')
        ->get();

    return view('scrumdash.index', compact('tasks',
    'activetasks',
    'notasks'

));
    }

}
