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

        <div class="comment-section">

                @php
                    $groupedActivities = $logNotifications->groupBy('project_id');
                @endphp

                <h4 class="mb-4 projectComment">Project Log Setting Activity</h4>

                <div class="row">

                    @foreach($groupedActivities as $projectId => $logs)

                    @php
                        $projectName = $logs->first()->project->project_name ?? 'Unknown Project';
                        $accordionId = 'activityAccordion'.$projectId;

                        $groupedByDate = $logs->groupBy(function ($log) {
                            $logDate = $log->updated_at->copy()->setTimezone('Asia/Kolkata')->startOfDay();
                            $today = now('Asia/Kolkata')->startOfDay();
                            $yesterday = now('Asia/Kolkata')->subDay()->startOfDay();

                            if ($logDate->eq($today)) {
                                return 'Today';
                            }

                            if ($logDate->eq($yesterday)) {
                                return 'Yesterday';
                            }

                            return $logDate->format('d-M-Y');
                        });
                    @endphp

                    <div class="col-lg-6 mb-4">

                    <div class="accordion" id="activityAccordion{{ $projectId }}">

                    <div class="accordion-item border rounded shadow-sm">

                    <h2 class="accordion-header" id="heading{{ $accordionId }}">
                    <button class="accordion-button collapsed text-white fw-bold"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse{{ $accordionId }}"
                            aria-expanded="true">

                    {{ $projectName }}

                    </button>
                    </h2>

                    <div id="collapse{{ $accordionId }}"
                        class="accordion-collapse collapse show"
                        data-bs-parent="#activityAccordion{{ $projectId }}">

                    <div class="accordion-body" style="max-height:300px; overflow-y:auto;">

                    @foreach($groupedByDate as $label => $logs)

                    <div class="text-center mb-2">
                    <span class="badge px-3 py-1 rounded-pill"
                        style="background:#e0e0e0;color:#333;font-weight:600;font-size:12px;">
                    {{ $label }}
                    </span>
                    </div>

                    @php
$log = $logs->first();
$userName = $log->user->first_name ?? 'System';
$status = $log->enabled ? 'enabled' : 'disabled';
@endphp

<div class="notification-entry mb-3 pb-2 border-bottom">

<i class="fa-solid fa-gear text-info me-2"></i>

<small>

<strong>{{ $userName }}</strong>

{{ $status }}

logs for project

<span class="text-primary fw-bold">
{{ $projectName }}
</span>

on

<span class="text-muted">
{{ $log->updated_at->setTimezone('Asia/Kolkata')->format('d-M-Y h:i A') }}
</span>

</small>

</div>

                
                    @endforeach

                    </div>
                    </div>
                    </div>
                    </div>

                    </div>

                    @endforeach
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
