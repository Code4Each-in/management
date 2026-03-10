@extends('layout')
@section('title', 'Project Logs')
@section('content')
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">

            <form method="GET" class="row mb-3">

                <div class="col-md-4">
                    <label>Project</label>
                    <select id="projectFilter" class="form-control">
                    <option value="">All Projects</option>

                    @foreach($projects as $project)
                    <option value="{{ $project->project_name }}">
                    {{ $project->project_name }}
                    </option>
                    @endforeach

                    </select>
                    </div>

                    <div class="col-md-4">
                    <label>Type</label>
                    <select id="typeFilter" class="form-control">
                    <option value="">All Types</option>

                    @foreach($types as $type)
                    <option value="{{ $type }}">
                    {{ ucfirst($type) }}
                    </option>
                    @endforeach

                    </select>
                </div>


            </form>
            <table  class="table table-borderless dashboard" id="logs_table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Context</th>
                        <th>Logged At</th>
                    </tr>
                </thead>
                <tbody>

                @forelse($logs as $key => $log)
                    <tr>
                        <td>{{ $log->project->project_name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($log->type) }}</td>
                        <td style="font-family: monospace;" title="{{ $log->message }}">
                            {{ \Illuminate\Support\Str::limit($log->message, 60) }}
                        </td>
                        <td>
                            @if($log->context)
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#contextModal{{ $key }}">
                                    View
                                </button>
                                <div class="modal fade" id="contextModal{{ $key }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Context</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <pre>{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $log->logged_at ? $log->logged_at->format('d M Y h:i A') : '' }}</td>
                    </tr>
                @empty
                @endforelse

                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {

    var table = $('#logs_table').DataTable({
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
</script>
@endsection
