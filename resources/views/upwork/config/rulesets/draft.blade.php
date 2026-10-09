@extends('layout')

@section('title', 'Upwork Scoring Draft')

@section('content')
@php $settings = $draft->settings ?? []; @endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="uw-card d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="uw-h mb-1">Draft{{ $draft->basedOn ? ' based on v' . $draft->basedOn->version : '' }}</div>
      <div class="uw-help">Nothing here affects scoring until you publish. Publishing creates the next version; current scores keep theirs.</div>
      @if($errorsPreview)
        <div class="text-danger small mt-1">Cannot publish yet: {{ implode(' ', $errorsPreview) }}</div>
      @endif
    </div>
    <div class="d-flex gap-2">
      <form method="POST" action="{{ route('upwork.config.rulesets.draft.publish') }}" onsubmit="return confirm('Publish this draft as the next ruleset version?');">@csrf<button class="btn btn-success btn-sm" {{ $errorsPreview ? 'disabled' : '' }}>Publish as next version</button></form>
      <form method="POST" action="{{ route('upwork.config.rulesets.draft.discard') }}" onsubmit="return confirm('Discard all draft changes?');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Discard draft</button></form>
    </div>
  </div>

  <form method="POST" action="{{ route('upwork.config.rulesets.draft.update') }}" class="uw-card">
    @csrf @method('PUT')
    <div class="row g-4">
      <div class="col-lg-5">
        <div class="uw-h">Component weights</div>
        <div class="uw-help mb-2">Any numbers; they are scaled to total 100.</div>
        <table class="uw-table" id="weights">
          <thead><tr><th>Component</th><th>Weight</th><th>= of 100</th></tr></thead>
          <tbody>
            @foreach(\App\Models\UpworkRuleset::COMPONENTS as $key => [$column, $label])
              <tr><td>{{ $label }}</td>
                <td style="width:100px;"><input type="number" step="any" min="0" max="100" name="weights[{{ $key }}]" value="{{ old("weights.$key", $draft->weights[$key] ?? 0) }}" class="form-control form-control-sm uw-weight"></td>
                <td class="uw-norm" style="width:80px;">—</td></tr>
            @endforeach
          </tbody>
        </table>
        <div class="uw-help mt-1">Total entered: <span id="weight-total">—</span></div>

        <div class="uw-h mt-3">Thresholds</div>
        <div class="row g-2">
          <div class="col-6"><label class="form-label small mb-1">APPLY at or above</label><input type="number" step="any" name="thresholds[apply]" value="{{ old('thresholds.apply', $draft->thresholds['apply'] ?? 80) }}" class="form-control form-control-sm"></div>
          <div class="col-6"><label class="form-label small mb-1">REVIEW at or above</label><input type="number" step="any" name="thresholds[review]" value="{{ old('thresholds.review', $draft->thresholds['review'] ?? 70) }}" class="form-control form-control-sm"></div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="uw-h">Key settings</div>
        <div class="row g-2">
          @foreach($keySettings as $key => [$label, $type, $help])
            <div class="col-md-6">
              @if($type === 'bool')
                <div class="form-check form-switch mt-3"><input type="hidden" name="settings[{{ $key }}]" value="0">
                  <input class="form-check-input" type="checkbox" name="settings[{{ $key }}]" value="1" id="s_{{ $key }}" {{ old("settings.$key", $settings[$key] ?? false) ? 'checked' : '' }}>
                  <label class="form-check-label small" for="s_{{ $key }}">{{ $label }}</label></div>
              @else
                <label class="form-label small mb-1">{{ $label }}</label>
                <input type="number" step="any" min="0" name="settings[{{ $key }}]" value="{{ old("settings.$key", $settings[$key] ?? '') }}" class="form-control form-control-sm">
              @endif
              @if($help)<div class="uw-help">{{ $help }}</div>@endif
            </div>
          @endforeach
        </div>
        <div class="uw-h mt-3">Advanced settings (JSON)</div>
        <div class="uw-help mb-1">Optional overrides: <code>strategic_weights</code>, <code>strategic_platform_techs</code>, <code>win_probability</code> coefficients, <code>ev_hours_per_week</code>, engine baseline curves. Must be a JSON object.</div>
        <textarea name="advanced_settings" rows="7" class="form-control form-control-sm font-monospace">{{ old('advanced_settings', $advanced) }}</textarea>
        <label class="form-label small mt-2 mb-1">Notes for this version</label>
        <textarea name="notes" rows="2" class="form-control form-control-sm">{{ old('notes', $draft->notes) }}</textarea>
      </div>
    </div>
    <button class="btn btn-primary btn-sm mt-3">Save draft settings</button>
  </form>

  @include('upwork.config.rulesets.partials.rules-table', ['rules' => $draft->rules, 'describer' => $describer, 'editable' => true])
</div>
@endsection

@section('js_scripts')
<script>
(function () {
  var inputs = document.querySelectorAll('.uw-weight');
  function update() {
    var total = 0;
    inputs.forEach(function (i) { total += Math.max(0, parseFloat(i.value) || 0); });
    document.getElementById('weight-total').textContent = total;
    inputs.forEach(function (i) {
      var cell = i.closest('tr').querySelector('.uw-norm');
      cell.textContent = total > 0 ? (Math.max(0, parseFloat(i.value) || 0) * 100 / total).toFixed(1) : '—';
    });
  }
  inputs.forEach(function (i) { i.addEventListener('input', update); });
  update();
})();
</script>
@endsection
