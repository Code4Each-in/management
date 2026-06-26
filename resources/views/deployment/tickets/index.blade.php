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

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('deployment.tickets.index') }}" class="row g-2">
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
                                <option value="{{ $project->id }}" >{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="developer_id" class="form-select">
                            <option value="">All Developers</option>
                            @foreach ($developers as $dev)
                                <option value="{{ $dev->id }}" >{{ $dev->first_name }} {{ $dev->last_name }}</option>
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
                        <button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Deployment Name</th>
                            <th>Project</th>
                            <th>Developer</th>
                            <th>Reviewer</th>
                            <th>QA</th>
                            <th>Priority</th>
                            <th>Open Bugs</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td><a href="{{ route('deployment.tickets.show', $ticket) }}">{{ $ticket->deployment_code }}</a></td>
                                <td>{{ $ticket->deployment_name }}</td>
                                <td>{{ $ticket->project->project_name ?? '-' }}</td>
                                <td>{{ $ticket->developer->first_name ?? '-' }} {{ $ticket->developer->last_name ?? '-' }}</td>
                                <td>{{ $ticket->reviewer->first_name ?? '-' }} {{ $ticket->reviewer->last_name ?? '-' }}</td>
                                <td>{{ $ticket->qaTester->first_name ?? '-' }} {{ $ticket->qaTester->last_name ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $ticket->priority }}</span></td>
                                <td>
                                    @if ($ticket->bugs_count > 0)
                                        <span class="badge bg-danger">{{ $ticket->bugs_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $ticket->statusBadgeClass() }}">{{ $ticket->status }}</span></td>
                                <td>
                                    <a href="{{ route('deployment.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No deployment tickets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                {{ $tickets->links() }}
            </div>
        </div>

    </div>

@endsection
