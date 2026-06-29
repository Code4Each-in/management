@extends('layout')
@section('title', 'Deployment Tickets')
@section('content')
    <div class="col-12">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.dashboard') }}">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('deployment.tickets.index') }}">Deployment Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.reports.index') }}">Reports</a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.notifications.index') }}">Notifications</a>
            </li> -->
        </ul>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('deployment.tickets.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Deployment
            </a>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('deployment.tickets.index') }}" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or code...">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="project_id" class="form-select">
                            <option value="">All Projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="developer_id" class="form-select">
                            <option value="">All Developers</option>
                            @foreach ($developers as $dev)
                                <option value="{{ $dev->id }}" @selected(request('developer_id') == $dev->id)>{{ $dev->first_name }} {{ $dev->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="priority" class="form-select">
                            <option value="">All Priorities</option>
                            @foreach (['Low', 'Medium', 'High', 'Critical'] as $p)
                                <option value="{{ $p }}" @selected(request('priority') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-secondary w-100" title="Search"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th style="width: 10%;">Code</th>
                            <th style="width: 25%;">Deployment Name</th>
                            <th>Project</th>
                            <th>Developer</th>
                            <th>Reviewer</th>
                            <th>QA</th>
                            <th>Priority</th>
                            <th class="text-center">Bugs</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td>
                                    <a href="{{ route('deployment.tickets.show', $ticket) }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">
                                        {{ $ticket->deployment_code }}
                                    </a>
                                </td>
                                <td class="fw-medium text-dark">{{ $ticket->deployment_name }}</td>
                                <td>{{ $ticket->project->project_name ?? '-' }}</td>
                                <td>{{ $ticket->developer->first_name ?? '-' }} {{ $ticket->developer->last_name ?? '-' }}</td>
                                <td>{{ $ticket->reviewer->first_name ?? '-' }} {{ $ticket->reviewer->last_name ?? '-' }}</td>
                                <td>{{ $ticket->qaTester->first_name ?? '-' }} {{ $ticket->qaTester->last_name ?? '-' }}</td>
                                <td>
                                    @php
                                        $classes = match($ticket->priority) {
                                            'Low' => 'text-success border border-success bg-success-subtle',
                                            'Medium' => 'text-primary border border-primary bg-primary-subtle',
                                            'High' => 'text-warning border border-warning bg-warning-subtle',
                                            'Critical' => 'text-danger border border-danger bg-danger-subtle',
                                            default => 'text-secondary border border-secondary bg-light',
                                        };
                                    @endphp

                                    <span class="badge rounded-pill px-2 py-1 {{ $classes }}" style="font-size: 0.75rem;">
                                        {{ $ticket->priority }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($ticket->bugs_count > 0)
                                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger px-2">{{ $ticket->bugs_count }}</span>
                                    @else
                                        <span class="text-muted small">0</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $ticket->statusBadgeClass() }}">{{ $ticket->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('deployment.tickets.show', $ticket) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light border text-secondary px-3">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary-50"></i>
                                    No deployment tickets found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $tickets->links() }}
            </div>
        </div>

    </div>

@endsection
