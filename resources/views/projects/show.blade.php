@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Show')
@section('content')
<div class="container">
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
                        <th>Description</th>
                        <th>Action</th>
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
                            <td>{!! Str::limit(strip_tags($sprint->description), 50) !!}</td>
                            <td>
                                <a href="{{ route('sprint.view', $sprint->id) }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                                    <i class="fa fa-edit fa-fw pointer"></i>
                                </a>
                                <i class="fa fa-trash fa-fw pointer" onclick="deleteSprint('{{ $sprint->id }}')"></i>
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