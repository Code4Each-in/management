<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Users;

class UsersController extends Controller
{
    /**
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
		
        return view('users.index');
    }
    
}