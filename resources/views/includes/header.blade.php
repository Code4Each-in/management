<!DOCTYPE html>
<html lang="en">

<body>

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">

        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ url('/dashboard') }}" class="logo d-flex align-items-center">
                <img src="{{ asset('assets/img/code4each_logo.png') }}" alt="Code4Each">
                <!-- <span class="d-none d-lg-block">Management</span> -->
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div><!-- End Logo -->

        <!-- <div class="search-bar">
            <form class="search-form d-flex align-items-center" method="POST" action="#">
                <input type="text" name="query" placeholder="Search" title="Enter search keyword">
                <button type="submit" title="Search"><i class="bi bi-search"></i></button>
            </form>
        </div>End Search Bar -->

        <nav class="header-nav ms-auto">
              @php
                use App\Models\Settings;
                use App\Models\Holidays;

                $setting = Settings::first();

                $now = \Carbon\Carbon::now('Asia/Kolkata');

                $isOnline = false;
                $isHoliday = false;
                $isWeekend = false;

                if ($setting && $setting->is_active) {

                    $start = \Carbon\Carbon::createFromTimeString($setting->start_time);
                    $end = \Carbon\Carbon::createFromTimeString($setting->end_time);

                    $isWeekend = !$now->isWeekday();

                    $isHoliday = Holidays::whereDate('from', '<=', $now)
                        ->whereDate('to', '>=', $now)
                        ->exists();

                    $isOnline = $now->between($start, $end);

                    if ($setting->skip_weekends && $isWeekend) {
                        $isOnline = false;
                    }

                    if ($isHoliday) {
                        $isOnline = false;
                    }
                }
            @endphp

            <div class="company-status">
                <span class="status-indicator {{ $isOnline ? 'online' : 'offline' }}"></span>
                <span class="status-text">
                    Code4Each is 
                    {{ $isOnline 
                        ? 'Online' 
                        : ($isHoliday 
                            ? 'Offline (Holiday)' 
                            : ($isWeekend 
                                ? 'Offline (Weekend)' 
                                : 'Offline (Outside Working Hours)'
                            )
                        )
                    }}
                </span>
            </div>
            @if (auth()->user()->role_id != 6)
                <div id="notificationDropdown">
                    @include('notifications.partials._dropdown')
                </div>
                @endif
            <ul class="d-flex align-items-center">

                <li class="nav-item d-block d-none">
                    <a class="nav-link nav-icon search-bar-toggle " href="#">
                        <i class="bi bi-search"></i>
                    </a>
                </li><!-- End Search Icon-->
                @php
                    $gender = strtolower(auth()->user()->gender ?? '');
                @endphp
                <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                    @if (auth()->user()->profile_picture)
                        <img src="{{ asset('assets/img/' . auth()->user()->profile_picture) }}" id="profile_picture"
                            alt="Profile" height="50px" width="50px" class="rounded-circle picture js-profile-picture">
                    @else
                        @if ($gender == 'male')
                            <img src="{{ asset('assets/img/dummyMale.png') }}" id="profile_picture"
                                alt="Profile" height="50px" width="50px" class="rounded-circle picture js-profile-picture">
                        @else
                            <img src="{{ asset('assets/img/dummyFemale.png') }}" id="profile_picture"
                                alt="Profile" height="50px" width="50px" class="rounded-circle picture js-profile-picture">
                        @endif
                    @endif
                </a>
                <li class="nav-item dropdown pe-3">

                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <!-- <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle"> -->
                        <span
                            class="d-none d-md-block dropdown-toggle ps-2">{{ auth()->user()->first_name ?? " " }}</span>
                    </a><!-- End Profile Iamge Icon -->

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <div class="row">
                                <div class="col-md-4">
                                    @if (auth()->user()->profile_picture)
                                    <img src="{{asset('assets/img/').'/'.auth()->user()->profile_picture}}"
                                        id="profile_picture" alt="Profile" height="50px" width="50px"
                                        class="rounded-circle picture js-profile-picture">
                                    @else
                                        @if ($gender == 'male')
                                            <img src="{{ asset('assets/img/dummyMale.png') }}" id="profile_picture"
                                                alt="Profile" height="50px" width="50px" class="rounded-circle picture js-profile-picture">
                                        @else
                                            <img src="{{ asset('assets/img/dummyFemale.png') }}" id="profile_picture"
                                                alt="Profile" height="50px" width="50px" class="rounded-circle picture js-profile-picture">
                                        @endif
                                    @endif
                                </div>
                                <div class="col-md-8">
                                    <h6>{{ auth()->user()->first_name ?? " " }}</h6>
                                    <span>{{ auth()->user()->role->name ?? " " }}</span>
                                </div>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{route('profile')}}">
                                <i class="bi bi-person"></i>
                                <span>My Profile</span>
                            </a>
                        </li>
                        @if(auth()->user()->role->name == 'Super Admin')
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{route('settings')}}">
                                <i class="bi bi-gear"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        @endif
                        <hr class="dropdown-divider">

                        <a class="dropdown-item d-flex align-items-center" href="{{ route('logout')}}">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Log Out</span>
                        </a>
                </li>

            </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->

            </ul>
        </nav><!-- End Icons Navigation -->

        @if(session()->has('message'))
            <div class="alert alert-success header-alert fade show" role="alert" id="header-alert">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ session()->get('message') }}
            </div>
        @endif

        @if(session()->has('error'))

        <div class="alert alert-danger header-alert fade show" role="alert" id="header-alert">
                        <i class="bi bi-exclamation-octagon me-1"></i>
                        {{ session()->get('error') }}
        </div>
        @endif

    </header><!-- End Header -->
