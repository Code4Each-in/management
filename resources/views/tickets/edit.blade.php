@extends('layout')
@section('title', 'Tickets')
@section('subtitle', 'Tickets')
@section('content')

<div class="col-lg-6">
    <div class="card">
        <div class="card-body">
            @if(session()->has('message'))
            <div class="alert alert-success message mt-4">
                {{ session()->get('message') }}
            </div>
            @endif
            <form method="post" id="editTicketsForm" action="{{route('ticket.update',$tickets->id)}}">
                <div class="row mb-3 mt-4">
                    <label for="edit_title" class="col-sm-3 col-form-label required">Title</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="title" id="edit_title"
                            value="{{$tickets->title}}">
                        @if ($errors->has('title'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('title') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="edit_description" class="col-sm-3 col-form-label required">Description</label>
                    <div class=" col-sm-9">
                        <textarea name="description" class="form-control"
                            id="edit_description">{{$tickets->description}}</textarea>
                        @if ($errors->has('description'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                        @endif
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
                        @if ($errors->has('assign'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('assign') }}</span>
                        @endif
                    </div>
                </div>
                @csrf
                <div class="row mb-3">
                    <label for="edit_status" class="col-sm-3 col-form-label required">Status</label>
                    <div class="col-sm-9">
                        <select name="status" class="form-select" id="edit_status">
                            <option value="">To do</option>
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
                <div class="row mb-3">
                    <label for="edit_priority" class="col-sm-3 col-form-label required">Priority</label>
                    <div class="col-sm-9">
                        <select name="priority" class="form-select" id="edit_priority">
                            <option value="">Priority</option>
                            <option value="normal" {{$tickets->priority == 'normal' ? 'selected' : '' }}> Normal
                            </option>
                            <option value="low" {{$tickets->priority == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="high" {{$tickets->priority == 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{$tickets->priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        @if ($errors->has('priority'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('priority') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="edit_document" class="col-sm-3 col-form-label">Document</label>
                    <div class="col-sm-9">
                        <input type="file" class="form-control" name="upload" id="edit_document">
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary" onClick="updateTicket()"
                        href="javascript:void(0)">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="col-lg-6 dashboard">
    <div class="card">
        <div class="card-body">
            @if(session()->has('messages'))
            <div class="alert alert-success message mt-4">
                {{ session()->get('messages') }}
            </div>
            @endif
            <h5 class="card-title">Comments</h5>
            <div class="news">
                @foreach ($CommentsData as $data)
                <div class="post-item clearfix mb-3">
                    <img src="{{asset('assets/img/').'/'.$data->user->profile_picture}}"
                        class="rounded-circle dashboards" alt="">
                    <p class="data">{{$data->user->first_name}}</p>
                    <p>{{date("M d h:s a", strtotime($data->created_at));}}</p>
                    <div class="">
                        <h4><span>{{$data->comments}}</span></h4>
                    </div>
                    <!-- <h4><span>{{$data->comments}}</span></h4> -->
                    <div class="row">
                        <div class="col-md-6 ">
                            <!-- <p>{{date("M d, Y H:s a", strtotime($data->created_at));}}</p> -->
                            <!-- <h4><span>{{$data->comments}}</span></h4> -->

                            <!-- <p>{{$data->user->first_name}}</p> -->
                        </div>
                        <div class="col-md-6 ">
                            <!-- <p>{{date("M d, Y H:s a", strtotime($data->created_at));}}</p> -->
                            <!-- <h4><span>{{$data->comments}}</span></h4> -->

                        </div>
                    </div>
                </div>
                @endforeach
                <form method="post" id="commentsData" action="{{route('comments.add')}}">
                    <div class=" post-item clearfix mb-3">
                        <textarea class="form-control nt-3" name="comment" id="comment" placeholder="Enter your comment"
                            rows="3"></textarea>
                    </div>
                    <div class="alert alert-danger" style="display:none"></div>
                    <input type="hidden" class="form-control" id="hidden_id" value="{{$tickets->id}}">
                    <button type="submit" style="float: right;" class="btn btn-primary"><i class="bi bi-send-fill"></i>
                        Comment</button>
                </form>
            </div>
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
                    location.reload();
                }
            },

        });
    });
});
</script>
@endsection