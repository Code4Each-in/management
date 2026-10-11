@extends('layout')

@section('title', 'Upwork Radar')

@section('content')
@php
  $M = \App\Models\UpworkOpportunity::class;
  $f = $data['funnel']; $p = $data['pipeline']; $h = $data['health']; $d = $data['decisions'];
  $card = 'background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04); padding:1.1rem 1.25rem; margin-bottom:1rem;';
  $hd = 'font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280; margin-bottom:0.75rem;';
  $catalog = \App\Models\UpworkClassification::catalog();
  $list = fn(array $q) => route('upwork.opportunities.index', $q);
  $applyAt = optional(\App\Models\UpworkRuleset::current())->thresholds['apply'] ?? config('upwork.thresholds.apply', 80);
@endphp
<div style="margin: 1rem auto 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div style="font-size:1.1rem; font-weight:700; letter-spacing:0.02em;">UPWORK RADAR — {{ strtoupper($ranges[$data['range']]) }}</div>
    <span class="text-muted small">{{ $data['from']->format('d M H:i') }} – {{ $data['to']->format('d M H:i') }} ({{ config('app.timezone') }})</span>
    <div class="btn-group btn-group-sm ms-auto">
      @foreach($ranges as $key => $label)<a href="{{ route('upwork.radar', ['range' => $key]) }}" class="btn {{ $data['range'] === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>@endforeach
    </div>
  </div>

  {{-- Funnel --}}
  <div class="row g-3 mb-1">
    @foreach([
      ['Jobs discovered', $f['discovered'], '#111827', 'Distinct jobs first seen in this period (' . $f['results'] . ' search results returned).', ['show' => 'all']],
      ['Relevant', $f['relevant'], '#374151', 'Not hard-rejected (' . $f['hard_rejected'] . ') and not excluded by every profile (' . $f['ignored'] . ').', ['show' => 'all']],
      ['Viable', $f['viable'], '#0f766e', 'Relevant and scoring ≥ ' . rtrim(rtrim(number_format($f['viable_min'], 1), '0'), '.') . ' (the detail-lookup threshold).', ['min_score' => $f['viable_min']]],
      ['Review', $f['review'], '#b58105', 'Recommendation REVIEW.', ['recommendation' => 'review']],
      ['Apply', $f['apply'], '#198754', 'Recommendation APPLY.', ['recommendation' => 'apply']],
    ] as [$label, $value, $color, $title, $query])
      <div class="col">
        <a href="{{ $list($query) }}" style="text-decoration:none;" title="{{ $title }}">
          <div style="{{ $card }} margin-bottom:0;">
            <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280;">{{ $label }}</div>
            <div style="font-size:1.75rem; font-weight:700; color:{{ $color }}; line-height:1.2;">{{ number_format($value) }}</div>
          </div>
        </a>
      </div>
    @endforeach
  </div>
  <div class="text-muted mb-3" style="font-size:0.72rem;">Hover a number for its definition. All figures are counted from stored data.</div>

  <div class="row g-3">
    <div class="col-lg-8">
      {{-- Top opportunities --}}
      <div style="{{ $card }}">
        <div style="{{ $hd }}">Top opportunities</div>
        @forelse($data['top'] as $i => $o)
          <div class="d-flex align-items-start gap-2 py-1" style="border-bottom:1px solid #f3f4f6;">
            <div style="width:1.5rem; color:#9ca3af; font-weight:600;">{{ $i + 1 }}.</div>
            <div style="width:3.5rem; font-weight:700; color:{{ $o->score >= $applyAt ? '#198754' : '#374151' }};">{{ rtrim(rtrim(number_format($o->score, 1), '0'), '.') }}</div>
            <div class="flex-grow-1">
              <a href="{{ route('upwork.opportunities.show', $o) }}" style="font-weight:600;">{{ $o->title }}</a>
              @include('upwork.opportunities.partials.recommendation-badge', ['opportunity' => $o])
              <div style="font-size:0.75rem;" class="text-muted">
                {{ $o->budget_label }} · {{ $o->client_country ?? '—' }}
                @if($o->strategic_score !== null) · strategic {{ round($o->strategic_score) }}@endif
                @if($o->win_probability !== null) · win ~{{ round($o->win_probability) }}% (est.)@endif
                · {{ str_replace('_', ' ', $o->status) }}
              </div>
              <div style="font-size:0.8em;">@include('upwork.opportunities.partials.classification-chips', ['codes' => $o->classifications, 'catalog' => $catalog, 'kind' => 'archetype'])</div>
            </div>
          </div>
        @empty
          <div class="text-muted small">No relevant opportunities in this period.</div>
        @endforelse
      </div>

      {{-- Per-profile yield --}}
      <div style="{{ $card }}">
        <div style="{{ $hd }}">Search profile yield</div>
        <table class="w-100" style="font-size:0.85rem;">
          <thead><tr class="text-muted" style="font-size:0.72rem; text-transform:uppercase;"><th>Profile</th><th>New jobs found</th><th>Excluded</th><th>Viable</th><th>Review / Apply</th></tr></thead>
          <tbody>
            @forelse($data['profiles'] as $row)
              <tr style="border-top:1px solid #f3f4f6;">
                <td>{{ $row['profile']->name }} @unless($row['profile']->enabled)<span class="badge bg-secondary">disabled</span>@endunless</td>
                <td>{{ $row['found'] }}</td><td>{{ $row['ignored'] }}</td><td>{{ $row['viable'] }}</td><td>{{ $row['review_plus'] }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-muted">No search profiles.</td></tr>
            @endforelse
          </tbody>
        </table>
        <div class="text-muted mt-1" style="font-size:0.72rem;">A job found by several profiles counts once per profile here, but only once in the funnel above.</div>
      </div>
    </div>

    <div class="col-lg-4">
      {{-- Categories --}}
      <div style="{{ $card }}">
        <div style="{{ $hd }}">Categories (relevant jobs)</div>
        @forelse($data['categories'] as $row)
          <div class="d-flex justify-content-between align-items-center py-1">
            <a href="{{ $list(['class' => $row['classification']->code, 'show' => 'all']) }}" style="text-decoration:none;">@include('upwork.opportunities.partials.classification-chips', ['codes' => [$row['classification']->code], 'catalog' => $catalog])</a>
            <strong>{{ $row['count'] }}</strong>
          </div>
        @empty
          <div class="text-muted small">Nothing classified in this period.</div>
        @endforelse
      </div>

      {{-- Pipeline --}}
      <div style="{{ $card }}">
        <div style="{{ $hd }}">Pipeline (now)</div>
        @foreach([
          ['New matches', $p['new_matches'], ['view' => 'new_matches']],
          ['Approved', $p['approved'], ['view' => 'approved']],
          ['Proposal drafts', $p['proposal_drafts'], ['view' => 'proposal_drafts']],
          ['Ready to submit', $p['ready_to_submit'], ['view' => 'ready_to_submit']],
          ['Submitted', $p['submitted'], ['status' => 'submitted']],
          ['Client response', $p['client_response'], ['status' => 'client_response']],
          ['Won / lost', $p['won'] . ' / ' . $p['lost'], ['status' => 'won']],
        ] as [$label, $value, $query])
          <div class="d-flex justify-content-between" style="font-size:0.85rem;"><a href="{{ $list($query) }}">{{ $label }}</a><strong>{{ $value }}</strong></div>
        @endforeach
        <div class="text-muted mt-1" style="font-size:0.72rem;">Win rate: {{ $p['win_rate'] !== null ? $p['win_rate'] . '% of decided proposals' : 'no won/lost outcomes yet' }}</div>
        <div class="d-flex justify-content-between mt-2" style="font-size:0.85rem;"><span>Expected value of open REVIEW/APPLY</span><strong>~{{ $M::money($data['expected_value']['total']) }}</strong></div>
        <div class="text-muted" style="font-size:0.72rem;">ESTIMATE: sum of contract value × heuristic win probability over {{ $data['expected_value']['jobs'] }} jobs in this period.</div>
      </div>

      {{-- Decisions --}}
      <div style="{{ $card }}">
        <div style="{{ $hd }}">Human decisions</div>
        <div style="font-size:0.85rem;">{{ $d['approved'] }} approved · {{ $d['rejected'] }} rejected</div>
        <div class="text-muted" style="font-size:0.72rem;">Agreement with the agent's recommendation: {{ $d['agreement'] !== null ? $d['agreement'] . '%' : 'no decisions in this period' }} (approved REVIEW/APPLY, or rejected SKIP).</div>
      </div>

      {{-- Health --}}
      <div style="{{ $card }}">
        <div style="{{ $hd }}">Discovery health</div>
        @include('upwork.partials.open-alerts', ['alerts' => $openAlerts ?? []])
        <div style="font-size:0.85rem;">
          Agent <strong style="color:{{ $h['agent_enabled'] ? '#166534' : '#991b1b' }};">{{ $h['agent_enabled'] ? 'enabled' : 'disabled' }}</strong> · every {{ $h['interval'] }} min · read-only Upwork<br>
          Runs: {{ $h['runs'] }} ({{ $h['completed'] }} completed, {{ $h['failed'] }} failed, {{ $h['abandoned'] }} abandoned{{ $h['running'] ? ', ' . $h['running'] . ' running' : '' }})<br>
          Searches: {{ $h['queries'] }} ({{ $h['failed_queries'] }} failed, {{ $h['rate_limited'] }} rate-limited)
          @if($h['avg_duration_s'] !== null)<br>Average run: {{ $h['avg_duration_s'] }}s @endif
        </div>
        @if($h['last_run'])<div class="text-muted" style="font-size:0.72rem;">Last run {{ $h['last_run']->status }}, {{ optional($h['last_run']->started_at)->diffForHumans() }}</div>@endif
        @foreach($h['backing_off'] as $profile)
          <div class="text-danger" style="font-size:0.75rem;">{{ $profile->name }} backing off until {{ $profile->backoff_until->format('H:i') }}{{ $profile->last_error ? ': ' . \Illuminate\Support\Str::limit($profile->last_error, 80) : '' }}</div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- AI & efficiency --}}
  @php
    $mx = $metrics; $ds = $mx['discovery']; $ai = $mx['ai']; $oc = $mx['outcomes']; $ef = $mx['efficiency'];
    $usd = fn($v, $d = 4) => $v === null ? '—' : '$' . number_format($v, $d);
    $num = fn($v) => $v === null ? '—' : $v;
  @endphp
  <div style="{{ $card }}">
    <div class="d-flex justify-content-between flex-wrap gap-2">
      <div style="{{ $hd }}">AI &amp; efficiency <span class="text-muted" style="text-transform:none; font-weight:400;">(costs are estimates from configured prices)</span></div>
      @can('viewConfig', $M)<a href="{{ route('upwork.config.ai.edit') }}" class="small">AI usage &amp; budget</a>@endcan
    </div>
    @if($ai['blocked'])<div class="alert alert-warning border-0 small py-1 px-2 mb-2">{{ $ai['blocked'] }}</div>@endif
    <div class="row g-3" style="font-size:0.84rem;">
      <div class="col-md-3">
        <div class="text-muted fw-semibold small">Upwork (read-only)</div>
        Searches: <strong>{{ $ds['searches'] }}</strong><br>
        Detail lookups: <strong>{{ $ds['detail_lookups'] }}</strong><br>
        New jobs: <strong>{{ $ds['new_jobs'] }}</strong> · duplicates {{ $ds['duplicates'] }}<br>
        Filtered locally: <strong>{{ $ds['filtered'] }}</strong>
      </div>
      <div class="col-md-3">
        <div class="text-muted fw-semibold small">AI</div>
        Analysed: <strong>{{ $ai['analyzed_jobs'] }}</strong> · skipped {{ $ai['skipped_jobs'] }} · blocked {{ $ai['blocked_jobs'] }}<br>
        Requests: <strong>{{ $ai['requests'] }}</strong> ({{ $ai['failed_requests'] }} failed, {{ $ai['deep_requests'] }} deep)<br>
        Tokens: {{ number_format($ai['tokens']) }}<br>
        Cost: <strong>{{ $usd($ai['cost']) }}</strong>
      </div>
      <div class="col-md-3">
        <div class="text-muted fw-semibold small">Results</div>
        Score 70+: <strong>{{ $oc['score_70'] }}</strong> · 80+: <strong>{{ $oc['score_80'] }}</strong><br>
        Approved: <strong>{{ $oc['approved'] }}</strong> · rejected {{ $oc['rejected'] }}<br>
        AI agreed with people: <strong>{{ $ef['ai_human_agreement_pct'] === null ? '—' : $ef['ai_human_agreement_pct'] . '%' }}</strong> <span class="text-muted">({{ $ef['ai_human_compared'] }} decisions)</span>
      </div>
      <div class="col-md-3">
        <div class="text-muted fw-semibold small">Efficiency</div>
        Cost / analysed job: {{ $usd($ef['cost_per_analyzed_job']) }}<br>
        Cost / 70+ job: {{ $usd($ef['cost_per_70_job']) }}<br>
        Cost / approved job: {{ $usd($ef['cost_per_approved_job']) }}<br>
        Searches / new job: {{ $num($ef['searches_per_new_job']) }} · AI share {{ $ef['ai_share_pct'] === null ? '—' : $ef['ai_share_pct'] . '%' }}
      </div>
    </div>
    <div class="mt-2" style="font-size:0.8rem;">
      Month to date (UTC): <strong>{{ $usd($ai['month_cost']) }}</strong> of {{ $usd($ai['monthly_budget'], 2) }} · remaining <strong>{{ $usd($ai['monthly_remaining']) }}</strong>
      <div class="progress mt-1" style="height:6px; max-width:420px;"><div class="progress-bar {{ $ai['monthly_budget'] > 0 && $ai['month_cost'] / $ai['monthly_budget'] > 0.8 ? 'bg-danger' : 'bg-success' }}" style="width:{{ $ai['monthly_budget'] > 0 ? min(100, round($ai['month_cost'] / $ai['monthly_budget'] * 100)) : 0 }}%;"></div></div>
    </div>
  </div>
</div>
@endsection
