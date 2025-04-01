@extends('layout')
@section('title', 'Ticket Details')
@section('subtitle', 'Ticket')
@section('content')

<div class="row mb-1" style="margin-bottom: 10px;">
    <div class="col-md-12">
        <div class="card" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <div class="card-body" style="padding: 15px;">
                <div class="accordion" id="ticketAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTitle">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTitle" aria-expanded="true" aria-controls="collapseTitle">
                                <strong>Title:&nbsp;</strong> {{ $tickets->title }}
                            </button>
                        </h2>
                        <div id="collapseTitle" class="accordion-collapse collapse" aria-labelledby="headingTitle" data-bs-parent="#ticketAccordion">
                            <div class="accordion-body">
                                <div class="row mb-1" style="margin-bottom: 8px;">
                                    <label for="tinymce_textarea" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Description</label>
                                    <div class="col-sm-9">
                                        <p style="font-size: 0.95rem; color: #333;">
                                            {{ preg_replace('/&nbsp;/', ' ', strip_tags(htmlspecialchars_decode($tickets->description))) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="row mb-1" style="margin-bottom: 8px;">
                                    <label for="edit_project_id" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Project</label>
                                    <div class="col-sm-9">
                                        @foreach ($projects as $project)
                                            <p style="font-size: 1rem; color: #333;">{{ $project['project_name'] }}</p>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="row mb-1" style="margin-bottom: 8px;">
                                    <label for="edit_assign" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Ticket Assigned</label>
                                    <div class="col-sm-9" id="Ticketsdata">
                                        @php
                                            $assignedUsers = $ticketAssign->map(function($data) {
                                                return $data->user->first_name;
                                            })->implode(', ');
                                        @endphp
                                        <p style="font-size: 0.95rem; color: #333; margin: 2px 0;">{{ $assignedUsers }}</p>
                                    </div>
                                </div>
                                <div class="row mb-1" style="margin-bottom: 8px;">
                                    <label for="etaDateTime" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">ETA</label>
                                    <div class="col-sm-9">
                                        <p style="font-size: 1rem; color: #333;">{{ $tickets->eta ? date("m/d/Y", strtotime($tickets->eta)) : '---' }}</p>                                        
                                    </div>
                                </div>
                                <div class="row mb-1" style="margin-bottom: 8px;">
                                    <label for="etaDateTime" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Status</label>
                                    <div class="col-sm-9">
                                        <p style="font-size: 1rem; color: #333;">
                                            @if($tickets->status == 'to_do')
                                                <span class="badge rounded-pill bg-primary">To do</span>
                                            @elseif($tickets->status == 'in_progress')
                                                <span class="badge rounded-pill bg-warning text-dark">In Progress</span>
                                            @elseif($tickets->status == 'ready')
                                                <span class="badge bg-info text-dark">Ready</span>
                                            @elseif($tickets->status == 'complete')
                                                <span class="badge rounded-pill bg-success">Complete</span>
                                            @else
                                                {{ $tickets->status ? $tickets->status : '---' }}
                                            @endif
                                        </p>                                        
                                    </div>
                                </div>
                                <div class="row mb-1" style="margin-bottom: 8px;">
                                    <label for="etaDateTime" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Ticket Priority</label>
                                    <div class="col-sm-9">
                                        <p style="font-size: 1rem; color: #333;">
                                            @if($tickets->priority == 'normal')
                                                <span class="badge rounded-pill bg-success">Normal</span>
                                            @elseif($tickets->priority == 'low')
                                                <span class="badge rounded-pill bg-warning text-dark">Low</span>
                                            @elseif($tickets->priority == 'high')
                                                <span class="badge rounded-pill bg-primary">High</span>
                                            @elseif($tickets->priority == 'priority')
                                                <span class="badge bg-info text-dark">Priority</span>
                                            @else
                                                <span class="badge rounded-pill bg-danger">Urgent</span>
                                            @endif
                                        </p>                                        
                                    </div>
                                </div>
                                <div class="row mb-1" style="margin-bottom: 8px;">
                                    <label for="etaDateTime" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Ticket Status</label>
                                    <div class="col-sm-9">
                                        <p style="font-size: 1rem; color: #333;">
                                            {{ $tickets->ticket_priority == 1 ? 'Active' : ($tickets->ticket_priority == 0 ? 'Inactive' : '---') }}
                                        </p>
                                    </div>                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-md-12">
        <h1 class="h1 pagetitle" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 20px; color: #012970;">Ticket Chat</h1>
        <div class="comments comment-design" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9; border-radius: 10px;">
            @if(!empty($ticketsCreatedByUser->ticketby->first_name))
            <p><strong>Created by:&nbsp;{{ $ticketsCreatedByUser->ticketby->first_name ?? '' }}</strong></p>
            @endif

            @if(count($CommentsData) != 0)
            @foreach ($CommentsData as $data)
    <div class="row mb-3" style="margin-bottom: 15px;">       
        @if(Auth::user()->id == $data->comment_by)
            <div class="col-md-10 offset-md-2 comment-bubble" style="background-color: #e7ecf1; border-radius: 25px; padding: 8px 16px; position: relative; text-align: right;">
                <p style="font-size: 0.95rem; font-weight: bold; margin-bottom: 5px;">{{ $data->user->first_name }}</p>
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d, Y h:i A", strtotime($data->created_at)) }}</p>                
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">{{ $data->comments }}</p>
            </div>
        @else
            @if(!empty($data->user->profile_picture))
                <div class="col-md-2 comment-user-profile" style="padding-right: 10px;">
                    <img src="{{ asset('assets/img/') . '/' . $data->user->profile_picture }}" class="rounded-circle" alt="" width="35" height="35">
                </div>
            @else
                <div class="col-md-2 comment-user-profile" style="padding-right: 10px;">
                    <img src="{{ asset('assets/img/blankImage.jpg') }}" alt="Profile" class="rounded-circle" width="35" height="35">
                </div>
            @endif
            <div class="col-md-10 comment-bubble" style="background-color: #ffb3b3; border-radius: 25px; padding: 8px 16px; position: relative;">
                <p style="font-size: 0.95rem; font-weight: bold; margin-bottom: 5px;">{{ $data->user->first_name }}</p>
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d, Y h:i A", strtotime($data->created_at)) }}</p>
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">{{ $data->comments }}</p>
            </div>
        @endif
    </div>
@endforeach       
            @else
                <div class="center text-center mt-2">
                    <span id="NoComments" style="color: #6c757d; font-size: 1rem;">No Comments</span>
                </div>
            @endif
        </div>
        <form method="POST" id="commentsData" action="{{ route('comments.add') }}">
            @csrf
            <div class="post-item clearfix mb-3 mt-3">
                <textarea class="form-control comment-input" name="comment" id="comment" placeholder="Enter your comment" rows="3" style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></textarea>
            </div>
            <div class="alert alert-danger" style="display:none;"></div>
            <input type="hidden" class="form-control" id="hidden_id" value="{{ $tickets->id }}">
            <div class="button-design">
                <button type="submit" class="btn  btncomment btn-primary float-right" style="padding: 8px 15px;font-size: 1rem; border: none;border-radius: 5px;/ margin: 0px auto; /display: flex;justify-content: flex-start;">
                                <i class="bi bi-send-fill"></i> Comment
                </button></div>
        </form>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#commentsData').on('submit', function(e) {
        e.preventDefault(); 
        $('#error-message').hide().html('');
        var formData = {
            comment: $('#comment').val(),
            id: $('#hidden_id').val(),
            _token: $('input[name="_token"]').val()
        };
        $.ajax({
            url: '{{ route('comments.add') }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.status == 200) {
                    $('#comment').val('');
                    location.reload();
                }
            },
            error: function(xhr) {
                $('#error-message').show().html('An error occurred while submitting the comment.');
            }
        });
    });
});
</script>
@endsection