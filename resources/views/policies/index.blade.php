@extends('layout')
@section('title', 'Policies')
@section('subtitle', 'Policies')
@section('content')
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openprojectModal()" href="javascript:void(0)">Add
                Policy</button>
            <div class="box-header with-border" id="filter-box">
                <br>
                <!-- filter -->
                <div class="box-header with-border mt-4" id="filter-box">
                    <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="projects">
                            <thead>
                                <tr>
                                    <th>#</id>
                                    <th>Name</th>
                                    <th>Policy Text</th>
                                    <th>Document</th>
                                    @if (auth()->user()->role['name'] == 'Super Admin' || auth()->user()->role['name'] == 'HR Manager')    
                                    <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($policies as $index => $data)
                                <tr>
                                    <td><a href="{{ url('/policy/'.$data->id)}}">#{{ $index + 1 }}</a>
                                    <td>{{($data->name )}}</td>

                                    <td>
                                        @if(strlen($data->policy_text) >= 100)
                                        <span class="description">
                                            @php
                                            $plainTextDescription = strip_tags(htmlspecialchars_decode($data->policy_text));
                                            $limitedDescription = substr($plainTextDescription, 0, 100) . '...';
                                            echo $limitedDescription;
                                            @endphp
                                        </span>
                                        <span class="fullDescription" style="display: none;">
                                         @php
                                            $plainTextDescription = strip_tags(htmlspecialchars_decode($data->policy_text));
                                            echo $plainTextDescription;
                                            @endphp
                                        </span>
                                        <a href="#" class="readMoreLink">Read More</a>
                                        <a href="#" class="readLessLink" style="display: none;">Read Less</a>
                                        @else
                                        {{ strip_tags(htmlspecialchars_decode($data->policy_text ?? '---'));}}
                                        @endif
                                    </td>
                                    <td>
                                    @foreach ($data->PolicyDocuments as $document)
                                        @php
                                        $filePath =  ($document->document_link ?? '#');
                                        $fileUrl = asset('storage/' . $filePath);

                                        $documentType = strtolower($document->document_type);
                                        $iconClass = '';

                                        if ($documentType === 'pdf') {
                                            $iconClass = 'bi-file-earmark-pdf';
                                        } elseif ($documentType === 'doc') {
                                            $iconClass = 'bi-file-earmark-word';
                                        } elseif ($documentType === 'txt') {
                                            $iconClass = 'bi-file-earmark-text';
                                        } else {
                                            $iconClass = 'bi-file-earmark';
                                        }
                                        @endphp

                                        <a href="{{ $fileUrl }}" target="_blank"> 
                                            <i class="{{ $iconClass }}" aria-hidden="true" style="font-size: 25px;"></i>
                                        </a>
                                    @endforeach
                                    </td>
                                    @if (auth()->user()->role['name'] == 'Super Admin' || auth()->user()->role['name'] == 'HR Manager')    
                                    <td> 
                                        @if($data->policy_text != null)
                                            <a href="{{ url('/edit/policy/'.$data->id)}}">
                                            <i style="color:#4154f1;" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i>
                                            </a>
                                        @endif
                                        
                                        <i style="color:#4154f1;" onClick="deletePolicy('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                @endforelse
                        </table>
  

                    </div>
                </div>
                <div>
                </div>
            </div>
        </div>

        <!----Add Projects--->
        <div class="modal modal-lg fade" id="addProjects" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Policy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                <div class="card">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Pills Tabs</h5> -->

                        <!-- Pills Tabs -->
                        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-policy-tab" data-bs-toggle="pill" data-bs-target="#pills-policy" type="button" role="tab" aria-controls="pills-policy" aria-selected="true">Add Policy</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-upload-document-tab" data-bs-toggle="pill" data-bs-target="#pills-upload-document" type="button" role="tab" aria-controls="pills-upload-document" aria-selected="false" tabindex="-1">Upload Document</button>
                        </li>
                        <!-- <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#pills-contact" type="button" role="tab" aria-controls="pills-contact" aria-selected="false" tabindex="-1">Contact</button>
                        </li> -->
                        </ul>
                        <div class="tab-content pt-2" id="myTabContent">
                        <div class="tab-pane fade active show" id="pills-policy" role="tabpanel" aria-labelledby="home-tab">
                            <form id="addProjectsForm" enctype="multipart/form-data" >
                            @csrf
                                <div class="row mb-3">
                                    <label for="name" class="col-sm-3 col-form-label required">Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="name" id="name">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Polict Text</label>
                                    <div class="col-sm-9">
                                        <textarea name="policy_text" class="form-control" id="tinymce_textarea"></textarea>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="" class="col-sm-3 col-form-label required">Save File In Format</label>
                                    <div class="col-sm-9">
                                    <input type="checkbox" class="form-check-input" name="pdf" id="pdf">
                                    <label for="pdf" class="">PDF</label>
                                    <input type="checkbox" class="form-check-input" name="word" id="word">
                                    <label for="word" class="">WORD</label>
                                    <input type="checkbox" class="form-check-input" name="text" id="text">
                                    <label for="text" class="">TEXT</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="text-center">
                                    <button type="submit" class="btn btn-primary" href="javascript:void(0)">Add Policy</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="pills-upload-document" role="tabpanel" aria-labelledby="profile-tab">
                            <form id="addPolicyDocumentsForm" enctype="multipart/form-data" >
                            @csrf
                            <div class="row mb-3">
                                <label for="policy_name" class="col-sm-3 col-form-label required ">Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="policy_name" id="policy_name">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="add_document" class="col-sm-3 col-form-label required ">Document</label>
                                <div class="col-sm-9">
                                    <input type="file" class="form-control" name="add_document[]" id="add_document" multiple />
                                </div>
                            </div>
                        <div class="row mb-3">
                                    <div class="text-center">
                                    <button type="submit" class="btn btn-primary">Add Policy</button>
                                    </div>
                        </div>
                        </form>
                        </div>
                        <!-- <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="contact-tab">
                            Saepe animi et soluta ad odit soluta sunt. Nihil quos omnis animi debitis cumque. Accusantium quibusdam perspiciatis qui qui omnis magnam. Officiis accusamus impedit molestias nostrum veniam. Qui amet ipsum iure. Dignissimos fuga tempore dolor.
                        </div> -->
                        </div><!-- End Pills Tabs -->

                    </div>
                </div>
                    <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
                </div>
            </div>
        </div>
        <!---end Add modal-->

        <div class="modal fade" id="ShowAssign" tabindex="-1" aria-labelledby="ShowAssign" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Project Assign To</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row projectAsssign">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class=" btn
                            btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!---end Add modal-->

        @endsection
        @section('js_scripts')
        <script>
            $(document).ready(function() {
                setTimeout(function() {
                    $('.message').fadeOut("slow");
                }, 2000);
                $('#projects').DataTable({
                    "order": []

                });
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $("#addProjectsForm").submit(function(event) {
                    event.preventDefault();
                    var formData = new FormData(this);
                    // var totalfiles = document.getElementById('add_document').files.length;

                    // for (var index = 0; index < totalfiles; index++) {
                    //     formData.append("add_document[]" + index, document.getElementById('add_document')
                    //         .files[
                    //             index]);
                    // }
                    // console.log(formData);
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('/add/policy')}}",
                        data: formData,
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
                                $("#addProjects").modal('hide');
                                location.reload();
                            }
                        },
                        error: function(data) {}
                    });
                });

                $("#addPolicyDocumentsForm").submit(function(event) {
                    event.preventDefault();
                    var formDataDoc = new FormData(this);
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('/add/policy-document')}}",
                        data: formDataDoc,
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
                                $("#addProjects").modal('hide');
                                location.reload();
                            }
                        },
                        error: function(data) {}
                    });
                });
            });

            function ShowAssignModal(id) {
                $('.projectAsssign').html('');
                $('#ShowAssign').modal('show');
                $.ajax({
                    type: 'POST',
                    url: "{{ url('/project/assign')}}",
                    data: {
                        id: id
                    },
                    cache: false,
                    success: (data) => {
                        if (data.projectAssigns.length > 0) {
                            var html = '';
                            $.each(data.projectAssigns, function(key, assign) {
                                var picture = 'blankImage';
                                if (assign.profile_picture != "") {
                                    picture = assign.profile_picture;
                                }
                                html +=
                                    '<div class="row leaveUserContainer mt-2 "> <div class="col-md-2"><img src="{{asset("assets/img")}}/' +
                                    picture +
                                    '"" width="50" height="50" alt="" class="rounded-circle"></div><div class="col-md-10 "><p><b>' +
                                    assign.first_name + '</b></p></div></div>';
                            })
                            $('.projectAsssign').html(html);
                        } else {
                            $('.projectAsssign').html('<span>No record found <span>');
                        }
                    },
                    error: function(data) {}
                });

            }

            function openprojectModal() {
                document.getElementById("addProjectsForm").reset();
                $('#addProjects').modal('show');
            }

            function editTickets(id) {
                $('#editProjects').modal('show');
                $('#ticket_id').val(id);

                $.ajax({
                    type: "POST",
                    url: "{{ url('/edit/project') }}",
                    data: {
                        id: id
                    },
                    success: function(res) {
                        if (res.projects != null) {
                            $('#edit_title').val(res.projects.title);
                            $('#edit_description').val(res.projects.description);
                            $('#edit_status').val(res.projects.status);
                            $('#edit_comment').val(res.projects.comment);

                            $('#edit_priority').val(res.projects.priority);
                            // var test = "http://127.0.0.1:8000/public/assets/img/" + res.projects.profile_picture;
                            // $("#profile").html(
                            //     '<img src="{{asset("assets/img")}}/' + res.projects.profile_picture +
                            //     '" width = "100" class = "img-fluid img-thumbnail" > '
                            // );

                        }
                        if (res.ticketAssign != null) {
                            $.each(res.ticketAssign, function(key, value) {
                                $('#edit_assign option[value="' + value.user_id + '"]')
                                    .attr(
                                        'selected', 'selected');
                            })
                        }
                    }
                });
            }

            function deletePolicy(id) {
                $('#policy_id').val(id);

                if (confirm("Are you sure You Want To Delete Policy ?") == true) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('/delete/policy') }}",
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

            $('.readMoreLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                description.text(fullDescription.text());
                $(this).hide();
                $(this).siblings('.readLessLink').show();
            });

            $('.readLessLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                var truncatedDescription = fullDescription.text().substring(0, 100) + '...';
                description.text(truncatedDescription);
                $(this).hide();
                $(this).siblings('.readMoreLink').show();
            });

            //TAGS KEY JS
            $('#tech_stacks').tagsinput({
            confirmKeys: [13, 188]
            });

            $('#tech_stacks').on('keypress', function(e){
            if (e.keyCode == 13){
                e.preventDefault();
            };
            });

        </script>
    <!-- <script src="{{ asset('assets/js/bootstrap-tags.js') }}"></script> -->
@endsection