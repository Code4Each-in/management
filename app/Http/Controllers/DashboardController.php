<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class DashboardController extends Controller
{
    /**
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('dashboard.index');
    }
    
}