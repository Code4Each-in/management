@extends('layout')

@section('title', $profile->exists ? 'Edit Search Profile' : 'New Search Profile')

@section('content')
@php $f = old('filters', $profile->upwork_filters ?? []); $ro = !$canManage; @endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <form method="POST" action="{{ $profile->exists ? route('upwork.config.profiles.update', $profile) : route('upwork.config.profiles.store') }}">
    @csrf @if($profile->exists) @method('PUT') @endif
    <div class="row g-3">
      <div class="col-lg-7">
        <div class="uw-card">
          <div class="uw-h">{{ $profile->exists ? "{$profile->name} · version {$profile->version}" : 'New search profile' }}</div>
          <div class="row g-2">
            <div class="col-md-8"><label class="form-label small fw-semibold mb-1">Name</label>
              <input name="name" class="form-control form-control-sm" value="{{ old('name', $profile->name) }}" maxlength="100" required {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Category</label>
              <input name="category" class="form-control form-control-sm" value="{{ old('category', $profile->category) }}" maxlength="50" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-12"><label class="form-label small fw-semibold mb-1">Description</label>
              <textarea name="description" rows="2" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}>{{ old('description', $profile->description) }}</textarea></div>
            <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Priority</label>
              <input type="number" name="priority" min="1" max="1000" class="form-control form-control-sm" value="{{ old('priority', $profile->priority) }}" {{ $ro ? 'disabled' : '' }}>
              <div class="uw-help">Lower runs first</div></div>
            <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Every (minutes)</label>
              <input type="number" name="frequency_minutes" min="10" max="1440" class="form-control form-control-sm" value="{{ old('frequency_minutes', $profile->frequency_minutes) }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Tier</label>
              <select name="tier" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                @foreach(\App\Models\UpworkSearchProfile::TIERS as $key => $label)
                  <option value="{{ $key }}" {{ old('tier', $profile->tier) === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
              <div class="uw-help">Hot runs first and most often</div></div>
            <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Searches per day (max)</label>
              <input type="number" name="daily_call_budget" min="0" max="500" class="form-control form-control-sm" value="{{ old('daily_call_budget', $profile->daily_call_budget) }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Results per search</label>
              <input type="number" name="max_results" min="1" max="30" class="form-control form-control-sm" value="{{ old('max_results', $profile->max_results) }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-1">
              <input type="hidden" name="search_related" value="0">
              <input class="form-check-input" type="checkbox" name="search_related" value="1" id="search_related" {{ old('search_related', $profile->search_related) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}>
              <label class="form-check-label small" for="search_related">Also search related technologies</label></div></div>
            <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Minimum score</label>
              <input type="number" step="any" name="min_score" min="0" max="100" class="form-control form-control-sm" value="{{ old('min_score', $profile->min_score) }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-1">
              <input type="hidden" name="enabled" value="0">
              <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" {{ old('enabled', $profile->enabled) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}>
              <label class="form-check-label small" for="enabled">Enabled</label></div></div>
          </div>
        </div>

        <div class="uw-card">
          <div class="uw-h">What to search for</div>
          @foreach($listFields as $field => [$label, $help])
            @include('upwork.config.partials.list-input', ['name' => $field, 'label' => $label, 'help' => $help, 'values' => $profile->{$field}, 'disabled' => $ro])
          @endforeach
          <label class="form-label small fw-semibold mb-1">Notes</label>
          <textarea name="notes" rows="2" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}>{{ old('notes', $profile->notes) }}</textarea>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="uw-card">
          <div class="uw-h">Upwork search filters</div>
          <div class="uw-help mb-2">Passed to the read-only find_jobs search.</div>
          <div class="row g-2">
            <div class="col-6"><label class="form-label small mb-1">Job type</label>
              <select name="filters[job_type]" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                <option value="">Any</option>
                @foreach(['hourly' => 'Hourly', 'fixed' => 'Fixed'] as $v => $l)<option value="{{ $v }}" {{ ($f['job_type'] ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
              </select></div>
            <div class="col-6"><label class="form-label small mb-1">Experience</label>
              <select name="filters[experience_level]" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                <option value="">Any</option>
                @foreach(['entry_level' => 'Entry', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $v => $l)<option value="{{ $v }}" {{ ($f['experience_level'] ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
              </select></div>
            <div class="col-6"><label class="form-label small mb-1">Min hourly rate ($)</label>
              <input type="number" step="any" name="filters[rate_min]" class="form-control form-control-sm" value="{{ $f['rate_min'] ?? '' }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-6"><label class="form-label small mb-1">Min fixed budget ($)</label>
              <input type="number" step="any" name="filters[budget_min]" class="form-control form-control-sm" value="{{ $f['budget_min'] ?? '' }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-6"><label class="form-label small mb-1">Max proposals</label>
              <input type="number" name="filters[proposals_max]" class="form-control form-control-sm" value="{{ $f['proposals_max'] ?? '' }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-6"><label class="form-label small mb-1">Workload</label>
              <select name="filters[workload]" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                <option value="">Any</option>
                @foreach(['full_time' => 'Full-time', 'part_time' => 'Part-time', 'as_needed' => 'As needed'] as $v => $l)<option value="{{ $v }}" {{ ($f['workload'] ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
              </select></div>
            <div class="col-6"><label class="form-label small mb-1">Sort</label>
              <select name="filters[sort]" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                @foreach(['recency' => 'Newest first', 'client_total_charge' => 'Client spend', 'client_rating' => 'Client rating'] as $v => $l)<option value="{{ $v }}" {{ ($f['sort'] ?? 'recency') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
              </select></div>
            <div class="col-6"><label class="form-label small mb-1">Pages per search</label>
              <input type="number" name="filters[pages]" min="1" max="{{ \App\Services\Upwork\Discovery\QueryPlanner::MAX_PAGES }}" class="form-control form-control-sm" value="{{ $f['pages'] ?? 1 }}" {{ $ro ? 'disabled' : '' }}></div>
            <div class="col-12"><div class="form-check">
              <input type="hidden" name="filters[verified_payment_only]" value="0">
              <input class="form-check-input" type="checkbox" name="filters[verified_payment_only]" value="1" id="vpo" {{ ($f['verified_payment_only'] ?? false) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}>
              <label class="form-check-label small" for="vpo">Verified payment only</label></div></div>
          </div>
        </div>

        <div class="uw-card">
          <div class="uw-h">Related technologies</div>
          <div class="uw-help mb-2">Their names are added as extra searches (EXCLUDED ones are never searched).</div>
          <div style="max-height:320px; overflow:auto;">
            @foreach($technologies->groupBy('tier') as $tier => $items)
              <div class="mt-1 mb-1"><span class="uw-tier uw-tier-{{ $tier }}">{{ strtoupper($tier) }}</span></div>
              @foreach($items as $t)
                <div class="form-check form-check-inline small">
                  <input class="form-check-input" type="checkbox" name="technologies[]" value="{{ $t->id }}" id="t{{ $t->id }}" {{ in_array($t->id, old('technologies', $selected)) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}>
                  <label class="form-check-label" for="t{{ $t->id }}">{{ $t->name }}</label>
                </div>
              @endforeach
            @endforeach
          </div>
        </div>

        @if($preview)
          <div class="uw-card">
            <div class="uw-h">Searches this profile runs</div>
            <div class="uw-help mb-1">In rotation, a few per run.</div>
            <ol class="small mb-0 ps-3">@foreach($preview as $term)<li><code>{{ $term['type'] }}</code> {{ $term['text'] }}</li>@endforeach</ol>
          </div>
        @endif
      </div>
    </div>

    <div class="d-flex gap-2">
      @if($canManage)<button class="btn btn-primary btn-sm">{{ $profile->exists ? 'Save profile' : 'Create profile' }}</button>@endif
      <a href="{{ route('upwork.config.profiles.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
  </form>
</div>
@endsection
