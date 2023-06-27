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
        if (auth()->user()->role_id==env('SUPER_ADMIN'))
		{
            // $userCount = Users::where('users.role_id','=',env('SUPER_ADMIN'))->orderBy('id','desc')-	
            $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
            $userLeaves= UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->orderBy('id','desc')->get(['user_leaves.*','users.first_name']);
            $currentDate = date('Y-m-d'); //current date
            $users = UserLeaves::whereDate('from', '<=',$currentDate)->whereDate('to', '>=',$currentDate)->where('leave_status','=','approved')->get()->count();
            $showLeaves= UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=',$currentDate)->whereDate('to', '>=',$currentDate)->where('leave_status','=','approved')->get();   
         
            //count of userleaves acc to current date
             $userAttendanceData= UserAttendances::join('users', 'user_attendances.user_id', '=', 'users.id')->orderBy('id','desc')->get(['user_attendances.*','users.first_name'])->count();

            $dayMonth = date('m-d');

            $userBirthdate = Users::whereRaw("DATE_FORMAT(joining_date, '%m-%d') = ?", [$dayMonth])
                ->orWhereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$dayMonth])
                ->get();
        }
        elseif (auth()->user()->role->name == 'HR Manager') {
            $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
            $userLeaves= UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->orderBy('id','desc')->get(['user_leaves.*','users.first_name']);
            $currentDate = date('Y-m-d'); //current date
            $users = UserLeaves::whereDate('from', '<=',$currentDate)->whereDate('to', '>=',$currentDate)->where('leave_status','=','approved')->get()->count();
            $showLeaves= UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=',$currentDate)->whereDate('to', '>=',$currentDate)->where('leave_status','=','approved')->get();   
         
            //count of userleaves acc to current date
             $userAttendanceData= UserAttendances::join('users', 'user_attendances.user_id', '=', 'users.id')->orderBy('id','desc')->get(['user_attendances.*','users.first_name'])->count();

            $dayMonth = date('m-d');

            $userBirthdate = Users::whereRaw("DATE_FORMAT(joining_date, '%m-%d') = ?", [$dayMonth])
                ->orWhereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$dayMonth])
                ->get(); 
        }
        else
        {
            $userCount=Managers::where('parent_user_id',auth()->user()->id)->get()->count();
            $userLeaves = UserLeaves::join('managers', 'user_leaves.user_id', '=', 'managers.user_id')->join('users', 'user_leaves.user_id', '=', 'users.id')->where('managers.parent_user_id',auth()->user()->id)->get(['user_leaves.*', 'managers.user_id','users.first_name']);

            $currentDate = date('Y-m-d'); //current date
            $users = UserLeaves::whereDate('from', '<=',$currentDate)->whereDate('to', '>=',$currentDate)->where('leave_status','=','approved')->get()->count();
            $userAttendanceData = UserAttendances::join('managers', 'user_attendances.user_id', '=', 'managers.user_id')->where('managers.parent_user_id',auth()->user()->id)->whereDate('user_attendances.created_at', '=',$currentDate)->get()->count(); //count of userAttendance acc to current date
            $showLeaves= UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=',$currentDate)->whereDate('to', '>=',$currentDate)->where('leave_status','=','approved')->get();
            
            $dayMonth = date('m-d');
            $userBirthdate = Users::whereRaw("DATE_FORMAT(joining_date, '%m-%d') = ?", [$dayMonth])
                ->orWhereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$dayMonth])
                ->get();

        }
        if (!empty($showLeaves)){
            $leaveStatus = UserLeaves::join('users', 'user_leaves.status_change_by', '=', 'users.id')
        ->select('user_leaves.leave_status','user_leaves.id as leave_id','user_leaves.updated_at', 'users.first_name', 'users.last_name', )
        ->get();
         }
        return view('dashboard.index',compact('userCount','users','userAttendanceData','userBirthdate','currentDate','userLeaves','showLeaves', 'dayMonth','leaveStatus'));
    }
}