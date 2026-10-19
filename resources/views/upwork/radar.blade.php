@extends('layout')

@section('title', 'Upwork Radar')
@section('show_title', 'false')

@section('content')
@php
  $M = \App\Models\UpworkOpportunity::class;
  $f = $data['funnel']; $p = $data['pipeline']; $h = $data['health']; $d = $data['decisions']; $tg = $data['targets'];
  $catalog = \App\Models\UpworkClassification::catalog();
  $list = fn(array $q) => route('upwork.opportunities.index', $q);
  $fmt = fn($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
  $applyAt = optional(\App\Models\UpworkRuleset::current())->thresholds['apply'] ?? config('upwork.thresholds.apply', 80);
  $mx = $metrics; $ds = $mx['discovery']; $ai = $mx['ai']; $oc = $mx['outcomes']; $ef = $mx['efficiency'];
  $usd = fn($v, $dp = 4) => $v === null ? '—' : '$' . number_format($v, $dp);
  $num = fn($v) => $v === null ? '—' : $v;
  $healthProblem = $h['failed'] || $h['backing_off']->count() || $ai['blocked'];
@endphp
@include('upwork.partials.styles')
<div class="uw-wrap">

  <div class="uw-head">
    <div>
      <h1 class="uw-title">Upwork Radar</h1>
      <p class="uw-lede">What discovery found, how it scored and where it stands now. Decisions happen on <a href="{{ $list([]) }}">Opportunities</a>.</p>
      <div class="uw-help mt-1">Showing <strong>{{ $ranges[$data['range']] }}</strong>: {{ $data['from']->format('d M H:i') }} – {{ $data['to']->format('d M H:i') }} ({{ config('app.timezone') }})</div>
    </div>
    <div class="uw-actions">
      <div class="btn-group btn-group-sm" role="group" aria-label="Period">
        @foreach($ranges as $key => $label)
          <a href="{{ route('upwork.radar', ['range' => $key]) }}" class="btn {{ $data['range'] === $key ? 'btn-secondary' : 'btn-outline-secondary' }}" @if($data['range'] === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
      </div>
      <a href="{{ $list(['view' => 'new_matches']) }}" class="btn btn-sm btn-primary">Review new matches <span class="badge bg-light text-primary">{{ $p['new_matches'] }}</span></a>
    </div>
  </div>

  @include('upwork.partials.open-alerts', ['alerts' => $openAlerts ?? []])

  {{-- Discovery at a glance; details further down --}}
  <div class="uw-metrics">
    <span class="uw-metrics-label">Discovery</span>
    <span class="uw-nowrap"><span class="uw-status-dot" style="background:{{ $h['agent_enabled'] ? '#16a34a' : '#dc2626' }};" aria-hidden="true"></span>Agent <strong style="color:{{ $h['agent_enabled'] ? '#166534' : '#991b1b' }};">{{ $h['agent_enabled'] ? 'enabled' : 'disabled' }}</strong> · every {{ $h['interval'] }} min · read-only Upwork</span>
    @if($h['last_run'])<span class="uw-nowrap text-muted">Last run {{ $h['last_run']->status }}, {{ optional($h['last_run']->started_at)->diffForHumans() }}</span>@endif
    <span class="uw-nowrap text-muted">{{ $h['runs'] }} runs this period{{ $h['failed'] ? ', ' . $h['failed'] . ' failed' : '' }}</span>
    @if($h['backing_off']->count())<span class="uw-nowrap text-danger">{{ $h['backing_off']->count() }} profile(s) backing off</span>@endif
    <span class="ms-auto d-flex flex-wrap align-items-center gap-2">
      @can('viewConfig', $M)
        <a href="{{ route('upwork.config.profiles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i> Search profiles</a>
        <a href="{{ route('upwork.config.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i> Configuration</a>
      @endcan
      @can('runDiscovery', $M)
        <form method="POST" action="{{ route('upwork.config.discovery.run') }}" class="m-0" onsubmit="return confirm('Run one read-only Upwork job search now?')">@csrf
          <button class="btn btn-sm btn-outline-primary"><i class="bi bi-play-fill"></i> Run discovery now</button></form>
      @endcan
    </span>
  </div>

  {{-- Funnel: discovered → scored → recommended. Each number opens the matching list. --}}
  <div class="uw-panel mb-3">
    <div class="uw-funnel">
      @foreach([
        ['Jobs discovered', $f['discovered'], '#111827', $f['results'] . ' search results returned', ['show' => 'all']],
        ['Relevant', $f['relevant'], '#374151', $f['hard_rejected'] . ' hard-rejected · ' . $f['ignored'] . ' excluded by every profile', ['show' => 'all']],
        ['Viable', $f['viable'], '#0f766e', 'Relevant and scoring ≥ ' . $fmt($f['viable_min']) . ' (detail-lookup threshold)', ['min_score' => $f['viable_min']]],
        ['Review', $f['review'], '#b58105', 'Recommendation REVIEW', ['recommendation' => 'review']],
        ['Apply', $f['apply'], '#198754', 'Recommendation APPLY', ['recommendation' => 'apply']],
      ] as [$label, $value, $color, $note, $query])
        <a href="{{ $list($query) }}" class="uw-step">
          <div class="uw-step-label">{{ $label }}</div>
          <div class="uw-step-value" style="color:{{ $color }};">{{ number_format($value) }}</div>
          <div class="uw-step-note">{{ $note }}</div>
        </a>
      @endforeach
    </div>
    <div class="uw-footer">Counts cover jobs first seen in this period, from stored data. Every discovered job is scored automatically; REVIEW and APPLY jobs wait for a decision on Opportunities. The lists open with all current jobs, not only this period.</div>
  </div>

  <div class="row g-3">
    <div class="col-xl-8">
      {{-- Top opportunities of the period --}}
      <div class="uw-panel">
        <div class="uw-toolbar">
          <div>
            <div class="uw-h mb-0">Top opportunities</div>
            <div class="uw-help">Relevant jobs first seen in this period, not rejected or archived, best score first.</div>
          </div>
          <a href="{{ $list([]) }}" class="ms-auto small">Open Opportunities <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
          <table class="uw-list">
            <thead><tr><th style="width:1%;">#</th><th>Score</th><th>Job</th><th>Budget / Rate</th><th>Client</th><th>Status</th></tr></thead>
            <tbody>
              @forelse($top as $i => $o)
                <tr>
                  <td class="text-muted">{{ $top->firstItem() + $i }}</td>
                  <td class="uw-nowrap">
                    <div class="uw-score" style="color:{{ $o->score >= $applyAt ? '#198754' : '#374151' }};">{{ $fmt($o->score) }}</div>
                    <div class="mt-1">@include('upwork.opportunities.partials.recommendation-badge', ['opportunity' => $o])</div>
                  </td>
                  <td class="uw-job">
                    <a href="{{ route('upwork.opportunities.show', $o) }}" class="uw-job-title" title="{{ $o->title }}">{{ $o->title }}</a>
                    @if(!empty($o->classifications))<div class="uw-sub">@include('upwork.opportunities.partials.classification-chips', ['codes' => $o->classifications, 'catalog' => $catalog, 'kind' => 'archetype'])</div>@endif
                    @if($o->strategic_score !== null || $o->win_probability !== null)
                      <div class="uw-sub">
                        @if($o->strategic_score !== null)<span style="color:#6d28d9;">Strategic {{ round($o->strategic_score) }}</span>@endif
                        @if($o->win_probability !== null) · win ~{{ round($o->win_probability) }}% (est.)@endif
                      </div>
                    @endif
                  </td>
                  <td class="uw-nowrap">{{ $o->budget_label }}</td>
                  <td class="uw-nowrap">{{ $o->client_country ?? '—' }}</td>
                  <td>@include('upwork.opportunities.partials.status-badge', ['opportunity' => $o])</td>
                </tr>
              @empty
                <tr><td colspan="6" class="uw-empty">
                  <i class="bi bi-broadcast" aria-hidden="true"></i>
                  <div class="fw-semibold text-dark">No relevant opportunities in this period.</div>
                  <div class="mt-1">Try a longer period, or check discovery health below.</div>
                </td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @include('upwork.partials.pager', ['paginator' => $top, 'options' => $perPageOptions, 'action' => route('upwork.radar'), 'keep' => ['range' => $data['range']], 'noun' => 'opportunities'])
      </div>
    </div>

    <div class="col-xl-4">
      {{-- Pipeline now, plus the period's human decisions --}}
      <div class="uw-card uw-card-sm">
        <div class="uw-h">Pipeline (now)</div>
        @foreach([
          ['New matches', $p['new_matches'], ['view' => 'new_matches']],
          ['Approved', $p['approved'], ['view' => 'approved']],
          ['Proposal drafts', $p['proposal_drafts'], ['view' => 'proposal_drafts']],
          ['Ready to submit', $p['ready_to_submit'], ['view' => 'ready_to_submit']],
          ['Submitted', $p['submitted'], ['status' => 'submitted']],
          ['Client response', $p['client_response'], ['status' => 'client_response']],
          ['Won / lost', $p['won'] . ' / ' . $p['lost'], ['status' => 'won']],
        ] as [$label, $value, $query])
          <div class="uw-kv"><a href="{{ $list($query) }}">{{ $label }}</a><strong>{{ $value }}</strong></div>
        @endforeach
        <div class="uw-help mt-1">Win rate: {{ $p['win_rate'] !== null ? $p['win_rate'] . '% of decided proposals' : 'no won/lost outcomes yet' }}</div>
        <div class="uw-kv mt-2"><span>Expected value of open REVIEW/APPLY</span><strong>~{{ $M::money($data['expected_value']['total']) }}</strong></div>
        <div class="uw-help">ESTIMATE: sum of contract value × heuristic win probability over {{ $data['expected_value']['jobs'] }} jobs in this period.</div>

        <div class="uw-h mt-3 mb-1">Human decisions (this period)</div>
        <div style="font-size:0.84rem;">{{ $d['approved'] }} approved · {{ $d['rejected'] }} rejected</div>
        <div class="uw-help">Agreement with the agent's recommendation: {{ $d['agreement'] !== null ? $d['agreement'] . '%' : 'no decisions in this period' }} (approved REVIEW/APPLY, or rejected SKIP).</div>
      </div>

      {{-- Dual-track targeting --}}
      <div class="uw-card uw-card-sm">
        <div class="uw-h">Target profiles</div>
        <table class="uw-table">
          <thead><tr><th>Best fit</th><th class="text-end">Relevant</th><th class="text-end">Review / Apply</th></tr></thead>
          <tbody>
            @foreach(\App\Services\Upwork\Targeting\TargetProfiles::TRACKS as $track => $label)
              <tr>
                <td><a href="{{ $list(['target' => $track, 'show' => 'all']) }}" class="text-decoration-none">@include('upwork.opportunities.partials.target-chip', ['target' => $track])</a></td>
                <td class="text-end">{{ $tg['fit'][$track]['relevant'] }}</td><td class="text-end">{{ $tg['fit'][$track]['review_plus'] }}</td>
              </tr>
            @endforeach
            @if($tg['fit']['unclassified']['relevant'])
              <tr class="uw-muted-row"><td>Not classified</td><td class="text-end">{{ $tg['fit']['unclassified']['relevant'] }}</td><td></td></tr>
            @endif
          </tbody>
        </table>
        <div class="uw-help mt-1 mb-2">Relevant jobs first seen in this period, by best-fit profile (each job counted once).</div>
        <table class="uw-table">
          <thead><tr><th>Bid as</th><th class="text-end">Chosen</th><th class="text-end">Submitted</th><th class="text-end">Won / lost</th></tr></thead>
          <tbody>
            @foreach($tg['bid_as'] as $track => $row)
              <tr>
                <td><a href="{{ $list(['bid_as' => $track, 'show' => 'all']) }}">{{ ucfirst($track) }}</a></td>
                <td class="text-end">{{ $row['total'] }}</td><td class="text-end">{{ $row['submitted'] }}</td>
                <td class="text-end">{{ $row['won'] }} / {{ $row['lost'] }}@if($row['win_rate'] !== null) <span class="text-muted">({{ $row['win_rate'] }}%)</span>@endif</td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="uw-help mt-1">Profile chosen at approval, all time. Submission is recorded manually; the PMS never submits.</div>
      </div>

      {{-- Categories --}}
      <div class="uw-card uw-card-sm">
        <div class="uw-h">Categories (relevant jobs)</div>
        @forelse($data['categories'] as $row)
          <div class="uw-kv align-items-center">
            <a href="{{ $list(['class' => $row['classification']->code, 'show' => 'all']) }}" class="text-decoration-none">@include('upwork.opportunities.partials.classification-chips', ['codes' => [$row['classification']->code], 'catalog' => $catalog])</a>
            <strong>{{ $row['count'] }}</strong>
          </div>
        @empty
          <div class="uw-help">Nothing classified in this period.</div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- Per-profile yield --}}
  <div class="uw-card uw-card-sm mt-3">
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <div class="uw-h">Search profile yield</div>
      @can('viewConfig', $M)<a href="{{ route('upwork.config.profiles.index') }}" class="small">Manage search profiles</a>@endcan
    </div>
    <div class="table-responsive">
      <table class="uw-table">
        <thead><tr><th>Profile</th><th class="text-end">New jobs found</th><th class="text-end">Excluded</th><th class="text-end">Viable</th><th class="text-end">Review / Apply</th></tr></thead>
        <tbody>
          @forelse($data['profiles'] as $row)
            <tr>
              <td>{{ $row['profile']->name }} @unless($row['profile']->enabled)<span class="badge bg-secondary">disabled</span>@endunless</td>
              <td class="text-end">{{ $row['found'] }}</td><td class="text-end">{{ $row['ignored'] }}</td><td class="text-end">{{ $row['viable'] }}</td><td class="text-end">{{ $row['review_plus'] }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted">No search profiles.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="uw-help mt-1">A job found by several profiles counts once per profile here, but only once in the funnel above.</div>
  </div>

  {{-- Health, AI usage and cost: detail most visits do not need (opens itself when something is wrong) --}}
  <details class="uw-card uw-card-sm uw-details" {{ $healthProblem ? 'open' : '' }}>
    <summary><i class="bi bi-chevron-right" aria-hidden="true"></i> Discovery health, AI &amp; efficiency
      @if($healthProblem)<span class="badge" style="background:#fee2e2; color:#991b1b; font-weight:600;">needs attention</span>@endif
      <span class="uw-help ms-auto" style="font-weight:400;">costs are estimates from configured prices</span>
    </summary>
    <div class="row g-3 mt-1" style="font-size:0.84rem;">
      <div class="col-md-6 col-xl-4">
        <div class="uw-h mb-1">Discovery health</div>
        Runs: {{ $h['runs'] }} ({{ $h['completed'] }} completed, {{ $h['failed'] }} failed, {{ $h['abandoned'] }} abandoned{{ $h['running'] ? ', ' . $h['running'] . ' running' : '' }})<br>
        Searches: {{ $h['queries'] }} ({{ $h['failed_queries'] }} failed, {{ $h['rate_limited'] }} rate-limited)
        @if($h['avg_duration_s'] !== null)<br>Average run: {{ $h['avg_duration_s'] }}s @endif
        @foreach($h['backing_off'] as $profile)
          <div class="text-danger" style="font-size:0.78rem;">{{ $profile->name }} backing off until {{ $profile->backoff_until->format('H:i') }}{{ $profile->last_error ? ': ' . \Illuminate\Support\Str::limit($profile->last_error, 80) : '' }}</div>
        @endforeach
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="uw-h mb-1">Upwork (read-only)</div>
        Searches: <strong>{{ $ds['searches'] }}</strong> · detail lookups: <strong>{{ $ds['detail_lookups'] }}</strong><br>
        New jobs: <strong>{{ $ds['new_jobs'] }}</strong> · duplicates {{ $ds['duplicates'] }}<br>
        Filtered locally: <strong>{{ $ds['filtered'] }}</strong>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="d-flex justify-content-between gap-2"><div class="uw-h mb-1">AI &amp; efficiency</div>@can('viewConfig', $M)<a href="{{ route('upwork.config.ai.edit') }}" class="small">AI usage &amp; budget</a>@endcan</div>
        @if($ai['blocked'])<div class="alert alert-warning border-0 small py-1 px-2 mb-2">{{ $ai['blocked'] }}</div>@endif
        Analysed: <strong>{{ $ai['analyzed_jobs'] }}</strong> · skipped {{ $ai['skipped_jobs'] }} · blocked {{ $ai['blocked_jobs'] }}<br>
        Requests: <strong>{{ $ai['requests'] }}</strong> ({{ $ai['failed_requests'] }} failed, {{ $ai['deep_requests'] }} deep) · tokens {{ number_format($ai['tokens']) }}<br>
        Cost: <strong>{{ $usd($ai['cost']) }}</strong>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="uw-h mb-1">Results</div>
        Score 70+: <strong>{{ $oc['score_70'] }}</strong> · 80+: <strong>{{ $oc['score_80'] }}</strong><br>
        Approved: <strong>{{ $oc['approved'] }}</strong> · rejected {{ $oc['rejected'] }}<br>
        AI agreed with people: <strong>{{ $ef['ai_human_agreement_pct'] === null ? '—' : $ef['ai_human_agreement_pct'] . '%' }}</strong> <span class="text-muted">({{ $ef['ai_human_compared'] }} decisions)</span>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="uw-h mb-1">Efficiency</div>
        Cost / analysed job: {{ $usd($ef['cost_per_analyzed_job']) }}<br>
        Cost / 70+ job: {{ $usd($ef['cost_per_70_job']) }}<br>
        Cost / approved job: {{ $usd($ef['cost_per_approved_job']) }}<br>
        Searches / new job: {{ $num($ef['searches_per_new_job']) }} · AI share {{ $ef['ai_share_pct'] === null ? '—' : $ef['ai_share_pct'] . '%' }}
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="uw-h mb-1">AI budget</div>
        Month to date (UTC): <strong>{{ $usd($ai['month_cost']) }}</strong> of {{ $usd($ai['monthly_budget'], 2) }} · remaining <strong>{{ $usd($ai['monthly_remaining']) }}</strong>
        <div class="progress mt-1" style="height:6px;" role="progressbar" aria-label="AI budget used"><div class="progress-bar {{ $ai['monthly_budget'] > 0 && $ai['month_cost'] / $ai['monthly_budget'] > 0.8 ? 'bg-danger' : 'bg-success' }}" style="width:{{ $ai['monthly_budget'] > 0 ? min(100, round($ai['month_cost'] / $ai['monthly_budget'] * 100)) : 0 }}%;"></div></div>
      </div>
    </div>
  </details>
</div>
@endsection
