<!DOCTYPE html>
<html lang="en">

<body>

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">

        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ url('/dashboard') }}" class="logo d-flex align-items-center">
                <img src="{{ asset('assets/img/logo.png') }}" alt="">
                <span class="d-none d-lg-block">Management</span>
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
            <ul class="d-flex align-items-center">

                <li class="nav-item d-block d-lg-none">
                    <a class="nav-link nav-icon search-bar-toggle " href="#">
                        <i class="bi bi-search"></i>
                    </a>
                </li><!-- End Search Icon-->

                <!-- <li class="nav-item dropdown"> -->

                <!-- <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="badge bg-primary badge-number">4</span> -->
                <!-- /  </a>End Notification Icon -->

                <!-- <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
                        <li class="dropdown-header">
                            You have 4 new notifications
                            <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="notification-item">
                            <i class="bi bi-exclamation-circle text-warning"></i>
                            <div>
                                <h4>Test</h4>
                                <p>Test here</p>
                                <p>30 min. ago</p>
                            </div>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="notification-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <div>
                                <h4>Test</h4>
                                <p>Test here</p>
                                <p>1 hr. ago</p>
                            </div>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="dropdown-footer">
                            <a href="#">Show all notifications</a>
                        </li> -->

                <!-- / </ul>End Notification Dropdown Items -->

                <!-- </li>End Notification Nav -->

                <!-- <li class="nav-item dropdown">-->

                <!-- <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-chat-left-text"></i>
                    <span class="badge bg-success badge-number">3</span>
                </a>End Messages Icon -->

                <!-- <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow messages">
                        <li class="dropdown-header">
                            You have 3 new messages
                            <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="message-item">
                            <a href="#">
                                <img src="" alt="" class="rounded-circle">
                                <div>
                                    <h4>Test</h4>
                                    <p>Test here...</p>
                                    <p>4 hrs. ago</p>
                                </div>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="message-item">
                            <a href="#">
                                <img src="" alt="" class="rounded-circle">
                                <div>
                                    <h4>Test</h4>
                                    <p>Test here...</p>
                                    <p>6 hrs. ago</p>
                                </div>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="dropdown-footer">
                            <a href="#">Show all messages</a>
                        </li>

                    </ul>End Messages Dropdown Items -->

                <!-- </li>End Messages Nav -->
                <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                    <img src="{{asset('assets/img/').'/'.auth()->user()->profile_picture}}" id="profile_picture"
                        alt="Profile" height="50px" width="50px" class="rounded-circle picture">
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
                                    <img src="{{asset('assets/img/').'/'.auth()->user()->profile_picture}}"
                                        id="profile_picture" alt="Profile" height="50px" width="50px"
                                        class="rounded-circle picture">
                                </div>
                                <div class="col-md-5">
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

    </header><!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <aside id="sidebar" class="sidebar">

        <ul class="sidebar-nav" id="sidebar-nav">

            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard') ? '' : 'collapsed' }}" href="{{ url('/dashboard') }}">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li><!-- End Dashboard Nav -->
            @if(auth()->user()->role_id==env('SUPER_ADMIN'))
            <li class="nav-item">
                <a class="nav-link {{ request()->is('departments') ? '' : 'collapsed' }}"
                    href="{{ route('departments.index') }}">
                    <i class="bi bi-buildings"></i>
                    <span>Departments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('role') ? '' : 'collapsed' }}" href="{{ route('role.index') }}">
                    <i class="bi bi-people"></i>
                    <span>Roles</span>
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
            @if(auth()->user()->role_id==env('SUPER_ADMIN'))
            <li class="nav-item">
                <a class="nav-link {{ request()->is('attendance/teams') ? '' : 'collapsed' }} show"
                    href="{{ route('teams.attendance') }}">
                    <i class="bi bi-person-vcard-fill"></i>
                    <span>Attendance</span>
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
                    class="nav-content collapse {{ request()->is('attendance') || request()->is('attendance/teams') ? 'show' : '' }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="{{ request()->is('attendance') ? 'active' : 'collapsed' }}"
                            href="{{ route('attendance.index') }}" href="">
                            <i class="bi bi-circle "></i><span>My Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->is('attendance/teams') ? 'active' : 'collapsed ' }}"
                            href="{{ route('teams.attendance')}}">
                            <i class="bi bi-circle"></i><span>Team Attendance</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
            @if(auth()->user()->role_id==env('SUPER_ADMIN'))
            <li class="nav-item">
                <a class="nav-link {{ request()->is('leaves/team') ? '' : 'collapsed' }}"
                    href=" {{ route('team.leaves')}}">
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
                    class="nav-content collapse {{ request()->is('leaves') || request()->is('leaves/teams') ? 'show' : '' }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class=" {{ request()->is('leaves') ? 'active' : 'collapsed' }} "
                            href=" {{ route('leaves.index') }}">
                            <i class="bi bi-circle "></i><span>My Leaves</span>
                        </a>
                    </li>
                    <li>
                        <a class=" {{ request()->is('leaves/teams') ? 'active' : 'collapsed' }} "
                            href=" {{ route('team.leaves')}}">
                            <i class="bi bi-circle"></i><span>Team Leaves</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
            <li class="nav-item">
                <a class="nav-link {{ request()->is('tickets') ? '' : 'collapsed' }}"
                    href="{{ route('tickets.index') }}">
                    <i class="bi bi-journal-code"></i> <span>Tickets</span>
                </a>
            </li>
            <!-- <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
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
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
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
        <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
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
        <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
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
        <a class="nav-link collapsed" href="users-profile.html">
          <i class="bi bi-person"></i>
          <span>Profile</span>
        </a>
      </li> -->
            <!-- End Profile Page Nav -->

            <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="pages-faq.html">
          <i class="bi bi-question-circle"></i>
          <span>F.A.Q</span>
        </a>
      </li> -->
            <!-- End F.A.Q Page Nav -->

            <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="pages-contact.html">
          <i class="bi bi-envelope"></i>
          <span>Contact</span>
        </a>
      </li> -->
            <!-- End Contact Page Nav -->

            <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="pages-register.html">
          <i class="bi bi-card-list"></i>
          <span>Register</span>
        </a>
      </li> -->
            <!-- End Register Page Nav -->

            <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="pages-login.html">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Login</span>
        </a>
      </li> -->
            <!-- End Login Page Nav -->

            <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="pages-error-404.html">
          <i class="bi bi-dash-circle"></i>
          <span>Error 404</span>
        </a>
      </li> -->
            <!-- End Error 404 Page Nav -->

            <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="pages-blank.html">
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

</body>

</html>