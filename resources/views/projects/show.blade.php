@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Show')
@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ url('/edit/project/'.$projects->id)}}" class="btn btn-outline-primary me-2">
        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Project
    </a>
    <a href="{{ url('messages?project_id=' . $projects->id) }}" class="btn btn-outline-success">
    <i class="fa-solid fa-comments me-1"></i> Chat
    </a>
</div>
<div class="container">
  <div class="row d-flex align-items-stretch">
    <div class="col-md-7 d-flex">
      <div class="task-card  flex-fill expanded">
        <div class="task-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="task-icon me-2">
              <i class="fa-solid fa-folder-open"></i>
            </div>
            <div class="task-title">
              <h4 class="mb-0">{{ $projects->project_name }}</h4>
            </div>
          </div>
          {{-- <div class="task-toggle-icon">
            <i class="fa-solid fa-chevron-down"></i>
          </div> --}}
        </div>
        <div class="task-details">
          {{-- Always visible --}}
          <div class="detail-item"><i class="fa-solid fa-user-tie"></i><strong>Client:</strong> <span>{{ $projects->client->name ?? '---' }}</span></div>
          <div class="detail-item"><i class="fa-solid fa-users"></i><strong>Assigned To:</strong> <span>{{ $projectAssigns->pluck('user.first_name')->join(', ') ?: '---' }}</span></div>
          <div class="detail-item"><i class="fa-solid fa-link"></i><strong>Live URL:</strong> {!! $projects->live_url ? "<a href='{$projects->live_url}' target='_blank'>{$projects->live_url}</a>" : '<span>---</span>' !!}</div>
          <div class="detail-item"><i class="fa-solid fa-code"></i><strong>Dev URL:</strong> {!! $projects->dev_url ? "<a href='{$projects->dev_url}' target='_blank'>{$projects->dev_url}</a>" : '<span>---</span>' !!}</div>
          <div class="detail-item"><i class="fa-brands fa-git-alt"></i><strong>Git Repo:</strong> {!! $projects->git_repo ? "<a href='{$projects->git_repo}' target='_blank'>{$projects->git_repo}</a>" : '<span>---</span>' !!}</div>

          {{-- Hidden by default --}}
          <div class="extra-details {{ request()->has('expanded') ? '' : 'd-none' }}">
            <div class="detail-item"><i class="fa-solid fa-layer-group"></i><strong>Tech Stacks:</strong> <span>{{ $projects->tech_stacks ?? '---' }}</span></div>

            <div class="detail-item">
              <i class="fa-solid fa-align-left"></i>
              <strong>Description:</strong>
              <div class="project-description">{!! $projects->description ?? '---' !!}</div>
            </div>

            <div class="detail-item"><i class="fa-solid fa-calendar-days"></i><strong>Start Date:</strong> <span>{{ \Carbon\Carbon::parse($projects->start_date)->format('d-m-Y') }}</span></div>
            <div class="detail-item"><i class="fa-solid fa-calendar-check"></i><strong>End Date:</strong> <span>{{ $projects->end_date ? \Carbon\Carbon::parse($projects->end_date)->format('d-m-Y') : '---' }}</span></div>

            <div class="detail-item"><i class="fa-solid fa-key"></i><strong>Credentials:</strong><div>{!! $projects->credentials ?? '---' !!}</div></div>

            <div class="detail-item">
              <i class="fa-solid fa-file-alt"></i><strong>Documents:</strong>
              @if ($ProjectDocuments->isEmpty())
                <span>No documents uploaded</span>
              @else
                @foreach ($ProjectDocuments as $doc)
                  @php
                    $ext = pathinfo($doc->document, PATHINFO_EXTENSION);
                    $icons = ['pdf' => 'fa-file-pdf', 'doc' => 'fa-file-word', 'docx' => 'fa-file-word', 'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel', 'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image'];
                    $icon = $icons[$ext] ?? 'fa-file';
                  @endphp
                  <button class="btn btn-outline-primary btn-sm mb-1" onclick="window.open('{{ asset('assets/img/'.$doc->document) }}', '_blank')">
                    <i class="fa {{ $icon }}"></i>
                  </button>
                @endforeach
              @endif
            </div>

            <div class="detail-item">
              <i class="fa-solid fa-circle-info"></i><strong>Status:</strong>
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
          <div class="mt-2">
            <a href="javascript:void(0);" class="text-primary small" onclick="toggleExtraDetails(this)">See more</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Developer Listing -->
    <div class="col-md-5 d-flex">
      <div class="card shadow rounded-4 flex-fill">
        <div class="card-header bg-primary text-white rounded-top-4 d-flex align-items-center">
          <i class="fa-solid fa-users me-2"></i><strong>Developer Listing</strong>
        </div>
        <div class="card-body">
          @forelse($developers as $assign)
            <div class="d-flex align-items-center mb-3">
              <img src="{{ asset('assets/img/' . ($assign->profile_picture ?? 'default-avatar.png')) }}"
                alt="Profile" class="rounded-circle me-3 border" width="35" height="35">
              <div><div class="fw-semibold">{{ $assign->first_name ?? 'N/A' }} {{ $assign->last_name ?? '' }}</div></div>
            </div>
          @empty
            <p class="text-muted mb-0">No developers assigned.</p>
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
    <div class="sprint-header production" data-bs-toggle="collapse" data-bs-target="#completedSprintsTable" aria-expanded="true" style="cursor: pointer;">
        <div class="section-left">
            <div class="section-icon bg-production" style="background-color: #297bab;">C</div>
            <div class="section-title" style="color: #297bab;">Completed Sprints</div>
            <div class="section-title">• {{ $completedsprints->count() }} Sprints</div>
        </div>
        <div>
            <i class="fa-solid fa-chevron-down toggle-icon"></i>
        </div>
    </div>

    <div id="completedSprintsTable" class="collapse">
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
                                <span class="badge {{ $sprints->status == 1 ? 'active' : 'inactive' }}">
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
                            <td class="text-center">
                                <div class="d-flex justify-content-center status-group">
                                    <div class="status-box text-white" title="To Do" style="background-color: #948979;">
                                        {{ $sprints->todo_tickets_count ?? 0 }}
                                    </div>
                                    <div class="status-box text-white" title="In Progress" style="background-color: #3fa6d7;">
                                        {{ $sprints->in_progress_tickets_count ?? 0 }}
                                    </div>
                                    <div class="status-box text-white" title="Ready" style="background-color: #e09f3e;">
                                        {{ $sprints->ready_tickets_count ?? 0 }}
                                    </div>
                                    <div class="status-box text-white" title="Deployed" style="background-color: #e76f51;">
                                        {{ $sprints->deployed_tickets_count ?? 0 }}
                                    </div>
                                    <div class="status-box text-white" title="Complete" style="background-color: #2a9d8f;">
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
</div>
<h4 class="mb-4 projectComment mt-3">Recent Project Comments</h4>
<div class="row mt-4">
    @foreach($projectMap as $projectId => $projectName)
        @php
            // Filter comments for this project
            $projectComments = $latestComments->where('ticket.project.id', $projectId);
        @endphp

        @if($projectComments->isNotEmpty())
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: #297bab;">
                        <h5 class="mb-0">{{ $projectName }}</h5>
                    </div>
                    <div class="card-body overflow-auto" style="max-height: 300px;">
                        @foreach($projectComments as $comment)
                            @php
                                $userName = $comment->user->first_name ?? 'Unknown User';
                                $ticketId = $comment->ticket_id ?? 'N/A';
                                $ticketUrl = url('/view/ticket/' . $ticketId);
                                $commentDate = $comment->created_at->setTimezone('Asia/Kolkata')->format('d-M-Y H:i');
                            @endphp

                            <div class="mb-3 pb-2 border-bottom">
                                <a href="{{ $ticketUrl }}" class="text-decoration-none text-dark d-block fw-semibold" style="transition: color 0.3s;">
                                    <small>
                                        You received a new comment on 
                                        <span class="text-primary">#{{ $ticketId }}</span> in project 
                                        <span class="fw-bold">{{ $projectName }}</span> by 
                                        <span class="fw-bold">{{ $userName }}</span> on 
                                        <span class="text-muted">{{ $commentDate }}</span>.
                                    </small>
                                </a>
                                <p class="mb-0 small text-muted">
                                    {!! Str::limit(strip_tags($comment->comments), 100) !!}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
