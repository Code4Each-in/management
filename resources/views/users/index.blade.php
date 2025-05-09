@extends('layout')
@section('title', 'Users')
@section('subtitle', 'Users')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openusersModal()" href="javascript:void(0)" style="background-color: #4154f1;">Add User</button>

            <form id="filter-data" method="GET" action="{{ route('users.index') }}">
                <div class="row mt-3 mx-auto">
                    <div class="col-md-6 filtersContainer d-flex p-0">
                        <div style="margin-right:20px;">

                            <input type="checkbox" class="form-check-input" name="all_users" id="all_users"
                                {{ $allUsersFilter == 'on' ? 'checked' : '' }}>
                            <label for="all_users">All Users</label>
                        </div>  

                    </div>
                </div>
            </form>


            <div class="box-header with-border" id="filter-box">
                <br>
                <div class="box-body " style="margin-bottom: 5%">
                    <div class="table-responsive">
                        <table class="table table-striped table-borderless dashboard" id="users_table">
                            <thead>
                                <tr>
                                    <th>Employee Id</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <!-- <th>Salary</th> -->
                                    <th>Role</th>
                                    <th>Designation</th>
                                    <th>Department</th>                                
                                    <th>Address</th>
                                    <th>Phone</th>
                                    <th>Gender</th>
                                    @if (auth()->user()->role->name == "Super Admin" || auth()->user()->role->name == "HR Manager")
                                    <th>Documents</th>
                                    <th>Leaves</th>
                                    @endif
                                    <th>Tshirt Size</th>
                                    <th>Active</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usersData as $data)
                                <tr>
                                    <td>{{ $data->employee_id ?? '---' }}</td>
                                    <td>{{ $data->first_name ?? '---' }}</td>
                                    <td>{{ $data->last_name ?? '---' }}</td>
                                    <td>{{ $data->email ?? '---$_GET' }}</td>
                                    <!-- <td>{{ $data->salary }}</td> -->
                                    <td>{{$data->role->name ?? '---'}}</td>
                                    <td>{{ $data->designation ?: '---' }}</td>
                                    <td>{{$data->department->name ?? '---'}}</td>                              
                                    <td>{{ $data->address }} , {{ $data->city }},{{ $data->state }},{{ $data->zip }}</td>
                                    <td>{{ $data->phone ?? '---' }}</td>
                                    <td>{{ $data->gender ?? '---' }}</td>
                                    @if (auth()->user()->role->name == "Super Admin" || auth()->user()->role->name == "HR Manager")
                                    <td>
                                        @if (count($data->documents) > 0)
                                        <a href="{{ route('users.documents.show',$data->id)}}">Show Documents</a>
                                        @else
                                        <p>No Documents</p>
                                        @endif

                                    </td>
                                    <td>
                                        @php
                                        // Calculate the total leaves count for a specific user (data->id)
                                        $totalLeavesCount = $totalLeaves->where('id', $data->id)->sum('leaves_count');

                                        // Calculate the approved leaves count for the same user (data->id)
                                        $approvedLeavesCount = $approvedLeaves->where('id', $data->id)->sum('leave_day_count');

                                        $joiningDate = $data->joining_date;
                                        // Calculate the date 3 months ago from the current date
                                        $threeMonthsAgo = now()->subMonths(3);

                                        @endphp
                                        @if ($joiningDate >= $threeMonthsAgo)
                                        @if ($approvedLeavesCount > 0)
                                        <span class="text-danger" title="User leaves exceeded from total available leaves">{{$approvedLeavesCount}}</span>
                                        @else
                                        <span title="Your leaves">{{$approvedLeavesCount}}</span>
                                        @endif
                                        @else
                                        @if ($approvedLeavesCount > $totalLeavesCount)
                                        <span class="text-danger" title="User leaves exceeded from total available leaves">{{$approvedLeavesCount}}</span>
                                        @else
                                        <span title="Your leaves">{{$approvedLeavesCount}}</span>
                                        @endif
                                        @endif
                                        / @if ($joiningDate < $threeMonthsAgo)
                                            <span title="Total Leaves">{{$totalLeavesCount}}</span>
                                            @else
                                            <span title="Total Leaves">0</span>
                                            @endif
                                    </td>
                                    @endif
                                    <td>{{ $data->tshirt_size ?? '---' }}</td>
                                    <td>
                                        <div class="form-group form-check active_user">
                                            @if ($data->assignedDevices > 0)
                                            <input type="checkbox" onClick="ShowDeviceRecoveryMessage(this)" data-user-id="{{ $data->id }}"
                                                class="form-check-input" id="{{ 'active_user_'.$data->id }}"
                                                {{ $data->status == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ 'active_user_'.$data->id }}"></label>
                                            @else
                                            <input type="checkbox" onClick="Showdata(this)" data-user-id="{{ $data->id}}"
                                                class="form-check-input" id="{{'active_user_'.$data->id}}"
                                                {{$data->status == 1 ? 'checked' : ''}}>
                                            <label class="form-check-label" for="active_user"></label>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="active_user_data">
                                            <a href="{{ route('users.view', ['id' => $data->id]) }}" title="View User Details" style="padding-right: 11px; ">
                                                <i class="fa-solid fa-eye" style="color:#4154f1; margin-left: 10px; cursor: pointer;"></i>
                                            </a>
                                            <i style="color:#4154f1;padding-right: 25px;" onClick="editUsers('{{ $data->id }}')"
                                                href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>

                                            <i style="color:#4154f1;" onClick="deleteUsers('{{ $data->id }}')"
                                                href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                @endforelse
                            </tbody>
                      </table>
