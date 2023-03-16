@extends('layout')
@section('title', 'Profile')
@section('subtitle', 'Profile')
@section('content')


<div class="col-xl-4 profile">
    <div class="card">
        <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
            @if(!empty($usersProfile->profile_picture))
            <img src="{{asset('assets/img/').'/'.$usersProfile->profile_picture}}" id="profile_picture" alt="Profile"
                class="rounded-circle picture">
            @else
            <img src="assets/img/blankImage" alt="Profile" class="rounded-circle">
            @endif
            <h2 class="profile_name">{{$usersProfile->first_name." ".$usersProfile->last_name}}</h2>
            <h3>{{$usersProfile->role->name}}</h3>
            <!-- <div class="social-links mt-2">
                <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
                <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
            </div> -->
        </div>
    </div>
</div>
<div class="col-xl-8 profile">
    <div class="card">
        <div class="card-body pt-3">
            <!-- Bordered Tabs -->
            <ul class="nav nav-tabs nav-tabs-bordered">

                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab"
                        data-bs-target="#profile-overview">Overview</button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit
                        Profile</button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Settings</button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Change
                        Password</button>
                </li>

            </ul>
            <div class="tab-content pt-2">

                <div class="tab-pane fade show active profile-overview" id="profile-overview">
                    <h5 class="card-title">About</h5>
                    <p class="small fst-italic">Sunt est soluta temporibus accusantium neque nam maiores cumque
                        temporibus.
                        Tempora libero non est unde veniam est qui dolor. Ut sunt iure rerum quae quisquam autem
                        eveniet
                        perspiciatis odit. Fuga sequi sed ea saepe at unde.</p>

                    <h5 class="card-title">Profile Details</h5>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label ">Full Name</div>
                        <div class="col-lg-9 col-md-8 detail_full_name">
                            {{$usersProfile->first_name." ".$usersProfile->last_name}}</div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Email</div>
                        <div class="col-lg-9 col-md-8 detail_full_email">{{$usersProfile->email}}</div>
                    </div>

                    <!-- <div class="row">
                        <div class="col-lg-3 col-md-4 label">Salary</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->salary}}</div>
                    </div>  -->

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Address</div>
                        <div class="col-lg-9 col-md-8 detail_full_address">{{$usersProfile->address}}</div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Phone</div>
                        <div class="col-lg-9 col-md-8 detail_full_phone">{{$usersProfile->phone}}</div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Joining Date</div>
                        <div class="col-lg-9 col-md-8 detail_full_joining_date">
                            {{date("d-m-Y", strtotime($usersProfile->joining_date))}}</div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Birthdate</div>
                        <div class="col-lg-9 col-md-8 detail_full_birth_date">
                            {{date("d-m-Y", strtotime($usersProfile->birth_date))}}</div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Role</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->role->name}}</div>
                    </div>
                    @if(isset($usersProfile->department->name))
                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Departement</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->department->name}}</div>
                    </div>
                    @endif
                </div>
                <!-- Profile Edit Form -->
                <div class="tab-pane fade profile-edit pt-3" id="profile-edit">
                    <div class="alert alert-success profile_message" style="display:none">
                    </div>
                    <div class="alert alert-success delete_message" style="display:none">
                    </div>
                    <div class="row mb-3">
                        <label for="profileImage" class="col-md-4 col-lg-3 col-form-label ">Profile Image</label>
                        <div class="col-md-8 col-lg-9 ">
                            @if(!empty($usersProfile->profile_picture))
                            <img src="{{asset('assets/img/').'/'.$usersProfile->profile_picture}}" class="picture"
                                id="edit_profile_picture" alt="Profile">
                            @else
                            <img src="assets/img/blankImage" alt="Profile" class="rounded-circle">
                            @endif

                            <div class="row pt-2 ">
                                @if(!empty($usersProfile->profile_picture))
                                <div class="col-md-1 picture">
                                    <input type="hidden" id="deletePicture" name="profileId"
                                        value="{{$usersProfile->id}}" />

                                    <a href="#" class="btn btn-danger btn-sm" title="Remove my profile image"><i
                                            id="delete_profile" class="bi bi-trash"></i></a>
                                </div>
                                @endif
                                <!-- CHANGE PROFILE FORM -->
                                <div class="col-md-10">
                                    <form id="update_profile_picture">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                        <input type="hidden" name="user_id" value="{{$usersProfile->id}}" />
                                        <input type="file" id="edit_profile_input" name="edit_profile_input"
                                            style="display:none" />
                                        <a href="#" class="btn btn-primary btn-sm" title="Upload new profile image"
                                            id="edit_profile_button"><i class="bi bi-upload"></i></a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form method="post" id="updateLoginUserProfile">
                        @csrf
                        <div class="alert alert-danger" style="display:none"></div>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                        <input type="hidden" name="user_id" value="{{$usersProfile->id}}" />

                        <div class="row mb-3">
                            <label for="first_name" class="col-md-4 col-lg-3 col-form-label">First Name</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="first_name" type="text" class="form-control" id="first_name"
                                    value='{{$usersProfile->first_name}}'>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="last_name" class="col-md-4 col-lg-3 col-form-label">Last Name</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="last_name" type="text" class="form-control" id="last_name"
                                    value='{{$usersProfile->last_name}}'>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-lg-3 col-form-label">Email</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="email" type="text" class="form-control" id="email"
                                    value="{{$usersProfile->email}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="phone" type="text" class="form-control" id="phone"
                                    value="{{$usersProfile->phone}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="joining_date" class="col-md-4 col-lg-3 col-form-label">Joining Date</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="joining_date" type="date" class="form-control" id="joining_date"
                                    value="{{$usersProfile->joining_date}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="birth_date" class="col-md-4 col-lg-3 col-form-label">Birthdate</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="birth_date" type="date" class="form-control" id="birth_date"
                                    value="{{$usersProfile->birth_date}}">
                            </div>
                        </div>
                        @php
                        $addressData=explode(",",$usersProfile->address);
                        @endphp
                        <div class="row mb-3">
                            <label for="address" class="col-md-4 col-lg-3 col-form-label">Address</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="address" type="text" class="form-control" id="address"
                                    value="{{$addressData[0]}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="city" class="col-sm-3 col-form-label">City</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="city" id="city"
                                    value="{{$addressData[1] ?? ' '}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="state" class="col-sm-3 col-form-label">State</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="state" id="state"
                                    value="{{$addressData[2] ?? ' '}}">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="zip" class="col-sm-3 col-form-label">Zip</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" name="zip" id="zip"
                                    value="{{$addressData[3] ?? ' '}}">
                            </div>
                        </div>
                        <div class="alert alert-success message" style="display:none">
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-primary" onClick="updateLoginUserProfile()">Save
                                Changes</button>
                        </div>
                    </form><!-- End Profile Edit Form -->
                </div>
                <!-- <div> -->
                <div class="tab-pane fade pt-3" id="profile-settings">
                    <!-- Settings Form -->
                    <form>
                        <div class="row mb-3">
                            <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Email
                                Notifications</label>
                            <div class="col-md-8 col-lg-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="changesMade" checked>
                                    <label class="form-check-label" for="changesMade">
                                        Changes made to your account
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newProducts" checked>
                                    <label class="form-check-label" for="newProducts">
                                        Information on new products and services
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="proOffers">
                                    <label class="form-check-label" for="proOffers">
                                        Marketing and promo offers
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="securityNotify" checked
                                        disabled>
                                    <label class="form-check-label" for="securityNotify">
                                        Security alerts
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form><!-- End settings Form -->
                </div>
                <div class="tab-pane fade pt-3" id="profile-change-password">
                    <!-- Change Password Form -->
                    <form method="post" id="changeUserPassword">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                        <input type="hidden" name="user_id" value="{{$usersProfile->id}}" />
                        <div class="alert alert-danger password_errors" style="display:none"></div>
                        <div class="alert alert-danger password_error" style="display:none"></div>

                        <div class="alert alert-success password_message" style="display:none">
                        </div>
                        <div class="row mb-3">
                            <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Current
                                Password</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="password" type="password" class="form-control" id="currentPassword">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="new_password" class="col-md-4 col-lg-3 col-form-label">New
                                Password</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="new_password" type="password" class="form-control" id="new_password">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="new_password_confirmation" class="col-md-4 col-lg-3 col-form-label">Re-enter
                                New
                                Password</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="new_password_confirmation" type="password" class="form-control"
                                    id="new_password_confirmation">
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="button" onClick="changeUserPassword()" class="btn btn-primary">Change
                                Password</button>
                        </div>
                    </form><!-- End Change Password Form -->
                </div>
                <!-- </div> -->
            </div>
        </div>
    </div>
    @endsection
    @section('js_scripts')
    <script>
    $(document).ready(function() {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
    // UPDATE LOGIN USER PROFILE
    function updateLoginUserProfile() {
        $.ajax({
            type: 'POST',
            url: "{{ url('/update/profile')}}",
            data: $('#updateLoginUserProfile').serialize(),
            cache: false,
            success: (data) => {
                if (data.errors) {
                    $('.alert-danger').html('');
                    $.each(data.errors, function(key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<li>' + value + '</li>');
                    })
                } else {
                    $('.alert-danger').html('');
                    $('.message').html(data.message);
                    $('.message').show();
                    setTimeout(function() {
                        $('.message').fadeOut("slow");
                    }, 2000);
                    // UPDATE OTHER VALUE ON PAGE
                    var user_profile_data = data.user_profile_data;
                    $('.detail_full_name').html(user_profile_data.first_name + ' ' +
                        user_profile_data
                        .last_name);
                    $('.detail_full_email').html(user_profile_data.email);
                    $('.detail_full_address').html(user_profile_data.address + ', ' +
                        user_profile_data
                        .city +
                        ', ' + user_profile_data.state + ', ' + user_profile_data.zip);
                    $('.detail_full_phone').html(user_profile_data.phone);
                    $('.detail_full_joining_date').html(moment(user_profile_data.joining_date)
                        .format(
                            'DD-MM-YYYY'));
                    $('.detail_full_birth_date').html(moment(user_profile_data.birth_date).format(
                        'DD-MM-YYYY'));
                    $('.profile_name').html(user_profile_data.first_name + ' ' + user_profile_data
                        .last_name);
                }
            },
            error: function(data) {
                console.log(data);
            }
        });
    }

    // TRIGGER CHOOSE MEDIA DIALOG ON UPLOAD ICON CLICK
    $('#edit_profile_button').click(function() {
        $('#edit_profile_input').trigger('click');
    });

    // CHAGE PROFILE IMAGE OF LOGIN USER
    $('form#update_profile_picture').change(function() {
        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: "{{ url('/update/profile/picture')}}",
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
                    // $('.picture').show();
                    $('.alert-danger').html('');
                    $('.profile_message').html(data.message);
                    $('.profile_message').show();
                    setTimeout(function() {
                        $('.profile_message').fadeOut("slow");
                    }, 2000);
                    $("#profile_picture").attr("src", data.path);
                    $("#edit_profile_picture").attr("src", data.path);
                }
            },
            error: function(data) {}
        });
    });

    // CHANGE PASSWORD OF LOGIN USER
    function changeUserPassword() {
        $.ajax({
            type: 'POST',
            url: "{{ url('/change/profile/password')}}",
            data: $('#changeUserPassword').serialize(),
            cache: false,
            success: (data) => {
                if (data.errors) {
                    $('.alert-danger').html('');
                    $.each(data.errors, function(key, value) {
                        $('.password_errors').show();
                        $('.password_errors').append('<li>' + value + '</li>');
                    })
                } else if (data.error) {
                    $('.alert-danger password_error ').html('');
                    $('.password_error').show();
                    $('.password_error').append('<li>' + data.error + '</li>');
                } else {
                    $('.alert-danger').html('');
                    $('.password_message').html(data.message);
                    $('.password_message').show();
                    setTimeout(function() {
                        $('.password_message').fadeOut("slow");
                    }, 2000);
                }
            },
            error: function(data) {
                console.log(data);
            }
        });
    }

    $("#delete_profile").click(function() {
        if (confirm("Are you sure ?") == true) {
            // var hv = $('#h_v').val();

            var profileId = $('#deletePicture').val();
            $.ajax({
                type: 'POST',
                url: "{{ url('/delete/profile/picture')}}",
                data: {
                    profileId: profileId
                },
                cache: false,

                success: (data) => {
                    $('.delete_message').html(data.message);
                    $('.delete_message').show();
                    // $('.picture').hide();
                    setTimeout(function() {
                        $('.delete_message').fadeOut("slow");
                    }, 2000);
                    $("#profile_picture").attr("src", '{{asset("assets/img/blankImage")}}');
                    $("#edit_profile_picture").attr("src", '{{asset("assets/img/blankImage")}}');

                },
                error: function(data) {}
            });
        }
    });
    </script>
    @endsection