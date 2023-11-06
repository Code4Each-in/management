@extends('layout')
@section('title', 'Tickets')
@section('subtitle', 'Tickets')
@section('content')

<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-lg-6">
    <div class="card">
        <div class="card-body">

            <form method="post" id="editTicketsForm" action="{{route('tickets.update',$tickets->id)}}" enctype="multipart/form-data">
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
                    <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                    <div class=" col-sm-9">
                        <textarea name="description" class="form-control" id="tinymce_textarea" >{{$tickets->description}}</textarea>
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
                <div class="row mb-5">
                    <label for="edit_assign" class="col-sm-3 col-form-label required"> Ticket Assigned</label>
                    <div class="col-sm-9" id="Ticketsdata">
                        @foreach ($ticketAssign as $data)
                        <button type="button" class="btn btn-outline-primary btn-sm mb-2">
                            {{$data->user->first_name}}<i class="bi bi-x pointer ticketassign" onClick="deleteTicketAssign('{{ $data->id }}')"></i></button>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="row mb-5">
                    <label for="edit_assign" class="col-sm-3 col-form-label required ">Add More Assign</label>
                    <div class="col-sm-9">
                        <select name="assign[]" class="form-select" id="edit_assign" multiple>
                            <option value="">Select User</option>
                            @foreach ($userCount as $data)
                            <option value="{{$data['id']}}">
                                {{$data['first_name']}}
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
                        <input type="datetime-local" class="form-control" id="edit_eta" name="eta" value="@if ($tickets->eta) {{
                            $tickets->eta}}@endif}}">
                    </div>
                </div>
                <div class="row mb-5">
                    <label for="edit_status" class="col-sm-3 col-form-label required">Status</label>
                    <div class="col-sm-9">
                        <select name="status" class="form-select" id="edit_status">
                            <option value="to_do">To do</option>
                            <option value="in_progress" {{$tickets->status == 'in_progress' ? 'selected' : ' ' }}>In
                                Progress
                            </option>
                            <option value="ready" {{$tickets->status == 'ready' ? 'selected' : ' ' }}>Ready</option>
                            <option value="complete" {{$tickets->status == 'complete' ? 'selected' : ' ' }}>
                                Complete </option>
                        </select>
                        @if ($errors->has('status'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('status') }}</span>
                        @endif
                    </div>
                </div>


                <div class="row mb-5">
                    <label for="edit_priority" class="col-sm-3 col-form-label required">Priority</label>
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
                            // Add more cases for other file extensions as needed
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
                    <button type="submit" class="btn btn-primary" onClick="updateTicket()" href="javascript:void(0)">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="col-lg-6 dashboard ">
    <div class="card" style="height:730px;">
        <div class="card-body ">


            <div class="alert alert-success Commentmessage mt-4" style="display:none">

            </div>
            <h5 class="card-title">Comments</h5>
                <div class="comments">
                    @if(!empty($ticketsCreatedByUser->ticketby->first_name))
                        <div class="row">Created by:
                        {{ $ticketsCreatedByUser->ticketby->first_name ?? '' }}</div>
                        @endif
                    @if(count($CommentsData) !=0)
                    @foreach ($CommentsData as $data)
                    <div class="row">
                        @if(!empty($data->user->profile_picture))
                        <div class="col-md-2 comment-user-profile">
                            <img src="{{asset('assets/img/').'/'.$data->user->profile_picture}}" class="rounded-circle " alt="">
                        </div>
                        @else
                        <img src="{{asset('assets/img/blankImage.jpg')}}" alt="Profile" class="rounded-circle">
                        @endif
                        <div class="col-md-3">
                            <p>{{$data->user->first_name}}</p>
                            <p>{{date("M d h:s A", strtotime($data->created_at));}}</p>

                        </div>
                        <div class="col-md-7 ">
                            {{$data->comments}}
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="center text-center mt-2 ">
                        <span class="center" id="NoComments"> No Comments </span>
                    </div>
                    @endif
                </div>
            </div>
            <form method="post" id="commentsData" action="{{route('comments.add')}}">
                <div class=" post-item clearfix mb-3 mt-3">
                    <textarea class="form-control comment nt-3" name="comment" id="comment" placeholder="Enter your comment" rows="3"></textarea>
                </div>
                <div class="alert alert-danger" style="display:none"></div>
                <input type="hidden" class="form-control" id="hidden_id" value="{{$tickets->id}}">
                <button type="submit" style="float: right;" class="btn btn-primary"><i class="bi bi-send-fill"></i>
                    Comment</button>
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
                      // Introduce a delay before hiding the spinner
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
                }, 3000); // Adjust the duration (in milliseconds) as needed
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

                    // if (data.user != null) {
                    //     $('#edit_assign').find('option').remove().end();
                    //     $.each(data.user, function(key, value) {
                    //         $('#edit_assign').append('<option value="' + value.id + '">' + value
                    //             .first_name + '</option>');
                    //     });
                    // }
                    // if (data.AssignData.length == 0) {

                    //     $('#Ticketsdata').hide();
                    // }
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

</script>

@endsection
