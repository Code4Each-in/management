@php
  $fit = $targeting['fit'];
  $fmt = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.');
  $suggestion = \App\Services\Upwork\Targeting\TargetProfiles::label($fit['suggested_profile'] ?? null);
@endphp
<div style="{{ $card }}" id="best-fit">
  <div style="{{ $h }}">Best-fit profile <span class="badge bg-light text-muted border ms-1" style="font-size:0.65rem;">RULES · EXPLAINED</span></div>
  @if(!$fit)
    <div class="text-muted small">Not classified yet. It is classified the next time the job is scored (re-seen by discovery, re-scored, or backfilled).</div>
  @else
    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
      @include('upwork.opportunities.partials.target-chip', ['target' => $fit['target'], 'ambiguous' => $fit['ambiguous']])
      <span class="small text-muted">confidence {{ $fit['confidence'] }}</span>
    </div>
    <div style="{{ $dd }}">{{ $fit['summary'] }}</div>
    @if($fit['ambiguous'])
      <div class="alert alert-warning border-0 small py-1 px-2 mb-2">Ambiguous: choose the profile yourself when approving. Nothing is excluded either way.</div>
    @endif
    <div class="row text-center mb-2" style="font-size:0.8rem;">
      @foreach(['agency' => 'Agency fit', 'individual' => 'Individual fit'] as $track => $label)
        @php $tr = $fit['tracks'][$track]; @endphp
        <div class="col-6">
          <div style="{{ $dt }}">{{ $label }}</div>
          <div style="font-size:1.25rem; font-weight:700; color:{{ $tr['eligible'] ? '#111827' : '#9ca3af' }};">{{ $tr['eligible'] ? $fmt($tr['score']) : 'n/a' }}</div>
          <div class="text-muted" style="font-size:0.7rem;">@if(!$tr['eligible'])posting excludes this profile @elseif($tr['base'] !== null)base {{ $fmt($tr['base']) }} {{ $tr['points'] >= 0 ? '+' : '' }}{{ $fmt($tr['points']) }} pts @else signals only ({{ $tr['points'] >= 0 ? '+' : '' }}{{ $fmt($tr['points']) }}) @endif</div>
        </div>
      @endforeach
    </div>
    <div style="{{ $dt }}">Why</div>
    <ul class="ps-3 mb-2" style="font-size:0.8rem;">
      @foreach($fit['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach
    </ul>
    <div class="small"><strong>Suggested:</strong> {{ $suggestion }} · angle “{{ $targeting['angles'][$fit['suggested_angle']][0] ?? '—' }}” <span class="text-muted">({{ $fit['angle_reason'] }})</span></div>
    <div class="text-muted mt-1" style="font-size:0.72rem;">A suggestion only. The opportunity score and recommendation are not affected, and no Upwork account is chosen.</div>
  @endif
</div>
