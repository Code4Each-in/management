<?php
 
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Managers;
use App\Models\UserLeaves;
use App\Models\UserAttendances;
use App\Models\Users;

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
		$userLeaves=UserLeaves::orderBy('id','desc')->get(); 

        $currentDate = date('Y-m-d');//current date
        $users = UserLeaves::whereDate('from', '<=',$currentDate)->whereDate('to', '>=',$currentDate)->where('leave_status','=','approved')->where('status_change_by',auth()->user()->id)->get()->count(); //count of userleaves acc to current date

        $userAttendanceData = UserAttendances::join('managers', 'user_attendances.user_id', '=', 'managers.user_id')->where('managers.parent_user_id',auth()->user()->id)->whereDate('user_attendances.created_at', '=',$currentDate)->get()->count(); //count of userAttendance acc to current date

        $userBirthdate = Users::whereDate('joining_date','=',$currentDate)->orwhereDate('birth_date','=',$currentDate)->get();
        return view('dashboard.index',compact('userCount','users','userAttendanceData','userBirthdate'));
    }
}