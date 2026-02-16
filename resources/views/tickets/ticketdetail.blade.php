@extends('layout')
@section('title', 'Ticket Details')
@section('subtitle', 'Ticket')
@section('content')
<div class="action_btn mt-3 d-flex flex-wrap gap-2 align-items-center mb-3">
    {{-- Back To Sprint Button --}}
    @if(!empty($tickets->sprint_id))
        <a href="{{ route('sprint.view', $tickets->sprint_id) }}" class="btn btn-primary">
            Back To Sprint
        </a>
    @endif

    {{-- Edit Ticket Button --}}
    <a href="{{ url('/edit/ticket/'.$tickets->id) }}?source=sprint" class="btn btn-primary">
        Edit Ticket
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
        <!-- <span class="task-status">
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
        </span> -->
      </div>
      <div class="task-toggle-icon">
        <i class="fa-solid fa-chevron-down"></i>
      </div>
    </div>

    <div class="task-details"> <!-- Always visible -->
     <div class="detail-item">
        <i class="fa-solid fa-align-left"></i>
        <strong>Description:</strong>
        
        @php
            $description = strip_tags($tickets->description); // Strip HTML for word count
            $words = explode(' ', $description);
            $shortDescription = implode(' ', array_slice($words, 0, 100));
            $isLong = count($words) > 100;
        @endphp

        <div>
            <span class="short-description">{!! nl2br(e($shortDescription)) !!}@if($isLong)... @endif</span>
            @if($isLong)
                <span class="full-description" style="display: none;">{!! $tickets->description !!}</span>
                <a href="javascript:void(0);" class="toggle-description" onclick="toggleDescription(this)">Show More</a>
            @endif
        </div>
    </div>

      <div class="detail-item">
        <i class="fa-solid fa-diagram-project"></i>
        <strong>Project:</strong>
        @foreach ($projects as $project)
          <span>{{ $project['project_name'] ?? '---' }}</span>
        @endforeach
      </div>
     @if($tickets->time_estimation)
          <div class="detail-item d-flex align-items-center gap-2">
              <i class="fa-solid fa-diagram-project"></i>
              <strong>Time Estimation:</strong>
              <span>
                  {{ rtrim(rtrim(number_format((float) $tickets->time_estimation, 2, '.', ''), '0'), '.') . ' hours' }}

                  @php
                      $isApproved = \App\Models\TicketEstimationApproval::where('ticket_id', $tickets->id)->exists();
                  @endphp

                 @if($isApproved)
                    <span class="badge bg-success ms-2">
                        <i class="fa-solid fa-check-circle me-1 white-icon"></i> Approved
                    </span>

                    @php
                      function hoursToHM($decimal) {
                          $hours = floor($decimal);
                          $minutes = round(($decimal - $hours) * 60);

                          return [
                              'h' => $hours,
                              'm' => $minutes
                          ];
                      }

                      $spent = hoursToHM($spentHours);
                      $remaining = hoursToHM(max($remainingHours, 0));
                    @endphp

                    @if(Auth::user()->role_id != 6) 
                      <span class="badge bg-info ms-2">
                          Spent: {{ $spent['h'] }} hrs {{ $spent['m'] }} min
                      </span>

                      <span class="badge bg-warning text-dark ms-2">
                          Remaining: {{ $remaining['h'] }} hrs {{ $remaining['m'] }} min
                      </span>
                    
                      @if($remainingHours > 0)
                        <span class="badge ">
                          <button class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#logHoursModal">
                              + Log Hours
                          </button>
                        </span>
                      @endif
                    
                      @if(!empty($tickets->workLogs))
                        <span class="badge ">
                          <button class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#showWorkLogs">
                            Logs History
                          </button>
                        </span>
                      @endif
                    @endif
                    
                @elseif($tickets->time_estimation && in_array(Auth::user()->role_id, [1, 6]))
                    <a href="{{ route('ticket.approveEstimation', $tickets->id) }}" class="badge bg-success text-white text-decoration-none ms-2">
                        <i class="fa-solid fa-check-circle me-1 white-icon"></i> Approve Estimation
                    </a>
                @endif
              </span>
          </div>
      @endif
      <div class="detail-item">
        <i class="fa-solid fa-diagram-project"></i>
        <strong>Category:</strong>
          <span>
            {{ $tickets->ticket_category ? trim($tickets->ticket_category, '{}') : '---' }}
        </span>
      </div>

      <div class="detail-item">
        <i class="fa-solid fa-user"></i>
        <strong>Client:</strong>
        <span>
          @if($client)
              <span> {{ $client->pluck('name')->join(', ') }}</span>
          @else
              <span>---</span>
          @endif
        </span>
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
        <strong>Created At:</strong>
        <span>{{ !empty($tickets->created_at) ? date("d/m/Y", strtotime($tickets->created_at)) : '---' }}</span>
      </div>

      <div class="detail-item">
        <i class="fa-solid fa-calendar-day"></i>
        <strong>ETA:</strong>
        <span>{{ !empty($tickets->eta) ? date("d/m/Y", strtotime($tickets->eta)) : '---' }}</span>
      </div>

      @php
          $priorityColors = [
              'normal' => '#3f996b',
              'low' => '#cda21d',
              'high' => '#D66A00',
              'urgent' => '#b00000d1',
          ];

          $priority = $tickets->priority ?? 'urgent';
          $bgColor = $priorityColors[$priority] ?? '#6c757d';
      @endphp

      <div class="detail-item">
          <i class="fa fa-layer-group"></i>
          <strong>Priority:</strong>
          <div><span class="priority {{ $priority }}" style="background-color: {{ $bgColor }}; color: white; padding: 6px 35px; border-radius: 5px; text-align: center;">
              {{ ucfirst($priority) }}
          </span>
      </div>
      </div>

    @php
      $statusLabels = [
          'to_do' => 'To do',
          'in_progress' => 'In progress',
          'ready' => 'Ready',
          'deployed' => 'Deployed',
          'complete' => 'Complete',
          'invoice_done' => 'Invoice Done'
      ];
      $statusColors = [
          'to_do' => '#948979',
          'in_progress' => '#3fa6d7',
          'ready' => '#e09f3e',
          'deployed' => '#e76f51',
          'complete' => '#2a9d8f',
          'invoice_done' => '#e76f51'
      ];
      $bgColor = $statusColors[$tickets->status] ?? '#6c757d';
  @endphp
  <div class="detail-item">
    <i class="fa-solid fa-bolt"></i>
    <strong>Ticket Status:</strong>
    @if(Auth::user()->role_id != 6)
        <div class="dropdown d-inline-block ms-0">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle status-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: {{ $bgColor }}; border-color: {{ $bgColor }}; color: white;">
                {{ $statusLabels[$tickets->status] ?? ucfirst($tickets->status) }}
            </button>
            <ul class="dropdown-menu status-options" data-ticket-id="{{ $tickets->id }}">
                @foreach(array_keys($statusLabels) as $status)
                    <li>
                        <a class="dropdown-item" href="#" data-value="{{ $status }}">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
      <button class="btn btn-sm btn-outline-secondary status-button ms-0" type="button" style="background-color: {{ $bgColor }}; border-color: {{ $bgColor }}; color: white; cursor: default;"> {{ $statusLabels[$tickets->status] ?? ucfirst($tickets->status) }}
      </button>
    @endif
  </div>
  </div>
  </div>
