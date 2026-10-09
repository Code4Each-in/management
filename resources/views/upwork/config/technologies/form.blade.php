@extends('layout')

@section('title', $technology->exists ? 'Edit Technology' : 'Add Technology')

@section('content')
@php
  $ro = !$canManage;
  $relationRows = old('relations', $relations->map(fn($r) => ['related_technology_id' => $r->related_technology_id, 'weight' => $r->weight, 'relation_type' => $r->relation_type])->all());
  $relationRows = array_merge($relationRows, $ro ? [] : array_fill(0, 3, ['related_technology_id' => null, 'weight' => 0.5, 'relation_type' => 'related']));
@endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <form method="POST" action="{{ $technology->exists ? route('upwork.config.technologies.update', $technology) : route('upwork.config.technologies.store') }}">
    @csrf @if($technology->exists) @method('PUT') @endif
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="uw-card">
          <div class="uw-h">{{ $technology->exists ? $technology->name : 'New technology' }}</div>
          <div class="row g-2">
            <div class="col-md-7"><label class="form-label small fw-semibold mb-1">Name</label>
              <input name="name" class="form-control form-control-sm" value="{{ old('name', $technology->name) }}" maxlength="100" required {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-5"><label class="form-label small fw-semibold mb-1">Slug</label>
              @if($technology->exists)
                <input class="form-control form-control-sm" value="{{ $technology->slug }}" disabled><div class="uw-help">Fixed: rules and the agent use it.</div>
              @else
                <input name="slug" class="form-control form-control-sm" value="{{ old('slug') }}" placeholder="auto from name" pattern="[a-z0-9-]+" {{ $ro ? 'disabled' : '' }}>
              @endif</div>
            <div class="col-md-6"><label class="form-label small fw-semibold mb-1">Capability tier</label>
              <select name="tier" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                @foreach(\App\Models\UpworkTechnology::TIERS as $tier => $label)<option value="{{ $tier }}" {{ old('tier', $technology->tier) === $tier ? 'selected' : '' }}>{{ $label }}</option>@endforeach
              </select></div>
            <div class="col-md-6"><label class="form-label small fw-semibold mb-1">Category</label>
              <input name="category" class="form-control form-control-sm" value="{{ old('category', $technology->category) }}" placeholder="backend, ai, domain, ..." {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-12"><label class="form-label small fw-semibold mb-1">Evidence <span class="uw-help">(required for PROVEN)</span></label>
              <textarea name="evidence" rows="3" class="form-control form-control-sm" placeholder="Portfolio project, agency profile, or codebase that proves this capability" {{ $ro ? 'disabled' : '' }}>{{ old('evidence', $technology->evidence) }}</textarea></div>
            <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Search weight</label>
              <input type="number" step="0.1" min="0" max="10" name="search_weight" class="form-control form-control-sm" value="{{ old('search_weight', $technology->search_weight) }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Scoring weight</label>
              <input type="number" step="0.1" min="0" max="10" name="scoring_weight" class="form-control form-control-sm" value="{{ old('scoring_weight', $technology->scoring_weight) }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-1">
              <input type="hidden" name="enabled" value="0">
              <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" {{ old('enabled', $technology->enabled) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}>
              <label class="form-check-label small" for="enabled">Enabled</label></div></div>
            <div class="col-12"><label class="form-label small fw-semibold mb-1">Notes</label>
              <textarea name="notes" rows="2" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}>{{ old('notes', $technology->notes) }}</textarea></div>
          </div>
        </div>
        @if($usedBy)
          <div class="uw-card"><div class="uw-h">Used by current scoring rules</div>
            <ul class="small mb-0 ps-3">@foreach($usedBy as $key => $name)<li>{{ $name }} <code>{{ $key }}</code></li>@endforeach</ul></div>
        @endif
      </div>

      <div class="col-lg-6">
        <div class="uw-card">
          <div class="uw-h">Aliases</div>
          @include('upwork.config.partials.list-input', ['name' => 'aliases', 'label' => 'Ways jobs mention it', 'help' => 'Matched as whole words, case-insensitive, plural allowed. The name is always an alias. Avoid common words.', 'values' => array_values(array_diff($aliases, [$technology->name])), 'disabled' => $ro])
        </div>
        <div class="uw-card">
          <div class="uw-h">Relationships</div>
          <div class="uw-help mb-2">Jobs mentioning this technology give partial credit to related ones (weight 0–1, followed up to 2 steps).</div>
          <table class="uw-table">
            <thead><tr><th>Related technology</th><th>Type</th><th>Weight</th></tr></thead>
            <tbody>
              @foreach($relationRows as $i => $row)
                <tr>
                  <td><select name="relations[{{ $i }}][related_technology_id]" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach($others as $o)<option value="{{ $o->id }}" {{ (int) ($row['related_technology_id'] ?? 0) === $o->id ? 'selected' : '' }}>{{ $o->name }} ({{ $o->tier }})</option>@endforeach
                  </select></td>
                  <td><select name="relations[{{ $i }}][relation_type]" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                    @foreach($relationTypes as $type => $label)<option value="{{ $type }}" {{ ($row['relation_type'] ?? 'related') === $type ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                  </select></td>
                  <td style="width:90px;"><input type="number" step="0.05" min="0" max="1" name="relations[{{ $i }}][weight]" value="{{ $row['weight'] ?? 0.5 }}" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}></td>
                </tr>
              @endforeach
            </tbody>
          </table>
          <div class="uw-help">Clear a row's technology to remove that relationship. Save to get more empty rows.</div>
        </div>
      </div>
    </div>
    <div class="d-flex gap-2">
      @if($canManage)<button class="btn btn-primary btn-sm">{{ $technology->exists ? 'Save technology' : 'Add technology' }}</button>@endif
      <a href="{{ route('upwork.config.technologies.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
  </form>
</div>
@endsection