<aside id="sidebar" class="sidebar">
<ul class="sidebar-nav" id="sidebar-nav">

{{-- ================= DASHBOARD ================= --}}
<li class="nav-item">
    <a class="nav-link {{ request()->is('dashboard') ? '' : 'collapsed' }}" href="{{ url('/dashboard') }}">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
    </a>
</li>

@if(auth()->user()->role_id != 6)
<li class="nav-item">
    <a class="nav-link {{ request()->is('scrumdash') ? '' : 'collapsed' }}" href="{{ route('scrumdash.index') }}">
        <i class="bi bi-grid"></i>
        <span>Scrum Dashboard</span>
    </a>
</li>
@endif


{{-- ================= WORK ================= --}}
<li class="nav-item">
    <a class="nav-link {{ request()->is('projects*','tickets*','sprint*','todo_list*','ticket-logs*','devlisting*','pending-approvals*') ? '' : 'collapsed' }}"
       data-bs-target="#work-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-kanban"></i><span>Work</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="work-nav"
        class="nav-content collapse {{ request()->is('projects*','tickets*','sprint*','todo_list*', 'reminder*', 'ticket-logs*','devlisting*','pending-approvals*') ? 'show' : '' }}"
        data-bs-parent="#sidebar-nav">

        <li><a class="{{ request()->is('projects*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><i class="bi bi-circle"></i>Projects</a></li>
        <li><a class="{{ request()->is('tickets*') ? 'active' : '' }}" href="{{ route('tickets.index') }}"><i class="bi bi-circle"></i>Tickets</a></li>
        

        @if(auth()->user()->role_id != 6)
        <li><a class="{{ request()->is('ticket-logs*') ? 'active' : '' }}" href="{{ route('ticket-logs.index') }}"><i class="bi bi-circle"></i>Ticket Logs</a></li>
        <li><a class="{{ request()->is('sprint*') ? 'active' : '' }}" href="{{ route('sprint.index') }}"><i class="bi bi-circle"></i>Sprint</a></li>

        @endif

        @if(auth()->user()->role_id == 6)
            <li><a class="{{ request()->is('devlisting*') ? 'active' : '' }}" href="{{ route('devlisting') }}"><i class="bi bi-circle"></i>Developer Listing</a></li>
            <li><a class="{{ request()->is('pending-approvals*') ? 'active' : '' }}" href="{{ route('client.pending.approvals') }}"><i class="bi bi-circle"></i>Pending Approvals</a></li>
        @endif

        <li><a class="{{ request()->is('todo_list*') ? 'active' : '' }}" href="{{ route('todo_list.index') }}"><i class="bi bi-circle"></i>ToDo</a></li>
        <li><a class="{{ request()->is('reminder') ? 'active' : '' }}" href="{{ route('reminder.create') }}"><i class="bi bi-circle"></i>Reminders</a></li>

    </ul>
</li>


