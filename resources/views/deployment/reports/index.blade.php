@extends('layout')

@section('title', 'Reports')

@section('content')

    <div class="col-12">
              <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.dashboard') }}">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link " href="{{ route('deployment.tickets.index') }}">Deployment Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('deployment.reports.index') }}">Reports</a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.notifications.index') }}">Notifications</a>
            </li> -->
        </ul>
        <p class="text-muted">Developer, Reviewer and QA performance metrics</p> 
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('deployment.reports.index') }}" class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">From</label>
                        <input type="date" name="from" value="{{ $from }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">To</label>
                        <input type="date" name="to" value="{{ $to }}" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-outline-secondary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Developer Metrics --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Developer Metrics</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Developer</th>
                            <th># Deployments</th>
                            <th>Review Pass Rate</th>
                            <th>Review Rejections</th>
                            <th>Bugs Raised</th>
                            <th>Bugs Fixed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($developerMetrics as $row)
                            <tr>
                                <td>{{ $row['developer']->first_name }} {{ $row['developer']->last_name }}</td>
                                <td>{{ $row['deployments'] }}</td>
                                <td>{{ $row['review_pass_rate'] !== null ? $row['review_pass_rate'] . '%' : '-' }}</td>
                                <td>{{ $row['review_rejections'] }}</td>
                                <td>{{ $row['bugs_raised'] }}</td> 
                                <td>{{ $row['bugs_fixed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No data for selected range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Reviewer Metrics --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Reviewer Metrics</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reviewer</th>
                            <th>Reviews Completed</th>
                            <th>Average Review Time (min)</th>
                            <th>Rejections Issued</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reviewerMetrics as $row)
                            <tr>
                                <td>{{ $row['reviewer']->first_name }} {{ $row['reviewer']->last_name }}</td>
                                <td>{{ $row['reviews_completed'] }}</td>
                                <td>{{ $row['avg_review_time_minutes'] ?? '-' }}</td>
                                <td>{{ $row['rejections_issued'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No data for selected range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- QA Metrics --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">QA Metrics</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>QA Tester</th>
                            <th>Bugs Found</th>
                            <th>Bugs Reopened</th>
                            <th>Testing Approvals</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($qaMetrics as $row) 
                            <tr>
                                <td>{{ $row['tester']->first_name }} {{ $row['tester']->last_name }}</td>
                                <td>{{ $row['bugs_found'] }}</td>
                                <td>{{ $row['bugs_reopened'] }}</td>
                                <td>{{ $row['testing_approvals'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No data for selected range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection
