@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Projects')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openprojectModal()" href="javascript:void(0)">Add
                Project</button>
            <div class="box-header with-border" id="filter-box">
                <br>
                @if(session()->has('message'))
                <div class="alert alert-success message">
                    {{ session()->get('message') }}
                </div>
                @endif
                <!-- filter -->
                <div class="box-header with-border mt-4" id="filter-box">
                    <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="projects">
                            <thead>
                                <tr>
                                    <th>Project Id</id>
                                    <th>Project Name</th>
                                    <th>Description</th>
                                    <th>Assigns</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $data)
                                <tr>
                                    <td><a href="{{ url('/edit/ticket/'.$data->id)}}">#{{$data->id}}</a>
                                    <td>{{($data->project_name )}}</td>

                                    <td>
                                        @if(strlen($data->description) >= 100)
                                        <span class="description">
                                            @php
                                            $plainTextDescription = strip_tags(htmlspecialchars_decode($data->description));
                                            $limitedDescription = substr($plainTextDescription, 0, 100) . '...';
                                            echo $limitedDescription;
                                            @endphp
                                        </span>
                                        <span class="fullDescription" style="display: none;">
                                         @php
                                            $plainTextDescription = strip_tags(htmlspecialchars_decode($data->description));
                                            echo $plainTextDescription;
                                            @endphp
                                        </span>
                                        <a href="#" class="readMoreLink">Read More</a>
                                        <a href="#" class="readLessLink" style="display: none;">Read Less</a>
                                        @else
                                        {{ strip_tags(htmlspecialchars_decode($data->description));}}
                                        @endif
                                    </td>

                                    <td> @if (count($data->ticketassign)<= 5) @foreach ($data->ticketassign as $assign)
                                            @if (!empty($assign->profile_picture))
                                            <img src="{{asset('assets/img/').'/'.$assign->profile_picture}}" width="20" height="20" class="rounded-circle " alt="">
                                            @else <img src="assets/img/blankImage" alt="Profile" width="20" height="20" class="rounded-circle">
                                            @endif
                                            @endforeach
                                            @endif

                                            @if(count($data->ticketassign)!=0)
                                            <a class="text-primary small pt-1 pointer text-right" onClick="ShowAssignModal('{{$data->id}}')" id="view"><i class="bi-person-lines-fill"></i>
                                            </a>
                                            @else
                                            <span>NA</span>
                                            @endif
                                    </td>
                                    <td>{{ $data->start_date}}</td>
                                    <td>{{ $data->end_date}}</td>
                                    <td>
                                    @if($data->status == 'not_started')
                                    <span class="badge rounded-pill bg-primary">Not Started</span>
                                    @elseif($data->status == 'active')
                                    <span class="badge rounded-pill bg-info text-dark">Active</span>
                                    @elseif($data->status == 'deactivated')
                                    <span class="badge bg-dark text-dark">Deactivated</span>
                                    @else
                                    <span class="badge rounded-pill  bg-success">Completed</span>
                                    @endif
                                    <!-- <p class="small mt-1" style="font-size: 11px;font-weight:600; margin-left:6px;">  By: {{ $projectstatusData->first_name ?? '' }} </p> -->
                                    </td>
                                    <td> 
                                        <a href="{{ url('/edit/project/'.$data->id)}}">
                                        <i style="color:#4154f1;" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i>
                                        </a>
                                        <i style="color:#4154f1;" onClick="deleteTickets('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                    </td>
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

        <!----Add Tickets--->
        <div class="modal fade" id="addProjects" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addProjectsForm" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label required">Project Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="project_name" id="project_name">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="" class="col-sm-3 col-form-label required ">Manager</label>     
                                <div class="col-sm-9">
                                <select name="manager[]" class="form-select form-control" id="manager">
                                        <option value="" disabled>Select User</option>
                                         @foreach ($managers as $data)
                                        <option value="{{$data->id}}">
                                            {{$data->first_name}}
                                        </option>
                                        @endforeach
                                    </select>

                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label ">Live Url</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="live_url" id="live_url">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label ">Dev Url</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="dev_url" id="dev_url">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label ">Git Repository</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="git_repo" id="git_repo">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label">Tech Stacks</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="tech_stacks" id="tech_stacks">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                                <div class="col-sm-9">
                                    <textarea name="description" class="form-control" id="tinymce_textarea"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="start_date" class="col-sm-3 col-form-label required">Start Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="end_date" class="col-sm-3 col-form-label required">End Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Credentials</label>
                                <div class="col-sm-9">
                                    <textarea name="credentials" class="form-control" id="tinymce_textarea"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select" id="status">
                                        <option value="not_started">Not Started</option>
                                        <option value="active">Active</option>
                                        <option value="deactivated">Deactivated</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="document" class="col-sm-3 col-form-label ">Document</label>
                                <div class="col-sm-9">
                                    <input type="file" class="form-control" name="add_document[]" id="add_document" multiple />
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" href="javascript:void(0)">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!---end Add modal-->

        <!----Edit Tickets--->
        <div class="modal fade" id="editTickets" tabindex="-1" aria-labelledby="projects" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Tickets</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" id="editTicketsForm" action="">
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="edit_title" class="col-sm-3 col-form-label required">Title</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="title" id="edit_title">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="edit_description" class="col-sm-3 col-form-label required">Description</label>
                                <div class="col-sm-9">
                                    <textarea name="description" class="form-control" id="edit_description"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="edit_assign" class="col-sm-3 col-form-label  ">Assign</label>
                                <div class="col-sm-9">
                                  
                                </div>
                            </div>

                            @csrf
                            <div class="row mb-3">
                                <label for="edit_status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select" id="edit_status">
                                        <option value="">To do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="ready">Ready</option>
                                        <option value="complete">
                                            Complete </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="edit_priority" class="col-sm-3 col-form-label required">Priority</label>
                                <div class="col-sm-9">
                                    <select name="priority" class="form-select" id="edit_priority">
                                        <option value="">Priority</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Low</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="edit_document" class="col-sm-3 col-form-label">Document</label>
                                <div class="col-sm-9">
                                    <input type="file" class="form-control" name="upload[]" id="edit_document" multiple>
                                </div>
                            </div>
                            <input type="hidden" class="form-control" name="ticket_id" id="ticket_id" value="">

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" href="javascript:void(0)">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="ShowAssign" tabindex="-1" aria-labelledby="ShowAssign" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ticket Assign To</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row ticketAsssign">
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
                        url: "{{ url('/add/projects')}}",
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

                $('#editTicketsForm').submit(function(event) {
                    event.preventDefault();
                    var formData = new FormData(this);

                    $.ajax({
                        type: "POST",
                        url: "{{ url('/update/projects') }}",
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
                                $("#editTickets").modal('hide');
                                location.reload();
                            }
                        }
                    });
                });
            });

            function ShowAssignModal(id) {
                $('.ticketAsssign').html('');
                $('#ShowAssign').modal('show');
                $.ajax({
                    type: 'POST',
                    url: "{{ url('/ticket/assign')}}",
                    data: {
                        id: id
                    },
                    cache: false,
                    success: (data) => {
                        if (data.ticketAssigns.length > 0) {
                            var html = '';
                            $.each(data.ticketAssigns, function(key, assign) {
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
                            $('.ticketAsssign').html(html);
                        } else {
                            $('.ticketAsssign').html('<span>No record found <span>');
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
                $('#editTickets').modal('show');
                $('#ticket_id').val(id);

                $.ajax({
                    type: "POST",
                    url: "{{ url('/edit/ticket') }}",
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

            function deleteTickets(id) {
                $('#ticket_id').val(id);
                // var id = $('#department_name').val();

                if (confirm("Are you sure ?") == true) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('/delete/projects') }}",
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
        </script>


        @endsection