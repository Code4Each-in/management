@extends('layout')

@section('title', 'Upwork Opportunity')

@section('content')
@php
    $o = $opportunity;
    $M = \App\Models\UpworkOpportunity::class;
    $card = 'background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04); padding:1.25rem; margin-bottom:1rem;';
    $h = 'font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280; margin-bottom:0.75rem;';
    $dt = 'font-size:0.75rem; color:#6b7280; font-weight:600;';
    $dd = 'font-size:0.9rem; color:#1f2937; margin-bottom:0.625rem;';
    $fmt = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.');
@endphp
<div style="margin: 1rem auto 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

  <a href="{{ route('upwork.opportunities.index') }}" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3" style="font-weight:600;">
    <i class="bi bi-arrow-left"></i> Back to opportunities
  </a>

  @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 small py-2 px-3 mb-3" style="font-weight:600;">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3" style="font-weight:600;">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3">
      @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
  @endif

  {{-- Header --}}
  <div style="{{ $card }}">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div style="min-width:0; flex:1;">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          @include('upwork.opportunities.partials.status-badge', ['opportunity' => $o])
          @include('upwork.opportunities.partials.recommendation-badge', ['opportunity' => $o])
          <span style="font-size:0.75rem; color:#9ca3af;">Upwork job {{ $o->upwork_job_id }}</span>
        </div>
        <h4 style="font-weight:700; margin:0; color:#111827;">{{ $o->title }}</h4>
        @if($o->status === $M::STATUS_APPROVED && $o->approver)
          <div class="small text-muted mt-1">Approved by {{ $o->approver->first_name }} {{ $o->approver->last_name }} · {{ optional($o->approved_at)->format('d M Y H:i') }}</div>
        @elseif($o->status === $M::STATUS_REJECTED)
          <div class="small text-muted mt-1">
            Rejected @if($o->rejecter) by {{ $o->rejecter->first_name }} {{ $o->rejecter->last_name }} @endif · {{ optional($o->rejected_at)->format('d M Y H:i') }}
            @if($o->rejection_reason)<br><span style="color:#842029;">Reason: {{ $o->rejection_reason }}</span>@endif
          </div>
        @endif
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        @can('approve', $o)
          @if($o->canTransitionTo($M::STATUS_APPROVED))
            <form method="POST" action="{{ route('upwork.opportunities.approve', $o) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Approve</button>
            </form>
          @endif
        @endcan
        @can('reject', $o)
          @if($o->canTransitionTo($M::STATUS_REJECTED))
            <button type="button" class="btn btn-outline-danger btn-sm js-reject"
                    data-action="{{ route('upwork.opportunities.reject', $o) }}" data-title="{{ $o->title }}">
              <i class="bi bi-x-lg"></i> Reject
            </button>
          @endif
        @endcan
        @if($o->upwork_url)
          <a href="{{ $o->upwork_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-up-right"></i> Open on Upwork
          </a>
        @endif
      </div>
    </div>
    <div class="small text-muted mt-2" style="font-size:0.75rem;">Approving only records that we want to pursue this job. Nothing is sent to Upwork.</div>
  </div>

  <div class="row g-3">
    {{-- Left column --}}
    <div class="col-lg-8">

      {{-- Job --}}
      <div style="{{ $card }}">
        <div style="{{ $h }}">Job <span class="badge bg-light text-success border ms-1" style="font-size:0.65rem;">VERIFIED · FROM UPWORK</span></div>
        <div class="row">
          <div class="col-sm-4"><div style="{{ $dt }}">Type</div><div style="{{ $dd }}">{{ ucfirst($o->job_type ?? '—') }}</div></div>
          <div class="col-sm-4"><div style="{{ $dt }}">Budget / rate</div><div style="{{ $dd }}">{{ $o->budget_label }}</div></div>
          <div class="col-sm-4"><div style="{{ $dt }}">Experience</div><div style="{{ $dd }}">{{ $o->experience_level ?? '—' }}</div></div>
          <div class="col-sm-4"><div style="{{ $dt }}">Duration</div><div style="{{ $dd }}">{{ $o->duration ?? '—' }}</div></div>
          <div class="col-sm-4"><div style="{{ $dt }}">Posted</div><div style="{{ $dd }}">{{ $o->posted_at ? $o->posted_at->format('d M Y H:i') . ' (' . $o->posted_at->diffForHumans() . ')' : '—' }}</div></div>
          <div class="col-sm-4"><div style="{{ $dt }}">Proposals / interviews / connects</div><div style="{{ $dd }}">{{ $o->proposals_label }} / {{ $o->interviews_count ?? '—' }} / {{ $o->connects_required ?? '—' }}</div></div>
        </div>
        @if(!empty($o->technologies))
          <div style="{{ $dt }}">Technologies</div>
          <div class="mb-2">
            @foreach($o->technologies as $tech)
              <span class="badge rounded-pill" style="background:#eef2ff; color:#3730a3; font-weight:600; margin:0 0.25rem 0.25rem 0;">{{ $tech }}</span>
            @endforeach
          </div>
        @endif
        @if($o->upwork_url)
          <div style="{{ $dt }}">Upwork URL</div>
          <div style="{{ $dd }} word-break:break-all;"><a href="{{ $o->upwork_url }}" target="_blank" rel="noopener noreferrer">{{ $o->upwork_url }}</a></div>
        @endif
        <div style="{{ $dt }}">Description</div>
        <div style="font-size:0.9rem; color:#374151; line-height:1.55; max-height:420px; overflow:auto; border:1px solid #f3f4f6; border-radius:0.375rem; padding:0.75rem; background:#fafafa;">{!! nl2br(e($o->description ?? '—')) !!}</div>
      </div>

      {{-- Analysis --}}
      <div style="{{ $card }}">
        <div style="{{ $h }}">Local scoring <span class="badge bg-light text-muted border ms-1" style="font-size:0.65rem;">RULES · DETERMINISTIC</span></div>
        @if(!empty($o->score_explanation['hard_filters']))
          <div class="alert alert-danger border-0 small py-2 px-3">
            <strong>Hard filters triggered (scored before any points were given):</strong>
            <ul class="mb-0 ps-3">
              @foreach($o->score_explanation['hard_filters'] as $filter)
                <li>{{ $filter['reason'] }} <span class="text-muted">— rule <code>{{ $filter['key'] }}</code>, ruleset v{{ $filter['ruleset_version'] }}@if(!empty($filter['evaluated_at'])), {{ \Illuminate\Support\Carbon::parse($filter['evaluated_at'])->setTimezone(config('app.timezone'))->format('d M Y H:i') }}@endif</span></li>
              @endforeach
            </ul>
          </div>
        @elseif(!empty($o->hard_filters))
          <div class="alert alert-danger border-0 small py-2 px-3">
            <strong>Hard filters triggered:</strong>
            <ul class="mb-0 ps-3">@foreach($o->hard_filters as $filter)<li>{{ $filter }}</li>@endforeach</ul>
          </div>
        @endif
        @if(!empty($o->classifications))
          @php $classDetails = $o->score_explanation['classifications'] ?? []; @endphp
          <div style="{{ $dt }}">Classifications</div>
          <div class="mb-1">
            @include('upwork.opportunities.partials.classification-chips', ['codes' => $o->classifications, 'catalog' => $catalog, 'details' => $classDetails])
          </div>
          @if($classDetails)
            <details class="mb-2 small">
              <summary class="text-muted">Why these classifications</summary>
              <ul class="ps-3 mt-1 mb-0">
                @foreach($classDetails as $code => $reasons)
                  @foreach($reasons as $reason)
                    <li><strong>{{ $catalog[$code]->label ?? $code }}</strong> — {{ $reason['why'] }}</li>
                  @endforeach
                @endforeach
              </ul>
              @if(($o->score_explanation['scope']['source'] ?? null) === 'inferred')
                <div class="text-muted mt-1">Scope “{{ $o->score_explanation['scope']['size'] }}” was inferred from duration and budget (no agent signal).</div>
              @endif
            </details>
          @endif
        @endif
        @php
          $detected = $o->score_explanation['technologies'] ?? null;
          $tierStyle = ['proven' => 'background:#dcfce7; color:#166534;', 'adjacent' => 'background:#e0f2fe; color:#075985;', 'emerging' => 'background:#fef3c7; color:#92400e;', 'excluded' => 'background:#fee2e2; color:#991b1b;'];
        @endphp
        @if($detected && (!empty($detected['direct']) || !empty($detected['related'])))
          <div style="{{ $dt }}">Detected technologies (Code4Each capability tier)</div>
          <div class="mb-1">
            @foreach($detected['direct'] ?? [] as $t)
              <span class="badge" style="{{ $tierStyle[$t['tier']] ?? '' }} font-weight:600; margin:0 0.25rem 0.25rem 0;" title="{{ ucfirst($t['tier']) }}{{ !empty($t['terms']) ? ' — matched: ' . implode(', ', $t['terms']) : '' }}{{ ($t['source'] ?? '') === 'agent' ? ' — identified by agent' : '' }}">{{ $t['name'] }} · {{ strtoupper($t['tier']) }}</span>
            @endforeach
          </div>
          @if(!empty($detected['related']))
            <div class="mb-2 small text-muted">Related (partial credit only):
              @foreach($detected['related'] as $t){{ $t['name'] }} <span title="via {{ $t['via'] }}">({{ round($t['weight'] * 100) }}%)</span>@if(!$loop->last), @endif @endforeach
            </div>
          @endif
        @endif
        <div style="{{ $dt }}">Why it matches</div>
        <div style="{{ $dd }}">{!! nl2br(e($o->match_summary ?? '—')) !!}</div>
        <div style="{{ $dt }}">Relevant Code4Each experience</div>
        <div style="{{ $dd }}">{!! nl2br(e($o->relevant_experience ?? '—')) !!}</div>
        <div style="{{ $dt }}">Risks / red flags</div>
        <div style="{{ $dd }}">{!! nl2br(e($o->risks ?? '—')) !!}</div>
        <div style="{{ $dt }}">Recommendation</div>
        <div style="{{ $dd }}">@include('upwork.opportunities.partials.recommendation-badge', ['opportunity' => $o])</div>
      </div>

      {{-- AI (judgement and estimates, separate from facts) --}}
      @include('upwork.opportunities.partials.ai-analysis', ['o' => $o, 'ai' => $ai, 'card' => $card, 'h' => $h, 'dt' => $dt, 'dd' => $dd, 'M' => $M])

      {{-- Proposal --}}
      @include('upwork.opportunities.partials.proposal', ['o' => $o, 'card' => $card, 'h' => $h, 'dt' => $dt, 'dd' => $dd])
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">

      {{-- Scoring --}}
      <div style="{{ $card }}">
        <div style="{{ $h }}">Scoring</div>
        <div class="d-flex align-items-baseline gap-2 mb-3">
          <span style="font-size:2.25rem; font-weight:700; color:#111827; line-height:1;">{{ $fmt($o->score) }}</span>
          <span class="text-muted small">/ 100</span>
          @if($o->scoring_version || $o->ruleset_version)
            <span class="ms-auto text-muted text-end" style="font-size:0.7rem;">
              @if($o->scoring_version){{ $o->scoring_version }}@endif
              @if($o->ruleset_version)<br>Ruleset v{{ $o->ruleset_version }}@endif
            </span>
          @endif
        </div>
        @foreach($o->scoreComponents() as [$label, $value, $max, $explained])
          @php $pct = $value !== null && $max > 0 ? min(100, $value / $max * 100) : 0; @endphp
          <div class="mb-2">
            <div class="d-flex justify-content-between" style="font-size:0.8rem;">
              <span style="color:#374151;">{{ $label }}</span>
              <span style="font-weight:600;">{{ $fmt($value) }} <span class="text-muted">/ {{ $fmt($max) }}</span></span>
            </div>
            <div style="height:6px; background:#f3f4f6; border-radius:3px; overflow:hidden;">
              <div style="height:100%; width:{{ $pct }}%; background:#0d6efd;"></div>
            </div>
            @if($explained)
              <div style="font-size:0.72rem; color:#6b7280; margin-top:0.2rem;">
                Base {{ $fmt($explained['baseline']) }} — {{ $explained['baseline_detail'] }}
                @foreach($explained['rules'] as $rule)
                  <br><span style="color:{{ $rule['points'] >= 0 ? '#166534' : '#991b1b' }};">{{ $rule['points'] >= 0 ? '+' : '' }}{{ $fmt($rule['points']) }} {{ $rule['name'] }}</span>
                @endforeach
                @if($explained['capped'] === 'max')<br><em>Bonuses capped at {{ $fmt($max) }}</em>@endif
              </div>
            @endif
          </div>
        @endforeach
        @if(!empty($o->score_explanation['client']))
          @php $cq = $o->score_explanation['client']; @endphp
          <details class="mt-2 small">
            <summary class="text-muted">Client quality {{ $fmt($cq['score']) }}/100</summary>
            <ul class="mb-0 ps-3 mt-1">
              @foreach(\App\Services\Upwork\Scoring\ClientQualityScorer::PARTS as $part => [$partLabel, $partMax])
                <li>{{ $partLabel }}: {{ $fmt($cq['parts'][$part] ?? null) }} / {{ $partMax }}</li>
              @endforeach
            </ul>
            @if(!empty($cq['unknown']))<div class="text-muted mt-1">Unknown (neutral credit): {{ implode(', ', $cq['unknown']) }}</div>@endif
          </details>
        @endif
        @if(!empty($o->score_breakdown))
          <details class="mt-2 small">
            <summary class="text-muted">Additional breakdown from agent</summary>
            <pre style="font-size:0.72rem; white-space:pre-wrap; margin:0.5rem 0 0;">{{ json_encode($o->score_breakdown, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
          </details>
        @endif
      </div>

      {{-- Strategic value (separate from the opportunity score) --}}
      @if(!empty($o->score_explanation['strategic']))
        @php $strategic = $o->score_explanation['strategic']; @endphp
        <div style="{{ $card }}">
          <div style="{{ $h }}">Strategic value</div>
          <div class="d-flex align-items-baseline gap-2 mb-1">
            <span style="font-size:1.75rem; font-weight:700; color:#6d28d9; line-height:1;">{{ $fmt($strategic['score']) }}</span>
            <span class="text-muted small">/ 100</span>
          </div>
          <div class="text-muted mb-2" style="font-size:0.72rem;">How much this could matter beyond one engagement. Separate from the opportunity score.</div>
          @foreach($strategic['parts'] as $part)
            <div class="d-flex justify-content-between" style="font-size:0.8rem;">
              <span style="color:#374151;">{{ $part['label'] }}</span>
              <span style="font-weight:600;">{{ $fmt($part['points']) }} <span class="text-muted">/ {{ $fmt($part['max']) }}</span></span>
            </div>
            <div class="text-muted mb-1" style="font-size:0.72rem;">{{ $part['detail'] }}</div>
          @endforeach
        </div>
      @endif

      {{-- Commercial outlook (estimates, never facts) --}}
      @if(!empty($o->score_explanation['outlook']))
        @php $outlook = $o->score_explanation['outlook']; $wp = $outlook['win_probability']; $val = $outlook['value']; @endphp
        <div style="{{ $card }}">
          <div style="{{ $h }}">Commercial outlook <span class="badge bg-light text-muted border ms-1" style="font-size:0.65rem;">ESTIMATE</span></div>
          <div class="d-flex justify-content-between align-items-baseline">
            <span style="font-size:0.8rem; color:#374151;">Win probability</span>
            <span style="font-size:1.25rem; font-weight:700; color:#0f766e;">~{{ $fmt($wp['value']) }}%</span>
          </div>
          <div class="text-muted mb-2" style="font-size:0.72rem;">{{ $wp['model'] }} · {{ $wp['note'] }}@if($wp['clamped']) ({{ $wp['clamped'] }})@endif</div>
          @if(!empty($wp['blend']))
            <div class="text-muted mb-1" style="font-size:0.72rem;">Heuristic {{ $fmt($wp['blend']['heuristic']) }}% blended with AI-implied {{ $fmt($wp['blend']['ai_implied']) }}% (AI fit {{ $wp['blend']['ai_fit_score'] }}, weight {{ $fmt($wp['blend']['ai_weight'] * 100) }}%).</div>
          @endif
          <details class="small mb-2">
            <summary class="text-muted">How it was estimated</summary>
            <ul class="ps-3 mt-1 mb-0">
              @foreach($wp['contributions'] as $c)
                <li><span style="color:{{ $c['value'] >= 0 ? '#166534' : '#991b1b' }};">{{ $c['value'] >= 0 ? '+' : '' }}{{ $fmt($c['value']) }}%</span> {{ $c['label'] }}</li>
              @endforeach
            </ul>
          </details>
          @if($val['contract_value'] !== null)
            <div class="d-flex justify-content-between" style="font-size:0.8rem;"><span>Estimated contract value</span><span style="font-weight:600;">~{{ $M::money($val['contract_value']) }}</span></div>
            <div class="d-flex justify-content-between" style="font-size:0.8rem;"><span>Expected value</span><span style="font-weight:700;">~{{ $M::money($val['expected_value']) }}</span></div>
            <div class="text-muted" style="font-size:0.72rem;">= contract value × win probability. Assumptions: {{ implode('; ', $val['assumptions']) }}.</div>
          @else
            <div class="text-muted" style="font-size:0.8rem;">No value estimate: {{ $val['reason'] }}</div>
          @endif
          @if(!empty($val['ai_estimate']))
            @php $ae = $val['ai_estimate']; @endphp
            <div class="mt-2 pt-2 border-top" style="font-size:0.78rem;">
              <div class="text-muted" style="font-size:0.7rem; font-weight:700;">AI HOUR ESTIMATE (ASSUMPTION)</div>
              <div class="d-flex justify-content-between"><span>Hours</span><span>~{{ $ae['hours_min'] }}–{{ $ae['hours_max'] }} h</span></div>
              @if($ae['effective_hourly'] !== null)<div class="d-flex justify-content-between"><span>Effective hourly (budget ÷ hours)</span><span>~{{ $M::money($ae['effective_hourly']) }}/hr</span></div>@endif
              @if($ae['contract_value'] !== null && ($val['source'] ?? null) !== 'ai_estimate')<div class="d-flex justify-content-between"><span>AI-based contract value</span><span>~{{ $M::money($ae['contract_value']) }}</span></div>@endif
              @if(($val['source'] ?? null) === 'ai_estimate')<div class="text-muted" style="font-size:0.7rem;">The value above uses this AI estimate because the posting gives no duration or budget.</div>@endif
            </div>
          @endif
        </div>
      @endif

      {{-- Client --}}
      <div style="{{ $card }}">
        <div style="{{ $h }}">Client</div>
        <div class="row">
          <div class="col-6"><div style="{{ $dt }}">Country</div><div style="{{ $dd }}">{{ $o->client_country ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Timezone</div><div style="{{ $dd }}">{{ $o->client_timezone ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Rating</div><div style="{{ $dd }}">{{ $o->client_rating !== null ? number_format($o->client_rating, 2) : '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Reviews</div><div style="{{ $dd }}">{{ $o->client_reviews_count ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Total spent</div><div style="{{ $dd }}">{{ $M::money($o->client_total_spent) }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Hire rate</div><div style="{{ $dd }}">{{ $o->client_hire_rate !== null ? round($o->client_hire_rate) . '%' : '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Jobs posted</div><div style="{{ $dd }}">{{ $o->client_jobs_posted ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Jobs hired</div><div style="{{ $dd }}">{{ $o->client_jobs_hired ?? '—' }}</div></div>
          <div class="col-12"><div style="{{ $dt }}">Payment</div>
            <div style="{{ $dd }}">
              @if($o->payment_verified) <span class="text-primary"><i class="bi bi-patch-check-fill"></i> Verified</span>
              @elseif($o->payment_verified === false) <span class="text-danger"><i class="bi bi-patch-exclamation"></i> Not verified</span>
              @else — @endif
            </div>
          </div>
        </div>
        @if($client && $clientHistory)
          @include('upwork.clients.partials.history', ['client' => $client, 'history' => $clientHistory, 'link' => true])
        @elseif(!$o->upwork_client_id)
          <div class="text-muted" style="font-size:0.72rem;">Client not identified yet (needs full job details with Upwork's client id).</div>
        @endif
      </div>

      {{-- Activity --}}
      <div style="{{ $card }}">
        <div style="{{ $h }}">Activity</div>
        @forelse($o->logs as $log)
          <div style="font-size:0.8rem; border-left:2px solid #e5e7eb; padding:0 0 0.625rem 0.625rem;">
            <div style="font-weight:600; color:#374151;">{{ ucfirst(str_replace('_', ' ', $log->action)) }}
              @if($log->old_status && $log->new_status)<span class="text-muted fw-normal">({{ str_replace('_', ' ', $log->old_status) }} → {{ str_replace('_', ' ', $log->new_status) }})</span>@endif
            </div>
            <div class="text-muted" style="font-size:0.72rem;">{{ $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'System' }} · {{ optional($log->created_at)->format('d M Y H:i') }}</div>
            @if($log->note)<div style="color:#4b5563;">{{ $log->note }}</div>@endif
          </div>
        @empty
          <div class="text-muted small">No activity yet.</div>
        @endforelse
        <div class="text-muted mt-2" style="font-size:0.72rem;">Fetched {{ optional($o->fetched_at)->format('d M Y H:i') ?? '—' }}</div>
      </div>
    </div>
  </div>
</div>

@include('upwork.opportunities.partials.reject-modal')
@endsection