</div>

<!-- Log Hours Modal -->
<div class="modal fade" id="logHoursModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('ticket.logHours') }}">
        @csrf
        <input type="hidden" name="ticket_id" value="{{ $tickets->id }}">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Work Hours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label>Date</label>
                    <input type="date" name="log_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>

                <div class="mb-3">
                  <label>Hours Worked</label>

                  <div class="d-flex gap-2">
                      <select name="hours" id="hoursSelect" class="form-control" required>
                          @for($i = 0; $i <= floor($remainingHours); $i++)
                              <option value="{{ $i }}">{{ $i }} hr</option>
                          @endfor
                      </select>
                      @if ($errors->has('hours'))
                          <small class="text-danger">
                              {{ $errors->first('hours') }}
                          </small>
                      @endif

                      <select name="minutes" id="minutesSelect" class="form-control" required>
                          <option value="0">00 min</option>
                          <option value="15">15 min</option>
                          <option value="30">30 min</option>
                          <option value="45">45 min</option>
                      </select>
                  </div>

                  <small class="text-muted">
                      Remaining: {{ max($remainingHours, 0) }} hrs
                  </small>

                  <small class="text-danger d-none" id="timeError">
                      Total time cannot exceed remaining hours
                  </small>
                </div>

                <div class="mb-3">
                    <label>Note (optional)</label>
                    <textarea name="note" class="form-control"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save Log</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Show Logs Modal -->
