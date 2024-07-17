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
use App\Models\Votes;
use App\Models\Winners;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {

        // Get the authenticated user
        $user = auth()->user();
        $joiningDate = $user->joining_date;
        $userId = $user->id;
        $userAttendances  = $this->getMissingAttendance();
        // Convert the joining_date attribute to a Carbon date instance
        $probationEndDate = Carbon::parse($user->joining_date)->addMonths(3);

        $today = Carbon::now();
        $endDate = Carbon::today()->addDays(7);
        $upcomingHoliday = Holidays::whereBetween('from', [$today, $endDate])
            ->orderBy('from')->first();
        // user count For dashboard
        $userCount = Users::orderBy('id', 'desc')->where('status', 1)->get()->count();
        
        $dayMonth = date('m-d');
        $userBirthdate = Users::whereRaw("DATE_FORMAT(joining_date, '%m-%d') = ?", [$dayMonth])
            ->orWhereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$dayMonth])
            ->where('status', 1)->get();

        $dayMonthEvent = date('m');
        $userBirthdateEvent = Users::whereRaw("DATE_FORMAT(joining_date, '%m') = ?", [$dayMonthEvent])
            ->orWhereRaw("DATE_FORMAT(birth_date, '%m') = ?", [$dayMonthEvent])
            ->where('status', 1)->get();

        if (auth()->user()->role->name == 'Super Admin') {
            // $userCount = Users::where('users.role_id','=',env('SUPER_ADMIN'))->orderBy('id','desc')-
            // $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
            $userLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_leaves.*', 'users.first_name']);
            $currentDate = date('Y-m-d'); //current date
            $users = UserLeaves::whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get()->count();
            $showLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();

            //count of userleaves acc to current date
            $userAttendancesData = UserAttendances::join('users', 'user_attendances.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_attendances.*', 'users.first_name'])->count();
        } elseif (auth()->user()->role->name == 'HR Manager') {
            // $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
            $userLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_leaves.*', 'users.first_name']);
            $currentDate = date('Y-m-d'); //current date
            $users = UserLeaves::whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get()->count();
            $showLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();

            //count of userleaves acc to current date
            $userAttendancesData = UserAttendances::join('users', 'user_attendances.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_attendances.*', 'users.first_name'])->count();
        } else {
            // $userCount=Managers::where('parent_user_id',auth()->user()->id)->get()->count();
            $userLeaves = UserLeaves::join('managers', 'user_leaves.user_id', '=', 'managers.user_id')->join('users', 'user_leaves.user_id', '=', 'users.id')->where('managers.parent_user_id', auth()->user()->id)->get(['user_leaves.*', 'managers.user_id', 'users.first_name']);

            $currentDate = date('Y-m-d'); //current date
            $users = UserLeaves::whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get()->count();
            $userAttendancesData = UserAttendances::join('managers', 'user_attendances.user_id', '=', 'managers.user_id')->where('managers.parent_user_id', auth()->user()->id)->whereDate('user_attendances.created_at', '=', $currentDate)->get()->count(); //count of userAttendance acc to current date
            $showLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();
        }
        if (!empty($showLeaves)) {
            $leaveStatus = UserLeaves::join('users', 'user_leaves.status_change_by', '=', 'users.id')
                ->select('user_leaves.leave_status', 'user_leaves.id as leave_id', 'user_leaves.updated_at', 'users.first_name', 'users.last_name',)
                ->get();
        }

        $assignedDevices = AssignedDevices::with('user', 'device')->where('user_id', '=',  auth()->user()->id)->where('status', 1)->orderBy('id', 'desc')->get();

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
            ->whereYear('from', date('Y'))
            ->join('users', 'users.id', '=', 'user_leaves.user_id')
            ->select('user_leaves.*', 'users.first_name', 'users.id', 'users.status')
            ->where('users.id', auth()->user()->id)
            ->where(function ($query) use ($joiningDate, $probationEndDate) {
                $query->where('from', '<', $joiningDate)
                    ->orWhere('from', '>', $probationEndDate);
            })->get();

        $approvedLeave = 0;

        foreach ($approvedLeaves as $apLeave) {
            $approvedLeave += $apLeave->leave_day_count;
        }
        // $availedLeaves =  $availableLeave - $approvedLeave;
        $totalLeaves = $availableLeave;

        $upcomingFourHolidays = Holidays::where('from', '>', $today)
            ->orderBy('from', 'asc')
            ->limit(4)
            ->get();

        //Vote part work    
        $loggedInUserId = auth()->id();
        $hasVoted = votes::where('from', $loggedInUserId)
        ->where('month',date('m'))
        ->where('year',date('Y'))
            ->exists();
        if ($hasVoted) {
            $uservote = collect();
        } else {
            $uservote = Users::where('status', 1)
                ->whereNotIn('role_id', [1, 2, 5])
                ->get();
        }
        
        // $winners = winners::latest()->take(2)->get(); // where condition for previous month
    
        
        // Fetch winners
        $winners = Winners::all();
        // Loop through winners to fetch associated user and votes
        foreach ($winners as $winner) {
            $user = Users::find($winner->user_id);
            $uservotes = Votes::where('to', $user->id)->get();
            $winner->user = $user;
            $winner->uservotes = $uservotes;
        }
        
       
        $userIds = $winners->pluck('user_id');
      
        $currentMonth = date('n');
        $currentYear = date('Y');
        $previousMonth = $currentMonth - 1;
        $previousYear = $currentYear;
        //fetch recent winners
        // $allVotes = Votes::where('month', $previousMonth)
        // ->where('year', $previousYear)
        // ->whereNotIn('to', $userIds)
        // ->orderBy('to')    
        // ->get();
        // foreach ($allVotes as $allVote) {
        //     $UserId = Users::find($allVote->to);
        //     $User_vote = Votes::where('to', $UserId->id)->get();
        //     $allVote->UserId = $UserId;
        //     $allVote->User_vote = $User_vote;
        // }
        $allVotes = Votes::where('month', $previousMonth)
        ->where('year', $previousYear)
        ->whereNotIn('to', $userIds)
        ->orderBy('to')
        ->join('users', 'votes.to', '=', 'users.id')
        ->select('votes.*', 'users.*')
        ->get();

        // $uservote = Users::where('status',1)->where('role_id', '!=', 1)->get();
        return view('dashboard.index', compact(
            'userCount',
            'users',
            'userAttendancesData',
            'userBirthdate',
            'userBirthdateEvent',
            'currentDate',
            'userLeaves',
            'showLeaves',
            'dayMonth',
            'dayMonthEvent',
            'leaveStatus',
            'upcomingHoliday',
            'assignedDevices',
            'approvedLeave',
            'totalLeaves',
            'upcomingFourHolidays',
            'userAttendances',
            'uservote',
            'winners',
            'allVotes'
        ));
    }

    public function getMissingAttendance()
    {
        // get all the users who are active and have role id of employee
        $userAttendances = [];
        $activeUsers = Users::where('status', '1')
            ->where('role_id', '3')
            ->get();

        //to get the currrent date and the date of 10 days before
        $currentDate = Carbon::now();
        $currentDateFormatted = $currentDate->format('Y-m-d');
        $yesterday = $currentDate->subDays(1);
        $yesterdayFormatted = $yesterday->format('Y-m-d');
        $tenDaysBefore = $currentDate->subDays(10);
        $tenDaysBeforeFormatted = $tenDaysBefore->format('Y-m-d');



        // parse the dates
        $dateSeries = collect();
        //    $currentDate = Carbon::parse($tenDaysBeforeFormatted);
        //    $endDateObject = Carbon::parse($currentDateFormatted);

        $currentDate = Carbon::parse($tenDaysBeforeFormatted);
        $endDateObject = Carbon::parse($yesterdayFormatted);

        // creating a series of the dates
        while ($currentDate <= $endDateObject) {
            if (
                !Holidays::whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->exists() &&
                $currentDate->dayOfWeek !== 0 &&  // Exclude Sundays
                $currentDate->dayOfWeek !== 6
            ) {
                $dateSeries->push($currentDate->copy());  // Add the current date to the collection
            }
            $currentDate->addDay();   // Move to the next day
        }

        $count = 0;
        foreach ($activeUsers as $user) {
            $userId = $user->id;
            $missingDates = [];

            foreach ($dateSeries as $date) {
                $leave = !UserLeaves::where('user_id', $userId)->whereDate('from', '<=', $date)->whereDate('to', '>=', $date)->exists();
                $attendance = UserAttendances::where('user_id', $userId)->whereDate('created_at', $date)->doesntExist();
                if ($leave && $attendance) {
                    $missingDates[] = $date->toDateString();
                }
            }
            if (!empty($missingDates)) {
                $userAttendances[] = [
                    'id' => $user->id,
                    'name' => $user->first_name . " " . $user->last_name,
                    'dates' => $missingDates,
                ];
            }
        }

        return $userAttendances;
    }
}
