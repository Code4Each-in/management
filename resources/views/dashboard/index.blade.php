@extends('layout')
@section('title', 'Dashboard')
@section('subtitle', 'Dashboard')
@section('content')
<!-- @php
use App\Models\Users;
use App\Models\Votes;
@endphp -->
<style>
    .reminder-close-btn {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    color: #0c5460; /* Adjust based on alert color */
    cursor: pointer;
    float: right;
    padding: 0 0.5rem;
    transition: color 0.2s, transform 0.2s;
}

.reminder-close-btn:hover {
    color: #721c24; /* Slightly darker or alert-danger tone on hover */
    transform: scale(1.2);
}
.alert.alert-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.reminder-box {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 6px 9px rgba(0, 0, 0, 0.1);
      margin-bottom: 1.5rem;
    }

    .reminder-box h5 {
      background-color: #f1c40f;
      font-weight: bold;
      color: #000;
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.3rem;
      margin: 0;
      padding: 1rem 1.2rem;
    }

    .fa-bell {
      animation: swing 1.5s ease-in-out infinite;
      transform-origin: top center;
    }

    @keyframes swing {
      0%   { transform: rotate(0deg); }
      20%  { transform: rotate(15deg); }
      40%  { transform: rotate(-10deg); }
      60%  { transform: rotate(5deg); }
      80%  { transform: rotate(-5deg); }
      100% { transform: rotate(0deg); }
    }

    .main-reminder-desc {
      background-color: #fffbea;
      display: flex;
      align-items: flex-start;
      gap: 0.8rem;
      padding: 1rem 1.2rem;
      align-items: center;
    }

    .reminder-icon {
      font-size: 1.3rem;
      color: #5c3b00;
      margin-top: 2px;
    }



    .reminder-label {
      font-weight: 600;
      color: #5c3b00;
    }

    .reminder-text {
      color: #5c3b00;
      font-size: 1rem;
      line-height: 1.6;
    }

    .reminder-text a {
      color: #5c3b00;
      text-decoration: underline;
      font-weight: 600;
    }
    .reminder-content {
        display: flex;
        /* flex-direction: column; */
        gap: 0.3rem;
    }
    @media (max-width: 767px) {
      .reminder-box h5 {
        font-size: 1.1rem;
        padding: 0.8rem 1rem;
      }

      .main-reminder-desc {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.6rem;
      }
      .reminder-content {
        display:block;
        /* flex-direction: column; */
        gap: 0.3rem;
    }
      .reminder-icon {
        font-size: 1.2rem;
        margin-top: 0;
      }

      .reminder-text {
        font-size: 0.95rem;
      }
    }
    .main-deiv {
    display: flex;
    justify-content: space-between;
    width: 100%;
    background-color: #f1c40f;
    align-items: center;
}
</style>
@if(auth()->user()->role_id != 6)
@if ($upcomingHoliday)

