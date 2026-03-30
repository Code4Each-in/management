@extends('layout')
@section('title', 'Project Logs')
@section('content')

<style>
    #logs_table tbody tr {
        border-bottom: 1px solid #090d12 !important;
    }
    #logs_table tbody td {
        border-bottom: 1px solid #090d12 !important;
        vertical-align: middle;
    }
    #logs_table thead th {
        border-bottom: 2px solid #dee2e6 !important;
    }
    .context-btn {
        background: none;
        border: none;
        color: #0d6efd;
        font-size: 0.85rem;
        padding: 0;
        cursor: pointer;
        text-decoration: underline;
    }
    .context-btn:hover { color: #0a58ca; }

    .modal .dataTables_info {
        display: none;
    }

    .modal-table th:nth-child(3),
    .modal-table td:nth-child(3) {
        width: 300px;
        /* max-width: 150px; */
        word-wrap: break-word;
        white-space: normal;
    }
</style>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <div class="row mb-4">

                {{-- Error Card --}}
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Errors</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #ffe0e0;">
                                    <i class="bi bi-x-circle" style="color: #dc3545;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $typeCounts['error'] ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info Card --}}
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Info</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #fff3cd;">
                                    <i class="bi bi-info-circle" style="color: #ffc107;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $typeCounts['info'] ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Success Card --}}
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Success</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #d1f5e0;">
                                    <i class="bi bi-check-circle" style="color: #198754;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $typeCounts['success'] ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- {{-- Other / Warning Card --}}
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Others</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #e2e3e5;">
                                    <i class="bi bi-question-circle" style="color: #6c757d;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $typeCounts->except(['error','info','success'])->sum() ?? 0 }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->

            </div>
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <label>Date</label>
                    <select name="date_filter" id="dateFilter" class="form-control">
                        <option value="">All Time</option>
                        <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>
                            Today
                        </option>
                        <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>
                            Yesterday
                        </option>
                        <option value="7days" {{ request('date_filter') == '7days' ? 'selected' : '' }}>
                            Last 7 Days
                        </option>
                        <option value="15days" {{ request('date_filter') == '15days' ? 'selected' : '' }}>
                            Last 15 Days
                        </option>
                        <option value="30days" {{ request('date_filter') == '30days' ? 'selected' : '' }}>
                            Last 30 Days
                        </option>
                    </select>
                </div>
            </form>

            <table class="table table-striped dashboard" id="logs_table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th style="text-align: center;">Logger ID</th>
                        <th style="text-align: center;">Logs</th>
                        <th style="text-align: center;">Log Details</th>
                        <th>Logged At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $loggerId => $groupLogs)
                        @php
                            $log = $groupLogs->first(); 
                        @endphp
                        <tr>
                            <td>{{ $log->project->project_name ?? 'N/A' }}</td>
                            <td style="text-align: center;">{{ $loggerId }}</td>
                            <td style="text-align: center;">
                                <span class="btn btn-sm btn-primary">
                                    {{ $groupLogs->count() }} Logs
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#contextModal{{ $loggerId }}">
                                    View
                                </button>
                            </td>
                            <td>
                                {{ $log->logged_at ? $log->logged_at->format('d M Y h:i:s A') : '' }}
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
            @foreach($logs as $loggerId => $groupLogs)
            <div class="modal fade" id="contextModal{{ $loggerId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Logs Details for Logger ID: {{ $loggerId }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">

                            {{-- Filters --}}
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Type</label>
                                    <select class="form-control modalTypeFilter">
                                        <option value="">All Types</option>
                                        @foreach($types as $type)
                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Table --}}
                            <table class="table table-striped modal-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Message</th>
                                        <th>Context</th>
                                        <th>Logged At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupLogs as $log)
                                    <tr>
                                        <td>
                                            @if($log->type == 'error')
                                                <span class="badge bg-danger">Error</span>
                                            @elseif($log->type == 'info')
                                                <span class="badge bg-warning text-dark">Info</span>
                                            @elseif($log->type == 'success')
                                                <span class="badge bg-success">Success</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($log->type) }}</span>
                                            @endif
                                        </td>
                                        <td>{!! \Illuminate\Support\Str::limit($log->message, 200) !!}</td>
                                        <td>
                                            @if($log->context)
                                                <pre>{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                <span style="color:#ccc;">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->logged_at ? $log->logged_at->format('d M Y h:i:s A') : '' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize main table
    $('#logs_table').DataTable({ "order": [] });

    // Initialize all modal tables
    $('.modal').each(function() {
        var modal = $(this);
        var table = modal.find('.modal-table').DataTable({
            "order": [],
            "columnDefs": [
                { "orderable": false, "targets": [1,2] }
            ]
        });

        // Type Filter
        modal.find('.modalTypeFilter').on('change', function() {
            var value = $(this).val();
            table.column(0).search(value).draw();
        });

    });

    // Reset modal filters when modal is closed
    $('.modal').on('hidden.bs.modal', function () {
        var modal = $(this);

        // Reset dropdown
        modal.find('.modalTypeFilter').val('');

        // Reset DataTable search
        var table = modal.find('.modal-table').DataTable();
        table.search('').columns().search('').draw();
    });
});

$('#dateFilter').on('change', function () {
    var value = $(this).val();
    var url = new URL(window.location.href);
    if (value) {
        url.searchParams.set("date_filter", value);
    } else {
        url.searchParams.delete("date_filter");
    }
    window.location.href = url.toString();
});
</script>
@endsection
