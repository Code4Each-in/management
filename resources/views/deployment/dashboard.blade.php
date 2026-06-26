@extends('layout')
@section('title', 'Deployment Dashboard')
@section('content')

    <div class="col-12">

        {{-- Simple tab nav between Dashboard / Tickets / Reports / Notifications --}}
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('deployment.dashboard') }}">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.tickets.index') }}">Deployment Tickets</a>
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

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('deployment.tickets.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Deployment
            </a>
        </div>

        {{-- Stat widgets --}}
        <div class="row g-3 mb-4">
            @php
                $widgets = [
                    ['label' => 'Total Deployment Tickets', 'value' => $stats['total'], 'icon' => 'rocket-takeoff', 'color' => 'primary'],
                    ['label' => 'Pending Reviews', 'value' => $stats['pending_review'], 'icon' => 'hourglass-split', 'color' => 'info'],
                    ['label' => 'Review In Progress', 'value' => $stats['review_in_progress'], 'icon' => 'eye', 'color' => 'info'],
                    ['label' => 'Testing In Progress', 'value' => $stats['testing_in_progress'], 'icon' => 'bug', 'color' => 'warning'],
                    ['label' => 'Ready For Deployment', 'value' => $stats['ready_for_deployment'], 'icon' => 'check2-circle', 'color' => 'primary'],
                    ['label' => 'Approved Deployments', 'value' => $stats['approved'], 'icon' => 'check-circle-fill', 'color' => 'success'],
                    ['label' => 'Rejected Deployments', 'value' => $stats['rejected'], 'icon' => 'x-circle-fill', 'color' => 'danger'],
                    ['label' => 'Open Bugs', 'value' => $stats['open_bugs'], 'icon' => 'exclamation-triangle-fill', 'color' => 'danger'],
                    ['label' => 'Closed Bugs', 'value' => $stats['closed_bugs'], 'icon' => 'check-square', 'color' => 'success'],
                ];
            @endphp

            @foreach ($widgets as $widget)
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3 fs-2 text-{{ $widget['color'] }}">
                                <i class="bi bi-{{ $widget['icon'] }}"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold">{{ $widget['value'] }}</div>
                                <div class="text-muted small">{{ $widget['label'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            {{-- Deployments per developer --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Deployments Per Developer</div>
                    <div class="card-body">
                        @if ($deploymentsPerDeveloper->isEmpty())
                            <p class="text-muted mb-0">No deployments assigned yet.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($deploymentsPerDeveloper as $row)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        {{ $row->developer->first_name ?? 'Unassigned' }}
                                        <span class="badge bg-primary rounded-pill">{{ $row->total }}</span>
                                    </li>
                                @endforeach
                            </ul> 
                        @endif
                    </div>
                </div>
            </div>

            {{-- My tickets --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">My Active Tickets</div>
                    <div class="card-body p-0">
                        @if ($myTickets->isEmpty())
                            <p class="text-muted p-3 mb-0">Nothing assigned to you right now.</p>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach ($myTickets as $t)
                                    <a href="{{ route('deployment.tickets.show', $t) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>{{ $t->deployment_code }} - {{ $t->deployment_name }}</span>
                                        <span class="badge bg-{{ $t->statusBadgeClass() }}">{{ $t->status }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent activity --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Recent Deployment Tickets</div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach ($recentTickets as $t)
                                <a href="{{ route('deployment.tickets.show', $t) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <span>{{ $t->deployment_code }} - {{ $t->deployment_name }}</span>
                                    <span class="badge bg-{{ $t->statusBadgeClass() }}">{{ $t->status }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
