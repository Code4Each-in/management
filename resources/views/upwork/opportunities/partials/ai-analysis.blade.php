{{-- AI analysis: judgement and estimates, always kept apart from verified Upwork facts. --}}
@php
  $a = $ai['deep'] && (!$ai['qualify'] || $ai['deep']->id > $ai['qualify']->id) ? $ai['deep'] : $ai['qualify'];
  $r = $a ? $a->result : null;
  $kindStyle = ['FACT' => 'background:#dcfce7; color:#166534;', 'INFERENCE' => 'background:#e0f2fe; color:#075985;', 'ASSUMPTION' => 'background:#fef3c7; color:#92400e;', 'UNKNOWN' => 'background:#f3f4f6; color:#374151;'];
  $statusLabel = ['queued' => 'Queued', 'analyzed' => 'Analyzed', 'skipped' => 'Not analyzed', 'failed' => 'Failed', 'blocked' => 'Blocked'];
  $usageCost = $ai['usage']->sum(fn($u) => $u->budget_cost);
@endphp
<div style="{{ $card }} border-left:3px solid #7c3aed;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div style="{{ $h }}">AI analysis <span class="badge bg-light text-muted border ms-1" style="font-size:0.65rem;">AI JUDGEMENT · NOT VERIFIED</span></div>
    @can('analyze', $o)
      <div class="d-flex gap-1 flex-wrap">
        @if(!$ai['qualify'])
          <form method="POST" action="{{ route('upwork.opportunities.ai.analyze', $o) }}" class="m-0">@csrf<input type="hidden" name="type" value="qualify">
            <button class="btn btn-sm btn-outline-primary" {{ $ai['status']['blocked'] ? 'disabled' : '' }}><i class="bi bi-stars"></i> Analyze with AI</button></form>
        @else
          <form method="POST" action="{{ route('upwork.opportunities.ai.analyze', $o) }}" class="m-0" onsubmit="return confirm('Ask the AI again? This is a new paid request.')">@csrf
            <input type="hidden" name="type" value="qualify"><input type="hidden" name="regenerate" value="1">
            <button class="btn btn-sm btn-outline-secondary" {{ $ai['status']['blocked'] ? 'disabled' : '' }}><i class="bi bi-arrow-repeat"></i> Regenerate</button></form>
        @endif
        <form method="POST" action="{{ route('upwork.opportunities.ai.analyze', $o) }}" class="m-0" onsubmit="return confirm('Deep analysis uses a larger request from its own monthly allowance. Continue?')">@csrf
          <input type="hidden" name="type" value="deep">@if($ai['deep'])<input type="hidden" name="regenerate" value="1">@endif
          <button class="btn btn-sm btn-outline-primary" {{ $ai['status']['blocked'] ? 'disabled' : '' }}><i class="bi bi-search"></i> Deep analyze</button></form>
      </div>
    @endcan
  </div>

  <div class="small mb-2">
    Status: <strong>{{ $statusLabel[$o->ai_status] ?? 'Not requested' }}</strong>@if($o->ai_status_reason) — {{ $o->ai_status_reason }}@endif
    @if($ai['status']['blocked'])<div class="text-danger">{{ $ai['status']['blocked'] }}</div>@endif
  </div>

  @if($r)
    <div class="d-flex flex-wrap gap-3 align-items-baseline mb-2">
      <div><span style="{{ $dt }}">AI fit</span> <span style="font-size:1.1rem; font-weight:700;">{{ $a->fit_score }}/100</span></div>
      <div><span style="{{ $dt }}">AI suggests</span> <span class="badge bg-{{ ['apply' => 'success', 'review' => 'warning', 'skip' => 'secondary'][$a->recommendation] ?? 'secondary' }}">{{ strtoupper($a->recommendation) }}</span></div>
      <div><span style="{{ $dt }}">Confidence</span> {{ number_format($a->confidence * 100) }}%
        @if($a->confidence < $ai['min_confidence'])<span class="text-muted">(below {{ number_format($ai['min_confidence'] * 100) }}%: not used in scoring)</span>@endif</div>
    </div>
    <div style="{{ $dd }}">{{ $r['summary'] }}</div>

    @if(!empty($r['proposal_angle']))
      <div style="{{ $dt }}">Proposal angle (deep)</div><div style="{{ $dd }}">{{ $r['proposal_angle'] }}</div>
    @endif
    @if(!empty($r['approach']))
      <div style="{{ $dt }}">Approach (deep)</div><ol class="small ps-3">@foreach($r['approach'] as $step)<li>{{ $step }}</li>@endforeach</ol>
    @endif
    @if(!empty($r['experience_to_highlight']))
      <div style="{{ $dt }}">Experience to highlight (deep)</div><div style="{{ $dd }}">{{ implode(', ', $r['experience_to_highlight']) }}</div>
    @endif

    @if(!empty($r['claims']))
      <div style="{{ $dt }}">Claims, labelled</div>
      <ul class="list-unstyled small mb-2">
        @foreach($r['claims'] as $c)
          <li class="mb-1"><span class="badge" style="{{ $kindStyle[$c['kind']] ?? '' }} font-size:0.65rem;">{{ $c['kind'] }}</span> {{ $c['statement'] }}
            @if(!empty($c['downgraded']))<span class="text-muted" title="The AI called this a fact, but its quote is not in the job post or client data.">(AI said FACT; quote not found)</span>@endif
            @if(!empty($c['evidence']))<span class="text-muted">— “{{ \Illuminate\Support\Str::limit($c['evidence'], 140) }}”</span>@endif</li>
        @endforeach
      </ul>
    @endif

    <div class="row small">
      @if(!empty($r['risks']) || !empty($r['red_flags']))
        <div class="col-md-6"><div style="{{ $dt }}">Risks</div><ul class="ps-3 mb-2">
          @foreach($r['risks'] ?? [] as $risk)<li>{{ $risk['risk'] }} <span class="text-muted">({{ $risk['severity'] }}, {{ strtolower($risk['kind']) }})</span></li>@endforeach
          @foreach($r['red_flags'] ?? [] as $flag)<li>{{ $flag['flag'] }} <span class="text-muted">({{ strtolower($flag['kind']) }})</span></li>@endforeach
        </ul></div>
      @endif
      @if(!empty($r['unknowns']) || !empty($r['questions_for_client']))
        <div class="col-md-6"><div style="{{ $dt }}">Unknowns / questions for the client</div><ul class="ps-3 mb-2">
          @foreach($r['unknowns'] ?? [] as $u)<li>{{ $u }}</li>@endforeach
          @foreach($r['questions_for_client'] ?? [] as $q)<li><em>{{ $q }}</em></li>@endforeach
        </ul></div>
      @endif
    </div>

    <div class="row small">
      <div class="col-md-6"><div style="{{ $dt }}">Scope (AI estimate)</div>
        <div style="{{ $dd }}">{{ str_replace('_', ' ', $r['scope']['type']) }}, {{ $r['scope']['complexity'] }} complexity
          @if($r['scope']['estimated_hours_min'] !== null && $r['scope']['estimated_hours_max'] !== null)
            · ~{{ $r['scope']['estimated_hours_min'] }}–{{ $r['scope']['estimated_hours_max'] }} h <span class="text-muted">(assumption{{ $r['scope']['estimate_basis'] ? ': ' . $r['scope']['estimate_basis'] : '' }})</span>
          @endif</div></div>
      <div class="col-md-6"><div style="{{ $dt }}">Signals (AI)</div>
        <div style="{{ $dd }}">Long-term: {{ $r['signals']['long_term_potential'] }} · AI work: {{ $r['signals']['ai_component'] }} · Budget realistic: {{ $r['signals']['budget_realistic'] }} · Agency-friendly: {{ $r['signals']['agency_friendly'] }} · Urgency: {{ $r['signals']['urgency'] }}</div></div>
    </div>
    @if(!empty($r['technologies']))
      <div style="{{ $dt }}">Technologies (AI)</div>
      <div class="mb-2">@foreach($r['technologies'] as $t)<span class="badge me-1 mb-1" style="{{ $kindStyle[$t['kind']] ?? '' }}" title="{{ $t['evidence'] }}">{{ $t['name'] }} · {{ $t['kind'] }}</span>@endforeach</div>
    @endif

    <div class="text-muted" style="font-size:0.72rem;">
      {{ $a->request_type === 'deep' ? 'Deep analysis' : 'Qualification' }} · {{ $a->provider }}/{{ $a->model }} · {{ $a->prompt_version }} · {{ $a->created_at->format('d M Y H:i') }}
      @if(isset($r['verification'])) · facts checked {{ $r['verification']['facts_checked'] }}, downgraded {{ $r['verification']['facts_downgraded'] }}@endif
    </div>
  @elseif($ai['last_failure'])
    <div class="small text-muted">Last attempt failed ({{ $ai['last_failure']->error_code }}). Local scoring is unaffected.</div>
  @else
    <div class="small text-muted">No AI analysis yet. Local scoring above stands on its own.</div>
  @endif

  <details class="small mt-2">
    <summary class="text-muted">AI usage for this job: {{ $ai['usage']->count() }} request{{ $ai['usage']->count() === 1 ? '' : 's' }}, ~${{ number_format($usageCost, 4) }}</summary>
    @if($ai['usage']->isNotEmpty())
      <table class="table table-sm mt-1 mb-1" style="font-size:0.75rem;">
        <thead><tr><th>When</th><th>Type</th><th>Status</th><th>Tokens in/out</th><th>Est. cost</th></tr></thead>
        <tbody>@foreach($ai['usage'] as $u)<tr><td>{{ $u->created_at->format('d M H:i') }}</td><td>{{ $u->request_type }}</td><td>{{ $u->status }}{{ $u->error_code ? " ({$u->error_code})" : '' }}</td>
          <td>{{ $u->input_tokens ?? '—' }}/{{ $u->output_tokens ?? '—' }}</td><td>${{ number_format($u->budget_cost, 5) }}</td></tr>@endforeach</tbody>
      </table>
    @endif
    @can('viewConfig', $M)<a href="{{ route('upwork.config.ai.edit') }}">View all AI usage and budget</a>@endcan
  </details>
</div>
