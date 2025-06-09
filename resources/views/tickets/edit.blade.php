@extends('layout')
@section('title', 'Tickets')
@section('subtitle', 'Tickets')
@section('content')

<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">

            <form method="post" id="editTicketsForm" action="{{route('tickets.update',$tickets->id)}}" enctype="multipart/form-data">
                 <input type="hidden" name="source" value="{{ request('source') }}">
                <div class="row mb-5 mt-4">
                    <label for="edit_title" class="col-sm-3 col-form-label required">Title</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="title" id="edit_title" value="{{$tickets->title}}">
                        @if ($errors->has('title'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('title') }}</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-5">
                <label class="col-sm-3 col-form-label required">Description</label>

                <div class="col-sm-9 mb-3">
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
                
                    <div id="editor" style="height: 300px;">{!! $tickets->description !!}</div>
                    <input type="hidden" name="description" id="edit_description">
                
                    @if ($errors->has('description'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                    @endif
                </div>     
                </div>                
                <div class="row mb-5">
                    <label for="edit_project_id" class="col-sm-3 col-form-label required">Project</label>
                    <div class="col-sm-9">
                        <select name="edit_project_id" class="form-select form-control" id="edit_project_id">
                            @foreach ($projects as $data)
                            <option value="{{$data->id}}"  {{$data->id == $tickets->project_id  ? 'selected' : ''}}>
                                {{$data->project_name}}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Sprint</label>
                    <div class="col-sm-9">
                        <select name="edit_sprint_id" class="form-select form-control" id="edit_sprint_id">
                            <option value="">Select Sprint</option>
                            @foreach ($sprints as $sprint)
                                <option value="{{ $sprint->id }}" {{ $tickets->sprint_id == $sprint->id ? 'selected' : '' }}>
                                    {{ $sprint->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('edit_sprint_id'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_sprint_id') }}</span>
                        @endif
                    </div>
                </div>                               
                <div class="row mb-5">
                    <label for="edit_assign1" class="col-sm-3 col-form-label required"> Ticket Assigned</label>
                    <div class="col-sm-9" id="Ticketsdata">
                        @foreach ($ticketAssign as $data)
                        <button type="button" class="btn btn-outline-primary btn-sm mb-2">
                            {{$data->user->first_name}}<i class="bi bi-x pointer ticketassign" onClick="deleteTicketAssign('{{ $data->id }}')"></i></button>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="row mb-5">
                    <label for="edit_assign1" class="col-sm-3 col-form-label required ">Add More Assign</label>
                    <div class="col-sm-9">
                        <select name="assign[]" class="form-select" id="edit_assign1" multiple>
                            <option value="">Select User</option>
                            @foreach ($userCount as $data)
                            <option value="{{$data['id']}}">
                                {{$data['first_name']}}&nbsp;-&nbsp;{{$data['designation']}}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @if ($errors->has('assign'))
                    <span style="font-size: 12px;" class="text-danger">{{ $errors->first('assign') }}</span>
                    @endif
                </div>
                @csrf
                <div class="row mb-5">
                    <label for="etaDateTime" class="col-sm-3 col-form-label ">Eta</label>
                    <div class="col-sm-9">
                        <input type="datetime-local" class="form-control" id="edit_eta" name="eta" 
                                value="{{ $tickets->eta ? \Carbon\Carbon::parse($tickets->eta)->format('Y-m-d\TH:i') : '' }}">
                    </div>
                </div>
                <div class="row mb-5">
                    <label for="edit_status" class="col-sm-3 col-form-label">Status</label>
                    <div class="col-sm-9">
                        <select name="status" class="form-select" id="edit_status">
                            <option value="to_do">To do</option>
                            <option value="in_progress" {{$tickets->status == 'in_progress' ? 'selected' : ' ' }}>In
                                Progress
                            </option>
                            <option value="ready" {{$tickets->status == 'ready' ? 'selected' : ' ' }}>Ready</option>
                            <option value="deployed" {{$tickets->status == 'deployed' ? 'selected' : ' ' }}>Deployed</option>
                            <option value="complete" {{$tickets->status == 'complete' ? 'selected' : ' ' }}>
                                Complete </option>
                        </select>
                        @if ($errors->has('status'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('status') }}</span>
                        @endif
                    </div>
                </div>
                 
                <div class="row mb-5">
                    <label for="edit_category" class="col-sm-3 col-form-label">Ticket Category</label>
                    <div class="col-sm-9">
                        <select name="ticket_category" class="form-select" id="edit_category">
                            <option value="Technical" {{ $tickets->ticket_category == 'Technical' ? 'selected' : '' }}>Technical</option>
                            <option value="Design" {{ $tickets->ticket_category == 'Design' ? 'selected' : '' }}>Design</option>
                            <option value="Data Entry" {{ $tickets->ticket_category == 'Data Entry' ? 'selected' : '' }}>Data Entry</option>
                            <option value="Others" {{ $tickets->ticket_category == 'Others' ? 'selected' : '' }}>Others</option>
                        </select>
                        @if ($errors->has('ticket_category'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('ticket_category') }}</span>
                        @endif
                    </div>
                </div>

                @php
                    $role_id = auth()->user()->role_id;
                @endphp

                @if ($role_id != 6)
                <div class="row mb-5">
                    <label for="edit_time_estimation" class="col-sm-3 col-form-label">Time Estimation (hours)</label>
                    <div class="col-sm-9">
                        <input
                            type="number"
                            name="time_estimation"
                            id="edit_time_estimation"
                            class="form-control"
                            min="0"
                            step="0.25"
                            placeholder="Enter estimated time (e.g. 1.5 for 1h 30m)"
                            value="{{ old('time_estimation', $tickets->time_estimation) }}"
                        >
                        @if ($errors->has('time_estimation'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('time_estimation') }}</span>
                        @endif
                    </div>
                </div>
                @endif



                <div class="row mb-5">
                    <label for="edit_priority" class="col-sm-3 col-form-label">Priority</label>
                    <div class="col-sm-9">
                        <select name="priority" class="form-select" id="edit_priority">
                            <option value="priority" {{$tickets->priority == 'priority' ? 'selected' : '' }}>
                                Priority
                            </option>
                            <option value="normal" {{$tickets->priority == 'normal' ? 'selected' : '' }}> Normal
                            </option>
                            <option value="low" {{$tickets->priority == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="high" {{$tickets->priority == 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{$tickets->priority == 'urgent' ? 'selected' : '' }}>Urgent
                            </option>
                        </select>
                        @if ($errors->has('priority'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('priority') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="ticket_priority" class="col-sm-3 col-form-label">Ticket State</label>
                    <div class="col-sm-9">
                        <select name="ticket_priority" class="form-select" id="edit_ticket_priority">
                            <option value="1" {{ $tickets->ticket_priority == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $tickets->ticket_priority == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-5">
                    <label for="edit_document" class="col-sm-3 col-form-label">Uploaded Documents</label>
                    <div class="col-sm-9" id="Ticketsdata" style="margin:auto;">
                        @if (count($TicketDocuments) < 1)
                        No Uploaded Document Found
                        @else
                        @foreach ($TicketDocuments as $data)
                         <button type="button" class="btn btn-outline-primary btn-sm mb-2">
                            @php
                            $extension = pathinfo($data->document, PATHINFO_EXTENSION);
                            $iconClass = '';

                            switch ($extension) {
                            case 'pdf':
                            $iconClass = 'bi-file-earmark-pdf';
                            break;
                            case 'doc':
                            case 'docx':
                            $iconClass = 'bi-file-earmark-word';
                            break;
                            case 'xls':
                            case 'xlsx':
                            $iconClass = 'bi-file-earmark-excel';
                            break;
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            $iconClass = 'bi-file-earmark-image';
                            break;
                            default:
                            $iconClass = 'bi-file-earmark';
                            break;
                            }
                            @endphp
                            <i class="bi {{ $iconClass }} mr-1" onclick="window.open('{{asset('assets/img/').'/'.$data->document}}', '_blank')"></i>
                            <i class="bi bi-x pointer ticketfile text-danger" onClick="deleteUploadedFile('{{ $data->id }}')"></i>
                            </button>
                            @endforeach
                            @endif
                    </div>
                </div>
                <div class="row mb-5">
                    <label for="edit_document" class="col-sm-3 col-form-label">Document</label>
                    <div class="col-sm-9">
                        <input type="file" class="form-control" name="edit_document[]" id="edit_document" multiple>
                    </div>
                    @if ($errors->has('edit_document.*'))
                    @foreach($errors->get('edit_document.*') as $key => $errorMessages)
                    @foreach($errorMessages as $error)
                    <span style="font-size: 12px; padding: 10px 100px;" class="text-danger">
                    @if ($error == 'The document failed to upload.')
                        {{$error}} The document may not be greater than 5 mb.
                        @else
                            {{$error}}
                    @endif
                    </span>
                    @endforeach
                    @endforeach
                    @endif

                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary ticketSave" onClick="updateTicket()" href="javascript:void(0)">Save</button>
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

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#commentsData").submit(function() {
            var spinner = $('#loader');
            spinner.show();
            event.preventDefault();
            var comment = $('#comment').val();
            var id = $('#hidden_id').val();

            $.ajax({
                type: 'POST',
                url: "{{ url('/add/comments')}}",
                data: {
                    comment: comment,
                    id: id,
                },
                success: (data) => {
                setTimeout(function() {
                    spinner.hide();
                    if (data.errors) {
                        $('.alert-danger').html('');
                        $.each(data.errors, function(key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<li>' + value +
                                '</li>');
                        });
                    } else {
                        $('.alert-danger').html('');
                        $('.alert-danger').hide();
                        $('#comment').val("");
                        var html = "";
                        $.each(data.CommentsData, function(key, data) {
                            var picture = 'blankImage';
                            if (data.user.profile_picture != "") {
                                picture = data.user.profile_picture;
                            }
                            html +=
                                '<div class="row post-item clearfix mb-3 "><div class="col-md-2"><img src="{{asset("assets/img")}}/' +
                                picture +
                                '" class="rounded-circle" alt = "" ></div><div class="col-md-3"><p>' +
                                data.user.first_name +
                                '</p><p>' + moment(data.created_at).format(
                                    'MMM DD LT') +
                                '</p></div><div class="col-md-7 text-left mt-3 ml-3">' +
                                data.comments + '</div></div>';
                        });

                        $('.comments').append(html);
                        $('.Commentmessage').html(data.Commentmessage);
                        $('.Commentmessage').show();
                        $('#NoComments').hide();
                        setTimeout(function() {
                            $('.Commentmessage').fadeOut("slow");
                        }, 2000);
                    }
                }, 3000); 
                }
            });
        });
    });

    function deleteTicketAssign(id) {
        var TicketId = $('#hidden_id').val();
        if (confirm("Are you sure ?") == true) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/delete/ticket')}}",
                data: {
                    id: id,
                    TicketId: TicketId,
                },
                success: (data) => {
                    location.reload();
                }

            });
        }
    }

    function deleteUploadedFile(id) {
        var TicketId = $('#hidden_id').val();
        if (confirm("Are you sure ?") == true) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/delete/ticket/file')}}",
                data: {
                    id: id,
                    TicketId: TicketId,
                },
                success: (data) => {
                    location.reload();
                }

            });
        }

    }

    $(function(){
  $('#editTicketsForm').submit(function() {
    $('#loader').show();
    return true;
  });
});
$(document).ready(function() {
    if ($('#edit_assign1').length) {
        if (!$('#edit_assign1').hasClass('select2-hidden-accessible')) {
            $('#edit_assign1').select2({
                allowClear: true,
                width: '100%'
            });
        }
    }

    $('#addTickets').on('shown.bs.modal', function () {
        if ($('#edit_assign1').length) {
            $('#edit_assign1').select2({
                allowClear: true,
                width: '100%'
            });
        }
    });
});

$('#edit_project_id').on('change', function () {
        var projectId = $(this).val();
        $('#edit_sprint_id').empty().append('<option value="">Loading...</option>');

        if (projectId) {
            $.ajax({
                url: '/get-sprints-by-project/' + projectId,
                type: 'GET',
                success: function (response) {
                    $('#edit_sprint_id').empty().append('<option value="">Select Sprint</option>');
                    $.each(response, function (key, sprint) {
                        $('#edit_sprint_id').append('<option value="' + sprint.id + '">' + sprint.name + '</option>');
                    });
                },
                error: function () {
                    $('#edit_sprint_id').empty().append('<option value="">Error loading sprints</option>');
                }
            });
        } else {
            $('#edit_sprint_id').empty().append('<option value="">Select Sprint</option>');
        }
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
    document.getElementById("edit_eta").setAttribute("max", maxEta);
});
</script>
<script>
$('#editTicketsForm').on('submit', function () {
    $('#edit_description').val(quill.root.innerHTML);
});
</script>
@endsection
