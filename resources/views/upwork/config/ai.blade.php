@extends('layout')

@section('title', 'Upwork AI Settings')

@section('content')
@php
  $ro = !$canManage;
  $usd = fn($v) => '$' . number_format((float) $v, 4);
@endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="uw-card">
        <div class="uw-h">AI usage (estimated, UTC calendar)</div>
        @if($status['blocked'])
          <div class="alert alert-warning border-0 small py-2 px-3 mb-2" style="font-weight:600;">{{ $status['blocked'] }}</div>
        @endif
        <table class="uw-table">
          <thead><tr><th></th><th>Today</th><th>This month</th></tr></thead>
          <tbody>
            <tr><td>Requests</td><td>{{ $status['today']['requests'] }} / {{ $settings['ai.daily_request_limit'] }}</td><td>{{ $status['month']['requests'] }} / {{ $settings['ai.monthly_request_limit'] }}</td></tr>
            <tr><td>Tokens</td><td>{{ number_format($status['today']['tokens']) }}</td><td>{{ number_format($status['month']['tokens']) }}</td></tr>
            <tr><td>Estimated cost</td><td>{{ $usd($status['today']['cost']) }} / {{ $usd($status['daily_budget_usd']) }}</td><td>{{ $usd($status['month']['cost']) }} / {{ $usd($status['monthly_budget_usd']) }}</td></tr>
            <tr><td>Deep analyses</td><td>{{ $status['deep']['today']['requests'] }} / {{ $status['deep']['daily_request_limit'] }}</td><td>{{ $usd($status['deep']['month']['cost']) }} / {{ $usd($status['deep']['monthly_budget_usd']) }}</td></tr>
          </tbody>
        </table>
        <div class="uw-help mt-1">Budget remaining this month: <strong>{{ $usd($status['monthly_remaining_usd']) }}</strong>. In-flight calls count at their worst case until they finish.</div>
        <div class="uw-help">Provider credentials: <strong>{{ $status['configured'] ? 'configured' : 'not configured' }}</strong> (set <code>OPENAI_API_KEY</code> in the server's .env; it is never shown here).</div>
      </div>

      <div class="uw-card">
        <div class="uw-h">Configured prices (USD per 1M tokens)</div>
        <table class="uw-table">
          <thead><tr><th>Model</th><th>Input</th><th>Cached</th><th>Output</th></tr></thead>
          <tbody>@foreach($pricing as $model => $r)<tr><td><code>{{ $model }}</code></td><td>{{ $r['input'] }}</td><td>{{ $r['cached_input'] ?? '—' }}</td><td>{{ $r['output'] }}</td></tr>@endforeach</tbody>
        </table>
        <div class="uw-help mt-1">From <code>config/upwork.php</code>, checked {{ $pricingCheckedAt }}. Only priced models can be selected; prices cannot be changed here.</div>
      </div>
    </div>

    <div class="col-lg-7">
      <form method="POST" action="{{ route('upwork.config.ai.update') }}" class="uw-card">
        @csrf @method('PUT')
        <div class="uw-h">AI qualification</div>
        <div class="alert alert-light border small py-2">AI only analyses jobs that pass local filters. When a limit is reached, AI stops and local scoring continues. Nothing is raised, bought or switched automatically.</div>
        <div class="row g-2">
          <div class="col-12"><div class="form-check form-switch">
            <input type="hidden" name="enabled" value="0">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="ai_enabled" {{ old('enabled', $settings['ai.enabled']) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}>
            <label class="form-check-label fw-semibold small" for="ai_enabled">AI enabled</label></div></div>
          <div class="col-12"><div class="form-check form-switch">
            <input type="hidden" name="auto_analyze" value="0">
            <input class="form-check-input" type="checkbox" name="auto_analyze" value="1" id="ai_auto" {{ old('auto_analyze', $settings['ai.auto_analyze']) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}>
            <label class="form-check-label small" for="ai_auto">Analyse eligible jobs automatically (otherwise only on request)</label></div></div>
          <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Provider</label>
            <select name="provider" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
              @foreach($providers as $p)<option value="{{ $p }}" {{ old('provider', $settings['ai.provider']) === $p ? 'selected' : '' }}>{{ $p }}</option>@endforeach
            </select></div>
          <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Model</label>
            <select name="model" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
              @foreach(array_keys($pricing) as $m)<option value="{{ $m }}" {{ old('model', $settings['ai.model']) === $m ? 'selected' : '' }}>{{ $m }}</option>@endforeach
            </select></div>
          <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Reasoning</label>
            <select name="reasoning_effort" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
              @foreach($reasoning as $v => $l)<option value="{{ $v }}" {{ (string) old('reasoning_effort', $settings['ai.reasoning_effort']) === (string) $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
            </select></div>
          <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Temperature</label>
            <input type="number" step="0.1" min="0" max="2" name="temperature" value="{{ old('temperature', $settings['ai.temperature']) }}" placeholder="not sent" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}></div>
          @foreach($fields as $field => [$key, $rules, $label, $help])
            <div class="col-md-6"><label class="form-label small fw-semibold mb-1">{{ $label }}</label>
              <input type="number" step="any" name="{{ $field }}" value="{{ old($field, $settings[$key]) }}" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}>
              @if($help)<div class="uw-help">{{ $help }}</div>@endif</div>
          @endforeach
        </div>
        @if($canManage)<button class="btn btn-primary btn-sm mt-3">Save AI settings</button>@endif
      </form>
    </div>
  </div>

  <div class="uw-card p-0">
    <div class="uw-h px-3 pt-3">Recent AI requests</div>
    <table class="uw-table">
      <thead><tr><th>When</th><th>Type</th><th>Model</th><th>Job</th><th>Status</th><th>Tokens in / cached / out</th><th>Est. cost</th><th>Time</th><th>Error</th></tr></thead>
      <tbody>
        @forelse($recent as $u)
          <tr>
            <td>{{ optional($u->created_at)->format('d M H:i') }}</td><td>{{ $u->request_type }} <span class="uw-help">{{ $u->prompt_version }}</span></td>
            <td><code>{{ $u->model }}</code></td>
            <td>@if($u->opportunity)<a href="{{ route('upwork.opportunities.show', $u->opportunity) }}">{{ \Illuminate\Support\Str::limit($u->opportunity->title, 40) }}</a>@else — @endif</td>
            <td>{{ $u->status }}@if($u->attempt > 1) (try {{ $u->attempt }})@endif</td>
            <td>{{ $u->input_tokens ?? '—' }} / {{ $u->cached_input_tokens ?? '—' }} / {{ $u->output_tokens ?? '—' }}</td>
            <td>{{ $usd($u->budget_cost) }}</td><td>{{ $u->duration_ms !== null ? round($u->duration_ms / 1000, 1) . 's' : '—' }}</td>
            <td class="uw-help">{{ $u->error_code }} {{ \Illuminate\Support\Str::limit($u->error, 80) }}</td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-muted">No AI requests yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
