@extends('layout')
@section('title', 'Ticket Details')
@section('subtitle', 'Ticket')
@section('content')
<div class="editticket">
<a href="{{ url('/edit/ticket/'.$tickets->id)}}" class="btn btn-primary">Edit Ticket
    <i style="color:#4154f1;"></i>
</a>
</div>
<div class="container">
    <div class="task-card">
      <div class="task-header" onclick="toggleTaskDetails(this)">
        <div class="task-icon">
          <i class="fa-solid fa-folder-open"></i>
        </div>
        <div class="task-title">
          <h4>{{ $tickets->title }}</h4>
          <span class="task-status">
            @if($tickets->status == 'complete')
              <i class="fa-solid fa-circle-check"></i> Complete
            @elseif($tickets->status == 'ready')
              <i class="fa-solid fa-circle-check"></i> Ready
            @elseif($tickets->status == 'in_progress')
              <i class="fa-solid fa-spinner fa-spin"></i> In Progress
            @else
              <i class="fa-solid fa-circle-dot"></i> To Do
            @endif
          </span>
        </div>
        <div class="task-toggle-icon">
          <i class="fa-solid fa-chevron-down"></i>
        </div>
      </div>
  
      <div class="task-details">
        <div class="detail-item">
          <i class="fa-solid fa-align-left"></i>
          <strong>Description:</strong>
          <span>{{ preg_replace('/&nbsp;/', ' ', strip_tags(htmlspecialchars_decode($tickets->description))) }}</span>
        </div>

        <div class="detail-item">
          <i class="fa-solid fa-diagram-project"></i>
          <strong>Project:</strong>
          @foreach ($projects as $project)
                <span>{{ $project['project_name'] ?? '---' }}</span>
            @endforeach

        </div>
  
        <div class="detail-item">
          <i class="fa-solid fa-user"></i>
          <strong>Assigned To:</strong>
          <span>
            @php
              $assignedUsers = $ticketAssign->map(fn($data) => $data->user->first_name)->implode(', ');
            @endphp
            {{ $assignedUsers }}
          </span>
        </div>
  
        <div class="detail-item">
          <i class="fa-solid fa-calendar-day"></i>
          <strong>ETA:</strong>
          <span>{{ $tickets->eta ? date("d/m/Y", strtotime($tickets->eta)) : '---' }}</span>
        </div>
  
        <div class="detail-item">
          <i class="fa fa-layer-group"></i>
          <strong>Priority:</strong>
          <span class="priority {{ $tickets->priority }}">
            <i class="fa fa-check-circle" aria-hidden="true"></i> {{ ucfirst($tickets->priority ?? 'Urgent') }}
          </span>
        </div>
  
        <div class="detail-item">
          <i class="fa-solid fa-bolt"></i>
          <strong>Ticket Status:</strong>
          <span class="status-badge {{ $tickets->ticket_priority == 1 ? 'active' : 'inactive' }}">
            <i class="fa-solid fa-circle-dot"></i>
            {{ $tickets->ticket_priority == 1 ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>
    </div>
  </div>
  



<div class="row">
    <div class="col-md-12">
        <h1 class="h1 pagetitle" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 20px; color: #012970;">Ticket Chat</h1>
        <div class="comments comment-design" style="overflow-y: auto; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9; border-radius: 10px;">
            @if(!empty($ticketsCreatedByUser->ticketby->first_name))
            <p><strong>Created by:&nbsp;{{ $ticketsCreatedByUser->ticketby->first_name ?? '' }}</strong></p>
            @endif

            @if(count($CommentsData) != 0)
            @foreach ($CommentsData as $data)
    <div class="row mb-3" style="margin-bottom: 15px;">       
        @if(Auth::user()->id == $data->comment_by)
            <div class="col-md-10 offset-md-2 comment-bubble" style="border-radius: 25px; padding: 8px 16px; position: relative; text-align: right;">
                <p style="font-size: 0.95rem; font-weight: bold; margin-bottom: 5px;">{{ $data->user->first_name }}</p>
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d, Y h:i A", strtotime($data->created_at)) }}</p>                
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">{{ $data->comments }}</p>
                @php
                $documents = explode(',', $data->document);
            @endphp

            @foreach ($documents as $doc)
                @if (!empty($doc))
                    <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">
                        <a href="{{ asset('assets/img/' . $doc) }}" target="_blank">
                            {{ basename($doc) }}
                        </a>
                    </p>
                @endif
@endforeach
                
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
            <div class="col-md-10 comment-bubble" style="border-radius: 25px; padding: 8px 16px; position: relative;">
                <p style="font-size: 0.95rem; font-weight: bold; margin-bottom: 5px;">{{ $data->user->first_name }}</p>
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d, Y h:i A", strtotime($data->created_at)) }}</p>
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">{{ $data->comments }}</p>
                @php
                $documents = explode(',', $data->document);
            @endphp
            
            @foreach ($documents as $doc)
                @if (!empty($doc))
                    <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">
                        <a href="{{ asset('assets/img/' . $doc) }}" target="_blank">
                            {{ basename($doc) }}
                        </a>
                    </p>
                @endif
            @endforeach                            
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
            <div class="mb-3 post-item clearfix upload_chat">
                <label for="comment_file" class="form-label">Attach File</label>
                <input type="file" name="comment_file[]" id="comment_file" class="form-control comment-input" multiple>
            </div>
            <div class="alert alert-danger" style="display:none;"></div>
            <input type="hidden" class="form-control" name="id" id="hidden_id" value="{{ $tickets->id }}">
            <div class="button-design">
                <button type="submit" class="btn  btncomment btn-primary float-right" style="padding: 8px 15px;font-size: 1rem; border: none;border-radius: 5px;/ margin: 0px auto; /display: flex;justify-content: flex-start;">
                                <i class="bi bi-send-fill"></i> Comment
                </button>
            </div>
        </form>
    </div>

</div>
<script>
    $(document).ready(function() {
        $('#commentsData').on('submit', function(e) {
            e.preventDefault();
            $('.alert-danger').hide().html('');
            var formData = new FormData(this);
            $.ajax({
                url: '{{ route('comments.add') }}',
                type: 'POST',
                data: formData,
                contentType: false, 
                processData: false, 
                success: function(response) {
                    if (response.status === 200) {
                        $('#comment').val('');
                        $('#comment_file').val('');
                        location.reload();
                    } else {
                        $('.alert-danger').show().html(response.message || 'Something went wrong.');
                    }
                },
                error: function(xhr) {
                    $('.alert-danger').show().html('An error occurred while submitting the comment.');
                }
            });
        });
    });
    function toggleTaskDetails(headerElement) {
      headerElement.parentElement.classList.toggle('expanded');
    }
    </script>    
@endsection