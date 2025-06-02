@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Show')
@section('content')
<div class="container">
    <div class="row">
      <div class="col-md-7">
        <div class="task-card collapsed">
          <div class="task-header d-flex justify-content-between align-items-center" onclick="toggleTaskDetails(this)">
            <div class="d-flex align-items-center">
              <div class="task-icon me-2">
                <i class="fa-solid fa-folder-open"></i>
              </div>
              <div class="task-title">
                <h4 class="mb-0">{{ $projects->project_name }}</h4>
              </div>
            </div>
            <div class="task-toggle-icon">
              <i class="fa-solid fa-chevron-down"></i>
            </div>
          </div>
          <div class="task-details">
            <div class="detail-item">
              <i class="fa-solid fa-user-tie"></i>
              <strong>Client:</strong>
              <span>{{ $projects->client->name ?? '---' }}</span>
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-users"></i>
              <strong>Assigned To:</strong>
              <span>
                {{ $projectAssigns->pluck('user.first_name')->join(', ') ?: '---' }}
              </span>
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-link"></i>
              <strong>Live URL:</strong>
              @if($projects->live_url)
                <a href="{{ $projects->live_url }}" target="_blank">{{ $projects->live_url }}</a>
              @else
                <span>---</span>
              @endif
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-code"></i>
              <strong>Dev URL:</strong>
              @if($projects->dev_url)
                <a href="{{ $projects->dev_url }}" target="_blank">{{ $projects->dev_url }}</a>
              @else
                <span>---</span>
              @endif
            </div>

            <div class="detail-item">
              <i class="fa-brands fa-git-alt"></i>
              <strong>Git Repo:</strong>
              @if($projects->git_repo)
                <a href="{{ $projects->git_repo }}" target="_blank">{{ $projects->git_repo }}</a>
              @else
                <span>---</span>
              @endif
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-layer-group"></i>
              <strong>Tech Stacks:</strong>
              <span>{{ $projects->tech_stacks ?? '---' }}</span>
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-align-left"></i>
              <strong>Description:</strong>
              <div>{!! $projects->description ?? '---' !!}</div>
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-calendar-days"></i>
              <strong>Start Date:</strong>
              <span>{{ \Carbon\Carbon::parse($projects->start_date)->format('d-m-Y') }}</span>
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-calendar-check"></i>
              <strong>End Date:</strong>
              <span>{{ $projects->end_date ? \Carbon\Carbon::parse($projects->end_date)->format('d-m-Y') : '---' }}</span>
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-key"></i>
              <strong>Credentials:</strong>
              <div>{!! $projects->credentials ?? '---' !!}</div>
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-file-alt"></i>
              <strong>Documents:</strong>
              @if ($ProjectDocuments->isEmpty())
                <span>No documents uploaded</span>
              @else
                @foreach ($ProjectDocuments as $doc)
                  @php
                    $ext = pathinfo($doc->document, PATHINFO_EXTENSION);
                    $icons = [
                      'pdf' => 'fa-file-pdf',
                      'doc' => 'fa-file-word',
                      'docx' => 'fa-file-word',
                      'xls' => 'fa-file-excel',
                      'xlsx' => 'fa-file-excel',
                      'jpg' => 'fa-file-image',
                      'jpeg' => 'fa-file-image',
                      'png' => 'fa-file-image',
                    ];
                    $icon = $icons[$ext] ?? 'fa-file';
                  @endphp
                  <button class="btn btn-outline-primary btn-sm mb-1" onclick="window.open('{{ asset('assets/img/'.$doc->document) }}', '_blank')">
                    <i class="fa {{ $icon }}"></i>
                  </button>
                @endforeach
              @endif
            </div>

            <div class="detail-items">
              <i class="fa-solid fa-circle-info"></i>
              <strong>Status:</strong>
              @php
                $statusMap = [
                  'not_started' => ['label' => 'Not Started', 'color' => 'primary'],
                  'active' => ['label' => 'Active', 'color' => 'info'],
                  'deactivated' => ['label' => 'Deactivated', 'color' => 'danger'],
                  'completed' => ['label' => 'Completed', 'color' => 'success'],
                ];
                $status = $statusMap[$projects->status] ?? ['label' => ucfirst($projects->status), 'color' => 'secondary'];
              @endphp
              <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-5">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <i class="fa-solid fa-users"></i> Developer Listing
          </div>
          <div class="card-body">
            @forelse($developers as $assign)
              <div class="mb-2">
                <i class="fa-solid fa-user"></i>
                {{ $assign->user->first_name ?? 'N/A' }} {{ $assign->user->last_name ?? '' }}
              </div>
            @empty
              <p>No developers assigned.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
    <div class="sprint-section">
        <div class="sprint-header production">
            <div class="section-left">
                <div class="section-icon bg-production" style="background-color: #297bab;">A</div>
                <div class="section-title" style="color: #297bab;">Active Sprints</div>
                <div class="section-title">• {{ $sprints->count() }} Sprints</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sprint Name</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                        <th>ToDo|InProgress|Ready|Deployed|Complete</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sprints as $sprint)
                        <tr>
                            <td>{{ $sprint->name }}</td>
                           <td>
                            <span class="badge {{ $sprint->status == 1 ? 'active' : 'inactive' }}">
                                {{ $sprint->status == 1 ? 'Active' : 'Inactive' }}
                            </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($sprint->start_date)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($sprint->end_date)->format('d-m-Y') }}</td>
                            <td>
                                <a href="{{ route('sprint.view', $sprint->id) }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                                    <i class="fa fa-edit fa-fw pointer"></i>
                                </a>
                                <i class="fa fa-trash fa-fw pointer" onclick="deleteSprint('{{ $sprint->id }}')"></i>
                            </td>
                            <td style="text-align: center;">
                      <div class="d-flex justify-content-center status-group">
                          <div class="status-box text-white" title="To Do" style="background-color: #948979;">
                              {{ $sprint->todo_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-info text-white" title="In Progress" style="background-color: #3fa6d7 !important;">
                              {{ $sprint->in_progress_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-success text-white" title="Ready" style="background-color: #e09f3e !important;">
                              {{ $sprint->ready_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-info text-white" title="Deployed" style="background-color: #e76f51 !important;">
                            {{ $sprint->deployed_tickets_count ?? 0 }}
                        </div>
                          <div class="status-box bg-warning text-white" title="Complete" style="background-color: #2a9d8f !important;">
                              {{ $sprint->completed_tickets_count ?? 0 }}
                          </div>
                      </div>
                  </td>  
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No sprints found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="sprint-section">
        <div class="sprint-header production">
            <div class="section-left">
                <div class="section-icon bg-production" style="background-color: #297bab;">C</div>
                <div class="section-title" style="color: #297bab;">Completed Sprints</div>
                <div class="section-title">• {{ $completedsprints->count() }} Sprints</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sprint Name</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                        <th>ToDo|InProgress|Ready|Deployed|Complete</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($completedsprints as $sprints)
                        <tr>
                            <td>{{ $sprints->name }}</td>
                           <td>
                            <span class="badge {{ $sprint->status == 1 ? 'active' : 'inactive' }}">
                                {{ $sprints->status == 1 ? 'Active' : 'Inactive' }}
                            </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($sprints->start_date)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($sprints->end_date)->format('d-m-Y') }}</td>
                            <td>
                                <a href="{{ route('sprint.view', $sprints->id) }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ url('/edit/sprint/'.$sprints->id) }}">
                                    <i class="fa fa-edit fa-fw pointer"></i>
                                </a>
                                <i class="fa fa-trash fa-fw pointer" onclick="deleteSprint('{{ $sprints->id }}')"></i>
                            </td>
                            <td style="text-align: center;">
                      <div class="d-flex justify-content-center status-group">
                          <div class="status-box text-white" title="To Do" style="background-color: #948979;">
                              {{ $sprints->todo_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-info text-white" title="In Progress" style="background-color: #3fa6d7 !important;">
                              {{ $sprints->in_progress_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-success text-white" title="Ready" style="background-color: #e09f3e !important;">
                              {{ $sprints->ready_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-info text-white" title="Deployed" style="background-color: #e76f51 !important;">
                            {{ $sprints->deployed_tickets_count ?? 0 }}
                        </div>
                          <div class="status-box bg-warning text-white" title="Complete" style="background-color: #2a9d8f !important;">
                              {{ $sprints->completed_tickets_count ?? 0 }}
                          </div>
                      </div>
                  </td>  
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No sprints found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow rounded-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Latest Comments</h4>

                <div class="row">
                    @forelse ($ticketComments as $comment)
                        <div class="col-md-6 col-xl-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="{{ asset($comment->user->profile_picture ?? 'images/default-avatar.png') }}"
                                             alt="avatar" class="rounded-circle me-3 border" width="45" height="45">
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $comment->user->first_name ?? 'Unknown' }}</h6>
                                            <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>

                                    <p class="text-muted mb-3 flex-grow-1" style="min-height: 60px;">
                                        {!! Str::limit(strip_tags($comment->comments), 120) !!}
                                    </p>

                                    <div class="mt-auto">
                                        @if($comment->ticket)
                                            <small class="text-primary d-block mb-1">
                                                Ticket: <strong>{{ $comment->ticket->title ?? 'N/A' }}</strong>
                                            </small>
                                            <a href="{{ route('tickets.show', $comment->ticket_id) }}" class="btn btn-sm btn-outline-primary">
                                                View Ticket
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted text-center">No comments available.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
function toggleTaskDetails(headerElement) {
  const taskCard = headerElement.closest('.task-card');
  const isExpanded = taskCard.classList.contains('expanded');
  const details = taskCard.querySelector('.task-details');
  const arrow = headerElement.querySelector('.arrow');

  if (isExpanded) {
    taskCard.classList.remove('expanded');
    taskCard.classList.add('collapsed');
    if (details) details.style.display = 'none';
    if (arrow) arrow.innerHTML = '&#9654;'; 
  } else {
    taskCard.classList.add('expanded');
    taskCard.classList.remove('collapsed');
    if (details) details.style.display = 'block';
    if (arrow) arrow.innerHTML = '&#9660;'; 
  }
}
 function deleteSprint(id) {
                $('#sprint_id').val(id);
                if (confirm("Are you sure ?") == true) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('/delete/sprint') }}",
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(res) {
                            location.reload();
                        }
                    });
                }
            }
</script>
@endsection