<div class="alert alert-info alert-dismissible upcoming-holiday-alert fade show" role="alert">
    <i class="bi bi-info-circle me-1"></i>
    @if ($upcomingHoliday->from === $upcomingHoliday->to)
    You have Upcoming Holiday on {{date("d-M-Y", strtotime($upcomingHoliday->from)) ?? ''}}! Of
    {{$upcomingHoliday->name ?? ''}}
    @else
    You have Upcoming Holiday from {{date("d-M-Y", strtotime($upcomingHoliday->from)) ?? ''}} to
    {{date("d-M-Y", strtotime($upcomingHoliday->to)) ?? ''}} ! Of {{$upcomingHoliday->name ?? ''}}
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($activeReminders->isNotEmpty())
    @foreach($activeReminders as $reminder)
        @if($reminder->user_id == auth()->user()->id)
            <div class="container my-5">
                <div class="reminder-box">
                    <div class="main-deiv">
                    <h5><i class="fa-solid fa-bell"></i>The {{ ucfirst($reminder->type) }} Reminder for You</h5>
                    <button class="close reminder-close-btn" data-id="{{ $reminder->id }}" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    </div>
                    <div class="main-reminder-desc">
                        <div class="reminder-icon">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div class="reminder-content">
                            <div class="reminder-label">
                                Description:
                            </div>
                            <div class="reminder-text">
                                {{ $reminder->description }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif

<!-- class=""> -->
<div class="row">
    <div class="col-lg-8 dashboard" style="margin-top: 20px !important;">
        <div class="row">

            <!-- Sales Card -->
            <div class="col-xxl-4 col-md-6">
                <div class="card info-card sales-card">
                    <div class="filter">
                        <!-- <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow"> -->
                        <!-- <li class="dropdown-header text-start">
                                <h6>Filter</h6>
                            </li> -->
                        <!--
                            <li><a class="dropdown-item" href="#">Today</a></li>
                            <li><a class="dropdown-item" href="#">This Month</a></li>
                            <li><a class="dropdown-item" href="#">This Year</a></li> -->
                        </ul>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Total Members</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="ps-3">

                                <h6>{{$userCount}}</h6>
                                <!-- <span class="text-success small pt-1 fw-bold">12%</span> <span
                                    class="text-muted small pt-2 ps-1">increase</span> -->
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Sales Card -->
            <!-- Revenue Card -->
            <div class="col-xxl-4 col-md-6">
                <div class="card info-card revenue-card">
                    <div class="filter">
                        <!-- <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <li class="dropdown-header text-start">
                                <h6>Filter</h6>
                            </li>

                            <li><a class="dropdown-item" href="#">Today</a></li>
                            <li><a class="dropdown-item" href="#">This Month</a></li>
                            <li><a class="dropdown-item" href="#">This Year</a></li>
                        </ul> -->
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Today's On Leave</h5>

                        <div class="d-flex align-items-center leavesMemberCont">
                            <div class="leavesMemeberInnerCont">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-card-list"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{$users}}</h6>
                                </div>
                            </div>
                            @if($users !=0)
                            <a class="text-primary small pt-1 pointer text-right" onClick="ShowLeavesModal()"
                                id="viewAll">View
                                all</a>
                            @endif
                        </div>
                        <!-- <div class="pull-left "> -->

                        <!-- </div> -->
                    </div>

                </div>
            </div>
            <!---End Revenue Card--->
            @if(auth()->user()->role->name != 'Super Admin')
            <div class="col-xxl-4 col-md-6">
                <div class="card info-card sales-card">
                    <div class="filter">
                        <!-- <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow"> -->
                        <!-- <li class="dropdown-header text-start">
                                <h6>Filter</h6>
                            </li> -->
                        <!--
                            <li><a class="dropdown-item" href="#">Today</a></li>
                            <li><a class="dropdown-item" href="#">This Month</a></li>
                            <li><a class="dropdown-item" href="#">This Year</a></li> -->
                        </ul>
                    </div>
                    <div class="card-body dashboard-my-leaves">
                        <h5 class="card-title">My Leaves</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar-week"></i>
                                <!-- <i class="fas fa-calendar-times"></i> -->
                            </div>
                            <div class="ps-3">
                                @php
                                // Get the authenticated user
                                $user = auth()->user();

                                // Convert the joining_date attribute to a Carbon date instance
                                $joiningDate = \Carbon\Carbon::parse($user->joining_date);

                                // Calculate the date 3 months ago from the current date
                                $threeMonthsAgo = now()->subMonths(3);
                                @endphp
                                <h6>
                                    @if ($joiningDate >= $threeMonthsAgo)
                                    @if ($approvedLeave > 0 )
                                    <span class="text-danger"
                                        title="your leaves exceded from total available leaves">{{$approvedLeave}}</span>
                                    @else
                                    <span title="Your leaves">{{$approvedLeave}}</span>
                                    @endif
                                    @else
                                    @if ($approvedLeave > $totalLeaves )
                                    <span class="text-danger"
                                        title="your leaves exceded from total available leaves">{{$approvedLeave}}</span>
                                    @else
                                    <span title="Your leaves">{{$approvedLeave}}</span>
                                    @endif
                                    @endif
                                    / @if ($joiningDate < $threeMonthsAgo) <span title="Total Leaves">
                                        {{$totalLeaves}}</span>
                                        @else
                                        <span title="Total Leaves">0</span>
                                        @endif
                                </h6>
                                <!-- <span class="text-success small pt-1 fw-bold">12%</span> <span
                                    class="text-muted small pt-2 ps-1">increase</span> -->
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- End Sales Card -->
            @endif


            <!-- Customers Card -->

            <div class="col-12">
                <div class="card">
                    <!-- <div class="filter">
                <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                    <h6>Filter</h6>
                </li>

                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
                <li><a class="dropdown-item" href="#">This Year</a></li>
                </ul>
                </div> -->

                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 dashboard" style="margin-top: 20px ">
        <!-- Recent Activity -->

        <div class="card">
            <div class="filter">
                <!-- <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a> -->
                <!-- <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                    <h6>Filter</h6>
                </li>

                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
                <li><a class="dropdown-item" href="#">This Year</a></li>
            </ul> -->
            </div>
            @if(count($userBirthdate)!=0)
            <div class="card-body">
                <h5 class="card-title"> Birthday/Anniversary</h5>
                <div class="row mb-2">
                    @if(count($userBirthdate) !=0)
                    @foreach ($userBirthdate as $birthday)
                    <div class="col-md-3 mb-2">
                        <img src="{{asset('assets/img/').'/'.$birthday->profile_picture}}" width="50" height="50" alt=""
                            class="rounded-circle">
                    </div>
                    <div class="col-md-9 mt-2 ">
                        <b>{{$birthday->first_name." ".$birthday->last_name}}</b>
                        <div>
                            @if($dayMonth == date('m-d', strtotime($birthday->birth_date)) && $dayMonth == date('m-d',
                            strtotime($birthday->joining_date)))
                            <i class="fa fa-birthday-cake" style="color:red" aria-hidden="true"></i>
                            <span>Birthday & <i class="fa fa-gift" style="color:green" aria-hidden="true"></i>
                                Anniversary</span>

                            @else
                            @if($dayMonth == date('m-d', strtotime($birthday->birth_date)))
                            <i class="fa fa-birthday-cake" style="color:red" aria-hidden="true"></i>
                            <span>Birthday</span>
                            @elseif ($currentDate == $birthday->joining_date)
                            <i class="fa fa-handshake-o" style="color:green" aria-hidden="true"></i>
                            <span style="font-size:smaller;">Warm Welcome On Joining!</span>
                            @else
                            <i class="fa fa-gift" style="color:green" aria-hidden="true"></i>
                            <span>Anniversary</span>
                            @endif
                            @endif
                        </div>
                    </div>
                    <hr>
                    @endforeach
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Sticky Notes Started -->
<div class="col-lg-12 stickyNotes">
    <div class="card">
        <div class="sticky-card">
            <div class="row">
                <!-- <div class="container"> -->
                <h3 class="sticky-heading"><i class="bi bi-pencil-square"></i> Sticky Notes</h3>
                <div class="notes-wrapper" id="noteGrid"></div>
            </div>
            <!-- </div> -->
        </div>
    </div>
    <!-- Sticky Notes Ended -->

    <!-- ---------- ToDo List Started ---------------- -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="task-head" style="display: flex; justify-content: space-between; align-items: center;">
                        <h5 class="card-title">Your Tasks</h5>
                        <a href="/todo_list" class="btn btn-primary" style="
    font-weight: 600;
    font-size: 15px;
    background-color: #4154f1;
">View All Tasks</a>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        @if($tasks->isEmpty())
                        <p>No tasks found.</p>
                        @else
                        <table class="table table-bordered teamstasks">
                            <thead class="table-light">
                                <tr>
                                    <th>All Tasks</th>
                                    <th>Created At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $task)
                                <tr>
                                    <td>{{ $task->title }}</td>
                                    <td>{{ $task->created_at->format('d M Y, h:i A') }}</td>
                                    <td>
                                        @php
                                        $statusClass = match($task->status) {
                                        'open' => 'primary', // Blue
                                        'hold' => 'warning', // Yellow
                                        'completed' => 'success', // Green
                                        'canceled' => 'danger', // Red
                                        default => 'secondary' // Gray for unknown statuses
                                        };

                                        // Apply custom color only if status is not "hold"
                                        $customColor = $task->status === 'hold' ? '' : 'background-color: #4154f1 !important;';
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}"
                                            style="{{ $customColor }} border-radius: 20px;">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>








    <!-- ---------- To Do List Ended ---------------- -->



    <!-- Employee Of The Month Section -->
    @include('votes.index', ['winners' => $winners])
    <!-- End of Employee Of The Month Section -->




    <!-- Recent Sales -->

    <div class="row">
        <div class="col-md-8 dashboard">
            <div class="card recent-sales overflow-auto">
                <div class="filter">
                    <!-- <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a> -->
                    <!-- <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                    <h6>Filter</h6>
                </li>

                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
                <li><a class="dropdown-item" href="#">This Year</a></li>
            </ul> -->
                </div>
                <div class="card-body">
                    <h5 class="card-title">Teams Leave</h5>
                    <table class="table table-borderless datatable" id="leavesss">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">From</th>
                                <th scope="col">To</th>
                                <th scope="col">Type</th>
                                <th scope="col">Status</th>

                            </tr>
                            <!-- <h5 class="text-white font-weight-bolder mb-4 pt-2">Notes</h5> -->
                        </thead>
                        <tbody>
                            @forelse($userLeaves as $data)
                            @if($data->status == 1)
                            <tr>
                                <td>{{ $data->first_name}}</td>
                                <td>{{date("d-M-Y", strtotime($data->from));}}</td>
                                <td>{{date("d-M-Y", strtotime($data->to));}}</td>
                                <td>{{$data->type }}</td>
                                <td>
                                    @php
                                    $leaveStatusData = $leaveStatus->where('leave_id', $data->id)->first();
                                    @endphp
                                    @if($data->leave_status == 'approved')
                                    <span class="badge rounded-pill approved">Approved</span>
                                    @elseif($data->leave_status == 'declined')
                                    <span class="badge rounded-pill denied">Declined</span>
                                    @else
                                    <span class="badge rounded-pill requested">Requested</span>
                                    @endif
                                    @if (!empty($leaveStatusData))
                                    <p class="small mt-1" style="font-size: 11px;font-weight:600; margin-left:6px;"> By:
                                        {{ $leaveStatusData->first_name ?? '' }}
                                    </p>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="row">
                @if (count($assignedDevices )> 0 && auth()->user()->role->name != 'Super Admin')
                <div class="col-md-12 dashboard">
                    <div class="card recent-sales overflow-auto">
                        <div class="filter">
                            <!-- <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a> -->
                            <!-- <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                        <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                </ul> -->
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Assigned Devices</h5>
                            <table class="table table-borderless datatable" id="devices">
                                <thead>
                                    <tr>
                                        <th scope="col">Device Name</th>
                                        <th scope="col">Model Name</th>
                                        <th scope="col">Serial Number</th>
                                        <th scope="col">From</th>
                                        <!-- <th scope="col">To</th> -->
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignedDevices as $data)
                                    <tr>
                                        <td>{{ $data->device->name ?? ''}}</td>
                                        <td>{{ $data->device->device_model ?? ''}}</td>
                                        <td>{{ $data->device->serial_number ?? '---'}}</td>
                                        <td>{{date("d-m-Y", strtotime($data->from));}}</td>
                                        <!-- <td> @if ($data->to)
                                                {{date("d-m-Y", strtotime($data->to)) }}
                                            @endif
                                </td> -->
                                        <td> @if ($data->status == 0)
                                            <span class="badge rounded-pill bg-success">Recovered</span>
                                            @else
                                            <span class="badge rounded-pill bg-primary">Assigned</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!------Vote Section-------->

        <div class="col-md-4 dashboard">
            @php
            $last7Days = \Carbon\Carbon::now()->day > (\Carbon\Carbon::now()->daysInMonth - 7);
            @endphp
            @if ($last7Days)
            <div class="card vote-section">
                <div class="card-body">
                    <div class="main-div">
                        <h5 class="card-title">Vote For The Employee Of The Month({{ \Carbon\Carbon::now()->format('F') }})</h5>
                        <div class="vote" style="max-height: 300px; overflow-y: auto;">
                            @if ($uservote->isNotEmpty())
                            <table class="table" id="voter">
                                <thead>
                                    <tr>
                                        <th scope="col">Employee Name</th>
                                        <th scope="col">Vote</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($uservote as $user)
                                    <!-- @if($user->status == 1 && $user->role_id != 1) -->
                                    <tr>
                                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" style="padding-bottom: 1px;" onclick="vote('{{ $user->first_name }}', '{{ $user->last_name }}', '{{$user->id}}')">Vote</button>
                                        </td>
                                    </tr>
                                    <!-- @endif -->
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <p>Your vote has been recorded. Results will be announced shortly.</p>
                            <div class="img-wrapper ">
                                <img src="/assets/img/votingresult.png">
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <!------ End Vote Section-------->


            @if ($userBirthdateEvent->isNotEmpty())
            @php
            $hasUpcomingEvents = false; // Flag to track if there are upcoming events
            @endphp

            @foreach ($userBirthdateEvent as $user)
            @php
            $birthMonth = date('m', strtotime($user->birth_date));
            $birthDay = date('d', strtotime($user->birth_date));
            $joinMonth = date('m', strtotime($user->joining_date));
            $joinDay = date('d', strtotime($user->joining_date));
            $currentMonth = date('m');
            $currentDay = date('d');

            // Check if person completed one year or not
            $joiningDate = new DateTime($user->joining_date);
            $currentDate = new DateTime(date('Y-m-d'));
            $interval = $joiningDate->diff($currentDate);

            // Check if events are in the current month and in the future
            $isBirthdayThisMonth = $currentMonth == $birthMonth;
            $isAnniversaryThisMonth = $currentMonth == $joinMonth;

            $isBirthdayUpcoming = $isBirthdayThisMonth && ($birthDay > $currentDay);
            if ($interval->y > 1) {
            $isAnniversaryUpcoming = $isAnniversaryThisMonth && ($joinDay > $currentDay);
            } else {
            $isAnniversaryUpcoming = false;
            }

            if ($isBirthdayUpcoming || $isAnniversaryUpcoming) {
            $hasUpcomingEvents = true; // Update the flag if any upcoming event is found
            }
            @endphp
            @endforeach

            @if ($hasUpcomingEvents)
            <div class="card upcoming-events">
                <div class="card-body pb-4">
                    <h5 class="card-title">Upcoming Events</h5>

                    <div class="news">
                        @foreach ($userBirthdateEvent as $user)
                        @php
                        $birthMonth = date('m', strtotime($user->birth_date));
                        $birthDay = date('d', strtotime($user->birth_date));
                        $joinMonth = date('m', strtotime($user->joining_date));
                        $joinDay = date('d', strtotime($user->joining_date));
                        $currentMonth = date('m');
                        $currentDay = date('d');

                        // Check if person completed one year or not
                        $joiningDate = new DateTime($user->joining_date);
                        $currentDate = new DateTime(date('Y-m-d'));
                        $interval = $joiningDate->diff($currentDate);

                        // Check if events are in the current month and in the future
                        $isBirthdayThisMonth = $currentMonth == $birthMonth;
                        $isAnniversaryThisMonth = $currentMonth == $joinMonth;

                        $isBirthdayUpcoming = $isBirthdayThisMonth && ($birthDay > $currentDay);
                        if ($interval->y > 1) {
                        $isAnniversaryUpcoming = $isAnniversaryThisMonth && ($joinDay > $currentDay);
                        } else {
                        $isAnniversaryUpcoming = false;
                        }
                        @endphp

                        @if ($isBirthdayUpcoming && $isAnniversaryUpcoming)
                        <div class="post-item clearfix">
                            <h4>{{ $user->first_name . " " . $user->last_name }}</h4>
                            <i class="fa fa-birthday-cake" style="color:red" aria-hidden="true"></i>
                            <span>Birthday on {{ date("d F", strtotime($user->birth_date)) }}</span> <span> & </span>
                            <i class="fa fa-gift" style="color:green" aria-hidden="true"></i>
                            <span>Anniversary on {{ date("d F", strtotime($user->joining_date)) }}</span>
                        </div>
                        @elseif ($isBirthdayUpcoming)
                        <div class="post-item clearfix">
                            <h4>{{ $user->first_name . " " . $user->last_name }}</h4>
                            <i class="fa fa-birthday-cake" style="color:red" aria-hidden="true"></i>
                            <span>Birthday on {{ date("d F", strtotime($user->birth_date)) }}</span>
                        </div>
                        @elseif ($isAnniversaryUpcoming)
                        <div class="post-item clearfix">
                            <h4>{{ $user->first_name . " " . $user->last_name }}</h4>
                            <i class="fa fa-gift" style="color:green" aria-hidden="true"></i>
                            <span>Anniversary on {{ date("d F", strtotime($user->joining_date)) }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @endif

            <div class="card upcoming-holidays">
                <!-- <div class="filter">
              <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
              <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                  <h6>Filter</h6>
                </li>

                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
                <li><a class="dropdown-item" href="#">This Year</a></li>
              </ul>
            </div> -->

                <div class="card-body">
                    <h5 class="card-title"> Upcoming Holidays</h5>

                    <div class="news">
                        @if ($upcomingFourHolidays)
                        @foreach ($upcomingFourHolidays as $holiday)
                        <div class="post-item clearfix">
                            <h4>{{$holiday->name}} <span>| @if ($holiday->from === $holiday->to)
                                    {{ \Carbon\Carbon::parse($holiday->from)->format('l') }}
                                    @else
                                    {{ \Carbon\Carbon::parse($holiday->from)->format('l') }} To
                                    {{ \Carbon\Carbon::parse($holiday->to)->format('l') }}
                                    @endif</span></h4>
                            <p>Holiday @if ($holiday->from === $holiday->to) On
                                {{date("d-M-Y", strtotime($holiday->from));}} @else From
                                {{date("d-M-Y", strtotime($holiday->from));}} to {{date("d-M-Y", strtotime($holiday->to));}}
                                @endif
                            </p>
                        </div>
                        @endforeach
                    </div><!-- End sidebar recent posts-->
                    @else
                    <div class="alert" role="alert">
                        No upcoming holidays found.
                    </div>
                    @endif
                </div>
            </div>

            {{-- For Missing attendance --}}
            @if (auth()->user()->role->name == 'Super Admin' || auth()->user()->role->name == 'HR Manager')
            <div class="col-md-12 dashboard">
                <div class="card recent-sales overflow-auto">
                    <!-- <div class="filter">
                       <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                       <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                       <li class="dropdown-header text-start">
                       <h6>Filter</h6>
                       </li>

                     <li><a class="dropdown-item" href="#">Today</a></li>
                     <li><a class="dropdown-item" href="#">This Month</a></li>
                     <li><a class="dropdown-item" href="#">This Year</a></li>
                     </ul>
                    </div> -->
                    <div class="card-body">
                        <h5 class="card-title">Missing Attendance</h5>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-borderless datatable" id="leavesss">
                                <thead>
                                    <tr>
                                        <th scope="col">Employee Name</th>
                                        <th scope="col">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($userAttendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance['name'] }}</td>
                                        <!-- <td>{{ implode(', ', $attendance['dates']) }}</td> -->
                                        <td>{{ implode(', ', array_map(function($date) {
                                        return date('d-M-Y', strtotime($date));
                                             }, $attendance['dates'])) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (empty($userAttendances))
                            <div class="alert" role="alert">
                                No results found.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- <div class="row">
            @if (count($assignedDevices )> 0 && auth()->user()->role->name != 'Super Admin')
            <div class="col-md-8 dashboard">
                <div class="card recent-sales overflow-auto">
                    <div class="filter">

                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Assigned Devices</h5>
                        <table class="table table-borderless datatable" id="devices">
                            <thead>
                                <tr>
                                    <th scope="col">Device Name</th>
                                    <th scope="col">Model Name</th>
                                    <th scope="col">Serial Number</th>
                                    <th scope="col">From</th>

                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignedDevices as $data)
                                <tr>
                                    <td>{{ $data->device->name ?? ''}}</td>
                                    <td>{{ $data->device->device_model ?? ''}}</td>
                                    <td>{{ $data->device->serial_number ?? '---'}}</td>
                                    <td>{{date("d-m-Y", strtotime($data->from));}}</td>

                                    <td> @if ($data->status == 0)
                                        <span class="badge rounded-pill bg-success">Recovered</span>
                                        @else
                                        <span class="badge rounded-pill bg-primary">Assigned</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div> -->
        </div>
    </div>


    <div class="modal fade" id="ShowLeaves" tabindex="-1" aria-labelledby="ShowLeaves" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">List of members on leave today</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    @foreach ($validLeaves as $data)
                    <div class="row leaveUserContainer mt-2 ">
                        <div class="col-md-2">
                            <img src="{{asset('assets/img/').'/'.$data->profile_picture}}" width="50" height="50" alt="" class="rounded-circle">
                        </div>
                        <div class="col-md-10 ">
                            <p><b>{{$data->first_name}} <b></p>
                        </div>
                    </div>

                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="voteModal" tabindex="-1" role="dialog" aria-labelledby="voteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voteModalLabel">Vote Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <p>Employee: <span class="toVoteUserName"> </span></p>
                    <div class="form-group">
                        <label for="reason" class="col-sm-3 col-form-label required">Reason</label>
                        <textarea class="form-control" id="reason" placeholder="Enter reason"></textarea>
                        <div id="reasonError" class="text-danger"></div>
                        <div id="successMessage" class="text-success"></div>
                        <input type="hidden" class="form-control" id="fromuser" value="{{ auth()->user()->id }} " />
                        <input type="hidden" class="form-control" id="touser" />

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="clearErrorMessage()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitVote()">Submit</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @if (auth()->user()->role_id == 6)
    <!-- Sticky Notes Started -->
    <div class="col-lg-12 stickyNotes">
        <div class="card">
            <div class="sticky-card">
                <div class="row">
                    <!-- <div class="container"> -->
                    <h3 class="sticky-heading"><i class="bi bi-pencil-square"></i> Sticky Notes</h3>
                    <div class="notes-wrapper" id="noteGrid"></div>
                </div>
                <!-- </div> -->
            </div>
        </div>
        <!-- Sticky Notes Ended -->
        <div class="row">
            <!-- Left 8-column block for both tables -->
            <div class="col-lg-8">
                <!-- First Card -->
                <div class="card mb-3">
                    <div class="card-body pb-4">
                        <h4 class="mb-3">Projects List</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Sr No</th>
                                        <th>Project Name</th>
                                        <th>Start Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @if($projects->isEmpty())
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No projects found</td>
                                    </tr>
                                @else
                                    @foreach ($projects as $project)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $project->project_name }}</td>
                                        <td>{{ $project->start_date }}</td>
                                        <td>
                                            &nbsp;&nbsp;&nbsp;
                                            <a href="{{ route('sprint.index', ['project_filter' => $project->id]) }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ url('/edit/project/'.$project->id)}}">
                                                <i style="color:#4154f1;" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body pb-4">
                        <h4 class="mb-3">Recent Notifications</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Notification</th>
                                        <th>Project Name</th>
                                        <th>Ticket</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @if($notifications->isEmpty())
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No notifications found</td>
                                    </tr>
                                @else
                                    @foreach($notifications as $notification)
                                    @php
                                    $ticket = \App\Models\Tickets::find($notification->ticket_id);
                                    $projectName = $ticket ? ($projectMap[$ticket->project_id] ?? 'Unknown') : 'Unknown';
                                    $creatorName = $notification->user->first_name ?? 'Unknown User';
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $notification->message }} <br>
                                            <small>By: {{ $creatorName }}</small>
                                        </td>
                                        <td>{{ $projectName }}</td>
                                        <td>
                                            <a href="{{ url('/view/ticket/' . $notification->ticket_id) }}" target="_blank">
                                                <i class="fa fa-eye text-primary"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ url('/notification/all') }}" class="btn btn-primary" style="background-color:#4154F1; border: 2px solid #4154f1;padding: 6px  20px;font-weight: 600;border-radius: 10px;">See All</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">

                @if ($userBirthdateEvent->isNotEmpty())
                @php
                $hasUpcomingEvents = false;
                @endphp

                @foreach ($userBirthdateEvent as $user)
                @php
                $birthMonth = date('m', strtotime($user->birth_date));
                $birthDay = date('d', strtotime($user->birth_date));
                $joinMonth = date('m', strtotime($user->joining_date));
                $joinDay = date('d', strtotime($user->joining_date));
                $currentMonth = date('m');
                $currentDay = date('d');

                $joiningDate = new DateTime($user->joining_date);
                $currentDate = new DateTime(date('Y-m-d'));
                $interval = $joiningDate->diff($currentDate);

                $isBirthdayThisMonth = $currentMonth == $birthMonth;
                $isAnniversaryThisMonth = $currentMonth == $joinMonth;

                $isBirthdayUpcoming = $isBirthdayThisMonth && ($birthDay > $currentDay);
                if ($interval->y > 1) {
                $isAnniversaryUpcoming = $isAnniversaryThisMonth && ($joinDay > $currentDay);
                } else {
                $isAnniversaryUpcoming = false;
                }

                if ($isBirthdayUpcoming || $isAnniversaryUpcoming) {
                $hasUpcomingEvents = true;
                }
                @endphp
                @endforeach
                @endif


                <div class="card upcoming-holidays">
                    <div class="card-body">
                        <h5 class="card-title">Code4each Upcoming Holidays</h5>

                        <div class="news">
                            @if ($upcomingFourHolidays)
                            @foreach ($upcomingFourHolidays as $holiday)
                            <div class="post-item clearfix">
                                <h4>
                                    {{ $holiday->name }}
                                    <span> |
                                        @if ($holiday->from === $holiday->to)
                                        {{ \Carbon\Carbon::parse($holiday->from)->format('l') }}
                                        @else
                                        {{ \Carbon\Carbon::parse($holiday->from)->format('l') }} To
                                        {{ \Carbon\Carbon::parse($holiday->to)->format('l') }}
                                        @endif
                                    </span>
                                </h4>
                                <p>
                                    Holiday
                                    @if ($holiday->from === $holiday->to)
                                    On {{ date("d-M-Y", strtotime($holiday->from)) }}
                                    @else
                                    From {{ date("d-M-Y", strtotime($holiday->from)) }}
                                    to {{ date("d-M-Y", strtotime($holiday->to)) }}
                                    @endif
                                </p>
                            </div>
                            @endforeach
                            @else
                            <div class="alert" role="alert">
                                No upcoming holidays found.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endsection
    @section('js_scripts')
    <script>
        $(document).ready(function() {


            $('#leavesss').DataTable({
                "order": []
            });

            $('#devices').DataTable({
                "order": []
            });

            $("#viewAll").click(function() {

            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        });

        function vote(first_name, last_name, id) {
            $(".toVoteUserName").text(first_name + ' ' + last_name);
            $('#voteModal').modal('show');
            $('#touser').val(id);
            console.log(id);
            $('#voteModal').on('hidden.bs.modal', function() {
                $('#reason').val('');


            });
        }

        function submitVote() {
            var reason = document.getElementById('reason').value.trim();
            var reasonWithoutSpaces = reason.replace(/\s/g, '');
            var charCount = reasonWithoutSpaces.length;

            var reasonError = document.getElementById('reasonError'); // Get the error message container

            if (charCount < 150) {
                reasonError.textContent = "Reason must be at least 150 characters.";
                return;
            } else {
                reasonError.textContent = "";
            }

            // Clear the error message if validation passes
            reasonError.textContent = "";
            var fromUserId = $("#fromuser").val();
            var toUserId = $("#touser").val();
            console.log(toUserId);
            var currentDate = new Date();
            var month = currentDate.getMonth() + 1;
            var year = currentDate.getFullYear();
            var notes = $("#reason").val();

            $.ajax({
                type: 'POST',
                url: "{{ url('/submit-vote')}} ",
                data: {
                    from: fromUserId,
                    to: toUserId,
                    month: month,
                    year: year,
                    notes: notes
                },
                success: function(response) {
                    //     if (response.success) {
                    //     $('#successMessage').text("Vote submitted successfully!");
                    //     // You can also clear the textarea or perform any other actions as needed
                    // } else
                    if (response.success) {
                        $('#voteModal').modal('hide'); // Hide the modal after successful vote submission
                        $('#voteSuccessMessage').text("Your vote has been counted. Results will be shown soon."); // Show success message
                    } else {
                        $('.alert-danger').html('');
                        $("#voteModal").modal('hide');
                        location.reload();
                    }
                },
                error: function(data) {
                    console.log(data);
                }
            });
        }

        function clearErrorMessage() {
            document.getElementById('reasonError').textContent = ""; // Clear the error message
        }

        function ShowLeavesModal() {

            $('#ShowLeaves').modal('show');
        }



        $(document).ready(function() {
            updateTaskUI();

            $('.list-group').on('click', '.btn-hold', function() {
                let taskId = getTaskId(this);
                holdTask(taskId);
            });

            $('.list-group').on('click', '.btn-reopen', function() {
                let taskId = getTaskId(this);
                reopenTask(taskId);
            });

            $('.list-group').on('change', '.task-checkbox', function() {
                toggleCompleted(this);
            });

            function updateTaskUI() {
                $('.list-group-item').each(function() {
                    let taskItem = $(this);
                    let status = taskItem.attr('class').split(' ').pop();
                    let checkbox = taskItem.find('.task-checkbox');
                    let holdButton = taskItem.find('.btn-hold');
                    let reopenButton = taskItem.find('.btn-reopen');
                    let editIcon = taskItem.find('.edit-task');
                    let deleteIcon = taskItem.find('.delete-task');

                    // Reset button visibility
                    holdButton.hide();
                    reopenButton.hide();
                    editIcon.show();
                    deleteIcon.show();
                    checkbox.prop('disabled', false);

                    if (status === 'pending') {
                        holdButton.show();
                    } else if (status === 'hold') {
                        checkbox.prop('disabled', true);
                        reopenButton.show();
                        editIcon.hide();
                        deleteIcon.hide();
                    } else if (status === 'completed') {
                        checkbox.prop('disabled', false).prop('checked', true);
                        reopenButton.show();
                        editIcon.hide();
                        deleteIcon.hide();
                    }
                });
            }

            function toggleCompleted(checkbox) {
                let taskId = $(checkbox).val();
                let taskItem = $('#task_' + taskId);
                let newStatus = $(checkbox).is(':checked') ? 'completed' : 'pending';

                $.ajax({
                    type: 'PUT',
                    url: "/todo_list/" + taskId + "/status",
                    data: {
                        status: newStatus,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        taskItem.removeClass('pending completed hold').addClass(newStatus);
                        updateTaskUI();
                    },
                    error: function(xhr, status, error) {
                        console.log("Error updating task status:", error);
                    }
                });
            }

            function holdTask(taskId) {
                $.ajax({
                    type: 'PUT',
                    url: "/todo_list/" + taskId + "/hold",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        $('#task_' + taskId).removeClass('pending').addClass('hold');
                        updateTaskUI();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error holding task:", error);
                    }
                });
            }

            function reopenTask(taskId) {
                $.ajax({
                    type: 'PUT',
                    url: "/todo_list/" + taskId + "/status",
                    data: {
                        status: 'pending',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        $('#task_' + taskId).removeClass('completed hold').addClass('pending');
                        updateTaskUI();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error reopening task:", error);
                    }
                });
            }

            // Fetch the sticky notes
            $.ajax({
                url: '{{ route('sticky.notes') }}',
                method: 'GET',
                success: function(response) {
                    const notes = response;
                    notes.forEach(function(note) {
                        const title = note.title ? note.title : undefined;
                        const body = note.notes ? note.notes : undefined;
                        createNote(note.id, note.userid, title, body, note.created_at, note.first_name, note.last_name);
                    });
                },
                error: function() {
                    console.error('Could not load sticky notes.');
                }
            });
        });
        // jQuery for handling the cross button click
        $(document).on('click', '.close', function() {
            var reminderId = $(this).data('id');  // Get the reminder ID
            var currentTime = new Date().toISOString();  // Get current time in ISO format

            // Send the AJAX request to the route defined above
            $.ajax({
                url: '/reminder/mark-as-read',  // The URL of the route you defined
                method: 'POST',
                data: {
                    id: reminderId,  // Pass the reminder ID
                    clicked_at: currentTime,  // Pass the current time as clicked_at
                    _token: '{{ csrf_token() }}'  // CSRF token for security
                },
                success: function(response) {
                    console.log(response); // Log the response to check success
                    $('[data-id="' + reminderId + '"]').closest('.alert').fadeOut();
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText); // Log the error details
                    alert('Failed to save the click time');
                }
            });
        });
        function getTaskId(element) {
            return $(element).closest('.list-group-item').attr('id').split('_')[1];
        }
    // jQuery for handling the cross button click
    $(document).on('click', '.close', function() {
    var reminderId = $(this).data('id');  // Get the reminder ID
    var currentTime = new Date().toISOString();  // Get current time in ISO format

    // Send the AJAX request to the route defined above
    $.ajax({
        url: '/reminder/mark-as-read',  // The URL of the route you defined
        method: 'POST',
        data: {
            id: reminderId,  // Pass the reminder ID
            clicked_at: currentTime,  // Pass the current time as clicked_at
            _token: '{{ csrf_token() }}'  // CSRF token for security
        },
        success: function(response) {
            console.log(response); // Log the response to check success
            $('[data-id="' + reminderId + '"]').closest('.alert').fadeOut();
        },
        error: function(xhr, status, error) {
            console.log(xhr.responseText); // Log the error details
            alert('Failed to save the click time');
        }
    });
});

        // sticky notes js started //
        let updateTimeout;
        const colors = ['color-yellow', 'color-green', 'color-blue', 'color-pink', 'color-purple'];
        const noteGrid = document.getElementById('noteGrid');
        let lastColorIndex = -1;

        function getRandomColorClass() {
            let newIndex;
            do {
                newIndex = Math.floor(Math.random() * colors.length);
            } while (newIndex === lastColorIndex && colors.length > 1);
            lastColorIndex = newIndex;
            return colors[newIndex];
        }

        function createNote(id, userid, title = false, body = false, created = '', first_name = '', last_name = '') {
            const createdDate = new Date(created);
            const formattedDate = createdDate.toLocaleDateString();
            const formattedTime = createdDate.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            const formattedDateTime = `${formattedDate} ${formattedTime}`;
            const fullName = first_name + ' ' + last_name;

            const colorClass = getRandomColorClass();
            const note = document.createElement('div');
            note.className = `note ${colorClass}`;

            if (title === false) {
                title = 'Title';
            }
            if (body === false) {
                body = 'Type here...';
            }
            note.innerHTML = `
                <input type="hidden" class="note-id" value="${id}">
                <input type="hidden" class="user-id" value="${userid}">
                <div class="note-inner-container">
                    <div class="delete-btn"><i class="fas fa-trash"></i></div>
                    <div class="note-content">
                        <div class="note-saved-message" style="display: none;">
                            <i class="fas fa-check-circle"></i> Saved
                        </div>
                        <div class="note-title" contenteditable="true">${title}</div>
                        <div class="note-body" contenteditable="true">${body}</div>
                    </div>
                    <div class="vbo-sticky-note-sign">
                        <span class="vbo-sticky-note-sign-dt">${formattedDateTime}</span>
                        <span class="vbo-sticky-note-sign-user">${fullName}</span>
                    </div>
                </div>
            `;

            note.querySelector('.note-title').addEventListener('blur', updateNote);
            note.querySelector('.note-body').addEventListener('blur', updateNote);

            // Add delete button functionality
            note.querySelector('.delete-btn').onclick = function() {
                const noteId = note.querySelector('.note-id').value;

                if (confirm('Are you sure you want to delete this note?')) {
                    fetch('/sticky-notes/delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                id: noteId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                note.remove();
                            } else {
                                alert('Failed to delete note.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            };

            // Insert the note before the add button
            noteGrid.insertBefore(note, document.getElementById('addBtn'));
        }

        function createAddBtn() {
            const addBtn = document.createElement('div');
            addBtn.id = 'addBtn';
            addBtn.className = 'add-note';
            addBtn.innerHTML = '<i class="bi bi-plus-circle-dotted"></i>';

            addBtn.onclick = () => {
                const notes = document.querySelectorAll('.note');

                for (let note of notes) {
                    const title = note.querySelector('.note-title').innerText.trim();
                    const body = note.querySelector('.note-body').innerText.trim();

                    // Check if note is still default (incomplete)
                    if ((title === 'Title' || title === '') && (body === 'Type here...' || body === '')) {
                        note.classList.add('shake');
                        setTimeout(() => note.classList.remove('shake'), 500);
                        return;
                    }
                }

                // If all notes are complete, proceed to create a new note
                fetch('/sticky-notes/create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        const note = data.note;
                        createNote(note.id, note.userid, false, false, note.created_at, note.first_name, note.last_name);
                    });
            };

            noteGrid.appendChild(addBtn);
        }

        function updateNote(event) {
            clearTimeout(updateTimeout);

            updateTimeout = setTimeout(() => {
                const noteElement = event.target.closest('.note');
                const noteId = noteElement.querySelector('.note-id').value;
                const title = noteElement.querySelector('.note-title').innerText.trim();
                const body = noteElement.querySelector('.note-body').innerText.trim();

                fetch(`/sticky-notes/update/${noteId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            title: title,
                            notes: body
                        })
                    })
                    .then(() => {
                        const savedMsg = noteElement.querySelector('.note-saved-message');
                        savedMsg.style.display = 'block';
                        savedMsg.classList.add('show');
                        setTimeout(() => {
                            savedMsg.classList.remove('show');
                            savedMsg.style.display = 'none';
                        }, 3000);
                    });
            }, 1000);
        }

        createAddBtn();
    $(document).on('click', '.reminder-close-btn', function() {
        let reminderId = $(this).data('id');
        $(this).closest('.container').fadeOut(); // or remove()

        // Optional: Send AJAX request to mark reminder as closed
        $.ajax({
            url: '/reminders/close', // adjust this URL as needed
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: reminderId
            },
            success: function(response) {
                console.log('Reminder closed.');
            },
            error: function(xhr) {
                console.error('Error closing reminder.');
            }
        });
    });
    </script>

    @endsection
