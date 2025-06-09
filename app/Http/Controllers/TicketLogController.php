<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TicketLogController extends Controller
{
    public function index()
{

    return view('ticket-logs.index');
}

}
