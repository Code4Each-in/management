@extends('layout')

@section('title', 'Reports')

@section('content')
<div style="margin: 2rem auto;">

  <div class="mb-4">
    <p class="text-muted small mb-0">Developer and QA performance metrics</p>
  </div>

  {{-- Filter bar --}}
    <form method="GET" class="card border rounded-3 shadow-sm p-3 mb-4">
        <div class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="form-label text-uppercase text-muted fw-medium"
                    style="font-size:10px;letter-spacing:.05em">
                    From
                </label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
            </div>

            <div class="col-md-3">
                <label class="form-label text-uppercase text-muted fw-medium"
                    style="font-size:10px;letter-spacing:.05em">
                    To
                </label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
            </div>

            <div class="col-md-4">
                <label class="form-label text-uppercase text-muted fw-medium"
                    style="font-size:10px;letter-spacing:.05em">
                    Deployment
                </label>
                <select name="deployment_id" class="form-select form-select-sm">
                    <option value="">All deployments</option>
                    @foreach($allTicketsForDropdown as $t)
                        <option value="{{ $t->id }}" @selected($selectedDeploymentId == $t->
                            {{ $t->deployment_code }} — {{ $t->deployment_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 py-1">
                    <i class="bi bi-funnel"></i> Apply
                </button>

                <a href="{{ route('deployment.reports') }}" class="btn btn-outline-secondary btn-sm px-3 py-1">
                    <i class="bi bi-arrow-counterclockwise"></i> Clear
                </a>
            </div>

        </div>
    </form>

  {{-- Developer Metrics --}}
  @php $devCount = count($developerMetrics); @endphp
  <div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
      <div class="d-flex align-items-center justify-content-center rounded-2 border bg-light" style="width:28px;height:28px;color:#6b7280;">
        <i class="bi bi-code-slash" style="font-size:13px;"></i>
      </div>
      <span class="fw-medium small">Developer metrics</span>
      <span class="text-muted ms-auto" style="font-size:11px;">{{ $devCount }} {{ Str::plural('developer', $devCount) }}</span>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm">
      <table class="table mb-0 align-middle" style="font-size:13px;">
        <thead style="background:var(--bs-light)">
          <tr>
            @foreach(['Developer','Deployments','Pass rate','Fixes requested','Bugs raised','Bugs fixed'] as $col)
              <th class="text-uppercase text-muted fw-medium border-bottom px-3 py-2" style="font-size:11px;letter-spacing:.04em;">{{ $col }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @forelse($developerMetrics as $d)
          @php
            $pct = (int) $d['pass_rate'];
            $barColor = $pct >= 80 ? 'var(--bs-success)' : ($pct >= 60 ? 'var(--bs-warning)' : 'var(--bs-danger)');
            $initials = collect(explode(' ', trim($d['name'])))->filter()->map(fn($w) => strtoupper($w[0]))->take(2)->implode('');
          @endphp
          <tr>
            <td class="px-3 py-2">
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-medium"
                  style="width:26px;height:26px;font-size:10px;background:rgba(var(--bs-primary-rgb),.1);color:var(--bs-primary);">
                  {{ $initials }}
                </div>
                <span class="fw-medium">{{ $d['name'] }}</span>
              </div>
            </td>
            <td class="px-3 py-2">{{ $d['deployments'] }}</td>
            <td class="px-3 py-2">
              <div class="d-flex align-items-center gap-2">
                <div style="width:60px;height:5px;border-radius:3px;background:#e9ecef;overflow:hidden;">
                  <div style="width:{{ $pct }}%;height:100%;border-radius:3px;background:{{ $barColor }};"></div>
                </div>
                <span class="fw-medium" style="font-size:12px;">{{ $pct }}%</span>
              </div>
            </td>
            <td class="px-3 py-2">{{ $d['fixes_requested'] }}</td>
            <td class="px-3 py-2">{{ $d['bugs_raised'] }}</td>
            <td class="px-3 py-2">{{ $d['bugs_fixed'] }}</td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted py-4" style="font-size:13px;">No data for this range.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- QA Metrics --}}
  @php $qaCount = count($qaMetrics); @endphp
  <div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
      <div class="d-flex align-items-center justify-content-center rounded-2 border bg-light" style="width:28px;height:28px;color:#6b7280;">
        <i class="bi bi-shield-check" style="font-size:13px;"></i>
      </div>
      <span class="fw-medium small">QA metrics</span>
      <span class="text-muted ms-auto" style="font-size:11px;">{{ $qaCount }} {{ Str::plural('tester', $qaCount) }}</span>
    </div>

    <div class="card border rounded-3 overflow-hidden shadow-sm">
      <table class="table mb-0 align-middle" style="font-size:13px;">
        <thead style="background:var(--bs-light)">
          <tr>
            @foreach(['QA tester','Bugs found','Testing approvals'] as $col)
              <th class="text-uppercase text-muted fw-medium border-bottom px-3 py-2" style="font-size:11px;letter-spacing:.04em;">{{ $col }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @forelse($qaMetrics as $q)
          @php
            $initials = collect(explode(' ', trim($q['name'])))->filter()->map(fn($w) => strtoupper($w[0]))->take(2)->implode('');
          @endphp
          <tr>
            <td class="px-3 py-2">
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-medium"
                  style="width:26px;height:26px;font-size:10px;background:rgba(var(--bs-success-rgb),.1);color:var(--bs-success);">
                  {{ $initials }}
                </div>
                <span class="fw-medium">{{ $q['name'] }}</span>
              </div>
            </td>
            <td class="px-3 py-2">{{ $q['bugs_found'] }}</td>
            <td class="px-3 py-2">{{ $q['testing_approvals'] }}</td>
          </tr>
          @empty
          <tr><td colspan="3" class="text-center text-muted py-4" style="font-size:13px;">No data for this range.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
