@extends('layout')
@section('title', 'Dashboard')
@section('subtitle', 'Dashboard')
@section('content')
<!-- <div class=""> -->
<div class="col-lg-8 dashboard">
    <div class="row">
        <!-- Sales Card -->
        @if(auth()->user()->role_id==env('SUPER_ADMIN'))
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
                    <h5 class="card-title">Total organization members</h5>
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
        @endif
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
                    <h5 class="card-title">Total members on leave today </h5>

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
                <div class="card-body">
                    <h5 class="card-title">Reports <span>/Today</span></h5>
                    <!-- Line Chart -->
                    <div id="reportsChart"></div>
                    <script>
                    document.addEventListener("DOMContentLoaded", () => {
                        new ApexCharts(document.querySelector("#reportsChart"), {
                            series: [{
                                name: 'Users',
                                data: [31, 40, 28, 51, 42, 82, 56],
                            }, {
                                name: 'Leaves',
                                data: [11, 32, 45, 32, 34, 52, 41]
                            }, {
                                name: 'Attendance',
                                data: [15, 11, 32, 18, 9, 24, 11]
                            }],
                            chart: {
                                height: 350,
                                type: 'area',
                                toolbar: {
                                    show: false
                                },
                            },
                            markers: {
                                size: 4
                            },
                            colors: ['#4154f1', '#2eca6a', '#ff771d'],
                            fill: {
                                type: "gradient",
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.3,
                                    opacityTo: 0.4,
                                    stops: [0, 90, 100]
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 2
                            },
                            xaxis: {
                                type: 'datetime',
                                categories: ["2018-09-19T00:00:00.000Z", "2018-09-19T01:30:00.000Z",
                                    "2018-09-19T02:30:00.000Z", "2018-09-19T03:30:00.000Z",
                                    "2018-09-19T04:30:00.000Z", "2018-09-19T05:30:00.000Z",
                                    "2018-09-19T06:30:00.000Z"
                                ]
                            },
                            tooltip: {
                                x: {
                                    format: 'dd/MM/yy HH:mm'
                                },
                            }
                        }).render();
                    });
                    </script>
                    <!-- End Line Chart -->
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 dashboard">
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
                <h5 class="card-title"> Teams Leave <span>| Today</span></h5>
                <table class="table table-borderless datatable" id="leavesss">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">From</th>
                            <th scope="col">To</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userLeaves as $data)
                        <tr>
                            <td>{{ $data->first_name}}</td>
                            <td>{{date("d-m-Y", strtotime($data->from));}}</td>
                            <td>{{date("d-m-Y", strtotime($data->to));}}</td>
                            <td>{{$data->type }}</td>
                            @if($data->leave_status == 'approved')
                            <td><span class="badge rounded-pill approved">Approved</span></td>
                            @elseif($data->leave_status == 'decline')
                            <td><span class="badge rounded-pill denied">Decline</span></td>
                            @else
                            <td><span class="badge rounded-pill requested">Requested</span></td>
                            @endif
                        </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- End Recent Sales -->
</div>
<div class="col-lg-4 dashboard">
    <!-- Recent Activity -->
    <div class="card">
        <div class="filter">
            <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <li class="dropdown-header text-start">
                    <h6>Filter</h6>
                </li>
                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">This Month</a></li>
                <li><a class="dropdown-item" href="#">This Year</a></li>
            </ul>
        </div>
        <div class="card-body">
            <h5 class="card-title">Recent Activity <span>| Today</span></h5>
            <div class="activity">
                <div class="activity-item d-flex">
                    <div class="activite-label">32 min</div>
                    <i class='bi bi-circle-fill activity-badge text-success align-self-start'></i>
                    <div class="activity-content">
                        Quia quae rerum <a href="#" class="fw-bold text-dark">explicabo officiis</a> beatae
                    </div>
                </div><!-- End activity item-->
                <div class="activity-item d-flex">
                    <div class="activite-label">56 min</div>
                    <i class='bi bi-circle-fill activity-badge text-danger align-self-start'></i>
                    <div class="activity-content">
                        Voluptatem blanditiis blanditiis eveniet
                    </div>
                </div><!-- End activity item-->
                <div class="activity-item d-flex">
                    <div class="activite-label">2 hrs</div>
                    <i class='bi bi-circle-fill activity-badge text-primary align-self-start'></i>
                    <div class="activity-content">
                        Voluptates corrupti molestias voluptatem
                    </div>
                </div><!-- End activity item-->

                <div class="activity-item d-flex">
                    <div class="activite-label">1 day</div>
                    <i class='bi bi-circle-fill activity-badge text-info align-self-start'></i>
                    <div class="activity-content">
                        Tempore autem saepe <a href="#" class="fw-bold text-dark">occaecati voluptatem</a>
                        tempore
                    </div>
                </div><!-- End activity item-->

                <div class="activity-item d-flex">
                    <div class="activite-label">2 days</div>
                    <i class='bi bi-circle-fill activity-badge text-warning align-self-start'></i>
                    <div class="activity-content">
                        Est sit eum reiciendis exercitationem
                    </div>
                </div><!-- End activity item-->
                <div class="activity-item d-flex">
                    <div class="activite-label">4 weeks</div>
                    <i class='bi bi-circle-fill activity-badge text-muted align-self-start'></i>
                    <div class="activity-content">
                        Dicta dolorem harum nulla eius. Ut quidem quidem sit quas
                    </div>
                </div><!-- End activity item-->
            </div>
        </div>
    </div><!-- End Recent Activity -->
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
        <div class="card-body pb-0">
            <h5 class="card-title"> Birthday/Anniversary</h5>
            <div class="row mb-2">
                @if(count($userBirthdate) !=0)
                @foreach ($userBirthdate as $birthday)
                <div class="col-md-3 mb-2">
                    <img src="{{asset('assets/img/').'/'.$birthday->profile_picture}}" width="50" height="50" alt=""
                        clVass="rounded-circle">
                </div>
                <div class="col-md-9 mt-2 ">
                    <b>{{$birthday->first_name."  ".$birthday->last_name}}</b>
                    <div>
                        @if($currentDate !=$birthday->joining_date)
                        <i class="fa fa-birthday-cake" style="color:red" aria-hidden="true"></i>
                        <span>Birthday</span>
                        @else
                        <i class="fa fa-gift" style="color:green" aria-hidden="true"></i>
                        <span> Anniversary</span>
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
<!-- Recent Sales -->


<div class="modal fade" id="ShowLeaves" tabindex="-1" aria-labelledby="ShowLeaves" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">List of members on leave today</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @foreach ($showLeaves as $data)
                <div class="row leaveUserContainer mt-2 ">
                    <div class="col-md-2">
                        <img src="{{asset('assets/img/').'/'.$data->profile_picture}}" width="50" height="50" alt=""
                            class="rounded-circle">
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

@endsection
@section('js_scripts')
<script>
$(document).ready(function() {

    $('#leavesss').DataTable({
        "order": []
    });

    $("#viewAll").click(function() {

    });
});

function ShowLeavesModal() {

    $('#ShowLeaves').modal('show');
}
</script>

@endsection