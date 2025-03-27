@extends('layout')
@section('title', 'Ticket Details')
@section('subtitle', 'Ticket')
@section('content')

<div class="row mb-1" style="margin-bottom: 10px;">  <!-- Reduced margin bottom -->
    <div class="col-md-12">
        <div class="card" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <div class="card-body" style="padding: 15px;">
                <!-- Ticket Details -->
                <div class="row mb-1" style="margin-bottom: 8px;">  <!-- Reduced margin bottom -->
                    <label for="edit_title" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Title</label>
                    <div class="col-sm-9">
                        <p style="font-size: 1rem; color: #333;">{{ $tickets->title }}</p>
                    </div>
                </div>
                <div class="row mb-1" style="margin-bottom: 8px;">  <!-- Reduced margin bottom -->
                    <label for="tinymce_textarea" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Description</label>
                    <div class="col-sm-9">
                        <p style="font-size: 0.95rem; color: #555; white-space: pre-line;">{{ preg_replace('/<\/?p>/i', '', str_replace('&nbsp;', ' ', $tickets->description)) }}</p>
                    </div>
                </div>
                <div class="row mb-1" style="margin-bottom: 8px;">  <!-- Reduced margin bottom -->
                    <label for="edit_project_id" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Project</label>
                    <div class="col-sm-9">
                        <p style="font-size: 1rem; color: #333;">{{ $data ?? '' }}</p>
                    </div>
                </div>
                <div class="row mb-1" style="margin-bottom: 8px;">  <!-- Reduced margin bottom -->
                    <label for="edit_assign" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">Ticket Assigned</label>
                    <div class="col-sm-9" id="Ticketsdata">
                        @foreach ($ticketAssign as $data)
                            <p style="font-size: 0.95rem; color: #333; margin: 2px 0;">{{$data->user->first_name}}</p>
                        @endforeach
                    </div>
                </div>
                <div class="row mb-1" style="margin-bottom: 8px;">  <!-- Reduced margin bottom -->
                    <label for="etaDateTime" class="col-sm-3 col-form-label" style="font-weight: bold; font-size: 0.9rem;">ETA</label>
                    <div class="col-sm-9">
                        <p style="font-size: 1rem; color: #333;">{{ $tickets->eta ? $tickets->eta : 'Not Set' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-md-12">
        <h3 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 20px;">Ticket Chat</h3>
        <div class="comments" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9; border-radius: 10px;">
            @if(!empty($ticketsCreatedByUser->ticketby->first_name))
            <p><strong>Created by:&nbsp;{{ $ticketsCreatedByUser->ticketby->first_name ?? '' }}</strong></p>
            @endif

            @if(count($CommentsData) != 0)
            @foreach ($CommentsData as $data)
    <div class="row mb-3" style="margin-bottom: 15px;">
    
        <!-- Check if the current user is the one who posted the comment -->
        
        @if(Auth::user()->id == $data->comment_by)
            <!-- Comment from logged-in user (Right side) -->
            <div class="col-md-10 offset-md-2 comment-bubble" style="background-color: #e7ecf1; border-radius: 25px; padding: 8px 16px; position: relative; text-align: right;">
                <p style="font-size: 0.95rem; font-weight: bold; margin-bottom: 5px;">{{ $data->user->first_name }}</p>
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d h:i A", strtotime($data->created_at)) }}</p>
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">{{ $data->comments }}</p>
            </div>
        @else
            <!-- Comment from other user (Left side) -->
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
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d h:i A", strtotime($data->created_at)) }}</p>
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

        <!-- Comment Form -->
        <form method="post" id="commentsData" action="{{ route('comments.add') }}">
            <div class="post-item clearfix mb-3 mt-3">
                <textarea class="form-control comment-input" name="comment" id="comment" placeholder="Enter your comment" rows="3" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></textarea>
            </div>
            <div class="alert alert-danger" style="display:none;"></div>
            <input type="hidden" class="form-control" id="hidden_id" value="{{ $tickets->id }}">
            <button type="submit" class="btn btn-primary float-right" style="padding: 8px 15px; font-size: 1rem; background-color: #007bff; border: none; border-radius: 5px;">
                <i class="bi bi-send-fill"></i> Comment
            </button>
        </form>
    </div>
</div>


@endsection