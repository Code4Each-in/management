@extends('layout')
@section('title', 'Tickets')
@section('subtitle', 'Tickets')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openticketModal()" href="javascript:void(0)">ADD
                Tickets</button>
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
                        <table class="table table-borderless dashboard" id="tickets">
                            <thead>
                                <tr>
                                    <th>Ticket Id</id>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Assign</th>
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
                                    <td>{{ $data->title }}</td>
                                    <td>{{ $data->description }}</td>
                                    <td> @if (count($data->ticketassign)<= 5) @foreach ($data->ticketassign as $assign)
                                            <img src="{{asset('assets/img/').'/'.$assign->profile_picture}}" width="20"
                                                height="20" class="rounded-circle " alt="">

                                            @endforeach
                                            @endif
                                            <a class="text-primary small pt-1 pointer text-right"
                                                onClick="ShowAssignModal('{{$data->id}}')" id="view"><i
                                                    class="bi-person-lines-fill"></i>
                                            </a>
                                    </td>
                                    <!-- <td>{{ $data->eta_from}}</td>
                                    <td>{{ $data->eta_to}}</td> -->

                                    <!-- <td>{{ $data->status }}</td> -->
                                    @if($data->status == 'in_progress')
                                    <td>
                                        <span class="badge rounded-pill bg-warning text-dark">In Progress</span>
                                    </td>
                                    @elseif($data->status == 'ready')
                                    <td><span class="badge rounded-pill bg-primary">Ready</span></td>
                                    @else
                                    <td><span class="badge rounded-pill  bg-success">Complete</span></td>
                                    @endif
                                    <!-- <td>{{ $data->priority }}</td> -->
                                    @if($data->priority == 'normal')
                                    <td>
                                        <span class="badge rounded-pill bg-success">Normal</span>
                                    </td>
                                    @elseif($data->priority == 'low')
                                    <td><span class="badge rounded-pill bg-warning text-dark">low</span></td>
                                    @elseif($data->priority == 'high')
                                    <td><span class="badge rounded-pill bg-primary">High</span></td>
                                    @else
                                    <td><span class="badge rounded-pill  bg-danger">Urgent</span></td>
                                    @endif
                                    <td> <i style="color:#4154f1;" onClick="editTickets('{{ $data->id }}')"
                                            href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i>

                                        <i style="color:#4154f1;" onClick="deleteTickets('{{ $data->id }}')"
                                            href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
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
                        <h5 class="modal-title" id="role">Add Tickets</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" id="addTicketsForm" action="">
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label required">Title</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="title" id="title">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="description" class="col-sm-3 col-form-label required">Description</label>
                                <div class="col-sm-9">
                                    <textarea name="description" class="form-control" id="description"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="" class="col-sm-3 col-form-label required ">Assign</label>

                                <div class="col-sm-9">
                                    <select name="assign[]" class="form-select" id="assign" multiple>
                                        <option value="" disabled>Select User</option>
                                        @foreach ($user as $data)
                                        <option value="{{$data->id}}">
                                            {{$data->first_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @csrf
                            <div class="row mb-3">
                                <label for="etaDateTime" class="col-sm-3 col-form-label required">Eta From</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="eta_from" name="eta_from">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="etaDateTime" class="col-sm-3 col-form-label required">Eta To</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="eta_to" name="eta_to">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select" id="status">
                                        <option value="to_do">To do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="ready">Ready</option>
                                        <option value="complete">
                                            Complete </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="priority" class="col-sm-3 col-form-label required ">Priority</label>
                                <div class="col-sm-9">
                                    <select name="priority" class="form-select" id="priority">
                                        <option value="priority">Priority</option>
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
                                    <input type="file" class="form-control" name="files[]" id="files">
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
                                <label for="edit_description"
                                    class="col-sm-3 col-form-label required">Description</label>
                                <div class="col-sm-9">
                                    <textarea name="description" class="form-control" id="edit_description"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="edit_assign" class="col-sm-3 col-form-label required ">Assign</label>
                                <div class="col-sm-9">
                                    <select name="assign" class="form-select" id="edit_assign" multiple>
                                        <option value="">Select User</option>
                                        @foreach ($user as $data)
                                        <option value="{{$data->id}}">
                                            {{$data->first_name}}</option>
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
                var totalfiles = document.getElementById('files').files.length;
                console.log(totalfiles);
                for (var index = 0; index < totalfiles; index++) {
                    formData.append("files[]", document.getElementById('files').files[index]);
                }
                $.ajax({
                    type: 'POST',
                    url: "{{ url('/add/tickets')}}",
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
                            $("#addTickets").modal('hide');
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
                            // $('.alert-danger').show();
                            html +=
                                '<div class="row leaveUserContainer mt-2 "> <div class="col-md-2"><img src="{{asset("assets/img")}}/' +
                                assign.profile_picture +
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
                    url: "{{ url('/delete/tickets') }}",
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(res) {
                        // location.reload();
                    }
                });
            }
        }
        </script>
        @endsection