@extends('layout')

@section('title', 'Reports')

@section('content')

    <div class="col-12">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.dashboard') }}">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('deployment.tickets.index') }}">Deployment Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active font-weight-bold" href="{{ route('deployment.reports.index') }}">Reports</a>
            </li>
        </ul>

        <div class="mb-4">
            <h1 class="h4 mb-1 font-weight-bold text-dark">Performance Reports</h1>
            <p class="text-muted small">Developer, Reviewer and QA performance metrics</p>
        </div>

        <div class="card border-0 shadow-sm mb-4 bg-light">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('deployment.reports.index') }}" class="row align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-secondary mb-1">From</label>
                        <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-secondary mb-1">To</label>
                        <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-secondary mb-1">Deployment</label>
                        <select name="deployment_id" class="form-control form-control-sm custom-select custom-select-sm">
                            <option value="">All Deployments</option>
                            @foreach ($deployments as $deployment)
                                <option value="{{ $deployment->id }}" {{ (string) $deploymentId === (string) $deployment->id ? 'selected' : '' }}>
                                    #{{ $deployment->id }} - {{ $deployment->title ?? $deployment->name ?? 'Deployment '.$deployment->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm btn-block font-weight-bold">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Developer Metrics --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 text-dark font-weight-bold" style="font-size: 1.05rem;">Developer Metrics</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0 pl-4">Developer</th>
                            <th class="border-0 text-center"># Deployments</th>
                            <th class="border-0 text-center">Review Pass Rate</th>
                            <th class="border-0 text-center">Review Rejections</th>
                            <th class="border-0 text-center">Bugs Raised</th>
                            <th class="border-0 text-center">Bugs Fixed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($developerMetrics as $row)
                            <tr style="vertical-align: middle;">
                                <td class="font-weight-bold pl-4 text-dark">{{ $row['developer']->first_name }} {{ $row['developer']->last_name }}</td>
                                <td class="text-center font-weight-bold text-muted">{{ $row['deployments'] }}</td>
                                <td class="text-center">
                                    @php
                                        $rate = $row['review_pass_rate'];
                                    @endphp

                                    <span class="badge rounded-pill px-3 py-2 fw-semibold
                                        {{ $rate === null ? 'bg-secondary-subtle text-secondary' :
                                        ($rate >= 85 ? 'bg-success-subtle text-success border border-success' :
                                        ($rate >= 60 ? 'bg-warning-subtle text-warning border border-warning' :
                                                        'bg-danger-subtle text-danger border border-danger')) }}">
                                        {{ $rate !== null ? $rate . '%' : '-' }}
                                    </span>
                                </td>
                                <td class="text-center text-danger font-weight-bold">{{ $row['review_rejections'] }}</td>
                                <td class="text-center text-muted">{{ $row['bugs_raised'] }}</td>
                                <td class="text-center text-success font-weight-bold">{{ $row['bugs_fixed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No data for selected range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Reviewer Metrics --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 text-dark font-weight-bold" style="font-size: 1.05rem;">Reviewer Metrics</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0 pl-4">Reviewer</th>
                            <th class="border-0 text-center">Reviews Completed</th>
                            <th class="border-0 text-center">Average Review Time (min)</th>
                            <th class="border-0 text-center">Rejections Issued</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reviewerMetrics as $row)
                            <tr style="vertical-align: middle;">
                                <td class="font-weight-bold pl-4 text-dark">{{ $row['reviewer']->first_name }} {{ $row['reviewer']->last_name }}</td>
                                <td class="text-center font-weight-bold text-muted">{{ $row['reviews_completed'] }}</td>
                                <td class="text-center text-muted">{{ $row['avg_review_time_minutes'] ?? '-' }}</td>
                                <td class="text-center text-danger font-weight-bold">{{ $row['rejections_issued'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No data for selected range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- QA Metrics --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 text-dark font-weight-bold" style="font-size: 1.05rem;">QA Metrics</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0 pl-4">QA Tester</th>
                            <th class="border-0 text-center">Bugs Found</th>
                            <th class="border-0 text-center">Bugs Reopened</th>
                            <th class="border-0 text-center">Testing Approvals</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($qaMetrics as $row)
                            <tr style="vertical-align: middle;">
                                <td class="font-weight-bold pl-4 text-dark">{{ $row['tester']->first_name }} {{ $row['tester']->last_name }}</td>
                                <td class="text-center text-danger font-weight-bold">{{ $row['bugs_found'] }}</td>
                                <td class="text-center text-warning font-weight-bold">{{ $row['bugs_reopened'] }}</td>
                                <td class="text-center text-success font-weight-bold">{{ $row['testing_approvals'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No data for selected range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection
