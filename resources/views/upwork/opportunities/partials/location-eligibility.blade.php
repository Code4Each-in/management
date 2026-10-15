{{-- Location eligibility on the job page: what Upwork said, what the rules made of it, and a correction form (audited). --}}
@php
    $L = \App\Services\Upwork\Eligibility\LocationEligibility::class;
    $tone = [$L::ELIGIBLE => 'success', $L::RESTRICTED => 'danger', $L::UNKNOWN => 'warning'][$o->location_eligibility] ?? 'secondary';
@endphp
<div class="mt-3 pt-3 border-top" id="location">
  <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div style="min-width:0; flex:1;">
      <div class="small fw-semibold text-muted text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Location eligibility (India-based applicants)</div>
      @if(!$o->location_eligibility)
        <div class="small text-muted">Not checked yet. It is checked the next time the job is seen, or by <code>php artisan upwork:classify-locations</code>.</div>
      @else
        <div>
          <span class="badge bg-{{ $tone }}{{ $tone === 'warning' ? ' text-dark' : '' }}">{{ $L::LABELS[$o->location_eligibility] }}</span>
          <span class="small">{{ $o->location_reason }}</span>
        </div>
        <div class="small mt-1" style="color:#374151;">
          Freelancer location: <strong>{{ $o->location_text ?? 'not given by Upwork' }}</strong>
          @if($o->location_source)<span class="text-muted">· from <code>{{ $o->location_source }}</code></span>@endif
          @if($o->client_country)<span class="text-muted">· client is in {{ $o->client_country }} (not a requirement on you)</span>@endif
        </div>
        <div class="small text-muted" style="font-size:0.75rem;">
          Rules said <strong>{{ $L::LABELS[$o->location_auto_eligibility] ?? '—' }}</strong> (rule <code>{{ $o->location_rule }}</code>, v{{ $o->location_rules_version }}, {{ optional($o->location_classified_at)->format('d M Y H:i') }})
          @if($o->location_override)
            · <strong>corrected to {{ $L::LABELS[$o->location_override] }}</strong> by {{ $o->locationOverrider ? $o->locationOverrider->first_name . ' ' . $o->locationOverrider->last_name : 'a reviewer' }} {{ optional($o->location_override_at)->format('d M Y H:i') }}: “{{ $o->location_override_note }}”
          @endif
        </div>
        @if($o->isLocationRestricted())
          <div class="small mt-1" style="color:#842029;">Held at SKIP: no review deadline, reminders, notifications, AI analysis or proposal drafting. Status is unchanged.</div>
        @elseif($o->location_eligibility === $L::UNKNOWN)
          <div class="small mt-1" style="color:#92400e;">Kept in review: check the posting on Upwork, then correct the classification if needed.</div>
        @endif
      @endif
    </div>
    @can('overrideLocation', $o)
      <form method="POST" action="{{ route('upwork.opportunities.location', $o) }}" class="d-flex gap-1 align-items-start flex-wrap m-0" style="max-width:520px;">
        @csrf
        <select name="classification" class="form-select form-select-sm" style="width:auto;" aria-label="Correct the location classification">
          <option value="eligible" {{ $o->location_override === 'eligible' ? 'selected' : '' }}>India can apply (eligible)</option>
          <option value="restricted" {{ $o->location_override === 'restricted' || (!$o->location_override && $o->location_eligibility !== 'restricted') ? 'selected' : '' }}>India excluded (skip)</option>
          @if($o->location_override)<option value="rules">Remove override (use the rules)</option>@endif
        </select>
        <input type="text" name="note" maxlength="500" class="form-control form-control-sm" style="width:220px;" placeholder="Why? (kept in the activity log)">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Correct</button>
      </form>
    @endcan
  </div>
</div>
