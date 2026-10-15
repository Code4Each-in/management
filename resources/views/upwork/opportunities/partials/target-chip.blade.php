@php
  // Best-fit track chip: agency / individual / either (+ "?" when a person should decide).
  $t = $target ?? null;
  $ambiguous = $ambiguous ?? false;
  $styles = ['agency' => 'background:#dbeafe; color:#1e40af;', 'individual' => 'background:#d1fae5; color:#065f46;', 'both' => 'background:#f3f4f6; color:#374151;'];
@endphp
@if($t)
  <span class="badge" style="{{ $styles[$t] ?? '' }} font-weight:600;" title="{{ $title ?? 'Best-fit profile' }}{{ $ambiguous ? ' — ambiguous, a person should choose' : '' }}">{{ \App\Services\Upwork\Targeting\TargetProfiles::TRACKS[$t] ?? $t }}{{ $ambiguous ? ' ?' : '' }}</span>
@endif
