@php
  // Profile + proposal angle, prefilled from the best-fit suggestion. Used when approving and before drafting.
  $fit = $targeting['fit'];
  $restricted = $fit['restriction']['track'] ?? null;
@endphp
<form method="POST" action="{{ $action }}" class="m-0" id="{{ $formId ?? '' }}">
  @csrf
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label small fw-semibold mb-1">Bid as</label>
      <select name="selected_profile" class="form-select form-select-sm" required>
        @foreach($targeting['options'] as $value => $option)
          <option value="{{ $value }}" {{ old('selected_profile', $targeting['profile']) === $value ? 'selected' : '' }}>
            {{ $option['label'] }}{{ ($fit['suggested_profile'] ?? null) === $value ? ' — suggested' : '' }}{{ $restricted && $restricted !== $option['track'] ? ' — posting excludes this' : '' }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold mb-1">Proposal angle</label>
      <select name="proposal_angle" class="form-select form-select-sm">
        <option value="">No specific angle</option>
        @foreach($targeting['angles'] as $code => [$label, $guidance])
          <option value="{{ $code }}" title="{{ $guidance }}" {{ old('proposal_angle', $targeting['angle']) === $code ? 'selected' : '' }}>{{ $label }}{{ ($fit['suggested_angle'] ?? null) === $code ? ' — suggested' : '' }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">{!! $button !!}</div>
    <div class="col-12">
      <input type="text" name="proposal_angle_note" maxlength="500" value="{{ old('proposal_angle_note', $targeting['note']) }}" class="form-control form-control-sm" placeholder="Angle note for the draft (optional), e.g. which verified project to lead with">
    </div>
  </div>
  <div class="text-muted mt-1" style="font-size:0.72rem;">The profile only decides how the draft is written. The PMS does not pick, switch or submit from any Upwork account.</div>
</form>