</div>
                  </div>
              </div>
            </div>
    </div>
</div>

<!--start: Add users Modal -->
<div class="modal fade" id="addUsers" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width:505px;">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUsersForm">
                @csrf
                <div class=" modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    <div class="row mb-3">
                        <label for="email" class="col-sm-3 col-form-label required">Email</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="email" id="email">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="password" class="col-sm-3 col-form-label required">Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" name="password" id="password">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="password_confirmation" class="col-sm-3 col-form-label required"> Confirm
                            Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control mb-6" name="password_confirmation"
                                id="password_confirmation">
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3 mt-4">
                        <label for="user_name" class="col-sm-3 col-form-label required">First Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="user_name" id="user_name">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="last_name" class="col-sm-3 col-form-label required">Last Name </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="last_name" id="last_name">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label required">Phone</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" name="phone" id="phone">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="gender" class="col-sm-3 col-form-label required">Gender</label>
                        <div class="col-sm-9">
                            <select id="gender" name="gender" class="form-select form-control">
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="joining_date" class="col-sm-3 col-form-label required">Joining date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="joining_date" id="joining_date">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="birth_date" class="col-sm-3 col-form-label required">Birth date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="birth_date" id="birth_date">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="profile_picture" class="col-sm-3 col-form-label required">Profile Picture</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="file" id="profile_picture" name="profile_picture">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="salaried" class="col-sm-3 col-form-label">If salaried</label>
                        <div class="col-sm-2 mt-1">
                            <input type="checkbox" class="form-check-input" name="salaried" id="salaried">
                        </div>
                        <div class="col-sm-7">
                            <div style="display:none;" class="input-group addsalary">
                                <span class="input-group-text"></span>
                                <input type="number" class="form-control" name="addsalary" id="addsalary">
                                <span class="input-group-text">.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 mt-2">
                        <label for="employee_id" class="col-sm-3 col-form-label required">Employee Id</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="employee_id" id="employee_id">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label required">Role</label>
                        <div class="col-sm-9">
                            <select name="role_select" class="form-select" id="role_select">
                                <option value="">Select Role</option>
                                @foreach ($roleData as $data)
                                <option value="{{$data->id}}">
                                    {{$data->name}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="designation" class="col-sm-3 col-form-label required">Designation</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="designation" id="designation">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label required">Department</label>
                        <div class="col-sm-9">
                            <select name="department_select" class="form-select" id="department_select">
                                <option value="">Select Department</option>
                                @foreach ($departmentData as $data)
                                <option value="{{$data->id}}">
                                    {{$data->name}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label">Select Manager</label>
                        <div class="col-sm-9">
                            <select name="manager_select[]" class="form-control select" id="manager_select" multiple>
                                <option value="" disabled>-- Select Manager --</option>
                                @foreach ($users_Data as $data)
                                <option value="{{$data->id}}">
                                    {{$data->first_name}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="address" class="col-sm-3 col-form-label required">Address</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="address" id="address"
                                placeholder="Apartment, studio, or floor">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="city" class="col-sm-3 col-form-label required">City</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="city" id="city">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="state" class="col-sm-3 col-form-label required">State</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="state" id="state">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="zip" class="col-sm-3 col-form-label required">Zip</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="zip" id="zip">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<!--end: Add department Modal -->

<div class="modal fade" id="editUsers" tabindex="-1" aria-labelledby="editUsersLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width:505px;">
            <div class="modal-header">
                <h5 class="modal-title" id="editUsersLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editUsersForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    <div class="row mb-4">
                        <label for="email" class="col-sm-3 col-form-label required">Email</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_email" id="edit_email">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="password" class="col-sm-3 col-form-label">Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" name="edit_password" id="edit_password">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="edit_password_confirmation" class="col-sm-3 col-form-label"> Confirm
                            Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control mb-6" name="edit_password_confirmation"
                                id="edit_password_confirmation">
                        </div>
                    </div>
                    <div class="row mb-3 mt-2">
                        <label for="edit_username" class="col-sm-3 col-form-label required">First Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_username" id="edit_username">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="user_name" class="col-sm-3 col-form-label required">Last Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_lastname" id="edit_lastname">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="edit_gender" class="col-sm-3 col-form-label required">Gender</label>
                        <div class="col-sm-9">
                            <select id="edit_gender" name="gender" class="form-select form-control">
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label required">Phone</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" name="edit_phone" id="edit_phone">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="edit_joining_date" class="col-sm-3 col-form-label required">Joining Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="edit_joining_date" id="edit_joining_date">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="edit_birthdate" class="col-sm-3 col-form-label required">Birth Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="edit_birthdate" id="edit_birthdate">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="edit_profile_picture" class="col-sm-3 col-form-label ">Profile
                            Picture</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="file" id="edit_profile_picture"
                                name="edit_profile_picture">
                            <div class="mt-2" id="profile">

                            </div>
                        </div>

                    </div>

                    <div class="row mb-3">
                        <label for="salaried" class="col-sm-3 col-form-label">If salaried</label>
                        <div class="col-sm-2 mt-1">
                            <input type="checkbox" class="form-check-input" name="edit_salaried" id="edit_salaried">
                        </div>
                        <div class="col-sm-7">
                            <div style="display:none;" class="input-group edit_salary">
                                <span class="input-group-text"></span>
                                <input type="number" class="form-control" name="edit_salary" id="edit_salary">
                                <span class="input-group-text">.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 mt-2">
                        <label for="edit_employee_id" class="col-sm-3 col-form-label required">Employee Id</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_employee_id" id="edit_employee_id">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label required">Role</label>
                        <div class="col-sm-9">
                            <select name="role_select" class="form-select" id="role_select">
                                <option value="">Select Role</option>
                                @foreach ($roleData as $data)
                                <option value="{{$data->id}}">
                                    {{$data->name}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="designation" class="col-sm-3 col-form-label required">Designation</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="designation" id="edit_designation">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label required">Department</label>
                        <div class="col-sm-9">
                            <select name="department_select" class="form-select" control id="department_select">
                                <option value="">Select Department</option>
                                @foreach ($departmentData as $data)
                                <option value="{{$data->id}}">
                                    {{$data->name}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label">Select Manager</label>
                        <div class="col-sm-9">
                            <select name="manager_select[]" class="form-control select" id="edit_manager_select"
                                multiple>
                                <option value="" disabled>Select Manager</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="address" class="col-sm-3 col-form-label required">Address</label>
                        <div class="col-sm-9">
                            <textarea name="address" class="form-control" id="edit_address"></textarea>
                        </div>
                    </div>
                   
                    <div class="row mb-3">
                        <label for="edit_city" class="col-sm-3 col-form-label required">City</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="edit_city" id="edit_city">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="edit_state" class="col-sm-3 col-form-label required">State</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="edit_state" id="edit_state">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="edit_zip" class="col-sm-3 col-form-label required">Zip</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="edit_zip" id="edit_zip">
                        </div>
                    </div>

                    <input type="hidden" class="form-control" name="users_id" id="users_id" value="">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
    $(document).ready(function() {
        setTimeout(function() {
            $('.message').fadeOut("slow");
        }, 2000);


        $('#users_table').DataTable({
            "order": []

        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#salaried').on('click', function(e) {
            if (e.target.checked == true) {

                $('.addsalary').show();
            } else {
                $('.addsalary').hide();
                $('addsalary').val('');
            }
        });
        $('#edit_salaried').on('click', function(e) {
            if (e.target.checked == true) {

                $('.edit_salary').show();
            } else {
                $('.edit_salary').hide();
                $('.edit_salary').val('');
            }
        });

        $('#addUsersForm').submit(function(event) {
            event.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: "{{ url('/add/users')}}",
                data: formData,
                cache: false,
                processData: false,
                contentType: false,
                success: (data) => {
                    if (data.errors) {
                        $('.alert-danger').html('');
                        $.each(data.errors, function(key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<li>' + value + '</li>');
                        })
                    } else {
                        $('.alert-danger').html('');
                        $("#addUsers").modal('hide');
                        location.reload();
                    }
                },
                error: function(data) {}
            });

        });

        $('#editUsersForm').submit(function(event) {

            event.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                type: "POST",
                url: "{{ url('/update/users') }}",
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.errors) {
                        $('.alert-danger').html('');
                        $.each(res.errors, function(key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<li>' + value + '</li>');
                        })
                    } else {
                        $('.alert-danger').html('');
                        $("#editUsers").modal('hide');
                        location.reload();
                    }
                }
            });
        });
    });

    function Showdata(ele) {
        var dataId = $(ele).attr("data-user-id");

        var status = 0;
        if ($("#active_user_" + dataId).prop('checked') == true) {
            status = 1;
        }
        $.ajax({
            type: 'POST',
            url: "{{ url('/update/users/status')}}",
            data: {
                dataId: dataId,
                status: status,
            },
            cache: false,
            success: (data) => {
                if (data.status == 200) {
                    location.reload();
                }
            },
            error: function(data) {}
        });

    }

    function openusersModal() {
        $('.alert-danger').html('');
        $('#first_name').val('');
        $('#addUsers').modal('show');
    }

    function editUsers(id) {
        $('.alert-danger').html('');
        $('#users_id').val(id);
        $.ajax({
            type: "POST",
            url: "{{ url('/edit/users') }}",
            data: {
                id: id
            },
            dataType: 'json',
            success: (res) => {
                if (res.users != null) {
                    $('#editUsers').modal('show');
                    $('#edit_username').val(res.users.first_name);
                    $('#edit_lastname').val(res.users.last_name);
                    $('#edit_gender').val(res.users.gender);
                    $('#edit_email').val(res.users.email);
                    // $('#edit_password').val(res.users.edit_password);
                    $('#edit_phone').val(res.users.phone);
                    $('#edit_joining_date').val(res.users.joining_date);
                    var Profile = "http://127.0.0.1:8000/public/assets/img/" + res.users.profile_picture;
                    $("#profile").html(
                        '<img src="{{asset("assets/img")}}/' + res.users.profile_picture +
                        '" width = "100" class = "img-fluid img-thumbnail" > '
                    );
                    $('#edit_birthdate').val(res.users.birth_date);
                    if (res.users.salary != null) {
                        $("#edit_salaried").prop('checked', true);
                        $('.edit_salary').show();
                        $('#edit_salary').val(res.users.salary);
                    }
                    $('#edit_employee_id').val(res.users.employee_id);
                    $('#edit_designation').val(res.users.designation);
                    $('#edit_address').val(res.users.address);
                    $('#edit_city').val(res.users.city);
                    $('#edit_state').val(res.users.state);
                    $('#edit_zip').val(res.users.zip);

                    $('#edit_password').val('');
                    $('#role_select option[value="' + res.users.role_id + '"]').attr('selected', 'selected');
                    $('#department_select option[value="' + res.users.department_id + '"]').attr('selected',
                        'selected');
                }
                $('#edit_manager_select').find('option').remove();
                if (res.managerSelectOptions != null) {
                    $.each(res.managerSelectOptions, function(key, value) {
                        $('#edit_manager_select').append('<option value="' + value.id + '">' + value
                            .first_name + " " + value.last_name + '</option>');
                    });
                }
                if (res.Managers != null) {
                    $.each(res.Managers, function(key, value) {
                        $('#edit_manager_select option[value="' + value.parent_user_id + '"]').attr(
                            'selected', 'selected');
                    })
                }
            }
        });
    }

    function deleteUsers(id) {
        if (confirm("Are you sure ?") == true) {
            $.ajax({
                type: "DELETE",
                url: "{{ url('/delete/users') }}",
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(res) {
                    location.reload();
                }
            });
        }
    }

    // Event listener for checkbox changes
    $("#filter-data input:checkbox").change(function() {
        // Submit the form
        $("#filter-data").submit();
    });


    function ShowDeviceRecoveryMessage(checkbox) {
        if (!checkbox.checked) {
            alert("Please recover assigned devices before Deactivating this User.");
            // Provide link to assigned device section with ID
            window.location.href = "/assigned-devices";
        }
    }
</script>
@endsection
