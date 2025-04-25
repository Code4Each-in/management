@extends('layout')
@section('title', 'Tickets')
@section('subtitle', 'Tickets')
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container {
            visibility: visible !important;
            display: block !important;
        }
    </style>
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading.......">
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <a class="btn btn-primary mt-3"  href="{{ route('tickets.create') }}">Add
                Ticket</a>
                </div>
            </div>
            <form id="filter-data" method="GET" action="{{ route('tickets.index') }}">
                <div class="row mt-3 mx-auto">
                    <div class="col-md-4 filtersContainer d-flex p-0">
                        <div style="margin-right:20px;">
                            <input type="checkbox" class="form-check-input" name="all_tickets" id="all_tickets"
                                {{ $allTicketsFilter == 'on' ? 'checked' : '' }}> 
                                <label for="all_tickets">All Tickets</label>
                        </div>
                        <div>
                        <input type="checkbox" class="form-check-input" name="complete_tickets" id="complete_tickets"
                            {{ $completeTicketsFilter == 'on' ? 'checked' : '' }}> 
                        <label for="complete_tickets">Completed Tickets</label>
                        </div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="projectFilterselectBox">Project</label>
                        <select class="form-control" id="projectFilterselectBox" name="project_filter">
                            <option value="" selected >Select Project</option>
                            @foreach ( $projects as $project)
                            <option value="{{$project->id}}" {{ request()->input('project_filter') == $project->id ? 'selected' : '' }} >{{$project->project_name}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('project_filter'))
                            <span style="font-size: 10px;" class="text-danger">{{ $errors->first('project_filter') }}</span>
                        @endif
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="assigneeFilterselectBox">Assigned To</label>

                        <select class="form-control" id="assigneeFilterselectBox" name="assigned_to_filter">
                            <option value="" selected >Select Assignee</option>
                            @foreach ($user as $u)
                            <option value="{{$u->id}}" {{ request()->input('assigned_to_filter') == $u->id ? 'selected' : '' }} >{{ $u->first_name . ' ' . $u->last_name }}</option>
                            @endforeach
                        </select>

                        @if ($errors->has('assigned_to_filter'))
                            <span style="font-size: 10px;" class="text-danger">{{ $errors->first('assigned_to_filter') }}</span>
                            @endif
                    </div>
                </div>
            </form>
            <div class="box-header with-border" id="filter-box">
                <br>
                <!-- @if(session()->has('message'))
                <div class="alert alert-success message">
                    {{ session()->get('message') }}
                </div>
                @endif -->
                <!-- filter -->
                <div class="box-header with-border mt-4" id="filter-box">
                    <div class="box-body table-responsive" style="margin-bottom: 5%">
                        <table class="table table-borderless dashboard" id="tickets">
                            <thead>
                                <tr>
                                    <th>Ticket Id</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Assign</th>
                                    <th>Project</th>
                                    <!-- <th>Total Time</th> -->
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $data)
                                <tr>
                                    <td><a href="{{ url('/edit/ticket/'.$data->id)}}">#{{$data->id}}</a>
                                    <td>{{($data->title )}}</td>

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
                                            echo $data->description;
                                            @endphp
                                        </span>
                                        <a href="#" class="readMoreLink">Read More</a>
                                        <a href="#" class="readLessLink" style="display: none;">Read Less</a>
                                        @else
                                        {!! $data->description !!}                                       
                                         @endif
                                    </td>

                                    <td> @if (count($data->ticketassign)<= 5) @foreach ($data->ticketassign as $assign)
                                            @if (!empty($assign->profile_picture))
                                            <img src="{{asset('assets/img/').'/'.$assign->profile_picture}}" width="20" height="20" class="rounded-circle " alt="">
                                            @else 
                                            <img src="{{ asset('assets/img/blankImage.jpg') }}" alt="Profile" width="20" height="20" class="rounded-circle">
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
                                    <td>{{ $data->ticketRelatedTo->project_name ?? '---' }}</td>
                                    <!-- <td>{{ $data->eta_from}}</td>
                                    <td>{{ $data->eta_to}}</td> -->

                                    <!-- <td>{{ $data->status }}</td> -->
                                    @php
                                    $ticketStatusData = $ticketStatus->where('ticket_id', $data->id)->first();
                                    @endphp
                                  <td>
                                    <div class="dropdown">
                                      <button 
                                        class="btn btn-sm btn-secondary dropdown-toggle" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false"
                                      >
                                        {{ ucfirst(str_replace('_', ' ', $data->status)) }}
                                      </button>
                                  
                                      <ul class="dropdown-menu status-options" data-ticket-id="{{ $data->id }}">
                                        @foreach(['to_do', 'in_progress', 'ready', 'deployed', 'complete'] as $status)
                                          <li>
                                            <a class="dropdown-item" href="#" data-value="{{ $status }}">
                                              {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </a>
                                          </li>
                                        @endforeach
                                      </ul>
                                    </div>
                                  </td>                                                                                                                                                                                                                                                                                                                                                   
                                    <!-- <td>{{ $data->priority }}</td> -->
                                    @if($data->priority == 'normal')
                                    <td>
                                        <span class="badge rounded-pill bg-success">Normal</span>
                                    </td>
                                    @elseif($data->priority == 'low')
                                    <td><span class="badge rounded-pill bg-warning text-dark">low</span></td>
                                    @elseif($data->priority == 'high')
                                    <td><span class="badge rounded-pill bg-primary">High</span></td>
                                    @elseif($data->priority == 'priority')
                                    <td><span class="badge bg-info text-dark">Priority</span></td>
                                    @else
                                    <td><span class="badge rounded-pill  bg-danger">Urgent</span></td>
                                    @endif
                                    <td> 
                                        <a href="{{ url('/view/ticket/'.$data->id)}}"  target="_blank">
                                            <i style="color:#4154f1;" class="fa fa-eye fa-fw pointer"></i>
                                        </a>
                                        <a href="{{ url('/edit/ticket/'.$data->id)}}"><i style="color:#4154f1;" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i>
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
        <div class="modal fade" id="addTickets" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addTicketsForm" enctype="multipart/form-data">
                     @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label required">Title</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="title" id="title" oninput="checkWordCount()">
                                    <small id="wordCountError" class="text-danger" style="display:none;">Please enter at least 15 characters.</small>
                                </div>
                            </div>                            
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                                <div class="col-sm-9">
                                    <textarea name="description" class="form-control" id="tinymce_textarea"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label required">Project</label>
                                <div class="col-sm-9">
                                    <select name="project_id" class="form-select form-control" id="project_id">
                                        <option value="">Select Project</option>
                                        @foreach ($projects as $data)
                                            <option value="{{ $data->id }}">{{ $data->project_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label required">Sprint</label>
                                <div class="col-sm-9">
                                    <select name="sprint_id" class="form-select form-control" id="sprint_id">
                                        <option value="">Select Sprint</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="edit_assign1" class="col-sm-3 col-form-label">Assign</label>
                                <div class="col-sm-9">
                                    <select name="assign[]" class="form-select" id="edit_assign1" multiple>
                                        <option value="">Select User</option>
                                        @foreach ($user as $data)
                                            <option value="{{ $data->id }}">
                                                {{ $data->first_name }}&nbsp;-&nbsp;{{ $data->designation }}
                                            </option>
                                        @endforeach
                                    </select>                                    
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="etaDateTime" class="col-sm-3 col-form-label ">Eta</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="eta" name="eta">
                                </div>
                            </div>

                            <!-- <div class="row mb-3">
                                <label for="etaDateTime" class="col-sm-3 col-form-label required">Eta To</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="eta_to" name="eta_to">
                                </div>
                            </div> -->
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label ">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select" id="status">
                                        <option value="to_do">To do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="ready">Ready</option>
                                        <option value="deployed">Deployed</option>
                                        <option value="complete">
                                            Complete </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="ticket_priority" class="col-sm-3 col-form-label  ">Ticket State</label>
                                <div class="col-sm-9">
                                    <select name="ticket_priority" class="form-select" id="ticket_priority">
                                        <option value="1">Active</option>
                                        <option value="0">In Active</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="priority" class="col-sm-3 col-form-label  ">Priority</label>
                                <div class="col-sm-9">
                                    <select name="priority" class="form-select" id="priority">
                                        <option value="normal">Normal</option>
                                        <option value="low">Low</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
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
        <div class="modal fade" id="editTickets" tabindex="-1" aria-labelledby="tickets" aria-hidden="true">
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
                                <label for="edit_assign1" class="col-sm-3 col-form-label">Assign</label>
                                <div class="col-sm-9">
                                    <select name="assign[]" class="form-select" id="edit_assign1" multiple>
                                        <option value="">Select User</option>
                                        @foreach ($user as $data)
                                            <option value="{{ $data->id }}">
                                                {{ $data->first_name }}
                                            </option>
                                        @endforeach
                                    </select>                                    
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
                                        <option value="deployed">Deployed</option>
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
                $('#tickets').DataTable({
                    "order": []

                });
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });


                $("#addTicketsForm").submit(function(event) {
                    event.preventDefault();                   
                    var formData = new FormData(this);
                    $('#loader').show();
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('/add/tickets')}}",
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
                                $("#addTickets").modal('hide');
                                location.reload();
                            }
                            $('#loader').hide();
                        },
                        error: function(data) {
                            $('#loader').hide();
                        }
                    });
                });

                $('#editTicketsForm').submit(function(event) {
                    event.preventDefault();
                    $('#description_input').val(quill.root.innerHTML);
                    var formData = new FormData(this);
                    $('#loader').show();

                    $.ajax({
                        type: "POST",
                        url: "{{ url('/update/tickets') }}",
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
                            $('#loader').hide();
                        },
                        error: function(data) {
                            $('#loader').hide();
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

                                var picture = '/blankImage.jpg';
                                console.log(assign.profile_picture);
                                if (assign.profile_picture != null) {
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

            function openticketModal() {
                document.getElementById("addTicketsForm").reset();
                $('#addTickets').modal('show');
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
                        if (res.tickets != null) {
                            $('#edit_title').val(res.tickets.title);
                            $('#edit_description').val(res.tickets.description);
                            $('#edit_status').val(res.tickets.status);
                            $('#edit_comment').val(res.tickets.comment);

                            $('#edit_priority').val(res.tickets.priority);
                            // var test = "http://127.0.0.1:8000/public/assets/img/" + res.tickets.profile_picture;
                            // $("#profile").html(
                            //     '<img src="{{asset("assets/img")}}/' + res.tickets.profile_picture +
                            //     '" width = "100" class = "img-fluid img-thumbnail" > '
                            // );

                        }
                        if (res.ticketAssign != null) {
                            $.each(res.ticketAssign, function(key, value) {
                                $('#edit_assign1 option[value="' + value.user_id + '"]')
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
                        url: "{{ url('/delete/tickets') }}",
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

                   // Event listener for checkbox changes
                $("#filter-data input:checkbox").change(function() {
                    // Submit the form
                    $("#filter-data").submit();
                });

                // Form submission
                $("#filter-data").submit(function(event) {
                    // Disable unchecked checkboxes
                    if (!$("#all_tickets").prop('checked')) {
                    $("#all_tickets").prop('disabled', true);
                    }
                    if (!$("#complete_tickets").prop('checked')) {
                    $("#complete_tickets").prop('disabled', true);
                    }
                });

                //Submit form on change the value of Project
                document.getElementById("projectFilterselectBox").addEventListener("change", function() {
                    document.getElementById("filter-data").submit();
                });

                  //Submit form on change the value of Assigned to 
                  document.getElementById("assigneeFilterselectBox").addEventListener("change", function() {
                    document.getElementById("filter-data").submit();
                });

                $(function(){
                    $('#addTickets').submit(function() {
                        $('#loader').show(); 
                        return true;
                    });
                });

            
                $(document).ready(function() {
    // Check if element exists before initializing Select2
    if ($('#edit_assign1').length) {
        // Initialize Select2 only if it's not already initialized
        if (!$('#edit_assign1').hasClass('select2-hidden-accessible')) {
            $('#edit_assign1').select2({
                allowClear: true,
                width: '100%'
            });
        }
    } 

    

    // If the modal is being used and you're opening it dynamically
    $('#addTickets').on('shown.bs.modal', function () {
        // Reapply Select2 after modal is shown
        if ($('#edit_assign1').length) {
            $('#edit_assign1').select2({
                allowClear: true,
                width: '100%'
            });
        }
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const currentDate = new Date();
    const dayOfWeek = currentDate.getDay();
    if (dayOfWeek === 5) { 
        currentDate.setHours(currentDate.getHours() + 72);
    } else if (dayOfWeek === 6 || dayOfWeek === 0) { 
        currentDate.setHours(currentDate.getHours() + (72 - currentDate.getHours() % 24));
    } else {
        currentDate.setHours(currentDate.getHours() + 48);
    }
    const year = currentDate.getFullYear();
    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
    const day = String(currentDate.getDate()).padStart(2, '0');
    const hours = String(currentDate.getHours()).padStart(2, '0');
    const minutes = String(currentDate.getMinutes()).padStart(2, '0');
    const maxEta = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById("eta").setAttribute("max", maxEta);
});

function checkCharLength() {
    const titleInput = document.getElementById('title');
    const charCountError = document.getElementById('wordCountError');
    const charLength = titleInput.value.trim().length;

    if (charLength < 15) { 
        charCountError.style.display = 'block';  
    } else {
        charCountError.style.display = 'none';   
    }
}

document.getElementById('f').addEventListener('input', checkCharLength);


        $('#project_id').on('change', function () {
        var projectId = $(this).val();
        $('#sprint_id').empty().append('<option value="">Loading...</option>');

        if (projectId) {
            $.ajax({
                url: '/get-sprints-by-project/' + projectId,
                type: 'GET',
                success: function (response) {
                    $('#sprint_id').empty().append('<option value="">Select Sprint</option>');
                    $.each(response, function (key, sprint) {
                        $('#sprint_id').append('<option value="' + sprint.id + '">' + sprint.name + '</option>');
                    });
                },
                error: function () {
                    $('#sprint_id').empty().append('<option value="">Error loading sprints</option>');
                }
            });
        } else {
            $('#sprint_id').empty().append('<option value="">Select Sprint</option>');
        }
    });


  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  
      document.body.addEventListener('click', function (e) {
        const clickedItem = e.target.closest('.dropdown-item');
  
        if (!clickedItem) return; 
  
        e.preventDefault(); 
  
        const newStatus = clickedItem.getAttribute('data-value');
        const ticketId = clickedItem.closest('.status-options')?.getAttribute('data-ticket-id');
  
        if (!newStatus || !ticketId) {
          alert("Missing status or ticket ID");
          return;
        }
  
        console.log(`Updating ticket ${ticketId} to status: ${newStatus}`);
  
        fetch(`/tickets/${ticketId}/update-status`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({ status: newStatus }),
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            location.reload(); 
          } else {
          }
        })
        .catch(error => {
          console.error(error);
          alert('An error occurred.');
        });
      });
    });
  </script>
  
  
    
        @endsection