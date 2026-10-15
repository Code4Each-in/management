{{-- Location eligibility in one line: the freelancer-location text as given, and why it is (not) a restriction. --}}
@php
    $L = \App\Services\Upwork\Eligibility\LocationEligibility::class;
    $locStyle = [
        $L::ELIGIBLE => ['#0f5132', 'bi-geo-alt'],
        $L::RESTRICTED => ['#842029', 'bi-geo-alt-fill'],
        $L::UNKNOWN => ['#92400e', 'bi-question-circle'],
    ][$opportunity->location_eligibility] ?? null;
@endphp
@if($locStyle)
  <div style="font-size:0.72rem; color:{{ $locStyle[0] }}; margin-top:0.125rem;" title="Freelancer location {{ $opportunity->location_source ? 'from ' . $opportunity->location_source : 'not given by Upwork' }}; rule {{ $opportunity->location_rule }}">
    <i class="bi {{ $locStyle[1] }}"></i>
    <strong>{{ $L::LABELS[$opportunity->location_eligibility] }}</strong>
    · {{ $opportunity->location_text !== null ? \Illuminate\Support\Str::limit($opportunity->location_text, 60) : 'no location restriction given' }}
    @if($opportunity->location_eligibility !== $L::ELIGIBLE || $opportunity->location_override) — {{ $opportunity->location_reason }}@endif
    @if($opportunity->location_override)<span class="badge bg-light text-dark border" style="font-size:0.6rem;">corrected</span>@endif
  </div>
@endif
