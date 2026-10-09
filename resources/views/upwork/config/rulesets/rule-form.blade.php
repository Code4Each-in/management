@extends('layout')

@section('title', $rule->exists ? 'Edit Scoring Rule' : 'New Scoring Rule')

@section('content')
@php $type = old('type', $rule->type); @endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <form method="POST" action="{{ $rule->exists ? route('upwork.config.rulesets.rules.update', $rule) : route('upwork.config.rulesets.rules.store') }}">
    @csrf @if($rule->exists) @method('PUT') @endif
    <div class="row g-3">
      <div class="col-lg-7">
        <div class="uw-card">
          <div class="uw-h">{{ $rule->exists ? 'Edit rule' : 'New rule' }} · draft{{ $draft->basedOn ? ' based on v' . $draft->basedOn->version : '' }}</div>
          <div class="row g-2">
            <div class="col-md-5"><label class="form-label small fw-semibold mb-1">Key</label>
              @if($rule->exists)
                <input class="form-control form-control-sm" value="{{ $rule->key }}" disabled><div class="uw-help">Fixed: identifies the rule across versions.</div>
              @else
                <input name="key" class="form-control form-control-sm" value="{{ old('key') }}" placeholder="e.g. tech_kendo" pattern="[a-z0-9_]{2,64}" required>
              @endif</div>
            <div class="col-md-7"><label class="form-label small fw-semibold mb-1">Name</label>
              <input name="name" class="form-control form-control-sm" value="{{ old('name', $rule->name) }}" maxlength="150" required></div>
            <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Type</label>
              <select name="type" id="rule-type" class="form-select form-select-sm">
                @foreach(\App\Models\UpworkRule::TYPES as $v => $l)<option value="{{ $v }}" {{ $type === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
              </select></div>
            <div class="col-md-5 uw-type uw-type-score"><label class="form-label small fw-semibold mb-1">Component</label>
              <select name="component" class="form-select form-select-sm">
                @foreach(\App\Models\UpworkRuleset::COMPONENTS as $key => [$col, $label])<option value="{{ $key }}" {{ old('component', $rule->component) === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach
              </select></div>
            <div class="col-md-3 uw-type uw-type-score"><label class="form-label small fw-semibold mb-1">Points</label>
              <input type="number" step="any" min="-100" max="100" name="points" class="form-control form-control-sm" value="{{ old('points', $rule->points) }}">
              <div class="uw-help">Negative = penalty</div></div>
            <div class="col-md-8 uw-type uw-type-classification"><label class="form-label small fw-semibold mb-1">Assigns classification</label>
              <select name="classification" class="form-select form-select-sm">
                @foreach($catalog as $c)<option value="{{ $c->code }}" {{ old('classification', $rule->classification) === $c->code ? 'selected' : '' }}>{{ $c->label }} ({{ $c->code }})</option>@endforeach
              </select>
              <div class="uw-help">New labels are added under Classifications.</div></div>
            <div class="col-12"><label class="form-label small fw-semibold mb-1">Reason <span class="uw-help">(shown in score explanations; required for hard filters)</span></label>
              <input name="reason" class="form-control form-control-sm" value="{{ old('reason', $rule->reason) }}" maxlength="255"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Order</label>
              <input type="number" name="sort" class="form-control form-control-sm" value="{{ old('sort', $rule->sort) }}"></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-1">
              <input type="hidden" name="enabled" value="0"><input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" {{ old('enabled', $rule->enabled) ? 'checked' : '' }}>
              <label class="form-check-label small" for="enabled">Enabled</label></div></div>
          </div>
        </div>

        <div class="uw-card">
          <div class="uw-h">When does it apply?</div>
          <div class="uw-help mb-2">Structured JSON, never code: a leaf <code>{"field": ..., "op": ..., "value": ...}</code>, or groups <code>{"all": [...]}</code>, <code>{"any": [...]}</code>, <code>{"not": {...}}</code>. A value can be <code>{"setting": "min_hourly"}</code> to use a ruleset setting.</div>
          <textarea name="conditions" id="conditions" rows="12" class="form-control form-control-sm font-monospace" required>{{ $conditionsJson }}</textarea>
          @if($preview)<div class="small mt-2"><strong>Reads as:</strong> {{ $preview }}</div>@endif

          <div class="border rounded p-2 mt-3 bg-light">
            <div class="small fw-semibold mb-1">Quick condition builder</div>
            <div class="row g-1 align-items-end">
              <div class="col-md-4"><select id="qb-field" class="form-select form-select-sm">
                <option value="">(technology operators: no field)</option>
                @foreach($fields as $field)<option value="{{ $field }}">{{ $fieldLabels[$field] ?? $field }} — {{ $field }}</option>@endforeach
              </select></div>
              <div class="col-md-3"><select id="qb-op" class="form-select form-select-sm">
                @foreach($operators as $group => $ops)<optgroup label="{{ $group }}">@foreach($ops as $op)<option value="{{ $op }}">{{ $op }}</option>@endforeach</optgroup>@endforeach
              </select></div>
              <div class="col-md-3"><input id="qb-value" class="form-control form-control-sm" placeholder="value, or a,b,c for lists"></div>
              <div class="col-md-2"><button type="button" id="qb-add" class="btn btn-sm btn-outline-primary w-100">Add</button></div>
            </div>
            <div class="uw-help mt-1">Adds the condition; if one already exists, both are combined with "all" (AND). Use "setting:min_hourly" as the value to reference a setting.</div>
          </div>
        </div>
        <button class="btn btn-primary btn-sm">{{ $rule->exists ? 'Save rule' : 'Add rule to draft' }}</button>
        <a href="{{ route('upwork.config.rulesets.draft.edit') }}" class="btn btn-outline-secondary btn-sm">Back to draft</a>
      </div>

      <div class="col-lg-5">
        <div class="uw-card">
          <div class="uw-h">Technologies (for has_any_tech / has_all_tech)</div>
          <div style="max-height:220px; overflow:auto;" class="small">
            @foreach($technologies as $t)<div><code>{{ $t->slug }}</code> {{ $t->name }} <span class="uw-tier uw-tier-{{ $t->tier }}">{{ strtoupper($t->tier) }}</span></div>@endforeach
          </div>
          <div class="uw-help mt-1">Tiers for has_tier / tier_count_gte: proven, adjacent, emerging, excluded.</div>
        </div>
        <div class="uw-card">
          <div class="uw-h">Settings in this draft</div>
          <div class="small">@forelse($settingKeys as $key)<code>{{ $key }}</code> @empty — @endforelse</div>
        </div>
        <div class="uw-card">
          <div class="uw-h">Notes</div>
          <ul class="small ps-3 mb-0">
            <li>Unknown values never match a comparison: a missing hire rate is neither "&lt; 20" nor "&ge; 20".</li>
            <li><code>scores.*</code> component fields are % of that component's maximum; <code>scores.total</code> and <code>scores.strategic</code> are 0–100. They are available to classification rules.</li>
            <li>Score bonuses are capped at the component's weight before penalties apply.</li>
            <li>Hard filters run before scoring; a match rejects the job (it is stored, not dropped).</li>
          </ul>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

@section('js_scripts')
<script>
(function () {
  var type = document.getElementById('rule-type');
  function showType() {
    document.querySelectorAll('.uw-type').forEach(function (el) {
      el.style.display = el.classList.contains('uw-type-' + type.value) ? '' : 'none';
    });
  }
  type.addEventListener('change', showType); showType();

  var techOps = ['has_any_tech', 'has_all_tech', 'has_tier', 'tier_count_gte'];
  var unary = ['is_null', 'not_null', 'is_true', 'is_false'];
  var listOps = ['in', 'not_in', 'contains_any', 'contains_all', 'not_contains_any', 'has_any_tech', 'has_all_tech'];
  function parseScalar(v) { return v !== '' && !isNaN(v) ? Number(v) : v; }

  document.getElementById('qb-add').addEventListener('click', function () {
    var field = document.getElementById('qb-field').value, op = document.getElementById('qb-op').value;
    var raw = document.getElementById('qb-value').value.trim(), leaf = { op: op };
    if (techOps.indexOf(op) === -1) {
      if (!field) { alert('Choose a field for this operator.'); return; }
      leaf = { field: field, op: op };
    }
    if (unary.indexOf(op) === -1) {
      if (raw.indexOf('setting:') === 0) leaf.value = { setting: raw.slice(8) };
      else if (op === 'between') leaf.value = raw.split(',').map(function (x) { return parseScalar(x.trim()); });
      else if (op === 'tier_count_gte') { var p = raw.split(','); leaf.value = { tier: (p[0] || '').trim(), count: parseInt(p[1] || '1', 10) }; }
      else if (listOps.indexOf(op) !== -1) leaf.value = raw.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
      else leaf.value = parseScalar(raw);
    }
    var box = document.getElementById('conditions'), current = null;
    try { current = box.value.trim() ? JSON.parse(box.value) : null; } catch (e) { alert('The JSON above is not valid yet; fix it before adding.'); return; }
    var next = !current ? leaf : (current.all ? { all: current.all.concat([leaf]) } : { all: [current, leaf] });
    box.value = JSON.stringify(next, null, 2);
  });
})();
</script>
@endsection
