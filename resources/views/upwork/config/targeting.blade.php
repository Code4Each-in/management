@extends('layout')

@section('title', 'Upwork Targeting')

@section('content')
@php
  $ro = !$canManage;
  $rows = array_merge($individuals, [['key' => '', 'name' => '', 'summary' => '', 'technologies' => []]]);
  $components = \App\Models\UpworkRuleset::COMPONENTS;
  $num = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
@endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="alert alert-light border small py-2">
    Code4Each bids as the <strong>agency profile</strong> and as <strong>individual freelancer profiles</strong>. Every job is scored for both and
    labelled <em>agency</em>, <em>individual</em> or <em>either</em>; nothing is excluded for not being agency-only.
    These profiles are writing personas inside the PMS only. They are <strong>not linked to Upwork accounts</strong>: the PMS never selects,
    switches or submits from an account. Whoever sends the proposal picks the account on Upwork themselves.
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <form method="POST" action="{{ route('upwork.config.targeting.update') }}" class="uw-card">
        @csrf @method('PUT')
        <div class="uw-h">Individual freelancer profiles</div>
        <div class="uw-help mb-2">Verified facts only. AI proposal drafts written for an individual may cite only that person's summary and the technologies ticked here; the job's experience fit for the individual track uses the same list.</div>
        @foreach($rows as $i => $p)
          <div class="border rounded p-2 mb-2 {{ $p['key'] === '' ? 'bg-light' : '' }}">
            <div class="row g-2">
              <div class="col-md-5"><label class="form-label small fw-semibold mb-1">{{ $p['key'] === '' ? 'Add a profile: name' : 'Name' }}</label>
                <input type="text" name="profiles[{{ $i }}][name]" value="{{ old("profiles.$i.name", $p['name']) }}" maxlength="100" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}>
                <input type="hidden" name="profiles[{{ $i }}][key]" value="{{ $p['key'] }}"></div>
              <div class="col-md-7"><label class="form-label small fw-semibold mb-1">Verified technologies</label>
                <select name="profiles[{{ $i }}][technologies][]" multiple size="4" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
                  @foreach($technologies as $t)
                    <option value="{{ $t->slug }}" {{ in_array($t->slug, old("profiles.$i.technologies", $p['technologies']), true) ? 'selected' : '' }}>{{ $t->name }} ({{ $t->tier }})</option>
                  @endforeach
                </select></div>
              <div class="col-12"><label class="form-label small fw-semibold mb-1">Approved summary</label>
                <textarea name="profiles[{{ $i }}][summary]" rows="2" maxlength="4000" class="form-control form-control-sm" placeholder="Delivered project types, public portfolio links. No unverifiable claims." {{ $ro ? 'disabled' : '' }}>{{ old("profiles.$i.summary", $p['summary']) }}</textarea></div>
              @if($p['key'] !== '')
                <div class="col-12 d-flex justify-content-between">
                  <span class="uw-help">Stored as <code>individual:{{ $p['key'] }}</code></span>
                  <label class="small text-danger"><input type="checkbox" name="profiles[{{ $i }}][remove]" value="1" {{ $ro ? 'disabled' : '' }}> Remove</label>
                </div>
              @endif
            </div>
          </div>
        @endforeach
        @unless($ro)<button type="submit" class="btn btn-primary btn-sm">Save profiles</button>@endunless
      </form>

      <div class="uw-card">
        <div class="uw-h">Agency profile</div>
        <div class="small" style="white-space:pre-wrap;">{{ $agencyProfile !== '' ? $agencyProfile : '(not set)' }}</div>
        <div class="uw-help mt-1">Edited under <a href="{{ route('upwork.config.ai.edit') }}">AI → Approved agency profile</a>. Agency experience also uses PROVEN catalog technologies.</div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="uw-card">
        <div class="uw-h">Track weights &amp; preferences @if($ruleset)<span class="text-muted" style="text-transform:none;">(ruleset v{{ $ruleset->version }}{{ $customised ? ', customised' : ', defaults' }})</span>@endif</div>
        <table class="uw-table">
          <thead><tr><th>Component</th><th>Agency</th><th>Individual</th></tr></thead>
          <tbody>
            @foreach($components as $key => [$column, $label])
              <tr><td>{{ $label }}</td><td>{{ $num($weights['agency'][$key] ?? 0) }}</td><td>{{ $num($weights['individual'][$key] ?? 0) }}</td></tr>
            @endforeach
          </tbody>
        </table>
        <div class="uw-help mt-1">Weights are scaled to 100 per track. The agency track defaults to the ruleset's own weights, so its fit equals the Opportunity Score before suitability points.</div>
        <table class="uw-table mt-2">
          <thead><tr><th>Preference</th><th>Agency</th><th>Individual</th></tr></thead>
          <tbody>
            <tr><td>Preferred scopes</td><td>{{ implode(', ', $config['preferences']['agency']['scopes'] ?? []) }}</td><td>{{ implode(', ', $config['preferences']['individual']['scopes'] ?? []) }}</td></tr>
            <tr><td>Minimum hourly ($)</td><td>{{ $config['preferences']['agency']['min_hourly'] ?? '—' }}</td><td>{{ $config['preferences']['individual']['min_hourly'] ?? '—' }}</td></tr>
            <tr><td>Minimum fixed budget ($)</td><td>{{ $config['preferences']['agency']['min_fixed'] ?? '—' }}</td><td>{{ $config['preferences']['individual']['min_fixed'] ?? '—' }}</td></tr>
          </tbody>
        </table>
        <div class="uw-help mt-1">“Either” when the two track fits are within <strong>{{ $num($config['margin']) }}</strong> points. A job outside a profile's preferences is still scored and listed; it only loses points on that track.</div>
        <div class="uw-help mt-1">To change these, edit the Scoring draft's <em>Advanced settings</em> key <code>target_profiles</code> (e.g. <code>{"target_profiles": {"margin": 10, "weights": {"individual": {"competition": 15}}}}</code>) and publish it, so the change is versioned.</div>
        <div class="uw-help mt-1">The agency-mode hard filter (<code>agency_mode</code>) is separate and unchanged by targeting.</div>
      </div>

      <div class="uw-card">
        <div class="uw-h">Classified jobs</div>
        @foreach(\App\Services\Upwork\Targeting\TargetProfiles::TRACKS as $track => $label)
          <div class="d-flex justify-content-between small"><a href="{{ route('upwork.opportunities.index', ['target' => $track, 'show' => 'all']) }}">{{ $label }}</a><strong>{{ $counts[$track] ?? 0 }}</strong></div>
        @endforeach
        <div class="uw-help mt-1">Jobs scored before targeting existed have no label until they are re-seen or backfilled (<code>php artisan upwork:classify-targets</code>).</div>
      </div>
    </div>
  </div>
</div>
@endsection
