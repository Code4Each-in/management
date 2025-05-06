@extends('layout')
{{-- @section('title', 'Chat Section') --}}
@section('subtitle', 'Chat')
@section('content')
<style>
    /* body {
      margin: 0;
      padding: 0;
      background-color: #f2f2f2;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    } */

    .chat-wrapper {
      /* max-width: 1200px; */
      /* width: 100%; */
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      display: flex;
      padding: 0;
      /* height: 80vh; */
    }

    .chatsidebar {
      width: 360px;
      border-right: 1px solid #ddd;
      background-color: #fff;
      display: flex;
      flex-direction: column;
    }

    .sidebar-header  {
    padding: 15px;
    font-size: 18px;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
    color: #297bab;
    background-color: #f8f9fa;
    border-radius: 10px 0px 0 0;
}

.contact-list {
    flex: 1;
    /* overflow: scroll; */
    /* height: 22vh; */
    max-height: 715px;
    overflow-y: auto;
    padding-right: 4px;
}

    .contact {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid #f1f1f1;
        cursor: pointer;
        position: relative;
        transition: background-color 0.2s ease-in-out;
        margin: 10px 8px 10px;
    }

    .contact:hover {
        background-color: #f0f0f0;
        border-radius: 11px;
        box-shadow: 0 0 40px rgb(0 0 0 / 12%);
        background: #d5efff;
        /* margin: 15px 5px 5px; */
     }
    .contact img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      margin-right: 10px;
    }

    .contact .details-clent {
        flex: 1;
        display: flex;
        flex-direction: column;
        row-gap: 1px;
    }

    .contact.name {
      font-weight: bold;
      font-size: 14px;
    }

    .contact.last-message {
      font-size: 12px;
      color: #666;
    }

    .contact .time {
      font-size: 11px;
      color: #999;
      position: absolute;
      top: 10px;
      right: 15px;
    }
    .message-section {
        width: 87%;
        /* max-width: 850px; */
   
    }
    .chat-wrapper .contact .badge {
        background-color: #297bab;
        color: white;
        font-size: 12px;
        padding: 4px 6px;
        border-radius: 50%;
        position: absolute;
        right: 15px;
        bottom: 10px;
        min-width: auto;
    }

    .chat-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      background-color: #fff;
    }
    .msgers-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      border-bottom: 1px solid #ccc;
      background-color: #297bab;
      color: #fff;
      border-radius: 0px 10px 0 0;
  }

    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background-color: #f7f9fb;
    }

    .msg {
      margin-bottom: 20px;
    }

    .msg.meta {
      font-size: 12px;
      color: #999;
      margin-bottom: 4px;
    }

    .msg .author {
      display: flex;
      align-items: center;
      margin-bottom: 5px;
    }

    .msg .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: #6c757d;
      color: #fff;
      font-size: 14px;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
    }

    .msg .name {
      font-weight: bold;
      font-size: 14px;
      margin-right: 8px;
    }

    .msg .role {
      font-size: 12px;
      color: #666;
      background-color: #e1e1e1;
      padding: 2px 6px;
      border-radius: 4px;
    }

    .msg .text {
      margin-left: 46px;
      font-size: 14px;
      background-color: #e9ecef;
      padding: 10px;
      border-radius: 10px;
    }
    .contact.active {border-radius: 11px;box-shadow: 0 0 40px rgb(0 0 0 / 12%);background: #d5efff;}

    .project {
        color: #297bab;
        font-weight: 600;
        font-size: 14px;
    }
    .msg-input {
      display: flex;
      padding: 15px;
      border-top: 1px solid #ccc;
    }

    .msg-input textarea {
      flex: 1;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      resize: none;
      font-size: 14px;
      height: 36px;
      margin-right: 10px;
    }

    .msg-input button {
      background-color: #297bab;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 0 20px;
      font-size: 14px;
      cursor: pointer;
    }

    .msg-input button:hover {
      background-color: #0056b3;
    }
    
    @media (max-width: 768px) {
      .chat-wrapper {
        flex-direction: column;
        height: auto;
      }

      .chatsidebar {
        width: 100%;
        max-height: 250px;
      }
    }
    @media (max-width: 767px) {
    .message-section {
        width: 100%;
       
    }
}


.chat-wrapper .card.mt-3.card-designform {
    margin-bottom: 0;
}
  </style>
<div class="container chat-wrapper">
    <!-- Sidebar -->
    <div class="chatsidebar">
        <div class="sidebar-header">Messages</div>
        <div class="contact-list">
          @forelse($projects as $project)
          <div class="contact {{ $loop->first ? 'active' : '' }}" onClick="loadMessages({{ $project->id }})" id="contact-{{ $project->id }}">
          <div class="avatar" style="background-color: #27ae60; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
            {{ strtoupper(substr($project->project_name, 0, 2)) }}
        </div>
                  <div class="details">
                      <div class="name">{{ $project->project_name }}</div>
                      <div class="project">{{ $client->name ?? 'N/A' }}</div>
                      <div class="last-message">
                        {{ Str::limit(strip_tags(html_entity_decode($project->last_message ? $project->last_message->message : 'No messages yet')), 15) }}
                      </div>
                  </div>
                  <div class="time">
                      {{ $project->last_message ? $project->last_message->created_at->format('H:i') : '--:--' }}
                  </div>
                  <div class="badge">
                      {{ $project->unread_count > 0 ? $project->unread_count : '' }}
                  </div>
              </div>
          @empty
              <p class="text-muted p-2">No projects available.</p>
          @endforelse
      </div>      
    </div>          
    <div class="message-section">
        <div class="msger-header">
            <h1>Comments</h1>
            <i class="fas fa-comment icon"></i> 
        </div>
        <div class="chat-container" style="overflow-y: auto; padding: 10px; background-color: #f9f9f9; border-radius: 10px;">
         
        </div>              
        <div class="card mt-3 card-designform">
          <form method="POST" id="commentsData" action="{{ route('comments.add') }}">
            @csrf
            <div class="post-item clearfix mb-3 mt-3">
              <label for="comment" class="col-sm-3 col-form-label">Comment</label>
              <div class="col-sm-12">
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
                  <div id="editor" style="height: 300px;">{!! old('comment') !!}</div>
                  <input type="hidden" name="message" id="comment_input">
                  <input type="hidden" name="project_id" id="project_id">
                  @if ($errors->has('comment'))
                      <span style="font-size: 12px;" class="text-danger">{{ $errors->first('comment') }}</span>
                  @endif
              </div>
          </div>
                   
            <div class="mb-3 post-item clearfix upload_chat">
                <label for="comment_file" class="form-label">Attach File</label>
                <input type="file" name="comment_file[]" id="comment_file" class="form-control comment-input" multiple>
            </div>
            <div class="alert alert-danger" style="display:none;"></div>
            <div class="button-design">
                <button type="submit" class="btn btncomment btn-primary float-right" style="padding: 8px 15px;font-size: 1rem; border: none;border-radius: 5px;/ margin: 0px auto; /display: flex;justify-content: flex-start;">
                                <i class="bi bi-send-fill"></i> Comment
                </button>
            </div>
        </form>
        </div>
        </div>
  </div>
  <script>  
  const csrfToken = '{{ csrf_token() }}';
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
              _token: csrfToken
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
    $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
    
    </script> 
        <script>
          $(document).ready(function() {
            // Find any chat with the 'active' class
          const $activeChat = $(".contact.active");

          if ($activeChat.length > 0) {
              const projectId = $activeChat.attr("id").replace("contact-", "");
              loadMessages(projectId); // Call the function to load its messages
          }


          $('#commentsData').on('submit', function(e) {
            e.preventDefault();

            document.getElementById('comment_input').value = quill.root.innerHTML;

            $('.alert-danger').hide().html('');
            const formData = new FormData(this);
            const currentUserId = {{ Auth::id() }};
            const clientName = @json($client->name ?? 'Project Not Assigned');

            $('#loader').show(); 

            $.ajax({
                url: '{{ route('message.add') }}',
                type: 'POST',
                data: formData,
                contentType: false, 
                processData: false, 
                success: function(response) {
                  console.log('fg', response);
                  if (response.status === 200) {
                      // Clear inputs
                      $('#comment').val('');
                      $('#comment_file').val('');
                      quill.root.innerHTML = "";

                      const message = response.message; // assuming `message` is returned from the controller
                      displayMessages(message, false); // Use the new function to append the message
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
      </script>      
                <script>
                  // let currentProjectId = null;
                  
                  function loadMessages(projectId) {
                    // currentProjectId = projectId;
                    const $chatContainer = $(".chat-container");
                    $chatContainer.empty(); // Clear messages

                    // Remove 'active' from all contacts and add to clicked one
                    $(".contact").removeClass("active");
                    $("#contact-" + projectId).addClass("active");

                    // AJAX call to fetch messages
                    $.ajax({
                        url: "{{ url('/get/project/messages') }}/" + projectId,
                        type: "GET",
                        success: function(data) {
                            displayMessages(data.messages); 
                            $('#project_id').val(projectId);
                            document.getElementById('to').value = data.messages[0].user?.id ?? '';
                        },
                        error: function(error) {
                            console.error("Error loading messages:", error);
                        }
                    });
                }
              //   setInterval(function() {
              //     if (currentProjectId) {
              //         loadMessages(currentProjectId);
              //     }
              // }, 1000); 
                function displayMessages(messages, clearMessages = true) {
                  console.log('dfdfd', messages);
                  const $chatContainer = $(".chat-container");

                  // If clearMessages is true, clear the chat container before adding new messages
                  if (clearMessages) {
                      $chatContainer.empty(); // Clear container
                  }

                  if (messages.length === 0) {
                      const noMessagesHTML = `
                          <div class="center text-center mt-2">
                              <span id="NoComments" style="color: #6c757d; font-size: 1rem;">No Comments</span>
                          </div>`;
                      $chatContainer.append(noMessagesHTML);
                      return;
                  }

                  const currentUserId = {{ Auth::id() }};
                  const clientName = @json($client->name ?? 'Project Not Assigned');
                  
                  // Ensure that we are working with an array of messages
                  if (!Array.isArray(messages)) {
                      messages = [messages];  // Convert to array if it's a single message
                  }

                  // Loop through the messages and append each message
                  messages.reverse().forEach(function(message) {
                      const createdAt = new Date(message.created_at).toLocaleString("en-IN", {
                          timeZone: "Asia/Kolkata",
                          year: "numeric", month: "short", day: "numeric",
                          hour: "numeric", minute: "numeric", hour12: true
                      });

                      const avatarHtml = message.user?.profile_picture
                          ? `<div class="avatar" style="background-color: #27ae60;">
                                  <img src="/assets/img/${message.user.profile_picture}" alt="Profile" class="rounded-circle" width="35" height="35">
                            </div>`
                          : `<div class="avatar" style="background-color: #27ae60;">
                                  ${(message.user?.first_name?.substring(0, 2).toUpperCase()) || 'NA'}
                            </div>`;

                            const role = message.user?.first_name || 'Unknown User';

                      const deleteBtn = message.from === currentUserId
                          ? `<button class="btn p-0 border-0 bg-transparent text-danger delete-comment" data-id="${message.id}" title="Delete Comment" style="font-size: 17px; line-height: 1; float: right; margin-bottom: 25px; margin-left: 15px;">
                                  <i class="fa-solid fa-trash"></i>
                            </button>`
                          : '';

                      const documentLinks = (message.document ?? '')
                          .split(',')
                          .filter(doc => doc.trim() !== '')
                          .map(doc => `
                              <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">
                                  <a href="/assets/img/${doc.trim()}" target="_blank">${doc.trim().split('/').pop()}</a>
                              </p>
                          `).join('');

                      const messageHTML = `
                          <div class="message">
                              <div class="info">${createdAt}</div>
                              <div class="user">
                                  ${avatarHtml}
                                  <div style="display: inline-block; vertical-align: middle;">
                                      <span class="name">${message.user?.first_name ?? 'Unknown'}</span>
                                      <span class="role">${role}</span>
                                  </div>
                              </div>
                              <div class="text">
                                  ${deleteBtn}
                                  <p>${message.message}</p>
                                  ${documentLinks}
                              </div>
                          </div>`;

                      $chatContainer.append(messageHTML);
                  });

                  $chatContainer.scrollTop($chatContainer.prop("scrollHeight"));
              }
    </script>    
@endsection
@section('js_scripts')
@endsection
