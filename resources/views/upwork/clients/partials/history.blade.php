{{-- Client history from stored PMS data only. Expects $client (UpworkClient), $history (array), $dt, $dd,
     optional $link (bool) and $mode ('opportunity': other jobs than the one shown; 'client': all jobs). --}}
@php
  $M = \App\Models\UpworkOpportunity::class;
  $money = fn($v) => $v === null ? '—' : '$' . rtrim(rtrim(number_format($v, 2), '0'), '.');
  $d = $history['decisions'];
  $decisionLabels = ['approved' => 'approved', 'rejected' => 'rejected', 'submitted' => 'proposals submitted', 'client_responses' => 'client responses', 'won' => 'won', 'lost' => 'lost'];
  $decided = array_filter($d);
  $clientMode = ($mode ?? 'opportunity') === 'client';
@endphp
<div style="border-top:1px solid #f3f4f6; margin-top:0.25rem; padding-top:0.75rem;">
  <div style="{{ $dt }}">Client history (PMS records)</div>
  <div style="{{ $dd }}">
    @if($clientMode)
      <strong>{{ $history['previous_jobs'] }} {{ \Illuminate\Support\Str::plural('job', $history['previous_jobs']) }} from this client in the PMS.</strong>
    @elseif($history['previous_jobs'] > 0)
      <strong>Seen before: {{ $history['previous_jobs'] }} previous {{ \Illuminate\Support\Str::plural('job', $history['previous_jobs']) }}.</strong>
    @else
      First job from this client in the PMS.
    @endif
  </div>

  @if($history['hire_rate_trend'])
    @php $t = $history['hire_rate_trend']; @endphp
    <div style="{{ $dt }}">Historical hire rate</div>
    <div style="{{ $dd }}">
      @if($t['snapshots'] > 1){{ round($t['first']) }}% → {{ round($t['latest']) }}% <span class="text-muted">({{ $t['snapshots'] }} snapshots)</span>
      @else{{ round($t['latest']) }}% <span class="text-muted">(1 snapshot)</span>@endif
    </div>
  @endif

  @if($history['typical_hourly'] || $history['typical_fixed'])
    <div style="{{ $dt }}">Typical budget</div>
    <div style="{{ $dd }}">
      @if($history['typical_hourly'])
        {{ $history['typical_hourly']['min'] !== null ? $money($history['typical_hourly']['min']) . '–' : 'up to ' }}{{ $money($history['typical_hourly']['max']) }}/hr
        <span class="text-muted">(median of {{ $history['typical_hourly']['samples'] }} hourly {{ \Illuminate\Support\Str::plural('job', $history['typical_hourly']['samples']) }})</span>@if($history['typical_fixed'])<br>@endif
      @endif
      @if($history['typical_fixed'])
        {{ $money($history['typical_fixed']['median']) }} fixed <span class="text-muted">(median of {{ $history['typical_fixed']['samples'] }})</span>
      @endif
    </div>
  @endif

  @if($history['technologies'])
    <div style="{{ $dt }}">Technologies requested before</div>
    <div class="mb-2">
      @foreach($history['technologies'] as $tech => $count)
        <span class="badge rounded-pill" style="background:#f3f4f6; color:#374151; font-weight:600; margin:0 0.25rem 0.25rem 0;">{{ $tech }}@if($count > 1) ×{{ $count }}@endif</span>
      @endforeach
    </div>
  @endif

  @if($history['previous_jobs'] > 0)
    <div style="{{ $dt }}">Code4Each decisions</div>
    <div style="{{ $dd }}">
      @if($decided)
        {{ implode(' · ', array_map(fn($k) => $decided[$k] . ' ' . $decisionLabels[$k], array_keys($decided))) }}
      @else
        No decisions recorded yet.
      @endif
      @foreach($history['rejection_reasons'] as $reason)<br><span class="text-muted">Rejected: “{{ $reason }}”</span>@endforeach
    </div>
    <div style="{{ $dt }}">{{ $clientMode ? 'Jobs' : 'Previous jobs' }}</div>
    <ul class="ps-3 mb-2" style="font-size:0.8rem;">
      @foreach($history['jobs'] as $job)
        <li><a href="{{ route('upwork.opportunities.show', $job['id']) }}">{{ \Illuminate\Support\Str::limit($job['title'], 60) }}</a>
          <span class="text-muted">· {{ $job['budget'] }} · {{ str_replace('_', ' ', $job['status']) }}@if($job['score'] !== null) · {{ round($job['score']) }}@endif</span></li>
      @endforeach
    </ul>
  @endif

  @if(!empty($client->work_history))
    <div style="{{ $dt }}">Recent contracts (reported by Upwork)</div>
    <ul class="ps-3 mb-2" style="font-size:0.8rem;">
      @foreach($client->work_history as $contract)
        <li>{{ \Illuminate\Support\Str::limit($contract['title'] ?? '—', 60) }}
          <span class="text-muted">· {{ strtolower($contract['status'] ?? '') ?: '—' }}@if(isset($contract['feedback_score'])) · freelancer gave {{ $contract['feedback_score'] }}★@endif</span></li>
      @endforeach
    </ul>
  @endif

  @if($link ?? false)
    <a href="{{ route('upwork.clients.show', $client) }}" class="small">Client intelligence <i class="bi bi-arrow-right"></i></a>
  @endif
</div>