<div class="modal fade" id="showWorkLogs" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Ticket Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Hours Logged</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                      @foreach($tickets->workLogs as $log)
                      <tr>
                          <td>{{ $log->log_date }}</td>
                          <td>{{ $log->user->first_name }}</td>
                          <td>{{ floor($log->hours) }} hr {{ ($log->hours - floor($log->hours)) * 60 }} min</td>
                          <td>{{ $log->note ?? '-' }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

          <div class="main-section">
            <div class="msger-header">
                <h1>Comments</h1>
                <i class="fas fa-comment icon"></i>
            </div>
            @php
              use Carbon\Carbon;
              $lastGroupDate = null;
            @endphp
            <div class="chat-container" id="comment-scroll"  style="height: {{ count($CommentsData) ? '600px' : 'auto' }}; overflow-y: auto; padding: 10px; background-color: #f9f9f9; border-radius: 10px;">
               <div id="spinner_loader" style="display:none; text-align:center; padding: 10px;">
                  <i class="fas fa-spinner fa-spin" style="font-size: 16px; color: #888;"></i>
              </div>
                @if(count($CommentsData) != 0)
                  <div id="comments-list">
                    @foreach ($CommentsData as $data)
                      @php
                        $commentDate = Carbon::parse($data->created_at)->timezone('Asia/Kolkata')->startOfDay();
                        $today = Carbon::now()->startOfDay();
                        $yesterday = Carbon::yesterday()->startOfDay();

                        if ($commentDate->eq($today)) {
                            $groupDateLabel = 'Today';
                        } elseif ($commentDate->eq($yesterday)) {
                            $groupDateLabel = 'Yesterday';
                        } else {
                            $groupDateLabel = $commentDate->format('M d, Y');
                        }
                      @endphp
                      @if ($lastGroupDate !== $groupDateLabel)
                          <div class="text-center text-muted my-2 date-label" style="font-weight: bold; font-size: 14px;">
                              {{ $groupDateLabel }}
                          </div>
                          @php $lastGroupDate = $groupDateLabel; @endphp
                      @endif
                        <div class="message" data-id="{{ $data->id }}" id="comment-{{ $data->id }}">
                            <div class="info">{{ \Carbon\Carbon::parse($data->created_at)->timezone('Asia/Kolkata')->format('M d, Y h:i A') }}</div>
                            @if(!$data->is_system)
                                  <div class="user">
                                      @if(!empty($data->user->profile_picture))
                                          <div class="avatar" style="background-color: #27ae60;">
                                              <img src="{{ asset('assets/img/' . $data->user->profile_picture) }}" alt="Profile" class="rounded-circle" width="35" height="35">
                                          </div>
                                      @else
                                          <div class="avatar" style="background-color: #27ae60;">{{ strtoupper(substr($data->user->first_name, 0, 2)) }}</div>
                                      @endif
                                      <div class="d-flex justify-content-between align-items-start w-100">
                                        <div>
                                            <span class="name">{{ $data->user->first_name }}</span>
                                            <span class="role">
                                                @if ($data->user->role_id == 6)
                                                    {{ $projectName ?? 'Project Not Assigned' }}
                                                @else
                                                    Code4Each
                                                @endif
                                            </span>
                                        </div>

                                        <button type="button"
                                                class="btn btn-sm btn-link p-0 share-comment"
                                                data-comment-id="{{ $data->id }}"
                                                title="Copy comment link">
                                            <i class="fa-solid fa-link"></i>
                                        </button>
                                      </div>

                                  </div>
                              @endif
                            <div class="text">
                              @if(!$data->is_system)
                              @if(Auth::user()->id == $data->comment_by)
                                <button class="btn p-0 border-0 bg-transparent text-danger delete-comment" data-id="{{ $data->id }}" title="Delete Comment" style="font-size: 17px;line-height: 1;float: right;margin-bottom: 25px;margin-left: 8px;">
                                  <i class="fa-solid fa-trash"></i>
                              </button>
                              @endif
                              @if(Auth::user()->id == $data->comment_by)
                              <button class="btn p-0 border-0 bg-transparent text-primary edit-comment"
                                      data-comment-id="{{ $data->id }}"
                                      data-content="{{ htmlspecialchars($data->comments, ENT_QUOTES) }}"
                                      title="Edit Comment"
                                      style="font-size: 17px; line-height: 1; float: right; margin-bottom: 25px;">
                                <i class="fa-solid fa-pen-to-square"></i>
                              </button>
                            @endif
                            @endif
                              <div style="word-break: auto-phrase;">
                                  {!! preg_replace('/<p>(h|g)?<\/p>/', '', $data->comments) !!}
                              </div>

                                @php
                                    $documents = explode(',', $data->document);
                                @endphp

                                <!-- Display Documents -->
                              @foreach ($documents as $doc)
                                @if (!empty($doc))
                                    @php
                                        $fileName = basename($doc);
                                        $isCsv = \Illuminate\Support\Str::endsWith(strtolower($fileName), '.csv');
                                        $isExternal = \Illuminate\Support\Str::startsWith($doc, 'http');

                                        $downloadUrl = $isExternal
                                            ? $doc
                                            : route('public.file.download', ['filename' => $fileName]);
                                    @endphp

                                    <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">
                                        <a href="{{ $downloadUrl }}"
                                          target="_blank"
                                          @if ($isCsv) download @endif>
                                            {{ $fileName }}
                                        </a>
                                    </p>
                                @endif
                            @endforeach
                            </div>
                            <!-- Delete button (only for the comment owner) -->
                        </div>
                    @endforeach
                  </div>
                @else
                    <div class="center text-center mt-2">
                        <span id="NoComments" style="color: #6c757d; font-size: 1rem;">No Comments</span>
                    </div>
                @endif
            </div>
            <div class="card mt-3 card-designform">
            <form method="POST" id="commentsData" action="{{ route('comments.add') }}">
              @csrf
              <div class="post-item clearfix mb-3 mt-3">
                <label for="comment" class="col-sm-3 col-form-label">Comment</label>
                <input type="hidden" name="comment_id" id="comment_id" value="">
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
                    <input type="hidden" name="comment" id="comment_input" value="{{ old('comment') }}">


                    @if ($errors->has('comment'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('comment') }}</span>
                    @endif
                </div>
            </div>

              <div class="mb-3 post-item clearfix upload_chat">
                <label for="comment_files" class="form-label">Attach File</label>
                <input type="file" name="comment_files[]" id="comment_files" class="form-control comment-input" multiple>
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
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
  let loading = false;
  let doneLoadingAll = false;

  $(document).ready(function () {
    
    const commentSection = document.getElementById('comment-scroll'); 
    commentSection.scrollTop = commentSection.scrollHeight;
  });

  document.addEventListener('DOMContentLoaded', function () {
    const editor = document.querySelector('#editor');

    if (!editor) {
      console.error('Editor element not found!');
      return;
    }

    const quill = new Quill(editor, {
      theme: 'snow',
      modules: {
        toolbar: '#toolbar-container'
      },
      placeholder: 'Type your comment here...'
    });

    // Initialize with old content if available
    const oldComment = document.getElementById('comment_input').value.trim();
    if (oldComment) {
        quill.root.innerHTML = oldComment;
    } else {
        quill.setContents([{ insert: '\n' }]); // sets a single empty line
    }

    editor.__quillInstance = quill;

    function decodeHTMLEntities(str) {
      const txt = document.createElement('textarea');
      txt.innerHTML = str;
      return txt.value;
    }

    // Edit Comment Click
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

    // Submit Form
    $('#commentsData').on('submit', function (e) {
      e.preventDefault();

      const commentHtml = quill.root.innerHTML.trim();
      const commentText = quill.getText().trim();

      document.getElementById('comment_input').value = commentHtml;
      $('.alert-danger').hide().html('');

      const fileInput = document.getElementById('comment_files');
      const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

      const hasValidContent =
        commentText.length > 0 ||
        /<img|<video|<iframe|<audio/.test(commentHtml) ||
        hasFile;

      if (hasValidContent) {
        const formData = new FormData(this);

        $('#loader').show();

        $.ajax({
          url: '{{ route('comments.add') }}',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function (response) {
            if (response.status === 200) {
              $('#comment').val('');
              $('#comment_files').val('');
              location.reload();
            } else if (response.errors) {
              let errorHtml = '<ul>';
              response.errors.forEach(function (error) {
                errorHtml += '<li>' + error + '</li>';
              });
              errorHtml += '</ul>';
              $('.alert-danger').show().html(errorHtml);
            } else {
              $('.alert-danger').show().html('Something went wrong.');
            }
          },
          error: function () {
            $('.alert-danger').show().html('An error occurred while submitting the comment.');
          },
          complete: function () {
            $('#loader').hide();
          }
        });
      } else {
        $('.alert-danger').html('Kindly type a message or attach a file before submitting.').fadeIn();
      }
    });

    let loading = false;
    let doneLoadingAll = false;

    $('#comment-scroll').on('scroll', function () {
        const container = $(this);

        if (container.scrollTop() <= 50 && !loading && !doneLoadingAll) {
            const firstComment = $('#comments-list .message').first();
            const lastId = firstComment.data('id');

            loading = true;
            $('#spinner_loader').show();

            const prevScrollHeight = container[0].scrollHeight;

            setTimeout(() => {
                $.ajax({
                    url: '{{ route('ticket.comments.load', ['ticketId' => $tickets->id]) }}',
                    method: 'GET',
                    data: { last_id: lastId },
                    success: function (res) {
                        if (res.comments && res.comments.length > 0) {
                            let html = '';
                            let lastDateGroup = $('#comments-list .date-label').first().text().trim();

                            res.comments.forEach(function (data) {
                                const currentGroupDate = data.created_date_label;
                                let showDateLabel = false;

                                if (currentGroupDate !== lastDateGroup) {
                                    showDateLabel = true;
                                    lastDateGroup = currentGroupDate;
                                }

                                html += buildCommentHTML(data, showDateLabel);
                            });

                            $('#comments-list').prepend(html);

                            const newScrollHeight = container[0].scrollHeight;
                            const scrollDiff = newScrollHeight - prevScrollHeight;
                            container.scrollTop(scrollDiff);
                        } else {
                            doneLoadingAll = true;
                        }
                    },
                    complete: function () {
                        loading = false;
                        $('#spinner_loader').hide();
                    }
                });
            }, 3000);
        }
    });
  });

  function toggleTaskDetails(headerElement) {
      const card = headerElement.closest('.task-card');
      card.classList.toggle('expanded');
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.share-comment');
    if (!btn) return;

    const commentId = btn.dataset.commentId;
    const baseUrl = window.location.origin + window.location.pathname;
    const shareUrl = baseUrl + '?comment=' + commentId;

    function showTooltip(message) {
        const tooltip = document.createElement('div');
        tooltip.textContent = message;

        tooltip.style.position = 'fixed';
        tooltip.style.background = '#25581a';
        tooltip.style.color = '#fff';
        tooltip.style.padding = '6px 12px';
        tooltip.style.fontSize = '12px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.zIndex = '9999';
        tooltip.style.whiteSpace = 'nowrap';

        document.body.appendChild(tooltip);

        const rect = btn.getBoundingClientRect();
        tooltip.style.top = (rect.top - 40) + "px";
        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + "px";

        setTimeout(() => tooltip.remove(), 2500);
    }

    function fallbackCopy(text) {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(shareUrl)
            .then(() => {
                btn.innerHTML = '<i class="fa-solid fa-check"></i>';
                showTooltip("Link copied successfully");
                setTimeout(() => {
                    btn.innerHTML = '<i class="fa-solid fa-link"></i>';
                }, 1500);
            })
            .catch(() => {
                fallbackCopy(shareUrl);
                showTooltip("Link copied successfully");
            });
    } else {
        fallbackCopy(shareUrl);
        showTooltip("Link copied successfully");
    }
  });

  document.addEventListener('DOMContentLoaded', function () {

    const commentId = new URLSearchParams(window.location.search).get('comment');
    if (!commentId) return;

    const container = document.getElementById('comment-scroll');
    if (!container) return;

    const taskCard = document.querySelector('.task-card');
    if (taskCard) {
        taskCard.classList.remove('expanded');
    }

    function scrollToComment() {
        const target = document.getElementById('comment-' + commentId);
        if (!target) return false;

        const containerTop = container.getBoundingClientRect().top;
        const targetTop = target.getBoundingClientRect().top;

        const scrollOffset = container.scrollTop + (targetTop - containerTop) - 20;

        container.scrollTo({
            top: scrollOffset,
            behavior: 'smooth'
        });

        target.style.transition = "background-color 0.4s ease";
        target.style.backgroundColor = "#fff3cd";

        setTimeout(() => {
            target.style.backgroundColor = "";
        }, 4000);

        return true;
    }

    setTimeout(() => {
        scrollToComment();
    }, 400);
  });

  document.addEventListener('DOMContentLoaded', function () {
    const targetCommentId = new URLSearchParams(window.location.search).get('comment');
    if (!targetCommentId) return;

    const container = $('#comment-scroll');

    function highlight(el) {
    const container = document.getElementById('comment-scroll');

    const offsetTop = el.offsetTop - container.offsetTop;

    container.scrollTo({
        top: offsetTop - 20, // small spacing from top
        behavior: 'smooth'
    });

    el.style.backgroundColor = '#fff3cd';
    setTimeout(() => el.style.backgroundColor = '', 10000);
  }


    function loadUntilFound() {
        const el = document.getElementById('comment-' + targetCommentId);
        if (el) {
            highlight(el);
            return;
        }

        if (doneLoadingAll || loading) return;

        container.scrollTop(0); // trigger lazy load

        setTimeout(loadUntilFound, 800);
    }

    loadUntilFound();
  });

  document.addEventListener('DOMContentLoaded', function () {
      const hoursSelect = document.getElementById('hoursSelect');
      const minutesSelect = document.getElementById('minutesSelect');
      const error = document.getElementById('timeError');

      const remaining = parseFloat("{{ max($remainingHours, 0) }}");

      function validateTime() {
          const hours = parseInt(hoursSelect.value || 0);
          const minutes = parseInt(minutesSelect.value || 0);

          const total = hours + (minutes / 60);

          if (total > remaining) {
              error.classList.remove('d-none');
              minutesSelect.value = 0;
          } else {
              error.classList.add('d-none');
          }
      }

      hoursSelect.addEventListener('change', validateTime);
      minutesSelect.addEventListener('change', validateTime);
  });

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

  document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const statusColors = {
        to_do: '#948979',
        in_progress: '#3fa6d7',
        ready: '#e09f3e',
        deployed: '#e76f51',
        complete: '#2a9d8f',
        invoice_done: '#e76f51'
    };

    document.body.addEventListener('click', function (e) {
      const clickedItem = e.target.closest('.dropdown-item');

      if (!clickedItem || !clickedItem.closest('.status-options')) return; // Only handle clicks inside status-options

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
          // Update button text (optional visual update)
          const statusButton = document.querySelector(`.status-options[data-ticket-id="${ticketId}"]`)?.previousElementSibling;
          if (statusButton) {
            const formattedStatus = newStatus
              .replace('_', ' ')
              .replace(/\b\w/g, char => char.toUpperCase()); // Capitalize each word
            statusButton.innerHTML = `${formattedStatus}`;
            const newColor = statusColors[newStatus] || '#6c757d';
                              statusButton.style.backgroundColor = newColor;
                              statusButton.style.borderColor = newColor;
          }
          // Always reload after a small delay (to let user briefly see change if needed)
          setTimeout(() => {
            location.reload(true);
          }, 300); // 300ms delay before reload
        } else {
          alert('Your time estimation is not approved yet.');
        }
      })
      .catch(error => {
        console.error(error);
        alert('An error occurred.');
      });
    });
  });

  function buildCommentHTML(data, showDateLabel = false) {
    const firstName = data.user?.first_name ?? 'N/A';
    const initials = firstName.substring(0, 2).toUpperCase();

    const profilePic = data.user?.profile_picture
      ? `<div class="avatar" style="background-color: #27ae60;">
          <img src="/assets/img/${data.user.profile_picture}" class="rounded-circle" width="35" height="35" alt="Profile">
        </div>`
      : `<div class="avatar" style="background-color: #27ae60;">${initials}</div>`;

    const role = data.user?.role_id === 6 ? (data.project_name ?? 'Project Not Assigned') : 'Code4Each';

    const documentsHTML = (data.document ?? '')
      .split(',')
      .filter(doc => doc.trim() !== '')
      .map(doc => `
        <p style="font-size: 0.9rem; color: #212529; line-height: 1.4;">
          <a href="/assets/img/${doc}" target="_blank">${doc.split('/').pop()}</a>
        </p>
      `).join('');

    const dateLabelHTML = showDateLabel
      ? `<div class="text-center text-muted my-2 date-label" style="font-weight: bold; font-size: 14px;">
          ${data.created_date_label}
        </div>`
      : '';

    return `
      ${dateLabelHTML}
      <div class="message" data-id="${data.id}" id="comment-${data.id}">
        <div class="info">${data.created_at_formatted ?? ''}</div>
        <div class="user d-flex align-items-start w-100">
          ${profilePic}

          <div class="d-flex justify-content-between align-items-start w-100 ms-2">
            
            <div>
              <span class="name">${firstName}</span>
              <span class="role">${role}</span>
            </div>

            <button type="button"
                    class="btn btn-sm btn-link p-0 share-comment"
                    data-comment-id="${data.id}"
                    title="Copy comment link">
                <i class="fa-solid fa-link"></i>
            </button>

          </div>
        </div>

        <div class="text">
          <div style="word-break: auto-phrase;">
            ${data.comments ?? ''}
          </div>
          ${documentsHTML}
        </div>
      </div>
    `;
  }

  function toggleDescription(link) {
      const container = link.closest('div');
      const shortDesc = container.querySelector('.short-description');
      const fullDesc = container.querySelector('.full-description');

      if (fullDesc.style.display === 'none') {
          fullDesc.style.display = 'inline';
          shortDesc.style.display = 'none';
          link.textContent = 'Show Less';
      } else {
          fullDesc.style.display = 'none';
          shortDesc.style.display = 'inline';
          link.textContent = 'Show More';
      }
  }
</script>

@endsection
