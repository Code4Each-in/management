@extends('layout')

@section('title', 'Upwork Search Profiles')

@section('content')
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="uw-help">Each enabled profile becomes read-only Upwork searches. Hot profiles run first, then Normal, Deep and Exploratory; within a tier, the longest-waiting profile runs first. Each tier has a per-run search allowance (Agent & discovery). Profiles are disabled, never deleted.</div>
    @if($canManage)<a href="{{ route('upwork.config.profiles.create') }}" class="btn btn-primary btn-sm">New search profile</a>@endif
  </div>

  <div class="uw-card p-0" style="overflow-x:auto;">
    <table class="uw-table">
      <thead><tr><th>Profile</th><th>Tier</th><th>Priority</th><th>Searches</th><th>Excludes</th><th>Every</th><th>Version</th><th>Jobs found</th><th>Last run</th><th></th></tr></thead>
      <tbody>
        @foreach($profiles as $p)
          <tr class="{{ $p->enabled ? '' : 'uw-muted-row' }}">
            <td><a href="{{ route('upwork.config.profiles.edit', $p) }}" style="font-weight:600;">{{ $p->name }}</a>
              @unless($p->enabled)<span class="badge bg-secondary ms-1">disabled</span>@endunless
              <div class="uw-help">{{ \Illuminate\Support\Str::limit(implode(', ', $p->keywords ?? []), 80) }}</div></td>
            <td><span class="badge bg-{{ ['hot' => 'danger', 'normal' => 'primary', 'deep' => 'info', 'exploratory' => 'secondary'][$p->tier] ?? 'secondary' }}">{{ \App\Models\UpworkSearchProfile::TIERS[$p->tier] ?? $p->tier }}</span></td>
            <td>{{ $p->priority }}</td>
            <td>{{ count($p->keywords ?? []) }} kw · {{ count($p->exact_phrases ?? []) }} title · {{ $p->technologies_count }} tech</td>
            <td>{{ count($p->excluded_keywords ?? []) + count($p->excluded_phrases ?? []) }}</td>
            <td>{{ $p->frequency_minutes }} min<div class="uw-help">≤ {{ $p->daily_call_budget }}/day</div></td>
            <td>v{{ $p->version }}</td>
            <td>{{ $sightings[$p->id] ?? 0 }}</td>
            <td>{{ optional($p->last_run_at)->diffForHumans() ?? '—' }}
              @if($p->backoff_until && $p->backoff_until->isFuture())<div class="text-danger uw-help">backing off until {{ $p->backoff_until->format('H:i') }}</div>@endif</td>
            <td class="text-end">
              @if($canManage)
                <form method="POST" action="{{ route('upwork.config.profiles.toggle', $p) }}" class="d-inline">@csrf
                  <button class="btn btn-sm btn-outline-secondary">{{ $p->enabled ? 'Disable' : 'Enable' }}</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
