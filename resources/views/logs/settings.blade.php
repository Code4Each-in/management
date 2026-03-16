@extends('layout')
@section('title', 'Project Logs Settings')
@section('content')
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">

            <table  class="table table-striped dashboard" id="log_settings_table">
            <thead>
            <tr>
            <th>Project</th>
            <th style="text-align: center;">Logging Status</th>
            <th width="120">Action</th>
            </tr>
            </thead>

            <tbody>

            @foreach($projects as $project)

                <tr>

                <td>{{ $project->project_name }}</td>

                <td style="text-align: center;" id="status-{{ $project->id }}">
                @if($project->enabled)
                <span class="badge bg-success">Enabled</span>
                @else
                <span class="badge bg-danger">Disabled</span>
                @endif
                </td>

                <td>
                <div class="form-check form-switch">
                <input
                class="form-check-input"
                type="checkbox"
                {{ $project->enabled ? 'checked' : '' }}
                onchange="toggleProjectLogs({{ $project->id }})"
                >
                </div>
                </td>

                </tr>

            @endforeach

            </tbody>

            </table>
        </div>
    </div>
    <div class="card">
        <div class="comment-section" >
                @php
                $activityProjects = $logNotifications->pluck('project.project_name')->unique();
                @endphp
            <!-- <h4 class="mb-4 projectComment">Project Log Setting Activity</h4> -->
                <div class="d-flex justify-content-between align-items-center mb-3">

                <h4 class="projectComment mb-0">Project Log Setting Activity</h4>

                <select id="activityProjectFilter" class="form-select w-auto">
                <option value="">All Projects</option>

                @foreach($activityProjects as $projectName)

                <option value="{{ $projectName }}">
                {{ $projectName }}
                </option>

                @endforeach

                </select>

                </div>
            <div class="activity-container" style="max-height:350px; overflow-y:auto;">

                <div class="row" id="activityList">

                @foreach($logNotifications as $log)

                @php
                $projectName = $log->project->project_name ?? 'Unknown Project';
                $userName = $log->user->first_name ?? 'System';
                $status = $log->enabled ? 'enabled' : 'disabled';
                @endphp

                <div class="col-md-6 mb-3 activity-item" data-project="{{ $projectName }}">

                <div class="notification-entry pb-2 border-bottom">

                <i class="fa-solid fa-gear text-info me-2"></i>

                <small>

                <strong>{{ $userName }}</strong>

                {{ $status }}

                logs for project

                <span class="text-primary fw-bold">{{ $projectName }}</span>

                on

                <span class="text-muted">
                {{ $log->updated_at->format('d-M-Y h:i A') }}
                </span>

                </small>

                </div>

                </div>

                @endforeach

                </div>

            </div>

        </div>
    </div>
</div>
<script>
$(document).ready(function() {

    var table = $('#log_settings_table').DataTable({
        "order": []
    });

    // Project filter
    $('#projectFilter').on('change', function () {
        table.column(0).search(this.value).draw();
    });

    // Type filter
    $('#typeFilter').on('change', function () {
        table.column(1).search(this.value).draw();
    });

});

function toggleProjectLogs(projectId) {

    fetch(`/project/${projectId}/toggle-logs`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Content-Type": "application/json"
        }
    })
    .then(response => response.json())
    .then(data => {

        let statusCell = document.getElementById('status-'+projectId);

        if(data.enabled){
            statusCell.innerHTML = '<span class="badge bg-success">Enabled</span>';
        }else{
            statusCell.innerHTML = '<span class="badge bg-danger">Disabled</span>';
        }

    });
}

$('#activityProjectFilter').on('change', function () {

    var selectedProject = $(this).val();

    $('.activity-item').each(function(){

        var project = $(this).data('project');

        if(selectedProject === "" || project === selectedProject){
            $(this).show();
        }else{
            $(this).hide();
        }

    });

});
</script>
@endsection