{{-- ================= COMMUNICATION ================= --}}
<li class="nav-item">
    <a class="nav-link {{ request()->is('messages*','teamchat*','comments*','search*') ? '' : 'collapsed' }}"
       data-bs-target="#comm-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-chat-dots"></i><span>Communication</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="comm-nav"
        class="nav-content collapse {{ request()->is('messages*','teamchat*','comments*','search*') ? 'show' : '' }}"
        data-bs-parent="#sidebar-nav">
        
        @if(auth()->user()->role_id != 6)
        <li><a class="{{ request()->is('comments') ? 'active' : '' }}" href="{{ route('comments') }}"><i class="bi bi-circle"></i>Comments</a></li>
        <li><a class="{{ request()->is('search*') ? 'active' : '' }}" href="{{ route('search.index') }}"><i class="bi bi-circle"></i>Comment Search</a></li>
        @endif

        @if(auth()->user()->role_id == 6 || auth()->user()->role->name == 'Super Admin')
        <li><a class="{{ request()->is('messages*') ? 'active' : '' }}" href="{{ route('messages') }}"><i class="bi bi-circle"></i>Messages</a></li>
        @endif

        <li><a class="{{ request()->is('teamchat*') ? 'active' : '' }}" href="{{ route('teamchat') }}"><i class="bi bi-circle"></i>Team Chat</a></li>

       


    </ul>
</li>


{{-- ================= HR ================= --}}
@if(auth()->user()->role_id != 6 || auth()->user()->role->name == 'Super Admin')
<li class="nav-item">
    <a class="nav-link {{ request()->is('attendance*','leaves*','holidays*','announcement*') ? '' : 'collapsed' }}"
       data-bs-target="#hr-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-person-vcard"></i><span>HR</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="hr-nav"
        class="nav-content collapse {{ request()->is('attendance*','leaves*','holidays*','announcement*') ? 'show' : '' }}"
        data-bs-parent="#sidebar-nav">

        @if(auth()->user()->role_id != 6 && auth()->user()->role->name != 'Super Admin')
        <li><a class="{{ request()->is('attendance') ? 'active' : '' }}" href="{{ route('attendance.index') }}"><i class="bi bi-circle"></i>My Attendance</a></li>
        <li><a class="{{ request()->is('leaves') ? 'active' : '' }}" href="{{ route('leaves.index') }}"><i class="bi bi-circle"></i>My Leaves</a></li>
        @endif

        <li><a class="{{ request()->is('attendance/team') ? 'active' : '' }}" href="{{ route('attendance.team.index') }}"><i class="bi bi-circle"></i>Team Attendance</a></li>
        <li><a class="{{ request()->is('leaves/team') ? 'active' : '' }}" href="{{ route('leaves.team.index') }}"><i class="bi bi-circle"></i>Team Leaves</a></li>


        @if(auth()->user()->role->name == 'HR Manager' || auth()->user()->role->name == 'Super Admin')
        <li><a class="{{ request()->is('holidays') ? 'active' : '' }}" href="{{ route('holidays.index') }}"><i class="bi bi-circle"></i>Holidays</a></li>
        <li><a class="{{ request()->is('attendance/history') ? 'active' : '' }}" href="{{ route('attendance.history') }}"><i class="bi bi-circle"></i>Attendance History</a></li>
        <li><a class="{{ request()->is('announcement') ? 'active' : '' }}" href="{{ route('announcement.create') }}"><i class="bi bi-circle"></i>Announcements</a></li>
        <li><a class="{{ request()->is('developer.feedback') ? 'active' : '' }}" href="{{ route('developer.feedback') }}"><i class="bi bi-circle"></i>Feedbacks</a></li>
        @endif


    </ul>
</li>
@endif


@if(auth()->user()->role->name == 'Super Admin' || auth()->user()->role->name == 'HR Manager')
<li class="nav-item">
    <a class="nav-link {{ request()->is('users*','roles*','departments*','devices*','assigned-devices*') ? '' : 'collapsed' }}"
       data-bs-target="#admin-core-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-gear"></i><span>Admin</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="admin-core-nav"
        class="nav-content collapse {{ request()->is('users*','roles*','departments*','devices*','assigned-devices*') ? 'show' : '' }}"
        data-bs-parent="#sidebar-nav">

        <li><a class="{{ request()->is('users*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-circle"></i>Users</a></li>
        <li><a class="{{ request()->is('roles*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="bi bi-circle"></i>Roles</a></li>
        <li><a class="{{ request()->is('departments*') ? 'active' : '' }}" href="{{ route('departments.index') }}"><i class="bi bi-circle"></i>Departments</a></li>

        <li>
                    <a class="{{ request()->is('devices') ? 'active' : '' }}" href="{{ route('devices.index') }}">
                        <i class="bi bi-circle"></i>All Devices
                    </a>
                </li>

                <li>
                    <a class="{{ request()->is('assigned-devices') ? 'active' : '' }}" href="{{ route('devices.assigned.index') }}">
                        <i class="bi bi-circle"></i>Assigned Devices
                    </a>
                </li>

    </ul>
