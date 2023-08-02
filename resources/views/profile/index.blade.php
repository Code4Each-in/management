@extends('layout')
@section('title', 'Profile')
@section('subtitle', 'Profile')
@section('content')
<style type="text/css">
    .crop_modal img{
        display: block;
        max-width: 100% !important; 
    }
    .preview {
        text-align: center;
        overflow: hidden;
        width: 300px; 
        height: 300px;
        margin: 10px;
        border: 1px solid red;
    }
    .modal-lg{
        max-width: 1000px !important;
    }
</style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.6/cropper.js"></script>

<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-xl-4 profile">
    <div class="card">
        <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
            @if(!empty($usersProfile->profile_picture))
            <img src="{{asset('assets/img/').'/'.$usersProfile->profile_picture}}" id="profile_picture" alt="Profile"
                class="rounded-circle picture js-profile-picture">
            @else
            <img src="{{ asset('assets/img/blankImage.jpg')}}" id="profile_picture" alt="Profile" class="rounded-circle js-profile-picture">
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
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#upload-documents">Documents</button>
                </li>

                <!-- <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Settings</button>
                </li> -->


                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Change
                        Password</button>
                </li>     

            </ul>
            <div class="tab-content pt-2">

                <div class="tab-pane fade show active profile-overview" id="profile-overview">
                    <!-- <h5 class="card-title">About</h5>
                    <p class="small fst-italic">Sunt est soluta temporibus accusantium neque nam maiores cumque
                        temporibus.
                        Tempora libero non est unde veniam est qui dolor. Ut sunt iure rerum quae quisquam autem
                        eveniet
                        perspiciatis odit. Fuga sequi sed ea saepe at unde.</p> -->

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
                        <div class="col-lg-9 col-md-8 detail_full_address"> 
                            @if ($usersProfile->address)
                            {{$usersProfile->address}}  {{$usersProfile->city}} , {{$usersProfile->state}} , {{$usersProfile->zip}}
                            @endif
                        </div>
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

                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">Skills</div>
                        <div class="col-lg-9 col-md-8 detail_skills">
                            @if ($usersProfile->skills)
                            {{$usersProfile->skills}}
                            @else
                            {{'---'}}
                            @endif
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-lg-3 col-md-4 label">T-Shirt Size</div>
                        <div class="col-lg-9 col-md-8 detail_tshirt_size tshirt-text">
                            @if ($usersProfile->tshirt_size)
                            {{$usersProfile->tshirt_size}}
                            @else
                            {{'---'}}
                            @endif
                        </div>
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
                            <img src="{{asset('assets/img/').'/'.$usersProfile->profile_picture}}" class="picture js-profile-picture"
                                id="edit_profile_picture" alt="Profile">
                            @else
                            <img src="{{ asset('assets/img/blankImage.jpg')}}" id="edit_profile_picture" alt="Profile" class="js-profile-picture">
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
                                        <!-- <input type="file" name="image" class="image"> -->

                                        <input type="file" id="edit_profile_input_option" name="edit_profile_input"
                                        class="image_edit_profile_input" style="display:none" />
                                        <a href="#" class="btn btn-primary btn-sm" title="Upload new profile image"
                                            id="edit_profile_button"><i class="bi bi-upload"></i></a>
                                    </form>


                                    <div class="modal fade crop_modal" id="modalCropImage" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="modalLabel">Crop Image</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true"></span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="img-container">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <img id="edit_profile_input" src="">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="preview"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary" id="crop">Crop</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                            
                        </div>
                    </div>
                    <form method="post" id="updateLoginUserProfile">
                        @csrf
                        <div class="alert alert-danger" style="display:none"></div>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                        <input type="hidden" name="user_id" value="{{$usersProfile->id}}" />

                        <!-- <div class="row mb-3">
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
                        <div class="row mb-3">
                            <label for="address" class="col-md-4 col-lg-3 col-form-label">Address</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="address" type="text" class="form-control" id="address"
                                    value="{{$usersProfile->address ?? ''}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="city" class="col-sm-3 col-form-label">City</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="city" id="city"
                                    value="{{$usersProfile->city ?? ''}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="state" class="col-sm-3 col-form-label">State</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="state" id="state"
                                    value="{{$usersProfile->state ?? ''}}">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="zip" class="col-sm-3 col-form-label">Zip</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" name="zip" id="zip"
                                    value="{{$usersProfile->zip?? ''}}">
                            </div>
                        </div> -->

                         <div class="row mb-3">
                            <label for="add_skills" class="col-md-4 col-lg-3 col-form-label">Add Skills</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="skills" type="text" class="form-control" id="add_skills"
                                    value='{{$usersProfile->skills}}' data-role="taginput">
                            </div>
                        </div>
                        


                         <div class="row mb-3">
                         <label for="add_skills" class="col-md-4 col-lg-3 col-form-label">T-Shirt Size</label>
                         <div class="col-md-8 col-lg-9">
                                <select class="form-control" id="tShirtSize" name="tshirt_size">
                                    <option value="" selected disabled>Select Size</option>
                                    <option value="S" {{ $usersProfile->tshirt_size == 'S' ? 'selected' : '' }}>S (Small)</option>
                                    <option value="M" {{ $usersProfile->tshirt_size == 'M' ? 'selected' : '' }}>M ( Medium)</option>
                                    <option value="L" {{ $usersProfile->tshirt_size == 'L' ? 'selected' : '' }} >L (Large)</option>
                                    <option value="XL" {{ $usersProfile->tshirt_size == 'XL' ? 'selected' : '' }}>XL (Extra Large)</option>
                                    <option value="XXL" {{ $usersProfile->tshirt_size == 'XXL' ? 'selected' : '' }}>XXL (Double Extra Large) </option>
                                </select>
                            </div>
                        </div>


                        <div class="alert alert-success message" style="display:none">
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-primary" onClick="updateLoginUserProfile()">Save
                                Changes</button>
                        </div>
                    </form>
                  
                    <!-- End Profile Edit Form -->
                </div>
                <!-- <div> -->
                    <!-- Settings Form -->
                <!-- <div class="tab-pane fade pt-3" id="profile-settings">
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
                    </form>
                </div> -->
                <!-- End settings Form -->
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

                <!-- Documents Tab -->
                <div class="tab-pane fade pt-3" id="upload-documents">
                        <div class="row mb-3">
                            <label for="upload_document" class="col-md-4 col-lg-3 col-form-label">Upload Documents</label>
                            <div class="col-md-8 col-lg-9">
                                <!-- <input type="file" class="form-control" name="upload_document" id="upload_document"> -->
                                <button class="btn btn-primary  mb-4" onClick="openUploadDocumentModal()" href="javascript:void(0)">Upload Document</button>
                            </div>
                        </div>
                        @if ($documents->count() > 0)
                        <div class="row mb-3">
                            <div class="">
                                    Uploaded Documents
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="documents-grid">
                                    @foreach ($documents as $document )
                                    <div class="card doc-card">
                                        <div class="card-body" style="padding: 0;">
                                            <div class="imagePreview">
                                                <a href="#" onclick="window.open('{{asset('assets/img/').'/'.$document->document_link}}', '_blank')">
                                                @php
                                                    $extension = pathinfo($document->document_link, PATHINFO_EXTENSION);
                                                @endphp

                                                @if (in_array($extension, ['pdf', 'doc', 'docx']))
                                                    <!-- Show icons for PDF, DOC, and DOCX file formats -->
                                                    <div class="file-icon">
                                                        @if ($extension === 'pdf')
                                                            <i class="bi bi-file-earmark-pdf" style="padding-left: 38px;font-size: 100px;"></i>
                                                        @elseif (in_array($extension, ['doc', 'docx']))
                                                            <i class="bi bi-file-earmark-word" style="padding-left: 38px;font-size: 100px;"></i>
                                                        @endif
                                                    </div>
                                                @else
                                                    <!-- Show image for other file formats -->
                                                    <img src="{{ asset('assets/img/') . '/' . $document->document_link }}" alt="{{ $document->document_title }}" id="document">
                                                @endif
                                            <a href="#" title="delete Document" onclick="deleteDocument({{ $document->id }})"> <i class="fa fa-times del"></i></a>
                                            </div>
                                            <div class="form-input">
                                                <p>{{ $document->document_title }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div> 
                        @endif
                     
                </div>
                <!-- End Documents Tab -->

                <!--start: Upload Document Modal -->
                <div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="uploadDocumentLabel">Upload Document</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post" id="uploadDocumentForm" action="">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                            <input type="hidden" name="user_id" value="{{$usersProfile->id}}" />
                                <div class="modal-body">
                                    <div class="alert alert-danger" style="display:none"></div>


                                    <div class="row mb-3">
                                        <label for="department_name" class="col-sm-3 col-form-label required">Document</label>
                                        <div class="col-sm-9">
                                            <input type="file" class="form-control" name="upload_document" id="upload_document">
                                        </div>
                                    </div>


                                    <div class="row mb-3">
                                        <label for="document_name" class="col-md-4 col-lg-3 col-form-label required">Document Name</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input type="text" class="form-control" name="document_name" id="document_name">
                                        </div>
                                    </div>


                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" onClick="uploadDocument()"
                                        href="javascript:void(0)">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!--end: Upload Document Modal -->




            </div>
        </div>
    </div>
    @endsection
    @section('js_scripts')
    <script>
    $(document).ready(function() {
        // loadDocuments(); 
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
                    // $('.detail_full_name').html(user_profile_data.first_name + ' ' +
                    //     user_profile_data
                    //     .last_name);
                    $('.detail_tshirt_size').html(user_profile_data.tshirt_size);
                    $('.detail_skills').html(user_profile_data.skills);
                    // $('.detail_full_email').html(user_profile_data.email);
                    // $('.detail_full_address').html(user_profile_data.address + ', ' +
                    //     user_profile_data
                    //     .city +
                    //     ', ' + user_profile_data.state + ', ' + user_profile_data.zip);
                    // $('.detail_full_phone').html(user_profile_data.phone);
                    // $('.detail_full_joining_date').html(moment(user_profile_data.joining_date)
                    //     .format(
                    //         'DD-MM-YYYY'));
                    // $('.detail_full_birth_date').html(moment(user_profile_data.birth_date).format(
                    //     'DD-MM-YYYY'));
                    // $('.profile_name').html(user_profile_data.first_name + ' ' + user_profile_data
                    //     .last_name);
                }
            },
            error: function(data) {
                console.log(data);
            }
        });
    }

    // TRIGGER CHOOSE MEDIA DIALOG ON UPLOAD ICON CLICK
    $('#edit_profile_button').click(function() {
        $('#edit_profile_input_option').trigger('click');
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
                    // console.log(data.path);
                    $("img.js-profile-picture").attr("src", data.path);
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



    // Open Model To Uplod Document
    function openUploadDocumentModal() {
        // $('#department_name').val('');
        $('#uploadDocumentModal').modal('show');
    }

    // Upload Document
    function uploadDocument() {
        var spinner = $('#loader');
        spinner.show();
        var document_name = $('#document_name').val();
        var user_id = $("input[name=user_id]").val();
        var upload_document = $('#upload_document')[0].files[0]; // Retrieve the file object

        var formData = new FormData();
        formData.append('document_name', document_name);
        formData.append('user_id', user_id);
        formData.append('upload_document', upload_document);

        $.ajax({
            type: 'POST',
            url: "{{ url('/profile/upload/document')}}",
            data: formData,
            cache: false,
            contentType: false, // Set content type to false for proper file uploads
            processData: false, // Prevent automatic processing of data
            success: function(data) {
                 // Introduce a delay before hiding the spinner
                setTimeout(function() {
                    spinner.hide();
                if (data.errors) {
                    $('.alert-danger').html('');

                    $.each(data.errors, function(key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<li>' + value + '</li>');
                    });
                } else {
                    $('.alert-danger').html('');
                    $("#uploadDocumentModal").modal('hide');
                    location.reload();
                }
            }, 3000); // Adjust the duration (in milliseconds) as needed
            },
            error: function(data) {
                console.log(data);
            }
        });
    }


    // function loadDocuments() {
    //     // Perform an Ajax request to retrieve the documents
    //     $.ajax({
    //         url: "{{ url('/profile/documents') }}",
    //         type: "GET",
    //         success: function(response) {
    //             var documents = response;
    //             console.log(documents);

    //             // Clear the existing documents grid
    //             $('#documents-grid').empty();

    //             // Loop through the documents and create the display-document elements
    //             $.each(documents, function(index, document) {
    //                 var documentElement = $('<div class="display-document"></div>');
    //                 var actionsElement = $('<div class="document-actions"></div>');
    //                 var crossIconElement = $('<a href="#"><span class="cross-icon">&times;</span></a>');
    //                 var editIconElement = $('<a href="#"><span class="edit-icon"><i class="fa fa-edit"></i></span></a>');
    //                 var imageElement = $('<img src="' + document.image_path + '" alt="" id="document">');
    //                 var nameElement = $('<div class="text-center document-name">' + document.document_name + '</div>');

    //                 documentElement.attr('id', document.id);
    //                 actionsElement.append(crossIconElement);
    //                 actionsElement.append(editIconElement);
    //                 documentElement.append(actionsElement);
    //                 documentElement.append(imageElement);
    //                 documentElement.append(nameElement);

    //                 $('#documents-grid').append(documentElement);
    //             });
    //         },
    //         error: function(xhr, status, error) {
    //             console.log(xhr.responseText);
    //         }
    //     });
    // }


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
                    $("img.js-profile-picture").attr("src", '{{asset("assets/img/blankImage.jpg")}}');

                },
                error: function(data) {}
            });
        }
    });

     //TAGS KEY JS
     $('#add_skills').tagsinput({
            confirmKeys: [13, 188]
            });

            $('#add_skills').on('keypress', function(e){
            if (e.keyCode == 13){
                e.preventDefault();
            };
     });


     var $modal = $('#modalCropImage');
     $('input#edit_profile_input_option[type="file"]').change(function () {
            var ext = this.value.match(/\.(.+)$/)[1];
            switch (ext) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    // $('#edit_profile_button').attr('disabled', false);
                    break;
                default:
                    alert('This is not an allowed file type.');
                    this.value = '';
            }
        });
        var image = document.getElementById('edit_profile_input');
        var cropper;
        var user_id = $("input[name=user_id]").val();    
        $("body").on("change", ".image_edit_profile_input", function(e){
            var files = e.target.files;
            var done = function (url) {
                image.src = url;
                $modal.modal('show');
            };

            var reader;
            var file;
            var url;

            if (files && files.length > 0) {
                file = files[0];

                if (URL) {
                    done(URL.createObjectURL(file));
                } else if (FileReader) {
                    reader = new FileReader();
                    reader.onload = function (e) {
                        done(reader.result);
                    };
                reader.readAsDataURL(file);
                }
            }
        });

        $modal.on('shown.bs.modal', function () {
            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 3,
                preview: '.preview'
            });
        }).on('hidden.bs.modal', function () {
            cropper.destroy();
            cropper = null;
        });

        $("#crop").click(function(){
            canvas = cropper.getCroppedCanvas({
                width: 1200,
                height: 1200,
            });

            canvas.toBlob(function(blob) {
                url = URL.createObjectURL(blob);
                var reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = function() {
                    var base64data = reader.result; 
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: "/update/profile/croped-picture",
                        data: {'_token': $('meta[name="_token"]').attr('content'), 'image': base64data, 'user_id': user_id, },
                        success: function(data){
                            $modal.modal('hide');
                            var baseUrl = window.location.protocol + "//" + window.location.host;
                            var imagePath = "/assets/img/" + data.path;
                            $(".js-profile-picture").attr("src", baseUrl + imagePath);
                        }
                    });
                }
            });
        });


     function deleteDocument(documentId) {
        if (confirm("Are you sure You Want To Delete Document?") == true) {
            $.ajax({
                url: "{{ url('/delete/profile/document')}}",
                data: {
                    documentId: documentId,
                },
                type: 'DELETE',
                success: function(response) {
                    // Handle success response
                    // For example, remove the document item from the DOM
                    $('#document-' + documentId).remove();
                    location.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.log(xhr.responseText);
                }
            });
        }
    }




    </script>
    @endsection