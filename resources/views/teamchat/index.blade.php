@extends('layout')
@section('title', 'TeamChat')
@section('subtitle', 'Chat')
@section('show_title', 'false')
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
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      display: flex;
      padding: 0;

    }
    .container.chat-wrapper {
        height: 1100px;
        overflow: hidden;
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
    /* max-height: 715px; */
    overflow-y: auto;
    padding-right: 4px;
}
.contact .name {
  word-break: break-word;
  overflow-wrap: anywhere;
  white-space: normal;
}


.details {
    display: flex;
    flex-direction: column;
    width: 240px;
    align-items: flex-start;
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
        gap: 10px;
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

    .contact .name {
    word-break: break-word;
    overflow-wrap: anywhere;
    white-space: normal;
    FONT-SIZE: 14PX;
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
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 10px;
    gap: 10px;
}
.chat-container{

    overflow-y: auto;
    background-color: #f9f9f9;
    border-radius: 10px;
    padding: 10px;
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
    .card.mt-3.card-designform {
        flex: 1;
        /* overflow-y: auto; */
        margin-top: 10px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .card-designform {
          background-color: #ffffff;
          border-radius: 10px;
          padding: 0px 20px 15px;
          box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
          margin-top: 20px;
          margin-bottom: 5px;
      }
    @media (max-width: 768px) {
      .chat-wrapper {
        flex-direction: column;
        height: fit-content !important;
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
    .chat-container{
        max-height: 450px;
    }
}

.message-section form#commentsData {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 0px;
}
.chat-wrapper .card.mt-3.card-designform {
    margin-bottom: 0;
}
  </style>
<div class="container chat-wrapper">
    <div class="chatsidebar">
        <div class="sidebar-header">Team Chat</div>
        <div class="contact-list">
            @foreach($projects->where('status', 'active') as $project)
                <div class="contact {{ $loop->first ? 'active' : '' }}" onClick="loadMessages({{ $project->id }}); markMessageAsRead({{ $project->last_message->id ?? 'null' }}, {{ $project->id }}); hideUnreadCount({{ $project->id }}); handleProjectClick({{ $project->id }});" id="contact-{{ $project->id }}">
                           <div class="avatar" style="background-color: #27ae60; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    {{ strtoupper(substr($project->project_name, 0, 2)) }}
                </div>
                <div class="details">
                    <div class="name">{{ $project->project_name }}</div>
                    @if ($roleid != 6)
                    <div class="project">{{ $project->client->name ?? 'N/A' }}</div>
                    @endif
                    <div class="last-message">
                        {{ Str::limit(strip_tags(html_entity_decode($project->last_message ? $project->last_message->message : ' ')), 15) }}
                    </div>
                </div>
                <div class="time">
                    {{ $project->last_message ? $project->last_message->created_at->timezone('Asia/Kolkata')->format('g:i a') : '' }}
                </div>
                <div class="badge" id="unread-count-{{ $project->id }}" style="{{ $project->unread_count > 0 ? '' : 'display: none;' }}">
                    {{ $project->unread_count }}
                </div>
            </div>
            @endforeach
            @foreach($projects->where('status', '!=', 'active') as $project)
            <div class="contact" style="background-color: #d4d4d4;" onClick="loadMessages({{ $project->id }}); markMessageAsRead({{ $project->last_message->id ?? 'null' }}, {{ $project->id }}); hideUnreadCount({{ $project->id }});" id="contact-{{ $project->id }}">
                <div class="avatar" style="background-color: #27ae60; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    {{ strtoupper(substr($project->project_name, 0, 2)) }}
                </div>
                <div class="details">
                    <div class="name">{{ $project->project_name }}</div>
                    <div class="project">{{ $project->client->name ?? 'N/A' }}</div>
                    <div class="last-message">
                        {{ Str::limit(strip_tags(html_entity_decode($project->last_message ? $project->last_message->message : ' ')), 15) }}
                    </div>
                </div>
                <div class="time">
                    {{ $project->last_message ? $project->last_message->created_at->timezone('Asia/Kolkata')->format('g:i a') : '' }}
                </div>
                @if($project->unread_count > 0)
                <div class="badge" id="unread-count-{{ $project->id }}">
                    {{ $project->unread_count }}
                </div>
                @endif
            </div>
            @endforeach
            @if($projects->isEmpty())
            <p class="text-muted p-2">No projects available.</p>
            @endif
        </div>
    </div>
    <div class="message-section">
        <div class="msger-header">
            <h1>Comments</h1>
            <i class="fas fa-comment icon"></i>
        </div>
        <div class="chat-container" style="overflow-y: auto; padding: 10px; background-color: #f9f9f9; border-radius: 10px;">

        </div>
        <div class="card mt-0 card-designform">
          <form method="POST" id="commentsData" action="{{ route('message.adds') }}">
            @csrf
            <div class="post-item clearfix mb-3 mt-0">
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
                  <input type="hidden" name="message" id="comment_input" value="{!! old('comment') !!}">
                  <input type="hidden" name="project_id" id="project_id">
                  <input type="hidden" name="comment_id" id="comment_id" value="">
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
@endsection
@section('js_scripts')
<script>
    function loadMessages(projectId) {
    offset = 0;
    allMessagesLoaded = false;

    const $chatContainer = $(".chat-container");
    $chatContainer.off("scroll").empty();

    $(".contact").removeClass("active");
    const $contact = $("#contact-" + projectId);
    if ($contact.length > 0) {
        $contact.addClass("active");
    } else {
        console.warn(`#contact-${projectId} not found`);
    }

    if (typeof quill !== 'undefined' && quill.root) {
        quill.root.innerHTML = "";
    } else {
        console.warn("Quill is not initialized or missing.");
    }

    $('#comment_file').val('');
    $('#project_id').val(projectId);

    $.ajax({
        url: `/get/project/group-messages/${projectId}?offset=${offset}`,
        type: "GET",
        cache: false,
        success: function(data) {
            if (data.messages && Array.isArray(data.messages)) {
                offset += data.messages.length;
                displayMessages(data.messages, true);
                setupInfiniteScroll(projectId);
            } else {
                console.error("Unexpected response format:", data);
            }
        },
        error: function(error) {
            console.error("Error loading messages:", error);
        }
    });
}


    let offset = 0;
    let isLoadingOlder = false;
    let allMessagesLoaded = false;

    $(document).ready(function() {
        // Find any chat with the 'active' class
        const $activeChat = $(".contact.active");
        if ($activeChat.length > 0) {
            const projectId = $activeChat.attr("id").replace("contact-", "");
            loadMessages(projectId); // Call the function to load its messages
        }
     const quill = new Quill(editor, {
        theme: 'snow',
        modules: {
            toolbar: '#toolbar-container'
        },
        placeholder: 'Type your comment here...'
    });

   $(document).on('submit', '#commentsData', function(e) {
  e.preventDefault();
 

  // Check if Quill is initialized
  if (typeof quill === 'undefined') {
    console.error('[ERROR] Quill editor is not initialized.');
    return;
  }

  const commentHtml = quill.root.innerHTML.trim();
  const commentText = quill.getText().trim();
  

  const commentInput = document.getElementById('comment_input');
  if (!commentInput) {
    console.error('[ERROR] Hidden input #comment_input not found');
    return;
  }
  commentInput.value = commentHtml;

  $('.alert-danger').hide().html('');

  const fileInput = document.getElementById('comment_file');
  const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
  const hasValidContent = commentText.length > 0 || /<img|<video|<iframe|<audio/.test(commentHtml);
  

  if (hasValidContent || hasFile) {
    const formData = new FormData(this);
    $('#loader').show();

    $.ajax({
      url: '{{ route('message.adds') }}', // If this is an external JS file, replace this with a JS variable
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function(response) {
        console.log('[DEBUG] AJAX success:', response);

        if (response.status === 200) {
          console.log('[DEBUG] Submission successful');
          $('#comment').val('');
          $('#comment_file').val('');
          location.reload();
        } else if (response.errors) {
          console.warn('[WARNING] Server returned validation errors:', response.errors);
          let errorHtml = '<ul>';
          response.errors.forEach(function(error) {
            errorHtml += '<li>' + error + '</li>';
          });
          errorHtml += '</ul>';
          $('.alert-danger').show().html(errorHtml);
        } else {
          console.error('[ERROR] Unexpected response format:', response);
          $('.alert-danger').show().html('Something went wrong.');
        }
      },
      error: function(xhr) {
        console.error('[ERROR] AJAX request failed:', xhr);
        $('.alert-danger').show().html('An error occurred while submitting the comment.');
      },
      complete: function() {
        console.log('[DEBUG] AJAX request complete');
        $('#loader').hide();
      }
    });
  } else {
    console.warn('[WARNING] No valid content or file to submit');
    $('.alert-danger').html('Kindly type a message or attach a file before submitting.').fadeIn();
  }
});
    });

      function handleProjectClick(projectId) {
        const newUrl = `${window.location.origin}${window.location.pathname}?project_id=${projectId}`;
        window.history.pushState({ path: newUrl }, '', newUrl);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('project_id');
        fetchUnreadCount(projectId);
        if (projectId) {
            document.querySelectorAll('.contact').forEach(el => el.classList.remove('active'));
            const contactDiv = document.getElementById(`contact-${projectId}`);
            if (contactDiv) {
                contactDiv.classList.add('active');
                handleProjectClick(projectId);
            }
        }
    });
    
    function displayMessages(messages, clearMessages = true, prepend = false) {
    const $chatContainer = $(".chat-container");

    if (!Array.isArray(messages)) {
        messages = [messages];
    }

    if (clearMessages) {
        $chatContainer.empty();
    }

    if (messages.length === 0 && clearMessages) {
        $chatContainer.append(`<div class="center text-center mt-2">
            <span id="NoComments" style="color: #6c757d; font-size: 1rem;">No Comments</span>
        </div>`);
        return;
    }

    messages.reverse(); 

    const currentUserId = {{ Auth::id() }};
    const clientName = @json($client->name ?? 'Project Not Assigned');

    let lastDateLabel = "";

    messages.forEach(function(message) {
        const msgDate = new Date(message.created_at);
        const msgDayLabel = getDateLabel(msgDate);

        const createdAt = msgDate.toLocaleString("en-IN", {
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

        const deleteBtn = message.user_id === currentUserId
            ? `<button class="btn p-0 border-0 bg-transparent text-danger delete-comment" data-id="${message.id}" title="Delete Comment" style="font-size: 17px; line-height: 1; float: right; margin-bottom: 25px; margin-left: 8px;">
                    <i class="fa-solid fa-trash"></i>
                </button>` : '';

        const editBtn = message.user_id === currentUserId
            ? `<button class="btn p-0 border-0 bg-transparent text-primary edit-comment"
                        data-comment-id="${message.id}"
                        data-content="${encodeURIComponent(message.message)}"
                        title="Edit Comment"
                        style="font-size: 17px; line-height: 1; float: right; margin-bottom: 25px; margin-left: 10px;">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>` : '';

        const documentLinks = (message.document ?? '')
            .split(',')
            .filter(doc => doc.trim() !== '')
            .map(doc => `
                <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">
                    <a href="/assets/img/${doc.trim()}" target="_blank">${doc.trim().split('/').pop()}</a>
                </p>
            `).join('');

        let html = `
            <div class="message" id="message-${message.id}">
                <div class="info">${createdAt}</div>
                <div class="user">
                    ${avatarHtml}
                    <div style="display: inline-block; vertical-align: middle;">
                        <span class="name">${message.user?.first_name ?? 'Unknown'}</span>
                    </div>
                </div>
                <div class="text">
                    ${deleteBtn}
                    <p>${message.message}</p>
                    ${documentLinks}
                </div>
            </div>`;

        const existingMessage = $(`#message-${message.id}`);
        if (existingMessage.length > 0) {
            existingMessage.replaceWith(html);
        } else {
            // Add date label only if it's a new message (not replacement)
            if (msgDayLabel !== lastDateLabel) {
                $chatContainer.append(`
                    <div class="date-label text-center my-3">
                        <span style="background: #eee; padding: 4px 12px; border-radius: 20px; color: #555; font-weight: 500;">
                            ${msgDayLabel}
                        </span>
                    </div>`);
                lastDateLabel = msgDayLabel;
            }

            if (prepend) {
                $chatContainer.prepend(html);
            } else {
                $chatContainer.append(html);
            }
        }
    });

    if (!prepend) {
        $chatContainer.scrollTop($chatContainer.prop("scrollHeight"));
    }
}
   function markMessageAsRead(messageId, projectId) {
    fetch(`/group-messages/${messageId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json', // important to ask for JSON response
        },
        body: JSON.stringify({ project_id: projectId })
    })
    .then(res => {
        if (!res.ok) {
            // If status is not OK, try to get text for debugging
            return res.text().then(text => {
                console.error('Server response (not JSON):', text);
                throw new Error(`HTTP error! Status: ${res.status}`);
            });
        }
        return res.json(); // parse JSON only if status is OK
    })
    .then(data => {
        if (data.status === 'success') {
            const unreadCountElement = document.getElementById('unread-count-' + projectId);
            const unreadCount = data.updatedUnreadCount;

            if (unreadCountElement) {
                unreadCountElement.textContent = unreadCount > 0 ? unreadCount : '';
                unreadCountElement.style.display = unreadCount > 0 ? 'inline-block' : 'none';
            }
        } else {
            console.warn(data.message || 'Unknown error from server');
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
    });
}

function fetchUnreadCount(projectId) {
    fetch(`/project-messages/${projectId}/unread-count`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const badge = document.getElementById('unread-count-' + projectId);
                if (badge) {
                    if (data.unreadCount > 0) {
                        badge.textContent = data.unreadCount;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                } else {
                    console.warn('Badge element not found for project ID:', projectId);
                }
            } else {
                console.warn('Unexpected response:', data);
            }
        })
        .catch(err => console.error('Error fetching unread count:', err));
}


     function setupInfiniteScroll(projectId) {
        const $chatContainer = $(".chat-container");

        $chatContainer.on("scroll", function() {
            if ($chatContainer.scrollTop() <= 50 && !isLoadingOlder && !allMessagesLoaded) {
                isLoadingOlder = true;
                $chatContainer.find('.loading-older').remove();
                // Show loading message
                const $loadingMessage = $(`
                    <div class="loading-older text-center my-2"> <i class="fas fa-spinner fa-spin" style="font-size: 16px; color: #888;"></i> </div> `
                );
                $chatContainer.prepend($loadingMessage);

                const oldScrollHeight = $chatContainer[0].scrollHeight;

                $.ajax({
                    url: `/get/project/group-messages/${projectId}?offset=${offset}`,
                    type: "GET",
                    success: function(data) {
                        setTimeout(() => {
                            $loadingMessage.remove();
                            if (data.messages.length === 0) {
                                allMessagesLoaded = true;
                                return;
                            }

                            offset += data.messages.length;

                            displayMessages(data.messages, false, true); // Prepend older
                            const newScrollHeight = $chatContainer[0].scrollHeight;
                            $chatContainer.scrollTop(newScrollHeight - oldScrollHeight); // Preserve position
                        }, 1000);
                    },
                    complete: function() {
                        isLoadingOlder = false;
                    }
                });
            }
        });
    }
     function getDateLabel(date) {
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);

        const msgDate = new Date(date);
        const msgDateStr = msgDate.toDateString();

        if (msgDateStr === today.toDateString()) {
            return 'Today';
        } else if (msgDateStr === yesterday.toDateString()) {
            return 'Yesterday';
        } else {
            return msgDate.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            }); // Example: 13 May 2025
        }
    }
    function hideUnreadCount(projectId) {
        const badge = document.getElementById('unread-count-' + projectId);
        if (badge) {
            badge.style.display = 'none';
        }
    }
    function deleteComment(commentId, commentElement) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    $.ajax({
        url: `/comments/${commentId}`, // uses the RESTful route
        type: 'DELETE',
        data: {
            _token: token
        },
        success: function (response) {
            if (response.status === 200) {
                commentElement.fadeOut(300, function () {
                    $(this).remove();
                });

                location.reload();
            } else {
                alert(response.message || 'Failed to delete comment.');
            }
        },
        error: function () {
            alert('An error occurred while deleting the comment.');
        }
    });
}

// Delegated event listener
$(document).on('click', '.delete-comment', function () {
    const commentId = $(this).data('id');
    const commentItem = $(this).closest('.post-item');

    if (confirm('Are you sure you want to delete this comment?')) {
        deleteComment(commentId, commentItem);
    }
});

</script> 
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editor = document.querySelector('#editor');
    if (!editor) {
        console.error('Editor element not found!');
        return;
    }

    // Initialize with old content if available
    const oldComment = document.getElementById('comment_input').value.trim();
    if (oldComment) {
        quill.root.innerHTML = oldComment;
    } else {
        quill.setContents([{ insert: '\n' }]);
    }

    editor.__quillInstance = quill;

    function decodeHTMLEntities(str) {
        const txt = document.createElement('textarea');
        txt.innerHTML = str;
        return txt.value;
    }

    // Bind edit-comment buttons after DOM is ready
    document.querySelectorAll('.edit-comment').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.getAttribute('data-comment-id');
            let commentContent = this.getAttribute('data-content') || '';
            commentContent = decodeHTMLEntities(decodeHTMLEntities(commentContent));

            document.getElementById('comment_input').value = commentContent;

            const commentIdInput = document.querySelector('#comment_id');
            if (commentIdInput) commentIdInput.value = commentId;

            if (quill) {
                quill.root.innerHTML = commentContent;
            }

            editor.scrollIntoView({ behavior: 'smooth' });
        });
    });
});
</script>   
@endsection
