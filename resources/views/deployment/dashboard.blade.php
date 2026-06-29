@extends('layout')
@section('title', 'Deployment Dashboard')
@section('content')

    <div class="col-12">

        {{-- Professional Pill Navigation --}}

            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold" href="{{ route('deployment.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('deployment.tickets.index') }}">Deployment Tickets</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link " href="{{ route('deployment.reports.index') }}">Reports</a>
                </li>
            </ul>
        </div>
           <div class="d-flex justify-content-end">
                <a href="{{ route('deployment.tickets.create') }}" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-plus-lg"></i> New Deployment
                </a>
            </div>
        {{-- Toast Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 px-4 py-3 mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 px-4 py-3 mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
                    <div>{{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Stat Widgets --}}
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

        {{-- Bottom Row Grid --}}
        <div class="row g-4">

            {{-- Deployments per developer --}}
            <div class="col-lg-4">
                <div class="card border border-light shadow-sm h-100 rounded-3">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark mb-0 fs-6">Deployments Per Developer</h5>
                        <i class="bi bi-people text-muted"></i>
                    </div>
                    <div class="card-body px-4 pb-4 pt-2">
                        @if ($deploymentsPerDeveloper->isEmpty())
                            <div class="text-center py-4">
                                <p class="text-muted small mb-0">No deployments assigned yet.</p>
                            </div>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($deploymentsPerDeveloper as $row)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-light">
                                        <span class="text-secondary fw-medium">{{ $row->developer->first_name ?? 'Unassigned' }}</span>
                                        <span class="badge bg-light text-primary fw-bold rounded-pill px-3 py-2 border border-primary-subtle">{{ $row->total }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- My tickets --}}
            <div class="col-lg-4">
                <div class="card border border-light shadow-sm h-100 rounded-3">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark mb-0 fs-6">My Active Tickets</h5>
                        <i class="bi bi-person-workspace text-muted"></i>
                    </div>
                    <div class="card-body p-0 pb-3">
                        @if ($myTickets->isEmpty())
                            <div class="text-center py-5">
                                <p class="text-muted small mb-0">Nothing assigned to you right now.</p>
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach ($myTickets as $t)
                                    <a href="{{ route('deployment.tickets.show', $t) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-4 py-3 border-light transition-row">
                                        <div class="text-truncate me-2">
                                            <span class="font-monospace fw-semibold text-primary small d-block mb-0.5">{{ $t->deployment_code }}</span>
                                            <span class="text-dark small text-truncate d-block">{{ $t->deployment_name }}</span>
                                        </div>
                                        <span class="badge bg-{{ $t->statusBadgeClass() }} bg-opacity-10 text-{{ $t->statusBadgeClass() }} border border-{{ $t->statusBadgeClass() }}-subtle px-2.5 py-1.5 rounded-2 small fw-semibold">
                                            {{ $t->status }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent activity --}}
            <div class="col-lg-4">
                <div class="card border border-light shadow-sm h-100 rounded-3">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark mb-0 fs-6">Recent Deployment Tickets</h5>
                        <i class="bi bi-clock-history text-muted"></i>
                    </div>
                    <div class="card-body p-0 pb-3">
                        <div class="list-group list-group-flush">
                            @foreach ($recentTickets as $t)
                                <a href="{{ route('deployment.tickets.show', $t) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-4 py-3 border-light transition-row">
                                    <div class="text-truncate me-2">
                                        <span class="font-monospace fw-semibold text-primary small d-block mb-0.5">{{ $t->deployment_code }}</span>
                                        <span class="text-dark small text-truncate d-block">{{ $t->deployment_name }}</span>
                                    </div>
                                    <span class="badge bg-{{ $t->statusBadgeClass() }} bg-opacity-10 text-{{ $t->statusBadgeClass() }} border border-{{ $t->statusBadgeClass() }}-subtle px-2.5 py-1.5 rounded-2 small fw-semibold">
                                        {{ $t->status }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
