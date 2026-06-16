@extends('layout')
@section('title', 'Ticket Details')
@section('subtitle', 'Ticket')
@section('content')
<style>
    .btn-sm1 {
           font-size: 15px;
    padding: 8px 14px;
    text-align: center;
    border-radius: 5px;
    }


.reply-arrow {
    font-size: 14px;
    color: #6b7280;
    margin-top: 6px;
}

.reply-card {
    background: #f3f4f6;
    border-radius: 12px;
    padding: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.reply-avatar img {
    object-fit: cover;
}

.avatar-fallback {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #22c55e;
    color: #fff;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.reply-header {
    font-weight: 600;
    font-size: 13px;
    color: #111827;
}

.reply-time {
    font-weight: 400;
    font-size: 11px;
    color: #6b7280;
    margin-left: 6px;
}

.reply-text {
    font-size: 12px;
    color: #374151;
    margin-top: 3px;

    /* clamp to 2 lines */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.reply-more {
    font-size: 12px;
    color: #22c55e;
    cursor: pointer;
}
.message-box{
    display:flex;
    flex-direction:column;
}

.reply-wrapper{
    margin-top:10px;
    margin-left:20px;
}

.reply-card{
    background:#f5f5f5;
    border-radius:10px;
    padding:10px;
    max-width:350px;
}
.reply-btn-inside {
    position: absolute;
    right: 10px;
    bottom: 4px;
    border: none;
    background: transparent;
    font-size: 14px;
    cursor: pointer;
    display: inline-block;   /* ✅ always visible */
    color: #6b7280;
}

/* hover effect */
.reply-btn-inside:hover {
    color: #2563eb;
}
.unpin-btn{
    border:none;
    background:none;
    color:#999;
    width:28px;
    height:28px;
    border-radius:50%;
    transition:0.2s;
    flex-shrink:0;
}

.unpin-btn:hover{
    background:#ffe5e5;
    color:#e74c3c;
}

.pin-highlight{
    background: #fff3a0 !important;
    border-left: 5px solid #f1c40f !important;
    padding: 8px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(241,196,15,0.4);
    transition: all 0.3s ease;
}
.msger-chat{
    overflow-y:auto;
    height:100%;
}
.highlight-comment {
    animation: highlightFade 2.5s ease;
    background: #fff3cd !important;
    border-radius: 8px;
}

@keyframes highlightFade {
    0% {
        background: #ffe082;
    }
    100% {
        background: transparent;
    }
}
.comment-tabs {
    border-bottom: none;
    gap: 10px;
    padding-left: 10px;
    background: #297bab;
}

.comment-tabs .nav-item {
    margin-bottom: -3px;
    margin-top: 10px;
}

.comment-tabs .nav-link {
    display: flex;
    align-items: center;
    gap: 10px;

    background: transparent;
    border: none;

    color: #ffffff;
    font-weight: 500;
    font-size: 16px;

    padding: 12px 18px;
    border-radius: 12px 12px 0 0;

    transition: all 0.2s ease;
}

.comment-tabs .nav-link:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.comment-tabs .nav-link.active {
    background: #f3f4f6;
    color: #374151;

    border: none;
}

.comment-tabs .badge {
    min-width: 38px;
    padding: 7px 16px;

    border-radius: 10px;

    font-size: 14px;
    font-weight: 600;
}

.comment-tabs .bg-primary {
    background: #1d6ff2 !important;
}

.comment-tabs .bg-warning {
    background: #fbbc04 !important;
    color: #111827 !important;
}
</style>
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
    {{-- Add Todo Button --}}

    @if(in_array(Auth::user()->role_id, [1,3]))

        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#ticketTodoModal">
            Add Todo
        </button>

    @endif


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
                          Logged: {{ $spent['h'] }} hrs {{ $spent['m'] }} min
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
            <!-- feedback from client -->
            @php
                $feedback = \App\Models\TicketFeedback::where('ticket_id', $tickets->id)->first();
            @endphp

            @if($feedback)
            <div class="detail-item">
                <i class="fa-solid fa-star"></i>
                <strong>Client Feedback:</strong>
                <span class="d-flex align-items-center gap-2">
                    {{-- Filled stars --}}
                    <span>
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star" style="font-size:14px; color:{{ $i <= $feedback->rating ? '#f59e0b' : '#d1d5db' }};"></i>
                        @endfor
                    </span>

                    {{-- Comment icon — opens modal --}}
                    @if($feedback->comments)
                    <button type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#feedbackModal"
                            style="background:none; border:none; padding:0; cursor:pointer; line-height:1;"
                            title="View Feedback Comment">
                        <i class="fa-solid fa-message" style="color:#4F46E5; font-size:15px;"></i>
                    </button>
                    @endif
                </span>
            </div>
            @endif
    </div>
  </div>

</div>
<!-- feedback modal -->
@if(isset($feedback) && $feedback)
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; overflow:hidden; border:none; box-shadow:0 10px 40px rgba(0,0,0,0.15);">

            <div class="modal-header" style="background: #297bab;border:none;padding:16px 20px;">
                <div>
                    <h6 class="modal-title text-white fw-bold mb-0" id="feedbackModalLabel">Client Feedback</h6>
                    <p class="text-white mb-0" style="font-size:12px; opacity:0.8;">
                        Ticket #{{ $tickets->id }} — {{ $tickets->title }}
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 30px;"></button>
            </div>

            <div class="modal-body px-4 py-4">

                {{-- Stars --}}
                <div class="text-center mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa-solid fa-star" style="font-size:28px; color:{{ $i <= $feedback->rating ? '#f59e0b' : '#d1d5db' }};"></i>
                    @endfor
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        {{ $feedback->rating }} out of 5
                    </div>
                </div>

                <hr style="border-color:#e5e7eb;">

                {{-- Comment --}}
                @if($feedback->comments)
                <div style="background:#f9fafb;border-radius:8px;padding:14px 16px;border-left: 3px solid #297bab;">
                    <p class="mb-0" style="font-size:13px; color:#374151; line-height:1.6;">
                        {{ $feedback->comments }}
                    </p>
                </div>
                @endif

                @if($feedback->created_at)
                <div class="text-end mt-2">
                    <span style="font-size: 13px;color: #2a7bab;font-weight: 700;">
                        Submitted {{ $feedback->created_at->format('M d, Y') }}
                    </span>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endif
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

    <!-- modal for adding todo -->
    <div class="modal fade"
        id="ticketTodoModal"
        tabindex="-1"
        aria-labelledby="ticketTodoModalLabel"
        aria-hidden="true">

        <div class="modal-dialog">
            <div class="modal-content">

                <form method="POST"
                    action="{{ route('todo_list.store') }}">

                    @csrf

                    <div class="modal-header">

                        <h5 class="modal-title"
                            id="ticketTodoModalLabel">
                            Create Ticket Todo
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>

                    </div>

                    <div class="modal-body">

                        {{-- Hidden Ticket ID --}}
                        <input type="hidden"
                            name="ticket_id"
                            value="{{ $tickets->id }}">

                        {{-- Todo Title --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Todo Title
                            </label>

                            <textarea name="title"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Enter todo details..."
                                    required></textarea>

                        </div>

                        {{-- Assign Developer --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Assign Developer
                            </label>

                                <select name="assigned_user_id"
                                        class="form-control"
                                        required>

                                    <option value="">
                                        Select Developer
                                    </option>

                                    @foreach($developers as $developer)

                                        <option value="{{ $developer->id }}">

                                            {{ $developer->first_name }}
                                            {{ $developer->last_name ?? '' }}

                                        </option>

                                    @endforeach

                                </select>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit"
                                class="btn btn-primary">
                            Create Todo
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>
    <!-- Edit Todo Modal -->
    <div class="modal fade"
        id="editTodoModal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog">
            <div class="modal-content">

                <form id="editTodoForm">

                    @csrf
                    @method('PUT')

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Edit Todo
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"></button>

                    </div>

                    <div class="modal-body">

                        <input type="hidden"
                            id="edit_todo_id">

                        {{-- Todo --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Todo
                            </label>

                            <textarea class="form-control"
                                    rows="4"
                                    name="title"
                                    id="edit_todo_title"
                                    required></textarea>

                        </div>

                        {{-- Assign User --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Assign User
                            </label>
                                <select class="form-control"
                                        name="assigned_user_id"
                                        id="edit_assigned_user">

                                    <option value="">Select Developer</option>

                                    @foreach($developers as $developer)

                                        <option value="{{ $developer->id }}"
                                            id="dev_{{ $developer->id }}">

                                            {{ $developer->first_name }}

                                        </option>

                                    @endforeach

                                </select>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit"
                                class="btn btn-primary">
                            Update Todo
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>
    @if(in_array(Auth::user()->role_id, [1,3]))
        <div class="container">
            @if($ticketTodos->count() > 0)
                <!-- ticket todo section -->
                <div class="task-card">

                    {{-- Header --}}
                    <div class="task-header"
                        onclick="toggleTodoSection(this)">

                        <div class="task-icon">
                            <i class="fa-solid fa-list-check"></i>
                        </div>

                        <div class="task-title">
                            <h4 class="mb-0">
                                Ticket Todos
                            </h4>
                        </div>

                        <div class="task-toggle-icon">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                    </div>

                    {{-- Body --}}
                    <div class="task-details"
                        style="display:none;">

                        <div class="mt-3">
                            {{-- Todo Table --}}
                            <div class="table-responsive">

                                <table class="table table-resposnive table-bordered tickettasks">

                                    <thead>

                                        <tr>
                                            <th width="35%">Todo</th>
                                            <th>Created At</th>
                                            <th>Assigned To</th>
                                            <th>Status</th>
                                            <th width="180">Actions</th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                        @forelse($ticketTodos as $todo)

                                            <tr id="todo_row_{{ $todo->id }}">

                                                {{-- Todo Text --}}
                                                <td>

                                                    <div class="todo-title-view-{{ $todo->id }}">
                                                        {{ $todo->title }}
                                                    </div>

                                                    {{-- Edit textarea --}}
                                                    <div class="todo-title-edit-{{ $todo->id }} d-none">

                                                        <textarea class="form-control todo-edit-text"
                                                                rows="3"
                                                                id="todo_text_{{ $todo->id }}">{{ $todo->title }}</textarea>

                                                    </div>

                                                </td>

                                                {{-- Created --}}
                                                <td>
                                                    {{ $todo->created_at->format('d M Y') }}
                                                </td>

                                                {{-- Assigned User --}}
                                                <td>

                                                    <div class="todo-user-view-{{ $todo->id }}">

                                                        {{ $todo->assignedUser->first_name ?? 'N/A' }}

                                                    </div>

                                                    {{-- Edit Dropdown --}}
                                                    <div class="todo-user-edit-{{ $todo->id }} d-none">

                                                        <select class="form-control"
                                                                id="todo_user_{{ $todo->id }}">

                                                            @foreach($ticketAssign as $assign)

                                                                @if($assign->user)

                                                                    <option value="{{ $assign->user->id }}"
                                                                        {{ $todo->user_id == $assign->user->id ? 'selected' : '' }}>

                                                                        {{ $assign->user->first_name }}

                                                                    </option>

                                                                @endif

                                                            @endforeach

                                                        </select>

                                                    </div>

                                                </td>

                                                {{-- Status --}}
                                                <td>

                                                    <span class="badge"

                                                        @if($todo->status == 'completed')

                                                            style="background:green;border-radius:20px;"

                                                        @elseif($todo->status == 'hold')

                                                            style="background:#ffc720;border-radius:20px;"

                                                        @else

                                                            style="background:#4154f1;border-radius:20px;"

                                                        @endif>

                                                        {{ ucfirst($todo->status) }}

                                                    </span>

                                                </td>

                                                {{-- Actions --}}
                                                <td>

                                                        <div class="d-flex align-items-center gap-2">

                                                            {{-- Hold --}}
                                                            @if($todo->status == 'open')
                                                                <button class="btn btn-warning btn-sm"
                                                                        onclick="holdTask({{ $todo->id }})">
                                                                    Hold
                                                                </button>
                                                            @endif

                                                            {{-- Reopen --}}
                                                            @if($todo->status != 'open')
                                                                <button type="button"
                                                                        class="btn btn-primary btn-sm"
                                                                        onclick="reopenTask({{ $todo->id }})">
                                                                    Reopen
                                                                </button>
                                                            @endif

                                                            {{-- Complete --}}
                                                            @if($todo->status != 'completed')
                                                                <button class="btn btn-success btn-sm"
                                                                        onclick="completeTodo({{ $todo->id }})">
                                                                    Complete
                                                                </button>
                                                            @endif

                                                            {{-- Edit --}}
                                                            <i class="fas fa-edit text-dark cursor-pointer"
                                                            onclick="openEditTodoModal(
                                                                    '{{ $todo->id }}',
                                                                    `{{ $todo->title }}`,
                                                                    '{{ $todo->user_id }}'
                                                            )"></i>

                                                            {{-- Delete --}}
                                                            <i class="fas fa-trash-alt text-danger cursor-pointer"
                                                            onclick="confirmDelete({{ $todo->id }})"></i>

                                                        </div>





                                                </td>

                                            </tr>

                                        @empty

                                            <tr>

                                                <td colspan="5" class="text-center">
                                                    No todos found
                                                </td>

                                            </tr>

                                        @endforelse

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>
            @endif
        </div>
    @endif
          <div class="main-section">
            <!-- <div class="msger-header">
                <h1>Comments</h1>
                <i class="fas fa-comment icon"></i>
            </div> -->
            <!-- pinned comment  -->
            @php
                $pinnedComments = $CommentsData->where('is_pinned', 1);

                use Carbon\Carbon;
                $lastGroupDate = null;
            @endphp

                <!-- tab navigation -->
            <ul class="nav nav-tabs mb-3 comment-tabs"
                id="commentTabs"
                role="tablist">

                {{-- COMMENTS TAB --}}
                <li class="nav-item" role="presentation">

                    <button class="nav-link active"
                            id="comments-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#comments-pane"
                            type="button"
                            role="tab">

                        <span>Comments</span><i class="fas fa-comment icon"></i>

                        <!-- <span class="badge bg-primary ms-2">
                            {{ count($CommentsData) }}
                        </span> -->

                    </button>

                </li>

                {{-- PINNED TAB --}}
                <li class="nav-item" role="presentation">

                    <button class="nav-link"
                            id="pinned-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#pinned-pane"
                            type="button"
                            role="tab">

                        <i class="fa-solid fa-thumbtack text-warning me-1"></i>

                        <span>Pinned</span>

                        <span class="badge bg-warning text-dark ms-2">
                            {{ $pinnedComments->count() }}
                        </span>

                    </button>

                </li>

            </ul>
            <!-- tab content -->
            <div class="tab-content">

                <!-- comment tab -->
                <div class="tab-pane fade show active"
                    id="comments-pane"
                    role="tabpanel">

                    <div class="chat-container"
                        id="comment-scroll"
                        style="height: {{ count($CommentsData) ? '600px' : 'auto' }};
                                overflow-y:auto;
                                padding:10px;
                                background:#f9f9f9;
                                border-radius:10px;">

                        <div id="spinner_loader"
                            style="display:none; text-align:center; padding:10px;">

                            <i class="fas fa-spinner fa-spin"
                            style="font-size:16px; color:#888;"></i>

                        </div>

                        @if(count($CommentsData) != 0)

                            <div id="comments-list">

                                @foreach ($CommentsData as $data)

                                    @php
                                        $commentDate = Carbon::parse($data->created_at)
                                            ->timezone('Asia/Kolkata')
                                            ->startOfDay();

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

                                    {{-- DATE LABEL --}}
                                    @if ($lastGroupDate !== $groupDateLabel)

                                        <div class="text-center text-muted my-2 date-label"
                                            style="font-weight:bold; font-size:14px;">

                                            {{ $groupDateLabel }}

                                        </div>

                                        @php $lastGroupDate = $groupDateLabel; @endphp

                                    @endif

                                    {{-- MESSAGE --}}
                                    <div class="message {{ $data->is_pinned ? 'pinned-comment' : '' }}"
                                        data-id="{{ $data->id }}"
                                        id="comment-{{ $data->id }}">

                                        {{-- TIME --}}
                                        <div class="info">

                                            {{ \Carbon\Carbon::parse($data->created_at)
                                                ->timezone('Asia/Kolkata')
                                                ->format('M d, Y h:i A') }}

                                        </div>

                                        {{-- USER --}}
                                        @if(!$data->is_system)

                                            <div class="user">

                                                {{-- AVATAR --}}
                                                @if(!empty($data->user->profile_picture))

                                                    <div class="avatar"
                                                        style="background-color:#27ae60;">

                                                        <img src="{{ asset('assets/img/' . $data->user->profile_picture) }}"
                                                            alt="Profile"
                                                            class="rounded-circle"
                                                            width="35"
                                                            height="35">

                                                    </div>

                                                @else

                                                    <div class="avatar"
                                                        style="background-color:#27ae60;">

                                                        {{ strtoupper(substr($data->user->first_name, 0, 2)) }}

                                                    </div>

                                                @endif

                                                <div class="d-flex align-items-center w-100">

                                                    {{-- LEFT --}}
                                                    <div>

                                                        <span class="name">
                                                            {{ $data->user->first_name }}
                                                        </span>

                                                        <span class="role">

                                                            @if ($data->user->role_id == 6)
                                                                {{ $projectName ?? 'Project Not Assigned' }}
                                                            @else
                                                                Code4Each
                                                            @endif

                                                        </span>

                                                        {{-- EDITED --}}
                                                        @if($data->updated_at && $data->updated_at != $data->created_at)

                                                            <span style="font-size:13px;
                                                                        color:#012970;
                                                                        margin-left:7px;">

                                                                Edited

                                                            </span>

                                                        @endif

                                                    </div>

                                                    {{-- RIGHT ACTIONS --}}
                                                    <div class="d-flex align-items-center ms-auto"
                                                        style="gap:8px;">

                                                        {{-- SHARE --}}
                                                        <button type="button"
                                                                class="btn btn-link p-0 m-0 share-comment"
                                                                data-comment-id="{{ $data->id }}"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-title="Copy link">

                                                            <i class="fa-solid fa-link"></i>

                                                        </button>
                                                        @if($data->user->role_id == 6 && in_array($data->status, ['replied','acknowledged']))

                                                                <span class="acknowledge-toggle {{ auth()->user()->role_id != 3 ? 'disabled' : '' }}"
                                                                    data-id="{{ $data->id }}"
                                                                    data-status="{{ $data->status }}"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="{{ $data->status == 'acknowledged' ? 'Acknowledged' : (in_array(auth()->user()->role_id, [1,3]) ? 'Click to acknowledge' : 'Waiting for developer') }}"
                                                                    style="
                                                                        position:relative;
                                                                        display:inline-flex; 
                                                                        align-items:center;
                                                                        font-size: 19px;
                                                                        cursor: {{ in_array(auth()->user()->role_id, [1,3]) ? 'pointer' : 'not-allowed' }};
                                                                        opacity: {{ in_array(auth()->user()->role_id, [1,3]) ? '1' : '0.6' }};
                                                                    ">

                                                                    <!--  Icon -->
                                                                    <i class="thumb-icon fa-thumbs-up
                                                                        {{ $data->status == 'acknowledged' ? 'fa-solid text-success' : 'fa-regular text-muted' }}">
                                                                    </i>

                                                                    <!-- Tick -->
                                                                    <i class="tick-icon fa-solid fa-check"
                                                                        style="
                                                                            position:absolute;
                                                                            top:-5px;
                                                                            right:-5px;
                                                                            font-size:10px;
                                                                            color:#22c55e;
                                                                            background:white;
                                                                            border-radius:50%;
                                                                            display: {{ $data->status == 'acknowledged' ? 'block' : 'none' }};
                                                                    ">
                                                                    </i>

                                                                </span>

                                                        @endif

                                                        {{-- PIN --}}
                                                        @php
                                                            $canUnpin = $data->pinned_by == Auth::id();
                                                        @endphp
                                                        <span data-bs-toggle="tooltip"
                                                            data-bs-title="{{ $data->is_pinned
                                                                    ? 'Pinned by ' . ($data->pinnedByUser->first_name ?? 'Unknown')
                                                                    : 'Pin Comment' }}">

                                                            <button type="button"
                                                                    class="btn btn-link p-0 m-0 pin-comment"
                                                                    data-id="{{ $data->id }}">

                                                                <i class="fa-solid fa-thumbtack
                                                                    {{ $data->is_pinned ? 'text-warning' : 'text-muted' }}">
                                                                </i>

                                                            </button>

                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                        @endif

                                        {{-- MESSAGE BODY --}}
                                        <div class="text message-box">

                                            {{-- TOP ROW --}}
                                            <div class="d-flex justify-content-between align-items-start">

                                                {{-- COMMENT --}}
                                                <div style="word-break:auto-phrase; flex:1;">
                                                    {!! preg_replace('/<p>(h|g)?<\/p>/', '', $data->comments) !!}
                                                </div>

                                                {{-- ACTIONS --}}
                                                <div class="comment-actions d-flex gap-2 ms-2">

                                                    @if(Auth::id() != $data->comment_by)
                                                        <button type="button"
                                                                class="reply-btn-inside"
                                                                data-id="{{ $data->id }}"
                                                                data-message="{{ strip_tags($data->comments) }}"
                                                                data-user="{{ $data->user->first_name }}">
                                                            <i class="fa fa-reply"></i>
                                                        </button>
                                                    @endif

                                                    @php
                                                        $canEdit = Auth::id() == $data->comment_by
                                                            && \Carbon\Carbon::parse($data->created_at)->diffInHours(now()) <= 5;
                                                    @endphp

                                                    @if($canEdit)

                                                        <button class="btn p-0 border-0 bg-transparent text-danger delete-comment"
                                                                data-id="{{ $data->id }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>

                                                        <button class="btn p-0 border-0 bg-transparent text-primary edit-comment"
                                                                data-comment-id="{{ $data->id }}"
                                                                data-content="{{ htmlspecialchars($data->comments, ENT_QUOTES) }}">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>

                                                    @endif

                                                </div>

                                            </div>

                                            {{-- REPLY SECTION --}}
                                            @if($data->reply_to)

                                                @php
                                                    $parent = $CommentsData->firstWhere('id', $data->reply_to);
                                                @endphp

                                                @if($parent)

                                                    <div class="reply-wrapper mt-2 d-flex align-items-start gap-2">

                                                        <div class="reply-arrow">
                                                            <i class="fa-solid fa-reply"
                                                            style="transform: rotate(180deg);"></i>
                                                        </div>

                                                        <div class="reply-card"
                                                            data-scroll-id="comment-{{ $parent->id }}">

                                                            <div class="reply-top d-flex align-items-start gap-2">

                                                                <div class="reply-avatar">
                                                                    @if(!empty($parent->user->profile_picture))
                                                                        <img src="{{ asset('assets/img/' . $parent->user->profile_picture) }}"
                                                                            class="rounded-circle"
                                                                            width="34"
                                                                            height="34">
                                                                    @else
                                                                        <div class="avatar-fallback">
                                                                            {{ strtoupper(substr($parent->user->first_name, 0, 2)) }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="reply-content">

                                                                    <div class="reply-header">
                                                                        {{ $parent->user->first_name ?? 'User' }}

                                                                        <span class="reply-time">
                                                                            {{ \Carbon\Carbon::parse($parent->created_at)->format('M d, h:i A') }}
                                                                        </span>
                                                                    </div>

                                                                    <div class="reply-text">
                                                                        {{ \Illuminate\Support\Str::limit(strip_tags($parent->comments), 120) }}
                                                                    </div>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    </div>

                                                @endif
                                            @endif
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

                                    </div>

                                @endforeach

                            </div>

                        @else

                            <div class="center text-center mt-2">

                                <span id="NoComments"
                                    style="color:#6c757d; font-size:1rem;">

                                    No Comments

                                </span>

                            </div>

                        @endif

                    </div>

                </div>
                <!-- pinned tab -->
                <div class="tab-pane fade"
                    id="pinned-pane"
                    role="tabpanel">

                    <div class="chat-container"
                        style="height:600px;
                                overflow-y:auto;
                                padding:10px;
                                background:#f9f9f9;
                                border-radius:10px;">

                        @if($pinnedComments->count())

                            @foreach($pinnedComments as $data)

                                <div class="message pinned-comment go-to-comment"
                                    data-id="{{ $data->id }}"
                                    id="pinned-comment-{{ $data->id }}"
                                    style="cursor:pointer;">

                                    <div class="info">

                                        {{ \Carbon\Carbon::parse($data->created_at)
                                            ->timezone('Asia/Kolkata')
                                            ->format('M d, Y h:i A') }}

                                    </div>

                                    <div class="user">

                                        {{-- AVATAR --}}
                                        @if(!empty($data->user->profile_picture))

                                            <div class="avatar">

                                                <img src="{{ asset('assets/img/' . $data->user->profile_picture) }}"
                                                    class="rounded-circle"
                                                    width="35"
                                                    height="35">

                                            </div>

                                        @else

                                            <div class="avatar">

                                                {{ strtoupper(substr($data->user->first_name, 0, 2)) }}

                                            </div>

                                        @endif

                                        <div class="d-flex align-items-center w-100">

                                            <div>

                                                <span class="name">
                                                    {{ $data->user->first_name }}
                                                </span>

                                            </div>

                                            {{-- ACTIONS --}}
                                            <div class="ms-auto d-flex align-items-center gap-2">

                                                {{-- SHARE --}}
                                                <button type="button"
                                                        class="btn btn-link p-0 share-comment"
                                                        data-comment-id="{{ $data->id }}">

                                                    <i class="fa-solid fa-link"></i>

                                                </button>

                                                {{-- UNPIN --}}
                                                @php
                                                    $canUnpin = $data->pinned_by == Auth::id();
                                                @endphp

                                                <span data-bs-toggle="tooltip"
                                                    data-bs-title="{{ $data->is_pinned
                                                            ? 'Pinned by ' . ($data->pinnedByUser->first_name ?? 'Unknown')
                                                            : 'Pin Comment' }}">

                                                    <button type="button"
                                                            class="btn btn-link p-0 pin-comment"
                                                            data-id="{{ $data->id }}"
                                                            @if(!$canUnpin) disabled @endif>

                                                        <i class="fa-solid fa-thumbtack text-warning"></i>

                                                    </button>

                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                    {{-- COMMENT --}}
                                    <div class="text message-box">

                                        {!! preg_replace('/<p>(h|g)?<\/p>/', '', $data->comments) !!}

                                    </div>

                                </div>

                            @endforeach

                        @else

                            <div class="text-center text-muted mt-4">

                                No pinned comments

                            </div>

                        @endif

                    </div>

                </div>

            </div>
            <div class="card mt-3 card-designform">
            <form method="POST" id="commentsData" action="{{ route('comments.add') }}">
              @csrf
              <div class="post-item clearfix mb-3 mt-3">
                <label for="comment" class="col-sm-3 col-form-label">Comment</label>
                <input type="hidden" name="comment_id" id="comment_id" value="">
                <input type="hidden" name="reply_to" id="reply_to">
                <input type="hidden" name="parent_comment_id" id="parent_comment_id">
                <div class="col-sm-12">
                       <!-- comment reply -->
                    <div id="replyPreview"
                        style="display:none; background:#f1f5f9; padding:8px; border-left:3px solid #3b82f6; margin-bottom:10px; border-radius:6px;">
                        <span id="cancelReply"
                            title="Cancel reply"
                            style="cursor:pointer; float:right; color:#ef4444; font-size:14px;">
                            <i class="fa-solid fa-xmark"></i>
                        </span>
                        <div style="font-size:15px; color:#334155;">
                            Replying to <strong id="replyUser"></strong>
                        </div>

                        <div id="replyText" style="font-size:14px; color:#64748b;"></div>


                    </div>
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

//   $(document).ready(function () {

//     const commentSection = document.getElementById('comment-scroll');
//     commentSection.scrollTop = commentSection.scrollHeight;
//   });

    $(document).ready(function () {

        const urlParams = new URLSearchParams(window.location.search);
        const targetCommentId = urlParams.get('comment');
        const commentSection = document.getElementById('comment-scroll');

        if (targetCommentId) {
            // Scroll page to top
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Collapse task card
            const taskCard = document.querySelector('.task-card');
            if (taskCard) taskCard.classList.remove('expanded');

            // After page scrolls to top, scroll container to the comment
            setTimeout(function () {
                const target = document.getElementById('comment-' + targetCommentId);
                if (!target) return;

                const containerRect = commentSection.getBoundingClientRect();
                const targetRect    = target.getBoundingClientRect();
                const scrollTo = commentSection.scrollTop + (targetRect.top - containerRect.top) - 80;

                commentSection.scrollTop = scrollTo;

                target.style.transition = 'background-color 0.3s ease';
                target.style.backgroundColor = '#fff3cd';
                setTimeout(function () {
                    target.style.backgroundColor = '';
                }, 4000);
            }, 500);

        } else {
            // No target comment — scroll container to bottom as normal
            commentSection.scrollTop = commentSection.scrollHeight;
        }
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
    $('#commentsData').on('submit', async function (e) {
        //    console.log("Reply TO:", $('#reply_to').val());
      e.preventDefault();

      let commentHtml = quill.root.innerHTML.trim();
      const commentText = quill.getText().trim();

      // 🔥 Find base64 images
        const imgTags = commentHtml.match(/<img[^>]+src="data:image[^">]+"[^>]*>/g);

        if (imgTags) {
            for (let imgTag of imgTags) {
                const srcMatch = imgTag.match(/src="([^"]+)"/);
                if (srcMatch && srcMatch[1].startsWith('data:image')) {

                    const base64 = srcMatch[1];

                    // upload and get URL
                    const imageUrl = await uploadBase64Image(base64);

                    // replace base64 with URL
                    if (imageUrl) {
                        commentHtml = commentHtml.replace(base64, imageUrl);
                    }
                }
            }
        }


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
    // todo list
    function toggleTodoSection(element)
    {
        let card = element.closest('.task-card');
        let details = element.nextElementSibling;

        if(details.style.display === 'none' || details.style.display === '')
        {
            details.style.display = 'block';
            card.classList.add('expanded');
        }
        else
        {
            details.style.display = 'none';
            card.classList.remove('expanded');
        }
    }
    function openEditTodoModal(id, title, userId)
    {
        $('#edit_todo_id').val(id);
        $('#edit_todo_title').val(title);

        $('#edit_assigned_user').val(userId);

        $('#editTodoModal').modal('show');
    }
    $('#editTodoForm').submit(function(e){

        e.preventDefault();

        let todoId = $('#edit_todo_id').val();

        $.ajax({

            url: "/todo_list/" + todoId,
            type: "POST",

            data: {
                _token: "{{ csrf_token() }}",
                _method: "PUT",

                title: $('#edit_todo_title').val(),
                assigned_user_id: $('#edit_assigned_user').val()
            },

            success: function(response){

                location.reload();

            },

            error: function(xhr){

                console.log(xhr.responseText);

                alert("Update failed");
            }

        });

    });
    function holdTask(taskId) {
            $.ajax({
                type: 'PUT',
                url: "{{ url('/todo_list') }}/" + taskId + "/hold",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    console.log("Task put on hold:", response);

                    let taskItem = $("#task_" + taskId); // ✅ Correctly select task row
                    taskItem.removeClass('completed').addClass('hold');

                    taskItem.find('input[type="checkbox"]').hide();
                    taskItem.find('.btn-reopen-hold .btn-hold').hide();
                    taskItem.find('.btn-reopen-hold .btn-primary').show();

                    location.reload(); // ✅ Refresh to reflect changes
                },
                error: function(xhr, status, error) {
                    console.error("Error:", xhr.responseText);
                    alert("Failed to put task on hold. Please check the console for details.");
                }
            });
    }
    function reopenTask(taskId)
    {
        $.ajax({
            type: 'PUT',
            url: "{{ url('/todo_list') }}/" + taskId + "/status",
            data: {
                status: 'open',
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                location.reload();
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    }
    // delet todo
    window.confirmDelete = function (id) {
        if (confirm("Are you sure you want to delete this personal task?")) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/todo_list') }}/" + id,
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#todo_row_' + id).remove();
                    alert("Ticket todo task deleted successfully!");
                },
                error: function(error) {
                    console.log(error);
                    alert("Error deleting ticket todo task.");
                }
            });
        }
    };
    function completeTodo(taskId) {

        $.ajax({
            type: 'PUT',
            url: "{{ url('/todo_list') }}/" + taskId + "/status",
            data: {
                _token: "{{ csrf_token() }}",
                status: "completed"
            },
            success: function(response) {

                console.log("Task completed:", response);

                let taskItem = $("#todo_row_" + taskId);

                taskItem.removeClass('open hold').addClass('completed');

                location.reload(); // keep simple like your hold function

            },
            error: function(xhr) {

                console.error("Error:", xhr.responseText);
                alert("Failed to complete task.");
            }
        });
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

    // const commentId = new URLSearchParams(window.location.search).get('comment');
    // if (!commentId) return;

    const urlParams = new URLSearchParams(window.location.search);
    const targetCommentId = urlParams.get('comment');

    const container = document.getElementById('comment-scroll');
    if (!container) return;

    const taskCard = document.querySelector('.task-card');
    if (taskCard) {
        taskCard.classList.remove('expanded');
    }

    function scrollToComment() {
        const target = document.getElementById('comment-' + targetCommentId);
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
// click reply button
$(document).ready(function () {

$(document).on('click', '.reply-btn-inside', function () {

    let commentId = $(this).data('id');
    let message = $(this).data('message');
    let user = $(this).data('user');

    // console.log("Reply TO:", commentId);

    // set hidden input
    $('#reply_to').val(commentId);

    // show preview
    $('#replyUser').text(user);
    $('#replyText').text(message.substring(0, 120));

    $('#replyPreview').show();

    // scroll to textarea
    $('html, body').animate({
        scrollTop: $('#replyPreview').offset().top - 120
    }, 400);
});

});
// cancel reply
$('#cancelReply').click(function () {
    $('#replyPreview').hide();
    $('#reply_to').val('');
});
$(document).on('click', '.reply-box', function () {
 console.log('CLICK WORKING');
    let targetId = $(this).data('scroll-id'); // comment-12
    let target = $('#' + targetId);
console.log(targetId);
    if (target.length) {

        let container = $('#comment-scroll');

        // ✅ correct scroll position calculation
        let scrollTo = target[0].offsetTop - container[0].offsetTop;

        container.animate({
            scrollTop: scrollTo - 20
        }, 400);

        // ✅ highlight effect
        target.css('background', '#fff3cd');

        setTimeout(() => {
            target.css('background', '');
        }, 1500);
    }
});
$(document).on('click', '.reply-card', function () {

    let targetId = $(this).data('scroll-id');
    let target = document.getElementById(targetId);

    if (target) {
        target.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        target.style.background = '#fff3cd';

        setTimeout(() => {
            target.style.background = '';
        }, 1500);
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
function showAcknowledgeMsg(message, el) {
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

    const rect = el.getBoundingClientRect();

    tooltip.style.top = (rect.top - 35) + "px";
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + "px";

    setTimeout(() => tooltip.remove(), 2000);
}
$(document).on('click', '.acknowledge-toggle', function () {

    const role = {{ auth()->user()->role_id }};
    if (![1, 3].includes(role)) { // only allow admin and developer
        return;
    }

    let el = $(this);
    let commentId = el.data('id');

    $.ajax({
        url: '/acknowledge-comment',
        type: 'POST',
        data: {
            comment_id: commentId,
            _token: '{{ csrf_token() }}'
        },
        success: function (res) {
            let thumb = el.find('.thumb-icon');
            let tick = el.find('.tick-icon');

            el.tooltip('dispose');

            if (res.new_status === 'acknowledged') {

            el.attr('data-status', 'acknowledged');
            el.attr('data-ack-user', res.user_name);
            el.attr('data-bs-title', 'Acknowledged by ' + res.user_name);
            thumb.removeClass('fa-regular text-muted')
                .addClass('fa-solid text-success');
            tick.show();
            showAcknowledgeMsg("Acknowledged by " + res.user_name, el[0]);

        } else {
            el.attr('data-status', 'replied');
            el.removeAttr('data-ack-user');
            el.attr('data-bs-title', 'Click to acknowledge');
            thumb.removeClass('fa-solid text-success')
                .addClass('fa-regular text-muted');
            tick.hide();
            showAcknowledgeMsg("Acknowledgement removed", el[0]);
        }

            el.tooltip();
        }
    });

});
$(document).ready(function () {

        const urlParams = new URLSearchParams(window.location.search);
        const commentId = urlParams.get('comment');

        if (commentId) {

            // set parent comment id
            $('#parent_comment_id').val(commentId);

            // OPTIONAL: show replying UI
            $('#replyingToBox').show();
            $('#replyingToId').text('#' + commentId);

            // OPTIONAL: scroll to editor
            // $('html, body').animate({
            //     scrollTop: $("#editor").offset().top - 100
            // }, 400);
        }

});
$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});

    //Change the image input to a file input and handle the file upload for quill editor
    async function uploadBase64Image(base64) {
        const res = await fetch(base64);
        const blob = await res.blob();

        const formData = new FormData();
        formData.append('image', blob, 'comment.png');

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const response = await fetch('/upload-comment-image', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf
            },
            body: formData
        });

        if (!response.ok) {
            const text = await response.text();
            console.error("Upload failed:", text);
            return null;
        }

        const data = await response.json();
        return data.url;
    }
// Pin/Unpin comment


$(document).on('click', '.pin-comment', function () {

    let id = $(this).data('id');

    $.ajax({
        url: '/ticket-comments/pin/' + id,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function (res) {

            if (res.success) {
                location.reload();
            } else {
                toastr.error(res.message);
            }
        },
        error: function (xhr) {

            if (xhr.status === 403) {
                toastr.error(xhr.responseJSON.message);
            } else {
                toastr.error('Something went wrong');
            }
        }
    });

});

    $(document).on('click', '.go-to-comment', function (e) {

        // prevent clicking inner buttons (pin/share/etc.)
        if ($(e.target).closest('button').length) {
            return;
        }

        let commentId = $(this).data('id');

        // switch to comments tab
        $('#comments-tab').tab('show');

        setTimeout(function () {

            let container = $('#comment-scroll');
            let target = $('#comment-' + commentId);

            if (!target.length) return;

            // adjust this value as needed (perfect alignment)
            let offset = 120;

            let top =
                target.offset().top
                - container.offset().top
                + container.scrollTop()
                - offset;

            container.animate({
                scrollTop: top
            }, 500);

            // highlight effect
            target.addClass('highlight-comment');

            setTimeout(function () {
                target.removeClass('highlight-comment');
            }, 2500);

        }, 200);

    });
</script>

@endsection
