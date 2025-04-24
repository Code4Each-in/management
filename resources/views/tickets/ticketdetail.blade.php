@extends('layout')
@section('title', 'Ticket Details')
@section('subtitle', 'Ticket')
@section('content')
<div class="editticket">
<a href="{{ url('/edit/ticket/'.$tickets->id)}}" class="btn btn-primary">Edit Ticket
    <i style="color:#4154f1;"></i>
</a>
</div>
<div id="loader">
  <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>
<div class="container">
  <div class="task-card expanded">
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
            @elseif($tickets->status == 'deployed')
            <i class="fa-solid fa-circle-check"></i> Deployed
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

    <div class="task-details"> <!-- Always visible -->
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
      
        <div class="dropdown d-inline-block ms-2">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle status-button" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-circle-dot"></i>
            {{ ucfirst($tickets->status) }}
          </button>
      
          <ul class="dropdown-menu status-options" data-ticket-id="{{ $tickets->id }}">
            @foreach(['to_do', 'in_progress', 'ready', 'deployed', 'complete'] as $status)
              <li>
                <a class="dropdown-item" href="#" data-value="{{ $status }}">
                  {{ ucfirst(str_replace('_', ' ', $status)) }}
                </a>
              </li>
            @endforeach
          </ul>
        </div>
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
                <p style="font-size: 0.95rem; font-weight: bold; margin-bottom: 5px;">{{ $data->user->first_name }}<a href="javascript:void(0);" class="text-danger delete-comment" 
                  data-id="{{ $data->id }}" 
                  title="Delete Comment" 
                  style="font-size: 1rem; line-height: 1; float: right; text-decoration: none; cursor: pointer;">
                   &times;
               </a></p>
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d, Y h:i A", strtotime($data->created_at)) }}</p>                
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">{!! preg_replace('/<p>(h|g)?<\/p>/', '', $data->comments) !!}</p>
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
                    <button class="btn btn-sm p-0 border-0 bg-transparent text-danger delete-comment" 
                    data-id="{{ $data->id }}" 
                    title="Delete Comment" 
                    style="font-size: 1rem; line-height: 1; float: right;">
                &times;
            </button>
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
                <p style="font-size: 0.95rem; font-weight: bold; margin-bottom: 5px;">{{ $data->user->first_name }}<a href="javascript:void(0);" class="text-danger delete-comment" 
                  data-id="{{ $data->id }}" 
                  title="Delete Comment" 
                  style="font-size: 1rem; line-height: 1; float: right; text-decoration: none; cursor: pointer;">
                   &times;
               </a></p>
                <p style="font-size: 0.75rem; color: #6c757d; margin-bottom: 6px;">{{ date("M d, Y h:i A", strtotime($data->created_at)) }}</p>
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">{!! preg_replace('/<p>(h|g)?<\/p>/', '', $data->comments) !!}</p>
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
                    <button class="btn btn-sm p-0 border-0 bg-transparent text-danger delete-comment" 
                    data-id="{{ $data->id }}" 
                    title="Delete Comment" 
                    style="font-size: 1rem; line-height: 1; float: right;">
                &times;
            </button>
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
            <div class="card mt-3 card-designform">
            <form method="POST" id="commentsData" action="{{ route('comments.add') }}">
              @csrf
              <div class="post-item clearfix mb-3 mt-3">
                <textarea class="form-control comment-input" name="comment" id="tinymce_textarea" rows="3"></textarea>
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
    </div>
</div>
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
  $(document).ready(function() {
      $('#commentsData').on('submit', function(e) {
          e.preventDefault();
          $('.alert-danger').hide().html('');
          var formData = new FormData(this);
          $('#loader').show(); 
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
              },
              complete: function() {
                  $('#loader').hide(); 
              }
          });
      });
  });

  function toggleTaskDetails(headerElement) {
      const card = headerElement.closest('.task-card');
      card.classList.toggle('expanded');
  }
  $(document).on('click', '.delete-comment', function() {
    const commentId = $(this).data('id');
    const commentItem = $(this).closest('.post-item');

    if (confirm('Are you sure you want to delete this comment?')) {
        $.ajax({
            url: '/comments/' + commentId + '/delete',
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status === 200) {
                    commentItem.fadeOut(300, function() {
                        $(this).remove(); 
                    });

                    location.reload(); 
                } else {
                    alert(response.message || 'Failed to delete comment.');
                }
            },
            error: function() {
                alert('An error occurred while deleting the comment.');
            }
        });
    }
});

</script> 
<script>
  tinymce.init({
    selector: '#tinymce_textarea',
    plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
    toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | forecolor backcolor | link image media | preview fullscreen',
    menubar: 'file edit view insert format tools table help',
    height: 300,
    setup: function (editor) {
      editor.on('keydown', function (e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
          $('#commentsData').submit(); 
          e.preventDefault();
        }
      });
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
            alert("Missing data");
            return;
          }
    
          fetch(`/tickets/${ticketId}/update-status`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ status: newStatus }),
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              location.reload(true);
              // Update the button text without reload
              const statusButton = document.querySelector(`.status-options[data-ticket-id="${ticketId}"]`)
                .previousElementSibling;
    
              if (statusButton) {
                statusButton.innerHTML = `<i class="fa-solid fa-circle-dot"></i> ${newStatus.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}`;
              }
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