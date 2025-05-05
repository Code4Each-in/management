<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Users;
use App\Models\Message;

class MessageController extends Controller
{
    /**
     * Display login page.
     * 
     * @return Renderable
     */
    public function index()
    {
        return view('developer.chat');
    }
 
}