</li>
@endif

@if(auth()->user()->role->name == 'Super Admin' || auth()->user()->role->name == 'HR Manager')
<li class="nav-item">
    <a class="nav-link {{ request()->is('jobs*','emailtoall*','policies*','hireus*','clients*','pages*','modules*','client-access-requests*') ? '' : 'collapsed' }}"
       data-bs-target="#admin-system-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-sliders"></i><span>Management</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="admin-system-nav"
        class="nav-content collapse {{ request()->is('jobs*','emailtoall*','policies*','hireus*','clients*','pages*','modules*','client-access-requests*') ? 'show' : '' }}"
        data-bs-parent="#sidebar-nav">

        <li><a class="{{ request()->is('emailtoall*') ? 'active' : '' }}" href="{{ route('emailall.index') }}"><i class="bi bi-circle"></i>Email</a></li>
        <li><a class="{{ request()->is('policies*') ? 'active' : '' }}" href="{{ route('policies.index') }}"><i class="bi bi-circle"></i>Policies</a></li>
        <li><a class="{{ request()->is('hireus*') ? 'active' : '' }}" href="{{ route('hireus.index') }}"><i class="bi bi-circle"></i>Hire Us</a></li>

        <li><a class="{{ request()->is('jobs*') ? 'active' : '' }}" href="{{ route('jobs.index') }}"><i class="bi bi-circle"></i>Jobs</a></li>
        <li><a class="{{ request()->is('job-categories*') ? 'active' : '' }}" href="{{ route('job_categories.index') }}"><i class="bi bi-circle"></i>Job Categories</a></li>
        <li><a class="{{ request()->is('applicants*') ? 'active' : '' }}" href="{{ route('applicants.index') }}"><i class="bi bi-circle"></i>Applicants</a></li>

        @if(auth()->user()->role->name == 'Super Admin')
            <li><a class="{{ request()->is('pages*') ? 'active' : '' }}" href="{{ route('pages.index') }}"><i class="bi bi-circle"></i>Pages</a></li>
            <li><a class="{{ request()->is('modules*') ? 'active' : '' }}" href="{{ route('modules.index') }}"><i class="bi bi-circle"></i>Modules</a></li>
            <li><a class="{{ request()->is('clients*') ? 'active' : '' }}" href="{{ route('clients.index') }}"><i class="bi bi-circle"></i>Clients</a></li>

            <li>
                <a class="{{ request()->routeIs('client-access-requests.*') ? 'active' : '' }}"
                   href="{{ route('client-access-requests.index') }}">
                    <i class="bi bi-circle"></i>Client Access Requests
                </a>
            </li>
        @endif

    </ul>
</li>
@endif


{{-- ================= EXTRA ================= --}}
@if(auth()->user()->designation == 'BDE' || auth()->user()->role->name == 'Super Admin')
<li class="nav-item">
    <a class="nav-link {{ request()->is('bid-sprints') ? '' : 'collapsed' }}" href="{{ route('bdeSprint.index') }}">
        <i class="bi bi-person-badge"></i>
        <span>BDE Panel</span>
    </a>
</li>
@endif

</ul>
</aside>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script>
function clearMessageCounter() {
    const counter = document.getElementById('unread-message-counts');
    if (counter) {
        counter.remove();
    }
}
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let isUserActive = true;
        let activityTimeout;

        function resetUserActivity() {
            isUserActive = true;
            clearTimeout(activityTimeout);
            activityTimeout = setTimeout(() => {
                isUserActive = false;
            }, 60000); // Mark inactive after 1 minute of no activity
        }

        ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            window.addEventListener(event, resetUserActivity);
        });

        function sendHeartbeat() {
            if (isUserActive) {
                fetch("{{ url('/user/heartbeat') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({})
                });
            }
        }

        resetUserActivity();
        sendHeartbeat();
        setInterval(sendHeartbeat, 30000);
    });
</script>
    <script>
        function fetchNotifications() {
            $.ajax({
                url: "{{ route('notifications.all') }}",
                method: 'GET',
                dataType: 'json',
                success: function (res) {
                    $('#notificationDropdown').html(res.html);
                },
                error: function(err) {
                    console.error('Notification fetch failed', err);
                }
            });
        }
        fetchNotifications();
        setInterval(fetchNotifications, 30000);
    </script>

</body>

</html>
