@extends('layout')

@section('title', 'Upwork Configuration')

@section('content')
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="row g-3">
    <div class="col-md-6 col-xl-3"><div class="uw-card h-100">
      <div class="uw-h">Search profiles</div>
      <div style="font-size:1.5rem; font-weight:600;">{{ $summary['profiles_enabled'] }} <span class="text-muted" style="font-size:0.9rem;">of {{ $summary['profiles'] }} enabled</span></div>
      <a href="{{ route('upwork.config.profiles.index') }}" class="small">Manage profiles</a>
    </div></div>
    <div class="col-md-6 col-xl-3"><div class="uw-card h-100">
      <div class="uw-h">Technologies</div>
      @foreach(\App\Models\UpworkTechnology::TIERS as $tier => $label)
        <span class="uw-tier uw-tier-{{ $tier }} me-1">{{ $summary['technologies'][$tier] ?? 0 }} {{ $label }}</span>
      @endforeach
      <div class="mt-2"><a href="{{ route('upwork.config.technologies.index') }}" class="small">Manage catalog</a></div>
    </div></div>
    <div class="col-md-6 col-xl-3"><div class="uw-card h-100">
      <div class="uw-h">Scoring</div>
      <div style="font-size:1.5rem; font-weight:600;">{{ $summary['ruleset'] ? 'v' . $summary['ruleset']->version : '—' }}</div>
      <div class="uw-help">{{ $summary['draft'] ? 'A draft is in progress.' : 'No draft in progress.' }}</div>
      <a href="{{ route('upwork.config.rulesets.index') }}" class="small">Scoring rules</a>
    </div></div>
    <div class="col-md-6 col-xl-3"><div class="uw-card h-100">
      <div class="uw-h">Agent</div>
      <div style="font-size:1.1rem; font-weight:600; color:{{ $summary['agent_enabled'] ? '#166534' : '#991b1b' }};">{{ $summary['agent_enabled'] ? 'Enabled' : 'Disabled' }}</div>
      <div class="uw-help">Every {{ $summary['interval'] }} min · Upwork access is read-only</div>
      @php $d = $summary['discovery']; @endphp
      <div class="uw-help">Server scheduler: {{ $d['scheduler_enabled'] ? 'on' : 'off (UPWORK_SCHEDULER_ENABLED)' }}@if($d['next_due_at']) · next run {{ $d['next_due_at']->isPast() ? 'due now' : $d['next_due_at']->diffForHumans() }}@endif</div>
      @if($summary['last_run'])
        <div class="uw-help">Last run: {{ $summary['last_run']->status }} · {{ optional($summary['last_run']->started_at)->diffForHumans() }}</div>
      @endif
      <div class="uw-help">Last successful run: {{ $d['last_success'] ? optional($d['last_success']->finished_at ?? $d['last_success']->started_at)->diffForHumans() . ' (' . $d['last_success']->trigger . ')' : 'none yet' }}</div>
      @if($d['last_failure'])
        <div class="uw-help" style="color:{{ $d['consecutive_failures'] ? '#991b1b' : 'inherit' }};">Last failure: {{ $d['last_failure']->status }} · {{ optional($d['last_failure']->started_at)->diffForHumans() }}{{ $d['last_failure_error'] ? ' · ' . \Illuminate\Support\Str::limit($d['last_failure_error'], 140) : '' }}@if($d['consecutive_failures']) · {{ $d['consecutive_failures'] }} in a row, retrying sooner @endif</div>
      @endif
      <a href="{{ route('upwork.config.settings.edit') }}" class="small">Agent & discovery settings</a>
      @can('runDiscovery', \App\Models\UpworkOpportunity::class)
        <form method="POST" action="{{ route('upwork.config.discovery.run') }}" class="mt-2" onsubmit="return confirm('Run one read-only Upwork job search now?')">@csrf
          <button class="btn btn-sm btn-outline-primary">Run discovery now</button></form>
        @if($m = $summary['manual_run'])
          <div class="uw-help mt-1">Manual run {{ \Illuminate\Support\Carbon::parse($m['at'])->diffForHumans() }}: {{ $m['status'] }}{{ $m['message'] && $m['message'] !== 'OK' ? ' · ' . $m['message'] : '' }}
            @if(!empty($m['stats'])) · {{ $m['stats']['searches'] ?? 0 }} searches, {{ $m['stats']['rows'] ?? 0 }} jobs, {{ $m['stats']['enriched'] ?? 0 }} enriched @endif</div>
        @endif
      @endcan
    </div></div>
  </div>

  <div class="uw-card mt-3">
    <div class="uw-h">AI qualification</div>
    <div style="font-size:0.9rem;">
      <strong style="color:{{ $summary['ai']['enabled'] ? '#166534' : '#991b1b' }};">{{ $summary['ai']['enabled'] ? 'Enabled' : 'Disabled' }}</strong>
      · {{ $summary['ai']['provider'] }} / <code>{{ $summary['ai']['model'] }}</code> · credentials {{ $summary['ai']['configured'] ? 'configured' : 'not configured' }}
      · this month ~${{ number_format($summary['ai']['month']['cost'], 4) }} of ${{ number_format($summary['ai']['monthly_budget_usd'], 2) }}
    </div>
    @if($summary['ai']['blocked'])<div class="text-warning small fw-semibold mt-1">{{ $summary['ai']['blocked'] }}</div>@endif
    <a href="{{ route('upwork.config.ai.edit') }}" class="small">AI settings & usage</a>
  </div>

  <div class="uw-card mt-3">
    <div class="uw-h">Recent configuration changes</div>
    @forelse($logs as $log)
      <div style="font-size:0.8rem; border-left:2px solid #e5e7eb; padding:0 0 0.5rem 0.625rem;">
        <strong>{{ ucfirst($log->action) }}</strong> {{ str_replace('_', ' ', $log->entity) }} <em>{{ $log->label }}</em>
        <span class="text-muted">— {{ $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'System' }}, {{ optional($log->created_at)->format('d M Y H:i') }}</span>
        @if($log->changes)
          <div class="text-muted" style="font-size:0.72rem;">{{ \Illuminate\Support\Str::limit(implode('; ', array_map(fn($f, $c) => $f . ': ' . json_encode($c[0] ?? null) . ' → ' . json_encode($c[1] ?? null), array_keys($log->changes), $log->changes)), 300) }}</div>
        @endif
      </div>
    @empty
      <div class="text-muted small">No changes recorded yet.</div>
    @endforelse
  </div>
</div>
@endsection
