@extends('layout')

@section('title', 'Task Details')
@section('subtitle', 'View Task')

@section('content')
<div class="action_btn d-flex justify-content-between mb-3">
  <div class="backToSprint">
    <a href="{{ route('bid-sprint.view', $task->bdesprint_id) }}" class="btn btn-primary">
      <i class="fa-solid fa-arrow-left"></i> Back To Sprint
    </a>
  </div>
  <div class="edittask">
    <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-primary">
      <i class="fa-solid fa-pen-to-square"></i> Edit Task
    </a>
  </div>
</div>

<div class="container">
  <div class="task-card expanded p-4 shadow rounded bg-white">
    <div class="task-header d-flex align-items-center mb-3">
      <div class="task-icon me-3">
        <i class="fa-solid fa-folder-open fa-2x text-primary"></i>
      </div>
      <div class="task-title">
        <h4 class="mb-0">{{ $task->job_title }}</h4>
      </div>
    </div>

    <div class="task-details">
      <div class="detail-item mb-2">
        <i class="fa-solid fa-link text-muted me-1"></i>
        <strong>Job Link:</strong>
        <a href="{{ $task->job_link }}" target="_blank">{{ $task->job_link }}</a>
      </div>

      <div class="detail-item mb-2">
        <i class="fa-solid fa-sitemap text-muted me-1"></i>
        <strong>Source:</strong>
        {{ ucfirst($task->source) ?? '---' }}
      </div>

      <div class="detail-item mb-2">
        <i class="fa-solid fa-user text-muted me-1"></i>
        <strong>Profile:</strong>
        {{ ucfirst($task->profile) ?? '---' }}
      </div>

      <div class="detail-item" style="display: inline-flex; align-items: center; gap: 6px;">
        <i class="fa-solid fa-flag text-muted"></i>
        <strong style="margin-right: 4px;">Status:</strong>
        @php
          $statusMap = [
            'applied' => '#2196f3',
            'viewed' => '#ffc107',
            'replied' => '#4caf50',
            'success' => '#9c27b0',
          ];
          $statusColor = $statusMap[$task->status] ?? '#6c757d';
        @endphp
        <span class="badge" style="background-color: {{ $statusColor }}; font-size: 0.75rem; padding: 0.25em 0.5em; border-radius: 0.25rem; min-width: auto; display: inline-block;">
          {{ ucfirst($task->status) }}
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Comment Section Added Below -->
<div class="main-section">
    <div class="msger-header">
        <h1>Comments</h1>
        <i class="fas fa-comment icon"></i> 
    </div>
    <div class="chat-container" style="overflow-y: auto; padding: 10px; background-color: #f9f9f9; border-radius: 10px;">
    {{-- @if(count($comments) > 0)
        @foreach ($comments as $data)
            <div class="message">
                <div class="info">{{ \Carbon\Carbon::parse($data->created_at)->timezone('Asia/Kolkata')->format('M d, Y h:i A') }}</div>
                <div class="user">
                    @if(!empty($data->user->profile_picture))
                        <div class="avatar" style="background-color: #27ae60;">
                            <img src="{{ asset('assets/img/' . $data->user->profile_picture) }}" alt="Profile" class="rounded-circle" width="35" height="35">
                        </div>
                    @else
                        <div class="avatar" style="background-color: #27ae60;">
                            {{ strtoupper(substr($data->user->first_name, 0, 2)) }}
                        </div>
                    @endif
                    <div>
                        <span class="name">{{ $data->user->first_name }}</span> 
                        <span class="role">
                          {{ $data->user->role_id == 6 ? ($projectName ?? 'Project Not Assigned') : 'Code4Each' }}
                        </span>                                  
                    </div>
                </div>
                <div class="text">
                    @if(Auth::user()->id == $data->comment_by)
                        <button class="btn p-0 border-0 bg-transparent text-danger delete-comment" data-id="{{ $data->id }}" title="Delete Comment" style="font-size: 17px; float: right; margin-left: 15px;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    @endif

                    {!! preg_replace('/<p>(h|g)?<\/p>/', '', $data->comments) !!}

                    @php $documents = explode(',', $data->document); @endphp
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
            </div>
        @endforeach
    @else
        <div class="center text-center mt-2">
            <span id="NoComments" style="color: #6c757d; font-size: 1rem;">No Comments</span>
        </div>
    @endif --}}
</div>
    <div class="card mt-3 card-designform">
        <form method="POST" id="commentsData">
            @csrf
            <div class="post-item clearfix mb-3 mt-3">
                <label for="comment" class="col-sm-3 col-form-label">Comment</label>
                <input type="hidden" name="task_id" value="{{ $task->id }}">
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
                    <div id="editor" style="height: 300px;"></div>
                    <input type="hidden" name="comment" id="comment_input">
                </div>
            </div>

            <div class="mb-3 post-item clearfix upload_chat">
                <label for="comment_file" class="form-label">Attach File</label>
                <input type="file" name="comment_file[]" id="comment_file" class="form-control comment-input" multiple>
            </div>
            <div class="alert alert-danger" style="display:none;"></div>
            {{-- Removed the hidden_id input with variable --}}
            <div class="button-design">
                <button type="submit" class="btn btncomment btn-primary float-right" style="padding: 8px 15px; font-size: 1rem; border: none; border-radius: 5px;">
                    <i class="bi bi-send-fill"></i> Comment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js_scripts')
<script>
$(document).ready(function () {
    $('#commentsData').on('submit', function (e) {
        e.preventDefault();

        // Set comment content from Quill into hidden input
        $('#comment_input').val(quill.root.innerHTML);
        $('.alert-danger').hide().html('');

        const fileInput = document.getElementById('comment_file');
        const hasText = quill.getText().trim().length > 0;
        const hasFile = fileInput.files && fileInput.files.length > 0;

        if (hasText || hasFile) {
            let formData = new FormData(this);

            $('#loader').show();

            $.ajax({
                url: '{{ route("bde.comment.add") }}',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    if (response.status === 200) {
                        // Reset form fields
                        quill.setContents([]);
                        $('#comment_file').val('');
                        $('#comment_input').val('');
                        $('.alert-danger').hide();

                        // Reload or append new comment (your choice)
                        location.reload();
                    } else if (response.errors) {
                        let errorHtml = '<ul>';
                        response.errors.forEach(function (error) {
                            errorHtml += '<li>' + error + '</li>';
                        });
                        errorHtml += '</ul>';
                        $('.alert-danger').html(errorHtml).fadeIn();
                    } else {
                        $('.alert-danger').html('Something went wrong.').fadeIn();
                    }
                },
                error: function (xhr) {
                    $('.alert-danger').html('An error occurred while submitting the comment.').fadeIn();
                },
                complete: function () {
                    $('#loader').hide();
                }
            });
        } else {
            $('.alert-danger').html('Kindly type a message or attach a file before submitting.').fadeIn();
        }
    });
});
</script>
@endsection