@section('js_scripts')
<script>
function toggleTaskDetails(headerElement) {
  const taskCard = headerElement.closest('.task-card');
  const isExpanded = taskCard.classList.contains('expanded');
  const arrow = headerElement.querySelector('.arrow');

  if (isExpanded) {
    taskCard.classList.remove('expanded');
    taskCard.classList.add('collapsed');
    if (arrow) arrow.innerHTML = '&#9654;'; // Right arrow
  } else {
    taskCard.classList.add('expanded');
    taskCard.classList.remove('collapsed');
    if (arrow) arrow.innerHTML = '&#9660;'; // Down arrow
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
<script>
function toggleExtraDetails(link) {
  const card = link.closest('.task-card');
  const extra = card.querySelector('.extra-details');

  if (extra.classList.contains('d-none')) {
    extra.classList.remove('d-none');
    link.textContent = 'See less';
  } else {
    extra.classList.add('d-none');
    link.textContent = 'See more';
  }
}
</script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.sprint-header');
    const collapsible = document.getElementById('completedSprintsTable');

    collapsible.addEventListener('shown.bs.collapse', () => {
      header.classList.add('expanded');
      header.classList.remove('collapsed');
    });

    collapsible.addEventListener('hidden.bs.collapse', () => {
      header.classList.remove('expanded');
      header.classList.add('collapsed');
    });
  });
</script>
@endsection