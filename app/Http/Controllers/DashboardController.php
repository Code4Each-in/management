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
use App\Models\Sprint;
use App\Models\Winners;
use App\Models\Notification;
use App\Models\Projects;
use App\Models\Tickets;
use App\Models\TodoList;
use App\Models\UserAttendancesTemporary;
use Illuminate\Support\Facades\DB;
use Auth;
use App\Models\Reminder;
use App\Models\TicketComments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Relationship;

class DashboardController extends Controller
{
    /**
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $tasks = TodoList::where('user_id', Auth::id())
        ->whereRaw("LOWER(status) != 'completed'")
        ->orderBy('created_at', 'desc')
        ->get();
       $notifications = collect(); 
        $projectMap = '';

        if ($user->role_id == 6) {
            $clientId = $user->client_id;

            $projectMap = Projects::where('client_id', $clientId)
                ->pluck('project_name', 'id');

            $projectIds = $projectMap->keys();

            $ticketIds = Tickets::whereIn('project_id', $projectIds)->pluck('id');

            $notifications = TicketComments::whereIn('ticket_id', $ticketIds)
                ->where('comments', '!=', '')
                ->where('comment_by', '!=', auth()->id())
                ->with(['user', 'ticket.project'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        elseif (in_array($user->role_id, [2, 3])) {
            $assignedTicketIds = DB::table('ticket_assigns')
                ->where('user_id', $user->id)
                ->pluck('ticket_id')
                ->toArray();

            $createdTicketIds = Tickets::where('created_by', $user->id)
                ->pluck('id')
                ->toArray();

            $ticketIds = array_unique(array_merge($assignedTicketIds, $createdTicketIds));

            $notifications = TicketComments::whereIn('ticket_id', $ticketIds)
                ->where('comments', '!=', '')
                ->where('comment_by', '!=', auth()->id())
                ->whereYear('created_at', 2025)
                ->whereHas('ticket.project', function ($query) {
                    $query->where('client_id', '!=', 10); // added condition
                })
                ->with(['user', 'ticket.project'])
                ->orderBy('created_at', 'desc')
                ->get();
                  
                $projectIds = Tickets::whereIn('id', $ticketIds)
                ->whereHas('project', function ($query) {
                    $query->where('client_id', '!=', 10); // also apply same condition here
                })
                ->pluck('project_id')
                ->unique();

                $projectMap = Projects::whereIn('id', $projectIds)->pluck('project_name', 'id');
        }
        else {
            $projectMap = Projects::pluck('project_name', 'id');

            $notifications = TicketComments::where('comments', '!=', '')
                ->where('comment_by', '!=', auth()->id())
                ->whereYear('created_at', 2025)
                ->whereHas('ticket.project', function ($query) {
                    $query->where('client_id', '!=', 10);
                })
                ->with(['user', 'ticket.project'])
                ->orderBy('created_at', 'desc')
                ->get();
            }

        $groupedNotifications = $notifications
            ->groupBy(function ($comment) {
                return optional(optional($comment->ticket)->project)->id ?? 'unknown';
            })
            ->map(function ($group) {
                return $group->take(10); 
            });

        if (in_array($user->role_id, [1])) {
            foreach ($projectMap as $projectId => $projectName) {
                if (!$groupedNotifications->has($projectId)) {
                    $groupedNotifications->put($projectId, collect());
                }
            }

            $groupedNotifications = $groupedNotifications->sortKeys();
        }
     
        $joiningDate = $user->joining_date;
        $userId = $user->id;
        $userAttendances  = $this->getMissingAttendance();
        $probationEndDate = Carbon::parse($user->joining_date)->addMonths(3);

        $today = Carbon::now();
        $endDate = Carbon::today()->addDays(7);
        $upcomingHoliday = Holidays::whereBetween('from', [$today, $endDate])
            ->orderBy('from')->first();
        // user count For dashboard
        $userCount = Users::where('status', 1)
            ->where('role_id', '!=', 6)
            ->orderBy('id', 'desc')
            ->count();

        $dayMonth = date('m-d');
        $userBirthdate = Users::where(function ($query) use ($dayMonth) {
            $query->whereRaw("DATE_FORMAT(joining_date, '%m-%d') = ?", [$dayMonth])
                  ->orWhereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$dayMonth]);
        })
        ->where('status', 1)
        ->where('role_id', '!=', 6)
        ->get();


        $dayMonthEvent = date('m');
        $userBirthdateEvent = Users::where(function ($query) use ($dayMonthEvent) {
            $query->whereRaw("DATE_FORMAT(joining_date, '%m') = ?", [$dayMonthEvent])
                  ->orWhereRaw("DATE_FORMAT(birth_date, '%m') = ?", [$dayMonthEvent]);
        })
        ->where('status', '=', 1)
        ->where('role_id', '!=', 6)
        ->get();


            $clientId = $user->client_id;
            $countsprints = 0;
            $projects = 0;
            if ($clientId !== null) {

                $projects = Projects::where('client_id', $clientId)->get();
                $sprints = Sprint::whereIn('project', $projects->pluck('id'))
                                ->where('status', 1)
                                ->get();

            }
        $clientId = $user->client_id;
        $countsprints = 0;
        if ($clientId !== null) {

                $countsprints = Sprint::where('client', $clientId)
                    ->where('status', 1)
                    ->count();
            }


        $thresholdTime = Carbon::now('Asia/Kolkata')->subMinutes(2);

        // Online Employees
        $onlineUsers = Users::where('status', 1)
            ->whereNotIn('role_id', [2, 6])
            ->where('last_seen_at', '>=', $thresholdTime)
            ->get()
            ->each(function ($user) {
                $user->status_rank = 1; // Online
                $user->sort_time = now()->timestamp;
            });

        // Offline Employees
        $offlineUsers = Users::where('status', 1)
            ->whereNotIn('role_id', [2, 6])
            ->where(function ($query) use ($thresholdTime) {
                $query->where('last_seen_at', '<', $thresholdTime)
                    ->orWhereNull('last_seen_at');
            })
            ->get()
            ->each(function ($user) {
                if ($user->last_seen_at) {
                    $user->status_rank = 2; // Away
                    $user->sort_time = Carbon::parse($user->last_seen_at)->timestamp;
                } else {
                    $user->status_rank = 3; // Offline
                    $user->sort_time = 0;
                }
            });

        // Online Clients
        $onlineClients = Users::where('status', 1)
            ->where('role_id', 6)
            ->where('last_seen_at', '>=', $thresholdTime)
            ->get()
            ->each(function ($user) {
                $user->status_rank = 1; // Online
                $user->sort_time = now()->timestamp;
            });

        // Offline Clients
        $offlineClients = Users::where('status', 1)
            ->where('role_id', 6)
            ->where(function ($query) use ($thresholdTime) {
                $query->where('last_seen_at', '<', $thresholdTime)
                    ->orWhereNull('last_seen_at');
            })
            ->get()
            ->each(function ($user) {
                if ($user->last_seen_at) {
                    $user->status_rank = 2; // Away
                    $user->sort_time = Carbon::parse($user->last_seen_at)->timestamp;
                } else {
                    $user->status_rank = 3; // Offline
                    $user->sort_time = 0;
                }
            });

        // Merge and sort
        $allEmployees = $onlineUsers->merge($offlineUsers)
            ->sortBy([
                ['status_rank', 'asc'],
                ['sort_time', 'desc'], // More recent last_seen_at first
            ])
            ->values();

        $allClients = $onlineClients->merge($offlineClients)
            ->sortBy([
                ['status_rank', 'asc'],
                ['sort_time', 'desc'],
            ])
            ->values();


        if (auth()->user()->role->name == 'Super Admin') {
            // $userCount = Users::where('users.role_id','=',env('SUPER_ADMIN'))->orderBy('id','desc')-
            // $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
            $userLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_leaves.*', 'users.first_name', 'users.status']);
            $currentDate = date('Y-m-d'); //current date
            $usrleaves = UserLeaves::whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();
            $users = $this->getLeavesCount($usrleaves, $currentDate);

            $showLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();

            $validLeaves = $this->getValidLeaves($showLeaves, $currentDate);

            //count of userleaves acc to current date
            $userAttendancesData = UserAttendances::join('users', 'user_attendances.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_attendances.*', 'users.first_name'])->count();
        } elseif (auth()->user()->role->name == 'HR Manager') {
            // $userCount = Users::orderBy('id','desc')->where('status',1)->get()->count();
            $userLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_leaves.*', 'users.first_name']);
            $currentDate = date('Y-m-d'); //current date
            $usrleaves = UserLeaves::whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();

            $users = $this->getLeavesCount($usrleaves, $currentDate);
            $showLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();

            $validLeaves = $this->getValidLeaves($showLeaves, $currentDate);

            //count of userleaves acc to current date
            $userAttendancesData = UserAttendances::join('users', 'user_attendances.user_id', '=', 'users.id')->orderBy('id', 'desc')->get(['user_attendances.*', 'users.first_name'])->count();
        } else {
            // $userCount=Managers::where('parent_user_id',auth()->user()->id)->get()->count();
            $userLeaves = UserLeaves::join('managers', 'user_leaves.user_id', '=', 'managers.user_id')->join('users', 'user_leaves.user_id', '=', 'users.id')->where('managers.parent_user_id', auth()->user()->id)->get(['user_leaves.*', 'managers.user_id', 'users.first_name']);

            $currentDate = date('Y-m-d'); //current date
            $usrleaves = UserLeaves::whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();

            $users = $this->getLeavesCount($usrleaves, $currentDate);
            $userAttendancesData = UserAttendances::join('managers', 'user_attendances.user_id', '=', 'managers.user_id')->where('managers.parent_user_id', auth()->user()->id)->whereDate('user_attendances.created_at', '=', $currentDate)->get()->count(); //count of userAttendance acc to current date
            $showLeaves = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->whereDate('from', '<=', $currentDate)->whereDate('to', '>=', $currentDate)->where('leave_status', '=', 'approved')->get();
            $validLeaves = $this->getValidLeaves($showLeaves, $currentDate);
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
            ->where('month', date('m'))
            ->where('year', date('Y'))
            ->exists();
        if ($hasVoted) {
            $uservote = collect();
        } else {
            $uservote = Users::where('status', 1)
                ->whereNotIn('role_id', [1, 2, 5, 6])
                ->get();
        }

        // $winners = winners::latest()->take(2)->get(); // where condition for previous month

        $currentMonth = date('n');
        $currentYear = date('Y');
        if ($currentMonth == 1) {
            $previousMonth = 12; // December of the previous year
            $previousYear = $currentYear - 1;
        } else {
            $previousMonth = $currentMonth - 1;
            $previousYear = $currentYear;
        }
        // Fetch winners
        // $winners = Winners::all();
        // Loop through winners to fetch associated user and votes
        // $winners = Winners::where('month', $previousMonth)
        //     ->where('year', $previousYear)
        //     ->get();
        // foreach ($winners as $winner) {
        //     $user = Users::find($winner->user_id);
        //     $uservotes = Votes::where('to', $user->id)->get();
        //     $winner->user = $user;
        //     $winner->uservotes = $uservotes;
        // }


        // $userIds = $winners->pluck('user_id');



        //     $winners = Winners::where('month', $previousMonth)
        //     ->where('year', $previousYear)
        //     ->get();

        // // Attach user details and votes to winners
        // foreach ($winners as $winner) {
        //     $user = Users::find($winner->user_id);
        //     $uservotes = Votes::where('to', $user->id)
        //         ->join('users', 'votes.to', '=', 'users.id') // Join users table to get voter details
        //         ->select('votes.*', 'users.first_name', 'users.last_name', 'users.profile_picture')
        //         ->get();

        //     $winner->user = $user;
        //     $winner->uservotes = $uservotes; // Now includes user details
        // }

        // $userIds = $winners->pluck('user_id');

        // Fetch winners from the previous month
        $latestVote = Votes::latest('created_at')->first();
        $latestMonth = Carbon::parse($latestVote->created_at)->month;
        $latestYear = Carbon::parse($latestVote->created_at)->year;
        // Get winners of the latest month
        $winners = Winners::where('month', $latestMonth)
            ->where('year', $latestYear)
            ->get();

        // Collect winner user IDs
        $userIds = $winners->pluck('user_id');

        // Fetch **all votes of winners**
        foreach ($winners as $winner) {
            $user = Users::find($winner->user_id);

            // Get **all votes of the winner in the latest month**
            $winnerVotes = Votes::where('to', $user->id)
                ->join('users', 'votes.to', '=', 'users.id') // Get voter details
                ->select('votes.*', 'users.first_name', 'users.last_name', 'users.profile_picture')
                ->whereMonth('votes.created_at', $latestMonth)
                ->whereYear('votes.created_at', $latestYear)
                ->orderBy('votes.created_at', 'desc')
                ->get(); // **All votes of the winner**

            $winner->user = $user;
            $winner->uservotes = $winnerVotes;
        }



        // $currentMonth = date('n');
        // $currentYear = date('Y');
        // $previousMonth = $currentMonth - 1;
        // $previousYear = $currentYear;
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
        // $allVotes = Votes::where('month', $previousMonth)
        //     ->where('year', $previousYear)
        //     ->whereNotIn('to', $userIds)
        //     ->orderBy('to')
        //     ->join('users', 'votes.to', '=', 'users.id')
        //     ->select('votes.*', 'users.*')
        //     ->get();


        $allVotes = Votes::where('month', $previousMonth)
            ->where('year', $previousYear)
            ->whereNotIn('to', $userIds)
            ->orderBy('to')
            ->join('users', 'votes.to', '=', 'users.id') // Ensure user details are included
            ->select('votes.*', 'users.first_name', 'users.last_name', 'users.profile_picture')
            ->get();


        $todolist = TodoList::where('user_id', $userId)
            ->select(['title', 'completed_at', 'user_id', 'status', 'created_at'])
            ->get();

            $currentDateTime = Carbon::now();
            $activeReminders = Reminder::whereDate('reminder_date', $currentDateTime->toDateString())
                ->whereNull('clicked_at')
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
            'validLeaves',
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
            'allVotes',
            'todolist',
            'tasks',
            'countsprints',
            'activeReminders',
            'projects',
            'notifications',
            'projectMap',
            'allEmployees',
            'allClients',
            'groupedNotifications'
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
            $joining_date = $user->joining_date;
            $missingDates = [];

            foreach ($dateSeries as $date) {
                $leave = !UserLeaves::where('user_id', $userId)->whereDate('from', '<=', $date)->whereDate('to', '>=', $date)->exists();
                $attendance = UserAttendances::where('user_id', $userId)->whereDate('created_at', $date)->doesntExist();
                if ($leave && $attendance) {
                    if ($date->toDateString() >= $joining_date) {
                        $missingDates[] = $date->toDateString();
                    }
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

    public function getLeavesCount($usrleaves, $currentDate)
    {
        $users = 0;
        foreach ($usrleaves as $usr) {
            if ($usr->half_day == NULL) {
                $users++;
            } else {
                if ($usr->half_day === 'Second Half') {
                    $userTable = UserAttendances::class;
                } else {
                    $userTable = UserAttendancesTemporary::class;
                }
                $usrAttendances = $userTable::where('user_id', $usr->user_id)
                    ->whereDate('date', '=', $currentDate)
                    ->get();

                if ($usr->half_day == 'First Half') {
                    if ($usrAttendances->isEmpty() || (optional($usrAttendances->first())->in_time < '14:00:00')) {
                        $users++;
                    }
                } else if ($usr->half_day == 'Second Half') {
                    if (!$usrAttendances->isEmpty() && !is_null($usrAttendances->first()->out_time) && $usrAttendances->first()->out_time > '14:00:00') {
                        $users++;
                    }
                }else if ($usr->half_day == 'Short Leave') {
                    $from = $usr->from_time;
                    $to = $usr->to_time;
                    $currentTime = now()->format('H:i:s');

                    if(!$usrAttendances->isEmpty() && ($from <= $currentTime && $to >= $currentTime)){
                        $users++;
                    }
                }
            }
        }
        return $users;
    }

    public function getValidLeaves($showLeaves, $currentDate)
    {
        $validLeaves = [];
        foreach ($showLeaves as $value) {
            if ($value->half_day === NULL) {
                // If half_day is NULL, include this leave in the validLeaves array`
                $validLeaves[] = $value;
            } else {
                if ($value->half_day === 'Second Half') {
                    $userTable = UserAttendances::class;
                } else {
                    $userTable = UserAttendancesTemporary::class;
                }
                $usrAttendances = $userTable::where('user_id', $value->user_id)
                    ->whereDate('date', '=', $currentDate)
                    ->get();

                if ($value->half_day === 'First Half') {
                    if ($usrAttendances->isEmpty() || (optional($usrAttendances->first())->in_time < '14:00:00')) {
                        $validLeaves[] = $value;
                    }
                } elseif ($value->half_day === 'Second Half') {
                    if (!$usrAttendances->isEmpty() && !is_null($usrAttendances->first()->out_time) && $usrAttendances->first()->out_time > '14:00:00') {
                        // If attendance exists and 'out_time' is not NULL, the leave is considered valid
                        $validLeaves[] = $value;
                    }
                } elseif ($value->half_day === 'Short Leave') {
                    $from = $value->from_time;
                    $to = $value->to_time;
                    $currentTime = now()->format('H:i:s');

                    if(!$usrAttendances->isEmpty() && ($from <= $currentTime && $to >= $currentTime)){
                        $validLeaves[] = $value;
                    }
                }
            }
        }
        return $validLeaves;
    }
}
