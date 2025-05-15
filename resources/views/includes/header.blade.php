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

    <!-- ======= Sidebar ======= -->
    <aside id="sidebar" class="sidebar">

        <ul class="sidebar-nav" id="sidebar-nav">
            @if(auth()->user()->role_id == 6)
            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard') ? '' : 'collapsed' }}" href="{{ url('/dashboard') }}">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li><!-- End Dashboard Nav -->
            <li class="nav-item">
                <a class="nav-link {{ request()->is('sprint') ? '' : 'collapsed' }}"
                    href="{{ route('sprint.index') }}">
                    <i class="bi bi-clipboard"></i>
                    <span>Sprint</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('projects') ? '' : 'collapsed' }}"
                    href="{{ route('projects.index') }}">
                    <i class="bi bi-collection"></i> <span>Projects</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('devlisting') ? '' : 'collapsed' }}"
                    href="{{ route('devlisting') }}">
                    <i class="bi bi-people"></i> <span>Developer Listing</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('comments') ? '' : 'collapsed' }}"
                    href="{{ route('comments') }}">
                    <i class="bi bi-bell"></i> <span>All Comments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('messages') ? '' : 'collapsed' }}" href="{{ route('messages') }}">
                    <i class="bi bi-people"></i>
                    <div class="position-relative">
                        @if(isset($unreadMessageCount) && $unreadMessageCount > 0)
                            <span id="unread-message-counts" class="bg-danger ms-2">{{ $unreadMessageCount }}</span>
                        @endif
                        <span>Messages</span>
                    </div>
                </a>
            </li>    
            <li class="nav-item">
                <a class="nav-link {{ request()->is('reminder') ? '' : 'collapsed' }}"
                    href="{{ route('reminder.create') }}">
                    <i class="bi bi-calendar-check"></i>
                    <span>Reminders</span>
                </a>
            </li>
        @endif
        @if(auth()->user()->role_id != 6)
            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard') ? '' : 'collapsed' }}" href="{{ url('/dashboard') }}">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li><!-- End Dashboard Nav -->
            <li class="nav-item">
                <a class="nav-link {{ request()->is('scrumdash') ? '' : 'collapsed' }}"
                    href="{{ route('scrumdash.index') }}">
                    <!-- <i class="bi bi-buildings"></i> -->
                    <i class="bi bi-grid"></i>
                    <span>Scrum Dashboard</span>
                </a>
            </li>

            @if(in_array(auth()->user()->role_id, [2, 3]))
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('sprint') ? '' : 'collapsed' }}"
                        href="{{ route('sprint.index') }}">
                        <i class="bi bi-clipboard"></i>
                        <span>Sprint</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('reminder') ? '' : 'collapsed' }}"
                        href="{{ route('reminder.create') }}">
                        <i class="bi bi-calendar-check"></i>
                        <span>Reminders</span>
                    </a>
                </li>
            @endif

          @if(auth()->user()->role->name == 'Super Admin')
          <li class="nav-item">
            <a class="nav-link {{ request()->is('sprint') ? '' : 'collapsed' }}"
                href="{{ route('sprint.index') }}">
                <i class="bi bi-clipboard"></i>
                <span>Sprint</span>
            </a>
        </li>
         <li class="nav-item">
                <a class="nav-link {{ request()->is('messages') ? '' : 'collapsed' }}" href="{{ route('messages') }}">
                    <i class="bi bi-people"></i>
                    <div class="position-relative">
                        @if(isset($unreadMessageCount) && $unreadMessageCount > 0)
                            <span id="unread-message-counts" class="bg-danger ms-2">{{ $unreadMessageCount }}</span>
                        @endif
                        <span>Messages</span>
                    </div>
                </a>
            </li>    
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('comments') ? '' : 'collapsed' }}"
                href="{{ route('comments') }}">
                <i class="bi bi-bell"></i> <span>All Comments</span>
            </a>
        </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('pages') ? '' : 'collapsed' }}"
                    href="{{ route('pages.index') }}">
                    <!-- <i class="bi bi-buildings"></i> -->
                    <i class="bi bi-file-earmark-fill"></i>
                    <span>Pages</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('modules') ? '' : 'collapsed' }}"
                    href="{{ route('modules.index') }}">
                    <!-- <i class="bi bi-buildings"></i> -->
                    <i class="bi bi-file-earmark-fill"></i>
                    <span>Modules</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('clients') ? '' : 'collapsed' }}"
                    href="{{ route('clients.index') }}">
                    <i class="bi bi-person-square"></i>
                    <span>Clients</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('departments') ? '' : 'collapsed' }}"
                    href="{{ route('departments.index') }}">
                    <i class="bi bi-buildings"></i>
                    <span>Departments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('role') ? '' : 'collapsed' }}" href="{{ route('roles.index') }}">
                    <i class="bi bi-people"></i>
                    <span>Roles</span>
                </a>
            </li>
          @endif
          @if (auth()->user()->role->name == 'HR Manager')
          <li class="nav-item">
                <a class="nav-link {{ request()->is('departments') ? '' : 'collapsed' }}"
                    href="{{ route('departments.index') }}">
                    <i class="bi bi-buildings"></i>
                    <span>Departments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('role') ? '' : 'collapsed' }}" href="{{ route('roles.index') }}">
                    <i class="bi bi-people"></i>
                    <span>Roles</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('sprint') ? '' : 'collapsed' }}"
                    href="{{ route('sprint.index') }}">
                    <i class="bi bi-clipboard"></i>
                    <span>Sprint</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('reminder') ? '' : 'collapsed' }}"
                    href="{{ route('reminder.create') }}">
                    <i class="bi bi-calendar-check"></i>
                    <span>Reminders</span>
                </a>
            </li>
          @endif
            <li class="nav-item">
                <a class="nav-link {{ request()->is('users') ? '' : 'collapsed' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-person-square"></i>
                    <span>Users
                    </span>
                </a>
            </li>
            @if(auth()->user()->role->name == 'Super Admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->is('attendance/team') ? '' : 'collapsed' }} show"
                    href="{{ route('attendance.team.index') }}">
                    <i class="bi bi-person-vcard-fill"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('role') ? '' : 'collapsed' }}" href="{{ route('developer.feedback') }}">
                    <i class="bi bi-people"></i>
                    <span>Feedbacks</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('reminder') ? '' : 'collapsed' }}"
                    href="{{ route('reminder.create') }}">
                    <i class="bi bi-calendar-check"></i>
                    <span>Reminders</span>
                </a>
            </li>
            @else
            <li class="nav-item">
                <a class="nav-link {{ request()->is('attendance') ? '' : 'collapsed' }}"
                    data-bs-target="#attendance-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-person-vcard-fill"></i></i><span>Attendance</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="attendance-nav"
                    class="nav-content collapse {{ request()->is('attendance') || request()->is('attendance/team') ? 'show' : '' }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="{{ request()->is('attendance') ? 'active' : 'collapsed' }}"
                            href="{{ route('attendance.index') }}" href="">
                            <i class="bi bi-circle "></i><span>My Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->is('attendance/team') ? 'active' : 'collapsed ' }}"
                            href="{{ route('attendance.team.index')}}">
                            <i class="bi bi-circle"></i><span>Team Attendance</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
            @if(auth()->user()->role->name == 'Super Admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->is('leaves/team') ? '' : 'collapsed' }}"
                    href=" {{ route('leaves.team.index')}}">
                    <i class="bi bi-menu-button-wide"></i>
                    <span>Leaves</span>
                </a>
            </li>
            @else
            <li class="nav-item">
                <a class="nav-link {{ request()->is('leaves') ? '' : 'collapsed' }}" data-bs-target="#leaves-nav"
                    data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-text-window-reverse"></i><span>Leaves</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="leaves-nav"
                    class="nav-content collapse {{ request()->is('leaves') || request()->is('leaves/team') ? 'show' : '' }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class=" {{ request()->is('leaves') ? 'active' : 'collapsed' }} "
                            href=" {{ route('leaves.index') }}">
                            <i class="bi bi-circle "></i><span>My Leaves</span>
                        </a>
                    </li>
                    <li>
                        <a class=" {{ request()->is('leaves/team') ? 'active' : 'collapsed' }} "
                            href=" {{ route('leaves.team.index')}}">
                            <i class="bi bi-circle"></i><span>Team Leaves</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            <li class="nav-item">
                <a class="nav-link {{ request()->is('projects') ? '' : 'collapsed' }}"
                    href="{{ route('projects.index') }}">
                    <i class="bi bi-list-task"></i> <span>Projects</span>
                </a>
            </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('todo_list') ? '' : 'collapsed' }}"
             href="{{ route('todo_list.index') }}">
             <i class="bi bi-journal-code"></i> <span>ToDo</span>
            </a>
        </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('tickets') ? '' : 'collapsed' }}"
                    href="{{ route('tickets.index') }}">
                    <i class="bi bi-journal-code"></i> <span>Tickets</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('holidays') ? '' : 'collapsed' }}"
                    href="{{ route('holidays.index') }}">
                    <!-- <i class="bi bi-buildings"></i> -->
                    <i class="bi bi-calendar-check"></i>
                    <span>Holidays</span>
                </a>
            </li>

            @if (auth()->user()->role->name == 'HR Manager' || auth()->user()->role->name == 'Super Admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->is('devices') ? '' : 'collapsed' }}"
                    data-bs-target="#devices-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-person-vcard-fill"></i></i><span>Devices</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="devices-nav"
                    class="nav-content collapse {{ request()->is('devices') || request()->is('assigned-devices') ? 'show' : '' }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="{{ request()->is('devices') ? 'active' : 'collapsed' }}"
                            href="{{ route('devices.index') }}" href="">
                            <i class="bi bi-circle "></i><span>All Devices</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->is('assigned-devices') ? 'active' : 'collapsed ' }}"
                            href="{{ route('devices.assigned.index')}}">
                            <i class="bi bi-circle"></i><span>Assigned Devices</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('emailtoall') ? '' : 'collapsed' }}"
                    href="{{ route('emailall.index') }}">
                    <i class="bi bi-envelope"></i>
                    <span>Email</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('policies') ? '' : 'collapsed' }}"
                    href="{{ route('policies.index') }}">
                    <i class="bi bi-files"></i> <span>Policies</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('hireus') ? '' : 'collapsed' }}"
                    href="{{ route('hireus.index') }}">
                    <i class="bi bi-person-square"></i> <span>Hire Us</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('jobs') ? '' : 'collapsed' }}" data-bs-target="#job-cat-nav"
                    data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-text-window-reverse"></i><span>Jobs</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="job-cat-nav"
                    class="nav-content collapse {{ request()->is('jobs') || request()->is('job-categories') ||  request()->is('applicants') ? 'show' : '' }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class=" {{ request()->is('jobs') ? 'active' : 'collapsed' }} "
                            href=" {{ route('jobs.index') }}">
                            <i class="bi bi-circle "></i><span>Jobs</span>
                        </a>
                    </li>
                    <li>
                        <a class=" {{ request()->is('job-categories') ? 'active' : 'collapsed' }} "
                            href=" {{ route('job_categories.index')}}">
                            <i class="bi bi-circle"></i><span>Job Categories</span>
                        </a>
                    </li>

                    <li>
                        <a class=" {{ request()->is('applicants') ? 'active' : 'collapsed' }} "
                            href=" {{ route('applicants.index')}}">
                            <i class="bi bi-circle"></i><span>Applicants</span>
                        </a>
                    </li>
                </ul>
            </li>

            @endif
            @endif
            <!-- <li class="nav-item">
        <a class="" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Forms</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="forms-elements.html">
              <i class="bi bi-circle"></i><span>Form Elements</span>
            </a>
          </li>
          <li>
            <a href="forms-layouts.html">
              <i class="bi bi-circle"></i><span>Form Layouts</span>
            </a>
          </li>
          <li>
            <a href="forms-editors.html">
              <i class="bi bi-circle"></i><span>Form Editors</span>
            </a>
          </li>
          <li>
            <a href="forms-validation.html">
              <i class="bi bi-circle"></i><span>Form Validation</span>
            </a>
          </li>
        </ul>
      </li> -->
            <!-- End Forms Nav -->

            <!-- <li class="nav-item">
        <a class="" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-layout-text-window-reverse"></i><span>Tables</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="tables-general.html">
              <i class="bi bi-circle"></i><span>General Tables</span>
            </a>
          </li>
          <li>
            <a href="tables-data.html">
              <i class="bi bi-circle"></i><span>Data Tables</span>
            </a>
          </li>
        </ul>
      </li> -->
            <!-- End Tables Nav -->

            <!-- <li class="nav-item">
        <a class="" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bar-chart"></i><span>Charts</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="charts-chartjs.html">
              <i class="bi bi-circle"></i><span>Chart.js</span>
            </a>
          </li>
          <li>
            <a href="charts-apexcharts.html">
              <i class="bi bi-circle"></i><span>ApexCharts</span>
            </a>
          </li>
          <li>
            <a href="charts-echarts.html">
              <i class="bi bi-circle"></i><span>ECharts</span>
            </a>
          </li>
        </ul>
      </li> -->
            <!-- End Charts Nav -->

            <!-- <li class="nav-item">
        <a class="" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Icons</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="icons-bootstrap.html">
              <i class="bi bi-circle"></i><span>Bootstrap Icons</span>
            </a>
          </li>
          <li>
            <a href="icons-remix.html">
              <i class="bi bi-circle"></i><span>Remix Icons</span>
            </a>
          </li>
          <li>
            <a href="icons-boxicons.html">
              <i class="bi bi-circle"></i><span>Boxicons</span>
            </a>
          </li>
        </ul>
      </li> -->
            <!-- End Icons Nav -->

            <!-- <li class="nav-heading">Pages</li> -->

            <!-- <li class="nav-item">
        <a class="" href="users-profile.html">
          <i class="bi bi-person"></i>
          <span>Profile</span>
        </a>
      </li> -->
            <!-- End Profile Page Nav -->

            <!-- <li class="nav-item">
        <a class="" href="pages-faq.html">
          <i class="bi bi-question-circle"></i>
          <span>F.A.Q</span>
        </a>
      </li> -->
            <!-- End F.A.Q Page Nav -->

            <!-- <li class="nav-item">
        <a class="" href="pages-contact.html">
          <i class="bi bi-envelope"></i>
          <span>Contact</span>
        </a>
      </li> -->
            <!-- End Contact Page Nav -->

            <!-- <li class="nav-item">
        <a class="" href="pages-register.html">
          <i class="bi bi-card-list"></i>
          <span>Register</span>
        </a>
      </li> -->
            <!-- End Register Page Nav -->

            <!-- <li class="nav-item">
        <a class="" href="pages-login.html">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Login</span>
        </a>
      </li> -->
            <!-- End Login Page Nav -->

            <!-- <li class="nav-item">
        <a class="" href="pages-error-404.html">
          <i class="bi bi-dash-circle"></i>
          <span>Error 404</span>
        </a>
      </li> -->
            <!-- End Error 404 Page Nav -->

            <!-- <li class="nav-item">
        <a class="" href="pages-blank.html">
          <i class="bi bi-file-earmark"></i>
          <span>Blank</span>
        </a>
      </li> -->
            <!-- End Blank Page Nav -->

        </ul>

    </aside><!-- End Sidebar-->


    <!-- End #main -->

    <!-- ======= Footer ======= -->
    <!-- <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>NiceAdmin</span></strong>. All Rights Reserved
    </div>
    <div class="credits"> -->
    <!-- All the links in the footer should remain intact. -->
    <!-- You can delete the links only if you purchased the pro version. -->
    <!-- Licensing information: https://bootstrapmade.com/license/ -->
    <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
    <!-- Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
  </footer> -->
    <!-- End Footer -->

    <!-- <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a> -->
    <script>
function clearMessageCounter() {
    const counter = document.getElementById('unread-message-counts');
    if (counter) {
        counter.remove();
    }
}
</script>
<script>
    function sendHeartbeat() {
        fetch("{{ url('/user/heartbeat') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        });
    }

    sendHeartbeat();
    setInterval(sendHeartbeat, 30000);
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
