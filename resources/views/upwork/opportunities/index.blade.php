@extends('layout')

@section('title', 'Upwork Opportunities')
@section('show_title', 'false')

@section('content')
@php
    $M = \App\Models\UpworkOpportunity::class;
    $VIEWS = \App\Services\Upwork\RadarService::VIEWS;
    $fmt = fn($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
    $highScore = $stats['high_score_min'] ?? config('upwork.thresholds.apply', 80);
    $list = fn(array $q) => route('upwork.opportunities.index', $q);
    $showAll = !$view && ($filters['show'] ?? null) === 'all';

    // View tabs: the review workflow up front, everything else under "More".
    $primaryViews = ['reminder_queue', 'overdue', 'eligible', 'approved', 'proposal_drafts', 'ready_to_submit', 'archived'];
    $moreViews = array_diff(array_keys($VIEWS), $primaryViews);
    $viewHelp = [
        'reminder_queue' => 'New APPLY/REVIEW jobs with full details: exactly the jobs review reminders cover',
        'overdue' => 'Awaiting review and past the 30-minute deadline',
        'snoozed' => 'Reminders paused for now; the deadline is unchanged',
        'eligible' => 'New APPLY/REVIEW jobs India-based freelancers can apply to (unclear locations included and flagged)',
        'location_restricted' => 'India-based applicants are not eligible: held at SKIP, no reminders, no proposals',
        'location_unknown' => 'Location text could not be read with confidence; kept in review',
        'archived' => 'Out of the queue; never reminded. Restore from this tab.',
        'all_jobs' => 'Every stored job, archived included',
    ];

    $locationOptions = ['eligible' => 'Eligible', 'restricted' => 'Skipped (restricted)', 'unknown' => 'Unclear', 'unclassified' => 'Not checked'];
    $targetOptions = ['agency' => 'Fits agency (incl. either)', 'individual' => 'Fits individual (incl. either)', 'both' => 'Fits either'];
    $bidOptions = ['agency' => 'Bid as agency', 'individual' => 'Bid as individual'];
    $sortOptions = ['score' => 'Score', 'posted_at' => 'Posted date', 'latest' => 'Recently added', 'expected_value' => 'Expected value (est.)',
        'strategic' => 'Strategic value', 'agency_fit' => 'Agency fit', 'individual_fit' => 'Individual fit'];
    if ($view === 'archived') { $sortOptions['archived_at'] = 'Archive date'; }

    // Active filters as removable chips (the view tab and sorting are shown elsewhere).
    $chipLabels = [
        'search' => fn($v) => 'Search: “' . \Illuminate\Support\Str::limit($v, 40) . '”',
        'status' => fn($v) => 'Status: ' . ($M::STATUSES[$v] ?? $v),
        'recommendation' => fn($v) => 'Recommendation: ' . ($M::RECOMMENDATIONS[$v] ?? $v),
        'min_score' => fn($v) => 'Score ≥ ' . $fmt($v),
        'location' => fn($v) => 'Location: ' . ($locationOptions[$v] ?? $v),
        'class' => fn($v) => 'Classification: ' . (optional(collect($catalog)->firstWhere('code', $v))->label ?? $v),
        'target' => fn($v) => 'Best fit: ' . ($targetOptions[$v] ?? $v),
        'bid_as' => fn($v) => $bidOptions[$v] ?? $v,
        'show' => fn($v) => 'Including hidden jobs',
    ];
    $chips = [];
    foreach ($chipLabels as $key => $label) {
        if (isset($filters[$key]) && (string) $filters[$key] !== '' && ($key !== 'show' || $showAll)) {
            $chips[] = [$label($filters[$key]), $list(\Illuminate\Support\Arr::except($filters, [$key]))];
        }
    }
    $resetUrl = $list(array_filter(['view' => $view]));
    $advancedCount = count(array_filter(\Illuminate\Support\Arr::only($filters, ['class', 'target', 'bid_as'])));
    $canBulk = auth()->user()->can('archive', $M) && $opportunities->count();
    $archivedView = $view === 'archived';
@endphp
@include('upwork.partials.styles')
<div class="uw-wrap">

  <div class="uw-head">
    <div>
      <h1 class="uw-title">Upwork Opportunities</h1>
      <p class="uw-lede">Review scored jobs, approve the ones worth a proposal and track them to an outcome. Nothing on this page contacts Upwork.</p>
    </div>
    <div class="uw-actions">
      <a href="{{ route('upwork.radar') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-broadcast"></i> Radar</a>
      <a href="{{ route('upwork.clients.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person-badge"></i> Client intelligence</a>
      @can('viewConfig', $M)<a href="{{ route('upwork.config.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i> Configuration</a>@endcan
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success border-0 small py-2 px-3 mb-3" role="status" style="font-weight:600;">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 small py-2 px-3 mb-3" role="alert" style="font-weight:600;">{{ session('error') }}</div>
  @endif
  @if(session('bulk_failures'))
    <div class="alert alert-warning border-0 small py-2 px-3 mb-3" role="alert">{{ session('bulk_failures') }}</div>
  @endif

  {{-- List views (review queue first) --}}
  <nav class="uw-views" aria-label="Opportunity views">
    <div class="uw-views-scroll">
      <a class="uw-view {{ $view ? '' : 'active' }}" href="{{ $list([]) }}" @unless($view) aria-current="page" @endunless title="Active jobs worth a look; skipped and excluded noise hidden">All relevant</a>
      @foreach(array_merge($primaryViews, $view && in_array($view, $moreViews, true) ? [$view] : []) as $name)
        <a class="uw-view {{ $view === $name ? 'active' : '' }}" href="{{ $list(['view' => $name]) }}" @if($view === $name) aria-current="page" @endif title="{{ $viewHelp[$name] ?? '' }}">
          {{ $VIEWS[$name] }} <span class="uw-count {{ $name === 'overdue' && $viewCounts[$name] ? 'uw-count-alert' : '' }}">{{ $viewCounts[$name] }}</span>
        </a>
      @endforeach
    </div>
    <div class="dropdown">
      <button class="uw-view dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">More views</button>
      <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.84rem;">
        @foreach($moreViews as $name)
          <li><a class="dropdown-item d-flex justify-content-between gap-3 {{ $view === $name ? 'active' : '' }}" href="{{ $list(['view' => $name]) }}" title="{{ $viewHelp[$name] ?? '' }}">
            {{ $VIEWS[$name] }} <span class="text-muted">{{ $viewCounts[$name] }}</span></a></li>
        @endforeach
      </ul>
    </div>
  </nav>

  {{-- Summary: pipeline and location counts not already on a tab. Each number opens the matching list. --}}
  <div class="uw-metrics">
    <span class="uw-metrics-label">Pipeline</span>
    @foreach([
        ['New', $stats['new'], '#0d6efd', ['status' => 'new'], 'Untouched jobs worth a look (skipped noise excluded)'],
        [$fmt($highScore) . '+ score', $stats['high_score'], '#198754', ['min_score' => $highScore], 'Jobs at or above the APPLY threshold'],
        ['Proposal draft', $stats['proposal_draft'], '#b58105', ['status' => 'proposal_draft'], null],
        ['Submitted', $stats['submitted'], '#3730a3', ['status' => 'submitted'], null],
        ['Client response', $stats['client_response'], '#055160', ['status' => 'client_response'], null],
        ['Won', $stats['won'], '#198754', ['status' => 'won'], null],
        ['Lost', $stats['lost'], '#6c757d', ['status' => 'lost'], null],
    ] as [$label, $value, $color, $query, $title])
      <a class="uw-metric" href="{{ $list($query) }}" @if($title) title="{{ $title }}" @endif>{{ $label }}<strong style="color:{{ $color }};">{{ $value }}</strong></a>
    @endforeach
    <span class="uw-sep" aria-hidden="true"></span>
    <span class="uw-metrics-label" title="Can India-based freelancers apply? Active jobs only.">Location</span>
    @foreach([
        ['Eligible', $stats['location_eligible'], '#0f5132', ['location' => 'eligible', 'show' => 'all'], 'India allowed, or no restriction given'],
        ['Skipped: location', $stats['location_restricted'], '#842029', ['view' => 'location_restricted'], $viewHelp['location_restricted']],
        ['Unclear', $stats['location_unknown'], '#92400e', ['view' => 'location_unknown'], $viewHelp['location_unknown']],
        ['Not checked', $stats['location_unclassified'], '#6b7280', ['location' => 'unclassified', 'show' => 'all'], 'Stored before location rules existed; run the backfill'],
    ] as [$label, $value, $color, $query, $title])
      <a class="uw-metric" href="{{ $list($query) }}" title="{{ $title }}">{{ $label }}<strong style="color:{{ $color }};">{{ $value }}</strong></a>
    @endforeach
  </div>

  <div class="uw-panel">

    {{-- Filters: applied together with "Apply filters"; the server validates every value. --}}
    <form method="GET" action="{{ route('upwork.opportunities.index') }}" class="uw-filters" role="search" aria-label="Filter opportunities">
      @if($view)<input type="hidden" name="view" value="{{ $view }}">@endif
      @if($showAll)<input type="hidden" name="show" value="all">@endif
      @isset($filters['sort'])<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endisset

      <div class="uw-fields">
        <div class="uw-field uw-field-search">
          <label for="f-search">Search</label>
          <input type="search" id="f-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, description, country, job or client ID" class="form-control form-control-sm">
        </div>
        <div class="uw-field uw-field-half">
          <label for="f-status">Status</label>
          <select id="f-status" name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach($M::STATUSES as $value => $label)
              <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="uw-field uw-field-half">
          <label for="f-rec">Recommendation</label>
          <select id="f-rec" name="recommendation" class="form-select form-select-sm">
            <option value="">All recommendations</option>
            @foreach($M::RECOMMENDATIONS as $value => $label)
              <option value="{{ $value }}" {{ ($filters['recommendation'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="uw-field uw-field-xs uw-field-half">
          <label for="f-score">Min score</label>
          <input type="number" id="f-score" name="min_score" min="0" max="100" step="1" value="{{ $filters['min_score'] ?? '' }}" placeholder="0–100" class="form-control form-control-sm">
        </div>
        <div class="uw-field uw-field-half">
          <label for="f-location" title="Can India-based freelancers apply?">Location eligibility</label>
          <select id="f-location" name="location" class="form-select form-select-sm">
            <option value="">Any location</option>
            @foreach($locationOptions as $value => $label)
              <option value="{{ $value }}" {{ ($filters['location'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="uw-fields-actions">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#uw-advanced"
                  aria-expanded="{{ $advancedCount ? 'true' : 'false' }}" aria-controls="uw-advanced">
            <i class="bi bi-sliders"></i> Advanced @if($advancedCount)<span class="badge bg-primary">{{ $advancedCount }}</span>@endif
          </button>
          <a href="{{ $resetUrl }}" class="btn btn-sm btn-outline-secondary">Reset filters</a>
          <button type="submit" class="btn btn-sm btn-primary">Apply filters</button>
        </div>
      </div>

      <div class="collapse {{ $advancedCount ? 'show' : '' }}" id="uw-advanced">
        <div class="uw-fields uw-advanced">
          <div class="uw-field">
            <label for="f-class">Classification</label>
            <select id="f-class" name="class" class="form-select form-select-sm">
              <option value="">All classifications</option>
              @foreach(collect($catalog)->where('enabled', true)->groupBy('kind') as $kind => $items)
                <optgroup label="{{ $kind === 'archetype' ? 'Archetypes' : 'Categories' }}">
                  @foreach($items as $item)
                    <option value="{{ $item->code }}" {{ ($filters['class'] ?? '') === $item->code ? 'selected' : '' }}>{{ $item->label }}</option>
                  @endforeach
                </optgroup>
              @endforeach
            </select>
          </div>
          <div class="uw-field">
            <label for="f-target" title="Best-fit profile from scoring. Agency / Individual include jobs suitable for either.">Best-fit profile</label>
            <select id="f-target" name="target" class="form-select form-select-sm">
              <option value="">All profiles</option>
              @foreach($targetOptions as $value => $label)
                <option value="{{ $value }}" {{ ($filters['target'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="uw-field">
            <label for="f-bid" title="Profile chosen by the reviewer">Bid profile</label>
            <select id="f-bid" name="bid_as" class="form-select form-select-sm">
              <option value="">Any bid profile</option>
              @foreach($bidOptions as $value => $label)
                <option value="{{ $value }}" {{ ($filters['bid_as'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      @if($chips)
        <div class="uw-chips" aria-label="Active filters">
          <span class="text-muted">Active filters:</span>
          @foreach($chips as [$label, $url])
            <a href="{{ $url }}" class="uw-chip" title="Remove this filter">{{ $label }} <i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Remove</span></a>
          @endforeach
          <a href="{{ $resetUrl }}" class="ms-1">Clear all</a>
        </div>
      @endif
    </form>

    {{-- Result count, hidden-noise toggle, sorting --}}
    <div class="uw-toolbar">
      <span><strong>{{ $opportunities->total() }}</strong> {{ $opportunities->total() === 1 ? 'opportunity' : 'opportunities' }}@if($view) in <strong>{{ $VIEWS[$view] }}</strong>@endif</span>
      @if(!$view && !$showAll && ($stats['hidden'] ?? 0) > 0)
        <a href="{{ $list(array_merge($filters, ['show' => 'all'])) }}" class="text-muted" title="Untouched jobs that were skipped, hard-rejected or excluded by every search profile">Show all ({{ $stats['hidden'] }} hidden)</a>
      @endif
      <form method="GET" action="{{ route('upwork.opportunities.index') }}" class="d-flex align-items-center gap-2 ms-auto">
        @foreach(\Illuminate\Support\Arr::except($filters, ['sort']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
        <label for="f-sort" class="text-muted mb-0">Sort by</label>
        <select id="f-sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
          @foreach($sortOptions as $value => $label)
            <option value="{{ $value }}" {{ $sort === $value ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-sm btn-outline-secondary">Sort</button></noscript>
      </form>
    </div>

    {{-- Bulk selection: ticked rows on this page, or every job matching this filter (the server re-applies the same filter). --}}
    @if($canBulk)
      <form id="bulk-form" method="POST" action="{{ route('upwork.opportunities.bulk') }}" class="uw-bulk" data-action-label="{{ $archivedView ? 'Restore' : 'Archive' }}"
            data-matching="{{ $matching }}">
        @csrf
        <input type="hidden" name="action" value="{{ $archivedView ? 'restore' : 'archive' }}">
        <input type="hidden" name="mode" value="ids" id="bulk-mode">
        <input type="hidden" name="expected" value="{{ $matching }}">
        @foreach($filterParams as $key => $value)
          <input type="hidden" name="filter[{{ $key }}]" value="{{ $value }}">
        @endforeach
        <span id="bulk-count" class="fw-semibold" aria-live="polite">No jobs selected</span>
        <button type="button" id="bulk-select-page" class="uw-linkbtn">Select current page ({{ $opportunities->count() }})</button>
        <button type="button" id="bulk-all-matching" class="uw-linkbtn d-none">Select all {{ $matching }} jobs matching this filter</button>
        <button type="button" id="bulk-clear" class="uw-linkbtn d-none">Clear selection</button>
        <span class="text-muted d-none d-lg-inline">Selection clears when you change page, filters or page size.</span>
        <button type="submit" id="bulk-submit" class="btn btn-sm {{ $archivedView ? 'btn-outline-primary' : 'btn-outline-secondary' }} ms-auto" disabled>
          <i class="bi {{ $archivedView ? 'bi-arrow-counterclockwise' : 'bi-archive' }}"></i> {{ $archivedView ? 'Restore Selected' : 'Archive Selected' }}
        </button>
      </form>
    @endif

    <div class="table-responsive">
      <table class="uw-list">
        <thead>
          <tr>
            <th style="width:1%;">@if($canBulk)<input type="checkbox" class="form-check-input" id="bulk-page" title="Select all on this page" aria-label="Select all on this page">@endif</th>
            <th>Score</th>
            <th>Job</th>
            <th>Budget / Rate</th>
            <th>Client</th>
            <th title="Proposals, invites the client already sent (full job details only) and connects">Competition</th>
            <th>Posted</th>
            <th>Status</th>
            <th class="uw-r">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($opportunities as $opportunity)
          @php
            $scoreColor = $opportunity->score === null ? '#9ca3af'
                : ($opportunity->score >= config('upwork.thresholds.apply', 80) ? '#198754'
                : ($opportunity->score >= config('upwork.thresholds.review', 70) ? '#b58105' : '#6b7280'));
            $inv = $opportunity->score_explanation['invites'] ?? null;
            $scoreTitle = $opportunity->score === null ? 'Not scored yet'
                : ($opportunity->invite_penalty && isset($inv['base_score']) ? 'Base ' . $fmt($inv['base_score']) . ' − ' . $fmt($opportunity->invite_penalty) . ' invite penalty. ' : '') . 'Open the job for the full score breakdown.';
            $otherFilters = array_diff((array) $opportunity->hard_filters, [\App\Services\Upwork\Eligibility\LocationEligibility::SKIP_REASON]);
          @endphp
          <tr>
            <td>@can('archive', $opportunity)<input type="checkbox" name="ids[]" value="{{ $opportunity->id }}" form="bulk-form" class="form-check-input js-bulk-row" aria-label="Select {{ $opportunity->title }}">@endcan</td>
            <td class="uw-nowrap">
              <div class="uw-stack">
                <a href="{{ route('upwork.opportunities.show', $opportunity) }}#invite-penalty" class="uw-score d-block text-decoration-none" style="color:{{ $scoreColor }};" title="{{ $scoreTitle }}">{{ $opportunity->score !== null ? $fmt($opportunity->score) : '—' }}</a>
                <div>@include('upwork.opportunities.partials.recommendation-badge')</div>
                @if($opportunity->aiAnalysis)
                  <div><span class="badge" style="background:#f3e8ff; color:#6b21a8; font-size:0.65rem; font-weight:600;" title="AI fit (estimate), {{ strtoupper($opportunity->aiAnalysis->recommendation) }}, confidence {{ number_format($opportunity->aiAnalysis->confidence * 100) }}%">AI {{ $opportunity->aiAnalysis->fit_score }}</span></div>
                @elseif(in_array($opportunity->ai_status, ['queued', 'failed', 'blocked'], true))
                  <div><span class="badge bg-light text-muted border" style="font-size:0.6rem;">AI {{ $opportunity->ai_status }}</span></div>
                @endif
              </div>
            </td>
            <td class="uw-job">
              <a href="{{ route('upwork.opportunities.show', $opportunity) }}" class="uw-job-title" title="{{ $opportunity->title }}">{{ $opportunity->title }}</a>
              @if($opportunity->status === $M::STATUS_ARCHIVED)
                <div class="uw-sub" style="color:#4b5563;"><i class="bi bi-archive"></i> Archived {{ optional($opportunity->archived_at)->format('d M Y H:i') }} · was {{ $M::STATUSES[$opportunity->archived_from_status] ?? '—' }} · first seen {{ optional($opportunity->first_seen_at ?? $opportunity->created_at)->format('d M Y') }}</div>
              @endif
              @if($otherFilters)
                <div class="uw-sub" style="color:#842029;"><i class="bi bi-exclamation-triangle"></i> {{ implode('; ', $otherFilters) }}</div>
              @endif
              @include('upwork.opportunities.partials.location-chip')
              @if(!empty($opportunity->classifications) || $opportunity->target_profile)
                <div class="uw-sub d-flex flex-wrap align-items-center gap-1">
                  @if(!empty($opportunity->classifications))@include('upwork.opportunities.partials.classification-chips', ['codes' => $opportunity->classifications, 'catalog' => $catalog, 'kind' => 'archetype'])@endif
                  @if($opportunity->target_profile)
                    @php $fmtFit = fn($v) => $v === null ? 'n/a' : round($v); @endphp
                    @include('upwork.opportunities.partials.target-chip', ['target' => $opportunity->target_profile, 'ambiguous' => $opportunity->score_explanation['target']['ambiguous'] ?? false, 'title' => $opportunity->score_explanation['target']['summary'] ?? 'Best-fit profile'])
                    <span>agency {{ $fmtFit($opportunity->agency_fit_score) }} · individual {{ $fmtFit($opportunity->individual_fit_score) }}</span>
                    @if($opportunity->selected_profile)<span>· <strong>bid as {{ \App\Services\Upwork\Targeting\TargetProfiles::label($opportunity->selected_profile) }}</strong></span>@endif
                  @endif
                </div>
              @endif
              @if($opportunity->strategic_score !== null || $opportunity->expected_value !== null)
                <div class="uw-sub">
                  @if($opportunity->strategic_score !== null)<span style="color:#6d28d9;">Strategic {{ round($opportunity->strategic_score) }}/100</span>@endif
                  @if($opportunity->win_probability !== null) · win ~{{ round($opportunity->win_probability) }}%@endif
                  @if($opportunity->expected_value !== null) · EV ~{{ $M::money($opportunity->expected_value) }} <span title="Estimate: contract value × heuristic win probability">(est.)</span>@endif
                </div>
              @endif
            </td>
            <td class="uw-nowrap">{{ $opportunity->budget_label }}</td>
            <td class="uw-nowrap">
              <div>
                {{ $opportunity->client_country ?? '—' }}
                @if($opportunity->payment_verified)
                  <i class="bi bi-patch-check-fill text-primary" title="Payment verified" aria-label="Payment verified"></i>
                @elseif($opportunity->payment_verified === false)
                  <i class="bi bi-patch-exclamation text-danger" title="Payment not verified" aria-label="Payment not verified"></i>
                @endif
              </div>
              <div class="uw-sub">
                @if($opportunity->client_rating !== null)<i class="bi bi-star-fill" style="color:#f59e0b;"></i> {{ number_format($opportunity->client_rating, 2) }} ({{ $opportunity->client_reviews_count ?? 0 }})@else No rating @endif
              </div>
              <div class="uw-sub">{{ $M::money($opportunity->client_total_spent) }} spent · {{ $opportunity->client_hire_rate !== null ? round($opportunity->client_hire_rate) . '%' : '—' }} hire</div>
            </td>
            <td class="uw-nowrap">
              <div class="uw-sub mt-0">Proposals: <strong>{{ $opportunity->proposals_label }}</strong></div>
              @if($opportunity->invites_sent === null)
                <div class="uw-sub" title="Not available: Upwork gives invites only in full job details"><i class="bi bi-envelope"></i> Invites sent: <strong>n/a</strong></div>
              @else
                <div class="uw-sub" style="{{ $opportunity->invite_penalty ? 'color:#991b1b;' : '' }}" @if($opportunity->invite_penalty) title="−{{ $fmt($opportunity->invite_penalty) }} invite penalty" @else title="Invites the client already sent on this posting" @endif>
                  <i class="bi bi-envelope"></i> Invites sent: <strong>{{ $opportunity->invites_sent }}</strong>@if($opportunity->invite_penalty) · −{{ $fmt($opportunity->invite_penalty) }} score @endif
                </div>
              @endif
              <div class="uw-sub">Connects: <strong>{{ $opportunity->connects_required ?? '—' }}</strong></div>
            </td>
            <td class="uw-nowrap" title="{{ optional($opportunity->posted_at)->format('d M Y H:i') }}">{{ $opportunity->posted_at ? $opportunity->posted_at->diffForHumans() : '—' }}</td>
            <td>@include('upwork.opportunities.partials.status-badge')</td>
            <td class="uw-r uw-nowrap">
              <div class="uw-row-actions">
                @can('approve', $opportunity)
                  @if($opportunity->canTransitionTo($M::STATUS_APPROVED) && !$opportunity->isLocationRestricted())
                    {{-- Approval asks for the profile and proposal angle, so it happens on the job page. --}}
                    <a href="{{ route('upwork.opportunities.show', $opportunity) }}#approve" class="btn btn-sm btn-outline-success" title="Approve: choose profile and angle (does not contact Upwork)" aria-label="Approve"><i class="bi bi-check-lg"></i></a>
                  @endif
                @endcan
                @can('reject', $opportunity)
                  @if($opportunity->canTransitionTo($M::STATUS_REJECTED))
                    <button type="button" class="btn btn-sm btn-outline-danger js-reject" title="Reject" aria-label="Reject"
                            data-action="{{ route('upwork.opportunities.reject', $opportunity) }}"
                            data-title="{{ $opportunity->title }}"><i class="bi bi-x-lg"></i></button>
                  @endif
                @endcan
                @can('archive', $opportunity)
                  @if($opportunity->status === $M::STATUS_ARCHIVED)
                    <form method="POST" action="{{ route('upwork.opportunities.restore', $opportunity) }}" class="d-inline m-0">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-primary" title="Restore to {{ $M::STATUSES[$opportunity->archived_from_status] ?? 'New' }}" aria-label="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </form>
                  @elseif($opportunity->canTransitionTo($M::STATUS_ARCHIVED))
                    <form method="POST" action="{{ route('upwork.opportunities.archive', $opportunity) }}" class="d-inline m-0" onsubmit="return confirm('Archive this job? It leaves the review queue and sends no reminders. You can restore it later.');">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-secondary" title="Archive (not deleted, approved or rejected)" aria-label="Archive"><i class="bi bi-archive"></i></button>
                    </form>
                  @endif
                @endcan
                @if($opportunity->upwork_url)
                  <a href="{{ $opportunity->upwork_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" title="Open on Upwork" aria-label="Open on Upwork"><i class="bi bi-box-arrow-up-right"></i></a>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="uw-empty">
            <i class="bi bi-inbox" aria-hidden="true"></i>
            @if($chips)
              <div class="fw-semibold text-dark">No opportunities match these filters.</div>
              <div class="mt-1"><a href="{{ $resetUrl }}">Reset filters</a>@if(!$view && !$showAll && ($stats['hidden'] ?? 0) > 0) or <a href="{{ $list(array_merge($filters, ['show' => 'all'])) }}">include {{ $stats['hidden'] }} hidden jobs</a>@endif</div>
            @elseif($view)
              <div class="fw-semibold text-dark">Nothing in {{ $VIEWS[$view] }} right now.</div>
              <div class="mt-1"><a href="{{ $list([]) }}">See all relevant opportunities</a></div>
            @else
              <div class="fw-semibold text-dark">No opportunities found.</div>
              <div class="mt-1">New jobs appear here after discovery runs. See the <a href="{{ route('upwork.radar') }}">Radar</a> for discovery health.</div>
            @endif
          </td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @include('upwork.partials.pager', ['paginator' => $opportunities, 'options' => $perPageOptions, 'action' => route('upwork.opportunities.index'), 'keep' => $filters, 'noun' => 'opportunities'])
  </div>
</div>

@include('upwork.opportunities.partials.reject-modal')
<script>
(function () {
  var form = document.getElementById('bulk-form');
  if (!form) return;
  var rows = Array.prototype.slice.call(document.querySelectorAll('.js-bulk-row'));
  var page = document.getElementById('bulk-page');
  var mode = document.getElementById('bulk-mode');
  var count = document.getElementById('bulk-count');
  var pageLink = document.getElementById('bulk-select-page');
  var allLink = document.getElementById('bulk-all-matching');
  var clear = document.getElementById('bulk-clear');
  var submit = document.getElementById('bulk-submit');
  var matching = parseInt(form.dataset.matching, 10);
  var label = form.dataset.actionLabel;

  function selected() { return rows.filter(function (r) { return r.checked; }).length; }
  function total() { return mode.value === 'filter' ? matching : selected(); }
  function render() {
    var n = total(), onPage = selected(), all = mode.value === 'filter';
    page.checked = rows.length > 0 && onPage === rows.length;
    page.indeterminate = onPage > 0 && onPage < rows.length;
    rows.forEach(function (r) { r.closest('tr').classList.toggle('is-selected', r.checked); });
    count.textContent = all
      ? 'All ' + matching + ' jobs matching this filter are selected (every page)'
      : (n ? n + ' selected on this page' : 'No jobs selected');
    pageLink.classList.toggle('d-none', all || (rows.length > 0 && onPage === rows.length));
    allLink.classList.toggle('d-none', all || matching <= rows.length);
    clear.classList.toggle('d-none', n === 0);
    form.classList.toggle('is-active', n > 0);
    submit.disabled = n === 0;
  }
  function setPage(on) { rows.forEach(function (r) { r.checked = on; }); }

  rows.forEach(function (r) { r.addEventListener('change', function () { mode.value = 'ids'; render(); }); });
  page.addEventListener('change', function () { mode.value = 'ids'; setPage(page.checked); render(); });
  pageLink.addEventListener('click', function () { mode.value = 'ids'; setPage(true); render(); });
  allLink.addEventListener('click', function () { mode.value = 'filter'; setPage(true); render(); });
  clear.addEventListener('click', function () { mode.value = 'ids'; setPage(false); render(); });
  form.addEventListener('submit', function (e) {
    var n = total();
    var what = mode.value === 'filter' ? 'all ' + n + ' jobs matching the current filter (not only this page)' : n + ' selected job(s)';
    if (!n || !confirm(label + ' ' + what + '?' + (label === 'Archive' ? ' They leave the review queue and send no reminders; nothing is deleted.' : ''))) {
      e.preventDefault();
    }
  });
  render();
})();
</script>
@endsection
