<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Managers;
use App\Models\UserLeaves;

use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $userCount=Managers::where('parent_user_id',auth()->user()->id)->get()->count();
        // $userLeaves=UserLeaves::where('user_id')->get();
        // dd("$userLeaves");
        // $From = Carbon::createFromFormat('Y-m-d', '2022-06-01');
        // $to = Carbon::createFromFormat('Y-m-d', '2022-06-30');
        // $users = UserLeaves::whereDate('start_at', '>=', $From)
        //         ->whereDate('end_at', '<=', $to)
        //         ->get();
		//dd($userData);
        return view('dashboard.index',compact('userCount'));
    }
}