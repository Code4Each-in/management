@extends('layout')
@section('title', 'Profile')
@section('subtitle', 'Profile')
@section('content')


<div class="col-xl-4 profile">
    <div class="card">
        <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">

            <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
            <h2>{{$usersProfile->first_name." ".$usersProfile->last_name}}</h2>
            <h3>Web Designer</h3>
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
                        <div class="col-lg-9 col-md-8">{{$usersProfile->first_name." ".$usersProfile->last_name}}</div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Email</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->email}}</div>
                    </div>

                    <!-- <div class="row">
                        <div class="col-lg-3 col-md-4 label">Salary</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->salary}}</div>
                    </div>  -->

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Address</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->address}}</div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Phone</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->phone}}</div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Joining Date</div>
                        <div class="col-lg-9 col-md-8">{{date("d-m-Y", strtotime($usersProfile->joining_date))}}</div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Birthdate</div>
                        <div class="col-lg-9 col-md-8">{{date("d-m-Y", strtotime($usersProfile->birth_date))}}</div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Role</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->role_id}}</div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Departement</div>
                        <div class="col-lg-9 col-md-8">{{$usersProfile->department_id}}</div>
                    </div>

                </div>

                <div class="tab-pane fade profile-edit pt-3" id="profile-edit">

                    <!-- Profile Edit Form -->
                    <form>
                        <div class="row mb-3">
                            <label for="profileImage" class="col-md-4 col-lg-3 col-form-label">Profile Image</label>
                            <div class="col-md-8 col-lg-9">
                                <img src="assets/img/profile-img.jpg" alt="Profile">
                                <div class="pt-2">
                                    <a href="#" class="btn btn-primary btn-sm" title="Upload new profile image"><i
                                            class="bi bi-upload"></i></a>
                                    <a href="#" class="btn btn-danger btn-sm" title="Remove my profile image"><i
                                            class="bi bi-trash"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Full Name</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="fullName" type="text" class="form-control" id="fullName"
                                    value='{{$usersProfile->first_name." ".$usersProfile->last_name}}'>
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
                            <label for="birthdate" class="col-md-4 col-lg-3 col-form-label">Birthdate</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="birthdate" type="date" class="form-control" id="birthdate"
                                    value="{{$usersProfile->birth_date}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="" class="col-sm-3 col-form-label ">Role</label>
                            <div class="col-sm-9">
                                <select name="role_select" class="form-select" id="role_select">
                                    <option value="">Select Role</option>
                                    @foreach ($roleData as $data)
                                    <option value="{{$data->id}}">
                                        {{$data->name}}
                                    </option>
                                    @endforeach
                                    <option value="">
                                    <option value="">
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="" class="col-sm-3 col-form-label ">Department</label>
                            <div class="col-sm-9">
                                <select name="department_select" class="form-select" id="department_select">
                                    <option value="">Select Department</option>
                                    @foreach ($departmentData as $data)
                                    <option value="{{$data->id}}">
                                        {{$data->name}}
                                    </option>
                                    @endforeach
                                    <option value="">
                                    <option value="">
                                </select>
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
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form><!-- End Profile Edit Form -->
                </div>
                <div>
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
                        <form>
                            <div class="row mb-3">
                                <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Current
                                    Password</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="password" type="password" class="form-control" id="currentPassword">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">New
                                    Password</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="newpassword" type="password" class="form-control" id="newPassword">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="renewPassword" class="col-md-4 col-lg-3 col-form-label">Re-enter New
                                    Password</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="renewpassword" type="password" class="form-control" id="renewPassword">
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </div>
                        </form><!-- End Change Password Form -->
                    </div>
                </div><!-- End Bordered Tabs -->
            </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {

});
</script>
@endsection