<?php
 
namespace App\Http\Controllers;

use App\Models\AssignedDevices;
use App\Models\Holidays;
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


        $today = Carbon::now();
        $endDate = Carbon::today()->addDays(7);
        $upcomingHoliday = Holidays::whereBetween('from', [$today, $endDate])
        ->orderBy('from')->first();
        // user count For dashboard
        $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
        
        if (auth()->user()->role->name == 'Super Admin')
		{
            // $userCount = Users::where('users.role_id','=',env('SUPER_ADMIN'))->orderBy('id','desc')-	
            // $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
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
            // $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
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
            // $userCount=Managers::where('parent_user_id',auth()->user()->id)->get()->count();
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

        $assignedDevices = AssignedDevices::with('user','device')->where('user_id', '=',  auth()->user()->id)->where('status',1)->orderBy('id','desc')->get();
        
        // Get Leaves Count For Dashbaord Total leaves And Availed Leaves
        $currentYear = Carbon::now()->year;
        $availableLeaves = Users::join('company_leaves', 'users.id', '=', 'company_leaves.user_id')
        ->select('users.first_name', 'users.last_name', 'users.id', 'company_leaves.leaves_count')
        ->whereYear('company_leaves.created_at', $currentYear)->where('users.id', auth()->user()->id)
        ->get();

        $availableLeave = 0;
        foreach ($availableLeaves as $avLeave) {
            $availableLeave += $avLeave->leaves_count;
        }

        $approvedLeaves = UserLeaves::where('leave_status', 'approved')
                                    ->join('users', 'users.id', '=', 'user_leaves.user_id')
                                    ->select('user_leaves.*', 'users.first_name' , 'users.id' , 'users.status')->where('users.id', auth()->user()->id)
                                    ->get();
        $approvedLeave = 0;
        foreach ($approvedLeaves as $apLeave) {
            $approvedLeave += $apLeave->leave_day_count;
        }
        // $availedLeaves =  $availableLeave - $approvedLeave;
        $totalLeaves = $availableLeave ;
        //end Count for total leave and approved leaves

        // Fetch the next four holidays where "from" date is greater than today
        $upcomingFourHolidays = Holidays::where('from', '>', $today)
                                ->orderBy('from', 'asc')
                                ->limit(4)
                                ->get();

        return view('dashboard.index',compact('userCount','users','userAttendanceData','userBirthdate','currentDate','userLeaves','showLeaves', 'dayMonth','leaveStatus','upcomingHoliday','assignedDevices','approvedLeave','totalLeaves','upcomingFourHolidays'));
    }
}