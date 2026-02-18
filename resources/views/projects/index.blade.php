<?php
use App\Models\Projects;?>

@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Projects')
@section('content')
<div class="row">
    <button class="btn btn-primary mt-3 project mb-3" onClick="openprojectModal()" href="javascript:void(0)" style="background-color: #4154f1;">Add
        Project</button>
</div>
<div class="row">
        <div class="sprint-section">
            
                <!-- filter -->
                <div class="sprint-header production">
                    <div class="section-left">
                      <div class="section-icon bg-production" style="background-color: #297bab;">P</div>
                      <div class="section-title" style="color: #297bab;">Total Projects</div>
                      <div class="section-title">• {{ $projectCount ?? 0 }} Projects</div>
                    </div>
                  </div>
                <div class="box-header with-border" id="filter-box">
                    <div class="box-body">
                        <div class="table-responsive">
                    <table class="styled-sprint-table sprint-table" id="projects" style="text-align: center;">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Assign</th>
                                    <th>Client Name</th>
                                    <th>Start Date</th>
                                    @if(auth()->user()->role_id != 6)
                                    <th>End Date</th>
                                    @endif
                                    <th>Status</th>
                                    <th>Active|Inactive|Completed</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $data)
                                <tr class="pointer" onclick="if (!event.target.closest('.actions-cell')) window.open('{{ url('/project/'.$data->id) }}', '_blank');">
                                    <td>{{($data->project_name )}}</td>
                                    <td class="actions-cell"> @if (count($data->projectassign)<= 5) @foreach ($data->projectassign as $assign)
                                            @if (!empty($assign->profile_picture))
                                            <img src="{{asset('assets/img/').'/'.$assign->profile_picture}}" width="20" height="20" class="rounded-circle " alt="">
                                            @else <img src="assets/img/blankImage" alt="Profile" width="20" height="20" class="rounded-circle">
                                            @endif
                                            @endforeach
                                            @endif

                                            @if(count($data->projectassign)!=0)
                                            <a class="text-primary small pt-1 pointer text-right" onClick="ShowAssignModal('{{$data->id}}')" id="view"><i class="bi-person-lines-fill"></i>
                                            </a>
                                            @else
                                            <span>NA</span>
                                            @endif
                                    </td>
                                    <!-- <td>{{ $data->client_name}}</td> -->
                                    <td>
                                        {{
                                            $data->clients->isNotEmpty()
                                                ? $data->clients->pluck('name')->join(', ')
                                                : ($data->client->name ?? '---')
                                        }}
                                    </td>

                                    <td>{{ $data->start_date}}</td>
                                    @if(auth()->user()->role_id != 6)
                                    <td>{{ $data->end_date ?? '---'}}</td>
                                    @endif
                                    <td>
                                    @if($data->status == 'not_started')
                                    <span class="badge rounded-pill bg-primary">Not Started</span>
                                    @elseif($data->status == 'active')
                                    <span class="badge rounded-pill bg-info ">Active</span>
                                    @elseif($data->status == 'deactivated')
                                    <span class="badge rounded-pill bg-danger text-mute">Deactivated</span>
                                    @else
                                    <span class="badge rounded-pill  bg-success">Completed</span>
                                    @endif
                                    <!-- <p class="small mt-1" style="font-size: 11px;font-weight:600; margin-left:6px;">  By: {{ $projectstatusData->first_name ?? '' }} </p> -->
                                    </td>
                                   <td style="text-align: center;">
                                        <div class="d-flex justify-content-center status-group">
                                            <div class="status-box text-white" title="Active" style="background-color: #2a9d8f;">
                                                {{ $data->active_sprints }}
                                            </div>
                                            <div class="status-box text-white" title="Inactive" style="background-color: #e76f51;">
                                                {{ $data->inactive_sprints }}
                                            </div>
                                            <div class="status-box text-white" title="Completed" style="background-color: #264653;">
                                                {{ $data->completed_sprints }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="actions-cell"> 
                                         <a href="{{ url('/project/'.$data->id)}}">
                                            <i class="fa fa-eye fa-fw pointer"></i>
                                        </a>
                                        <a href="{{ url('/edit/project/'.$data->id)}}">
                                        <i style="color:#4154f1;" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i>
                                        </a>
                                        @if (auth()->user()->role['name'] == 'Super Admin')
                                        <i style="color:#4154f1;" onClick="deleteProjects('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                @endforelse
                                @if($projects->isEmpty())
                                <tr>
                                    <td colspan="{{ auth()->user()->role['name'] == 'Super Admin' || auth()->user()->role['name'] == 'HR Manager' ? 9 : 8 }}" class="text-center">
                                        No records to show
                                    </td>
                                </tr>
                                @endif
                        </table>
                        </div>

                    </div>
                </div>
                <div>
                </div>
        </div>
</div>
        <!----Add Projects--->
        <div class="modal fade" id="addProjects" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addProjectsForm" enctype="multipart/form-data" >
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label required">Project Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="project_name" id="project_name">
                                </div>
                            </div>

                            @if(auth()->user()->role_id != 6)
                            <div class="row mb-3">
                                <label for="client_id" class="col-sm-3 col-form-label required">Client Name</label>
                                <div class="col-sm-9">
                                    <select name="client_id[]" class="form-select form-control" id="client_id" multiple>
                                        <!-- <option value="" disabled selected>Select Clients</option> -->
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>  
                                </div>
                            </div>
                            @endif

                            @if(auth()->user()->role_id != 6)
                            <div class="row mb-3">
                                <label for="" class="col-sm-3 col-form-label required">Assign To</label>     
                                <div class="col-sm-9">
                                <select class="form-select form-control" id="user" name="assign_to[]" data-placeholder="Select User" multiple>
                                <option value="" disabled>Select User</option>
                                        @foreach ($users ?? '' as $data)
                                            <option value="{{$data->id}}">
                                                {{$data->first_name}} - {{$data->designation}}
                                            </option>
                                        @endforeach
                                </select>
                                </div>
                            </div>
                            @endif
                            
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
                                    <input type="text" class="form-control" name="tech_stacks" id="tech_stacks" data-role="taginput">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label">Description</label>
                                <div class="col-sm-9">
                                    <!-- Quill Toolbar -->
                                    <div id="toolbar-container">
                                        <span class="ql-formats">
                                            <select class="ql-font"></select>
                                            <select class="ql-size"></select>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-bold"></button>
                                            <button class="ql-italic"></button>
                                            <button class="ql-underline"></button>
                                            <button class="ql-strike"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <select class="ql-color"></select>
                                            <select class="ql-background"></select>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-script" value="sub"></button>
                                            <button class="ql-script" value="super"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-header" value="1"></button>
                                            <button class="ql-header" value="2"></button>
                                            <button class="ql-blockquote"></button>
                                            <button class="ql-code-block"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-list" value="ordered"></button>
                                            <button class="ql-list" value="bullet"></button>
                                            <button class="ql-indent" value="-1"></button>
                                            <button class="ql-indent" value="+1"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-direction" value="rtl"></button>
                                            <select class="ql-align"></select>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-link"></button>
                                            <button class="ql-image"></button>
                                            <button class="ql-video"></button>
                                            <button class="ql-formula"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-clean"></button>
                                        </span>
                                    </div>
                            
                                    <div id="editor" style="height: 300px;">{!! old('description') !!}</div>
                                    <input type="hidden" name="description" id="description_input">
                            
                                    @if ($errors->has('description'))
                                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                                    @endif
                                </div>
                            </div>                            
                            <div class="row mb-3">
                                <label for="start_date" class="col-sm-3 col-form-label required">Start Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="end_date" class="col-sm-3 col-form-label">End Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label">Credentials</label>
                                <div class="col-sm-9">
                                    <textarea name="credentials" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select" id="status" name="status">
                                        <option value="" disabled selected>Select Status</option>
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
                
                $("#addProjectsForm").submit(function(event) {
                    event.preventDefault();
                    $('#description_input').val(quill.root.innerHTML);
                    var formData = new FormData(this);
                    // var totalfiles = document.getElementById('add_document').files.length;

                    // for (var index = 0; index < totalfiles; index++) {
                    //     formData.append("add_document[]" + index, document.getElementById('add_document')
                    //         .files[
                    //             index]);
                    // }
                    // console.log(formData);

                    $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

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

                $('#client_id').select2({
                    dropdownParent: $('#addProjects'),
                });

                $( '#user' ).select2( {
                    dropdownParent: $('#addProjects')
                } );
            });

            $('#addProjects').on('hidden.bs.modal', function () {
                $('.alert-danger').html('').hide();
                $('#addProjectsForm')[0].reset();
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

                $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
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

            function deleteProjects(id) {
    if (confirm("Are you sure?")) {
        $.ajax({
            type: "DELETE",
            url: "{{ url('/delete/projects') }}",
            data: {
                id: id,
                _token: "{{ csrf_token() }}" 
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 200) {
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function(xhr) {
                alert('Something went wrong.');
                console.log(xhr.responseText);
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