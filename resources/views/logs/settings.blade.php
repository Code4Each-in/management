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
            <th>Logging Status</th>
            <th width="120">Action</th>
            </tr>
            </thead>

            <tbody>

            @foreach($projects as $project)

                <tr>

                <td>{{ $project->project_name }}</td>

                <td id="status-{{ $project->id }}">
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
</script>
@endsection
