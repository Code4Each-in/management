@extends('layout')

@section('title', 'Upwork Opportunities')

@section('content')
@php
    $th = 'padding:0.625rem 0.75rem; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; white-space:nowrap;';
    $td = 'padding:0.625rem 0.75rem; vertical-align:middle;';
    $highScore = config('upwork.thresholds.apply', 80);
@endphp
<div style="margin: 1rem auto 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

  @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 small py-2 px-3 mb-3" style="font-weight: 600;">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3" style="font-weight: 600;">{{ session('error') }}</div>
  @endif
  @if(session('bulk_failures'))
    <div class="alert alert-warning border-0 rounded-3 small py-2 px-3 mb-3">{{ session('bulk_failures') }}</div>
  @endif

  {{-- List views --}}
  <ul class="nav nav-pills mb-3 flex-wrap" style="font-size:0.85rem;">
    <li class="nav-item"><a class="nav-link py-1 px-2 {{ $view ? '' : 'active' }}" href="{{ route('upwork.opportunities.index') }}">All relevant</a></li>
    @foreach(\App\Services\Upwork\RadarService::VIEWS as $name => $label)
      <li class="nav-item"><a class="nav-link py-1 px-2 {{ $view === $name ? 'active' : '' }}" href="{{ route('upwork.opportunities.index', ['view' => $name]) }}">{{ $label }} <span class="badge {{ $view === $name ? 'bg-light text-dark' : 'bg-secondary' }}">{{ $viewCounts[$name] }}</span></a></li>
    @endforeach
    <li class="nav-item ms-auto"><a class="nav-link py-1 px-2" href="{{ route('upwork.radar') }}"><i class="bi bi-broadcast"></i> Radar</a></li>
  </ul>

  {{-- Review queue: the jobs reminders cover, and where everything else went --}}
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3" style="font-size:0.84rem;">
    <span class="text-muted fw-semibold me-1">Review queue:</span>
    @foreach([
        ['Awaiting review', $stats['awaiting_review'], '#0d6efd', ['view' => 'reminder_queue'], 'New APPLY/REVIEW jobs with full details: exactly the jobs review reminders cover'],
        ['Overdue', $stats['overdue'], '#dc3545', ['view' => 'overdue'], 'Awaiting review and past the 30-minute deadline'],
        ['Snoozed', $stats['snoozed'], '#6f42c1', ['view' => 'snoozed'], 'Reminders paused for now; the deadline is unchanged'],
        ['Approved', $stats['approved'], '#0f5132', ['view' => 'approved'], null],
        ['Rejected', $stats['rejected'], '#842029', ['view' => 'rejected'], null],
        ['Archived', $stats['archived'], '#4b5563', ['view' => 'archived'], 'Out of the queue; never reminded. Restore from this tab.'],
    ] as [$label, $value, $color, $query, $title])
      <a href="{{ route('upwork.opportunities.index', $query) }}" class="text-decoration-none px-2 py-1 rounded border bg-white" title="{{ $title }}">
        {{ $label }} <strong style="color:{{ $color }};">{{ $value }}</strong>
      </a>
    @endforeach
  </div>

  {{-- Location eligibility: can India-based freelancers apply? (active jobs; archived ones are counted under Archived) --}}
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3" style="font-size:0.84rem;">
    <span class="text-muted fw-semibold me-1">Location:</span>
    @foreach([
        ['All jobs', $stats['all'], '#111827', ['view' => 'all_jobs'], 'Every stored job, archived included'],
        ['Recommended & eligible', $viewCounts['eligible'], '#198754', ['view' => 'eligible'], 'New APPLY/REVIEW jobs India-based freelancers can apply to (unclear locations included and flagged)'],
        ['Eligible', $stats['location_eligible'], '#0f5132', ['location' => 'eligible', 'show' => 'all'], 'India allowed, or no restriction given'],
        ['Skipped: location', $stats['location_restricted'], '#842029', ['view' => 'location_restricted'], 'India-based applicants are not eligible: held at SKIP, no reminders, no proposals'],
        ['Unclear: manual review', $stats['location_unknown'], '#92400e', ['view' => 'location_unknown'], 'Location text could not be read with confidence; kept in review'],
        ['Not checked yet', $stats['location_unclassified'], '#6b7280', ['location' => 'unclassified', 'show' => 'all'], 'Stored before location rules existed; run the backfill'],
        ['Archived', $stats['archived'], '#4b5563', ['view' => 'archived'], null],
    ] as [$label, $value, $color, $query, $title])
      <a href="{{ route('upwork.opportunities.index', $query) }}" class="text-decoration-none px-2 py-1 rounded border bg-white" title="{{ $title }}">
        {{ $label }} <strong style="color:{{ $color }};">{{ $value }}</strong>
      </a>
    @endforeach
  </div>

  {{-- Stat cards --}}
  <div class="row g-3 mb-4">
    @foreach([
        ['New',             $stats['new'],             '#0d6efd', ['status' => 'new']],
        [$highScore . '+ Score', $stats['high_score'], '#198754', ['min_score' => $highScore]],
        ['Approved',        $stats['approved'],        '#0f5132', ['status' => 'approved']],
        ['Rejected',        $stats['rejected'],        '#dc3545', ['status' => 'rejected']],
        ['Proposal Draft',  $stats['proposal_draft'],  '#b58105', ['status' => 'proposal_draft']],
        ['Submitted',       $stats['submitted'],       '#3730a3', ['status' => 'submitted']],
        ['Client Response', $stats['client_response'], '#0dcaf0', ['status' => 'client_response']],
        ['Won',             $stats['won'],             '#198754', ['status' => 'won']],
        ['Lost',            $stats['lost'],            '#6c757d', ['status' => 'lost']],
    ] as [$label, $value, $color, $query])
    <div class="col-xl col-md-3 col-6">
      <a href="{{ route('upwork.opportunities.index', $query) }}" style="text-decoration:none;">
        <div style="background:#fff; border-radius:0.5rem; padding:0.875rem 1rem; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04);">
          <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; margin-bottom:0.25rem; white-space:nowrap;">{{ $label }}</div>
          <div style="font-size:1.5rem; font-weight:600; color:{{ $color }}; line-height:1.2;">{{ $value }}</div>
        </div>
      </a>
    </div>
    @endforeach
  </div>

  <div style="background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">

    {{-- Filters --}}
    <form method="GET" action="{{ route('upwork.opportunities.index') }}"
          style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; padding:0.75rem 1.25rem; border-bottom:1px solid #e5e7eb;">
      <span style="font-size:0.875rem; color:#6b7280; font-weight:500; margin-right:0.5rem;">{{ $opportunities->total() }} opportunities</span>
      @if($view)<input type="hidden" name="view" value="{{ $view }}">@endif
      @if(($filters['show'] ?? null) === 'all')
        <input type="hidden" name="show" value="all">
        <a href="{{ route('upwork.opportunities.index', \Illuminate\Support\Arr::except(request()->query(), ['show', 'page'])) }}" class="small me-2">Hide skipped</a>
      @elseif(($stats['hidden'] ?? 0) > 0)
        <a href="{{ route('upwork.opportunities.index', array_merge(request()->query(), ['show' => 'all'])) }}" class="small text-muted me-2" title="Untouched jobs that were skipped, hard-rejected or excluded by every search profile">Show all ({{ $stats['hidden'] }} hidden)</a>
      @endif

      <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search title, description, country…"
             class="form-control form-control-sm" style="width:240px;">

      <select name="status" class="form-select form-select-sm" style="width:auto;">
        <option value="">All statuses</option>
        @foreach(\App\Models\UpworkOpportunity::STATUSES as $value => $label)
          <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>

      <select name="recommendation" class="form-select form-select-sm" style="width:auto;">
        <option value="">All recommendations</option>
        @foreach(\App\Models\UpworkOpportunity::RECOMMENDATIONS as $value => $label)
          <option value="{{ $value }}" {{ ($filters['recommendation'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>

      <select name="class" class="form-select form-select-sm" style="width:auto;">
        <option value="">All classifications</option>
        @foreach(collect($catalog)->where('enabled', true)->groupBy('kind') as $kind => $items)
          <optgroup label="{{ $kind === 'archetype' ? 'Archetypes' : 'Categories' }}">
            @foreach($items as $item)
              <option value="{{ $item->code }}" {{ ($filters['class'] ?? '') === $item->code ? 'selected' : '' }}>{{ $item->label }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>

      <select name="target" class="form-select form-select-sm" style="width:auto;" title="Best-fit profile from scoring. Agency / Individual include jobs suitable for either.">
        <option value="">All profiles</option>
        @foreach(['agency' => 'Fits agency (incl. either)', 'individual' => 'Fits individual (incl. either)', 'both' => 'Fits either'] as $value => $label)
          <option value="{{ $value }}" {{ ($filters['target'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>

      <select name="bid_as" class="form-select form-select-sm" style="width:auto;" title="Profile chosen by the reviewer">
        <option value="">Any bid profile</option>
        <option value="agency" {{ ($filters['bid_as'] ?? '') === 'agency' ? 'selected' : '' }}>Bid as agency</option>
        <option value="individual" {{ ($filters['bid_as'] ?? '') === 'individual' ? 'selected' : '' }}>Bid as individual</option>
      </select>

      <select name="location" class="form-select form-select-sm" style="width:auto;" title="Can India-based freelancers apply?">
        <option value="">Any location</option>
        @foreach(['eligible' => 'Location: eligible', 'restricted' => 'Location: skipped (restricted)', 'unknown' => 'Location: unclear', 'unclassified' => 'Location: not checked'] as $value => $label)
          <option value="{{ $value }}" {{ ($filters['location'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>

      <input type="number" name="min_score" min="0" max="100" step="1" value="{{ $filters['min_score'] ?? '' }}"
             placeholder="Min score" class="form-control form-control-sm" style="width:110px;">

      <select name="sort" class="form-select form-select-sm" style="width:auto;">
        <option value="score" {{ $sort === 'score' ? 'selected' : '' }}>Sort: score</option>
        <option value="posted_at" {{ $sort === 'posted_at' ? 'selected' : '' }}>Sort: posted date</option>
        <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Sort: recently added</option>
        <option value="expected_value" {{ $sort === 'expected_value' ? 'selected' : '' }}>Sort: expected value (est.)</option>
        <option value="strategic" {{ $sort === 'strategic' ? 'selected' : '' }}>Sort: strategic value</option>
        <option value="agency_fit" {{ $sort === 'agency_fit' ? 'selected' : '' }}>Sort: agency fit</option>
        <option value="individual_fit" {{ $sort === 'individual_fit' ? 'selected' : '' }}>Sort: individual fit</option>
        @if($view === 'archived')<option value="archived_at" {{ $sort === 'archived_at' ? 'selected' : '' }}>Sort: archive date</option>@endif
      </select>

      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="{{ route('upwork.opportunities.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
      <a href="{{ route('upwork.clients.index') }}" class="btn btn-link btn-sm ms-auto">Client intelligence</a>
    </form>

    {{-- Bulk selection: ticked rows, or every job matching this filter (the server re-applies the same filter). --}}
    @php $archivedView = $view === 'archived'; $bulkAction = $archivedView ? 'restore' : 'archive'; @endphp
    @can('archive', \App\Models\UpworkOpportunity::class)
      @if($opportunities->count())
        <form id="bulk-form" method="POST" action="{{ route('upwork.opportunities.bulk') }}" data-action-label="{{ $archivedView ? 'Restore' : 'Archive' }}"
              data-matching="{{ $matching }}" data-page="{{ $opportunities->count() }}"
              style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; padding:0.5rem 1.25rem; border-bottom:1px solid #e5e7eb; background:#f9fafb; font-size:0.84rem;">
          @csrf
          <input type="hidden" name="action" value="{{ $bulkAction }}">
          <input type="hidden" name="mode" value="ids" id="bulk-mode">
          <input type="hidden" name="expected" value="{{ $matching }}">
          @foreach($filterParams as $key => $value)
            <input type="hidden" name="filter[{{ $key }}]" value="{{ $value }}">
          @endforeach
          <span id="bulk-count" class="text-muted">No jobs selected</span>
          <a href="#" id="bulk-all-matching" class="d-none">Select all {{ $matching }} jobs matching this filter</a>
          <a href="#" id="bulk-clear" class="d-none">Clear selection</a>
          <button type="submit" id="bulk-submit" class="btn btn-sm {{ $archivedView ? 'btn-outline-primary' : 'btn-outline-secondary' }} ms-auto" disabled>
            <i class="bi {{ $archivedView ? 'bi-arrow-counterclockwise' : 'bi-archive' }}"></i> {{ $archivedView ? 'Restore Selected' : 'Archive Selected' }}
          </button>
        </form>
      @endif
    @endcan

    {{-- Table --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered mb-0" style="font-size:0.84rem; color:#1f2937;">
        <thead>
          <tr style="background:#f9fafb;">
            <th style="{{ $th }} width:1%;">@can('archive', \App\Models\UpworkOpportunity::class)<input type="checkbox" id="bulk-page" title="Select all on this page" aria-label="Select all on this page">@endcan</th>
            <th style="{{ $th }}">Score</th>
            <th style="{{ $th }}">Rec.</th>
            <th style="{{ $th }}">Job</th>
            <th style="{{ $th }}">Budget / Rate</th>
            <th style="{{ $th }}">Client</th>
            <th style="{{ $th }}">Rating</th>
            <th style="{{ $th }}">Spend</th>
            <th style="{{ $th }}">Hire rate</th>
            <th style="{{ $th }}">Proposals</th>
            <th style="{{ $th }}" title="Invites the client already sent (full job details only)">Invites</th>
            <th style="{{ $th }}">Connects</th>
            <th style="{{ $th }}">Posted</th>
            <th style="{{ $th }}">Status</th>
            <th style="{{ $th }} text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($opportunities as $opportunity)
          @php
            $scoreColor = $opportunity->score === null ? '#9ca3af'
                : ($opportunity->score >= config('upwork.thresholds.apply', 80) ? '#198754'
                : ($opportunity->score >= config('upwork.thresholds.review', 70) ? '#b58105' : '#6b7280'));
          @endphp
          <tr>
            <td style="{{ $td }}">@can('archive', $opportunity)<input type="checkbox" name="ids[]" value="{{ $opportunity->id }}" form="bulk-form" class="js-bulk-row" aria-label="Select {{ $opportunity->title }}">@endcan</td>
            <td style="{{ $td }} font-weight:700; font-size:1rem; color:{{ $scoreColor }};">
              {{ $opportunity->score !== null ? rtrim(rtrim(number_format($opportunity->score, 1), '0'), '.') : '—' }}
              @if($opportunity->aiAnalysis)
                <div><span class="badge" style="background:#f3e8ff; color:#6b21a8; font-size:0.65rem; font-weight:600;" title="AI fit (estimate), {{ strtoupper($opportunity->aiAnalysis->recommendation) }}, confidence {{ number_format($opportunity->aiAnalysis->confidence * 100) }}%">AI {{ $opportunity->aiAnalysis->fit_score }}</span></div>
              @elseif(in_array($opportunity->ai_status, ['queued', 'failed', 'blocked'], true))
                <div><span class="badge bg-light text-muted border" style="font-size:0.6rem;">AI {{ $opportunity->ai_status }}</span></div>
              @endif
            </td>
            <td style="{{ $td }}">@include('upwork.opportunities.partials.recommendation-badge')</td>
            <td style="{{ $td }} min-width:260px;">
              <a href="{{ route('upwork.opportunities.show', $opportunity) }}" style="color:#0d6efd; text-decoration:none; font-weight:600;">{{ $opportunity->title }}</a>
              @if($opportunity->status === \App\Models\UpworkOpportunity::STATUS_ARCHIVED)
                <div style="font-size:0.72rem; color:#4b5563; margin-top:0.125rem;"><i class="bi bi-archive"></i> Archived {{ optional($opportunity->archived_at)->format('d M Y H:i') }} · was {{ \App\Models\UpworkOpportunity::STATUSES[$opportunity->archived_from_status] ?? '—' }} · first seen {{ optional($opportunity->first_seen_at ?? $opportunity->created_at)->format('d M Y') }}</div>
              @endif
              @php $otherFilters = array_diff((array) $opportunity->hard_filters, [\App\Services\Upwork\Eligibility\LocationEligibility::SKIP_REASON]); @endphp
              @if($otherFilters)
                <div style="font-size:0.72rem; color:#842029; margin-top:0.125rem;"><i class="bi bi-exclamation-triangle"></i> {{ implode('; ', $otherFilters) }}</div>
              @endif
              @include('upwork.opportunities.partials.location-chip')
              <div style="font-size:0.72rem; color:{{ $opportunity->invite_penalty ? '#991b1b' : '#6b7280' }}; margin-top:0.125rem;" title="Invites the client already sent on this posting (Upwork full job details)">
                <i class="bi bi-envelope"></i> Invites sent: <strong>{{ $opportunity->invites_sent ?? 'Not available' }}</strong>@if($opportunity->invite_penalty) · −{{ rtrim(rtrim(number_format($opportunity->invite_penalty, 1), '0'), '.') }} score @endif
              </div>
              @if(!empty($opportunity->classifications))
                <div style="margin-top:0.25rem; font-size:0.85em;">@include('upwork.opportunities.partials.classification-chips', ['codes' => $opportunity->classifications, 'catalog' => $catalog, 'kind' => 'archetype'])</div>
              @endif
              @if($opportunity->target_profile)
                @php $fmtFit = fn($v) => $v === null ? 'n/a' : round($v); @endphp
                <div style="margin-top:0.25rem; font-size:0.72rem; color:#6b7280;">
                  @include('upwork.opportunities.partials.target-chip', ['target' => $opportunity->target_profile, 'ambiguous' => $opportunity->score_explanation['target']['ambiguous'] ?? false, 'title' => $opportunity->score_explanation['target']['summary'] ?? 'Best-fit profile'])
                  agency {{ $fmtFit($opportunity->agency_fit_score) }} · individual {{ $fmtFit($opportunity->individual_fit_score) }}
                  @if($opportunity->selected_profile) · <strong>bid as {{ \App\Services\Upwork\Targeting\TargetProfiles::label($opportunity->selected_profile) }}</strong>@endif
                </div>
              @endif
              @if($opportunity->strategic_score !== null || $opportunity->expected_value !== null)
                <div style="font-size:0.72rem; color:#6b7280;">
                  @if($opportunity->strategic_score !== null)<span style="color:#6d28d9;">Strategic {{ round($opportunity->strategic_score) }}/100</span>@endif
                  @if($opportunity->win_probability !== null) · win ~{{ round($opportunity->win_probability) }}%@endif
                  @if($opportunity->expected_value !== null) · EV ~{{ \App\Models\UpworkOpportunity::money($opportunity->expected_value) }} <span title="Estimate: contract value × heuristic win probability">(est.)</span>@endif
                </div>
              @endif
            </td>
            <td style="{{ $td }} white-space:nowrap;">{{ $opportunity->budget_label }}</td>
            <td style="{{ $td }} white-space:nowrap;">
              {{ $opportunity->client_country ?? '—' }}
              @if($opportunity->payment_verified)
                <i class="bi bi-patch-check-fill text-primary" title="Payment verified"></i>
              @elseif($opportunity->payment_verified === false)
                <i class="bi bi-patch-exclamation text-danger" title="Payment not verified"></i>
              @endif
            </td>
            <td style="{{ $td }} white-space:nowrap;">
              @if($opportunity->client_rating !== null)
                <i class="bi bi-star-fill" style="color:#f59e0b;"></i> {{ number_format($opportunity->client_rating, 2) }}
                <span style="color:#9ca3af;">({{ $opportunity->client_reviews_count ?? 0 }})</span>
              @else — @endif
            </td>
            <td style="{{ $td }}">{{ \App\Models\UpworkOpportunity::money($opportunity->client_total_spent) }}</td>
            <td style="{{ $td }}">{{ $opportunity->client_hire_rate !== null ? round($opportunity->client_hire_rate) . '%' : '—' }}</td>
            <td style="{{ $td }}">{{ $opportunity->proposals_label }}</td>
            <td style="{{ $td }}" @if($opportunity->invite_penalty) title="−{{ rtrim(rtrim(number_format($opportunity->invite_penalty, 1), '0'), '.') }} invite penalty" @endif>
              @if($opportunity->invites_sent === null)<span class="text-muted" title="Not available: Upwork gives invites only in full job details">n/a</span>@else{{ $opportunity->invites_sent }}@if($opportunity->invite_penalty)<span style="color:#991b1b; font-size:0.7rem;"> −{{ rtrim(rtrim(number_format($opportunity->invite_penalty, 1), '0'), '.') }}</span>@endif @endif
            </td>
            <td style="{{ $td }}">{{ $opportunity->connects_required ?? '—' }}</td>
            <td style="{{ $td }} white-space:nowrap;" title="{{ optional($opportunity->posted_at)->format('d M Y H:i') }}">{{ $opportunity->posted_at ? $opportunity->posted_at->diffForHumans() : '—' }}</td>
            <td style="{{ $td }}">@include('upwork.opportunities.partials.status-badge')</td>
            <td style="{{ $td }} text-align:right; white-space:nowrap;">
              @can('archive', $opportunity)
                @if($opportunity->status === \App\Models\UpworkOpportunity::STATUS_ARCHIVED)
                  <form method="POST" action="{{ route('upwork.opportunities.restore', $opportunity) }}" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Restore to {{ \App\Models\UpworkOpportunity::STATUSES[$opportunity->archived_from_status] ?? 'New' }}"><i class="bi bi-arrow-counterclockwise"></i></button>
                  </form>
                @elseif($opportunity->canTransitionTo(\App\Models\UpworkOpportunity::STATUS_ARCHIVED))
                  <form method="POST" action="{{ route('upwork.opportunities.archive', $opportunity) }}" class="d-inline m-0" onsubmit="return confirm('Archive this job? It leaves the review queue and sends no reminders. You can restore it later.');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Archive (not deleted, approved or rejected)"><i class="bi bi-archive"></i></button>
                  </form>
                @endif
              @endcan
              @can('approve', $opportunity)
                @if($opportunity->canTransitionTo(\App\Models\UpworkOpportunity::STATUS_APPROVED) && !$opportunity->isLocationRestricted())
                  {{-- Approval asks for the profile and proposal angle, so it happens on the job page. --}}
                  <a href="{{ route('upwork.opportunities.show', $opportunity) }}#approve" class="btn btn-sm btn-outline-success" title="Approve: choose profile and angle (does not contact Upwork)"><i class="bi bi-check-lg"></i></a>
                @endif
              @endcan
              @can('reject', $opportunity)
                @if($opportunity->canTransitionTo(\App\Models\UpworkOpportunity::STATUS_REJECTED))
                  <button type="button" class="btn btn-sm btn-outline-danger js-reject" title="Reject"
                          data-action="{{ route('upwork.opportunities.reject', $opportunity) }}"
                          data-title="{{ $opportunity->title }}"><i class="bi bi-x-lg"></i></button>
                @endif
              @endcan
              @if($opportunity->upwork_url)
                <a href="{{ $opportunity->upwork_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" title="Open on Upwork"><i class="bi bi-box-arrow-up-right"></i></a>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="15" style="padding:1.5rem; text-align:center; color:#6b7280;">No opportunities found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1.25rem; border-top:1px solid #e5e7eb; flex-wrap:wrap; gap:0.5rem; background:#f9fafb;">
      <span style="font-size:0.875rem; color:#6b7280;">
        @if($opportunities->total() > 0)Showing {{ $opportunities->firstItem() }}–{{ $opportunities->lastItem() }} of {{ $opportunities->total() }}@endif
      </span>
      <div style="font-size:0.875rem;">{{ $opportunities->links('pagination::bootstrap-4') }}</div>
    </div>
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
  var allLink = document.getElementById('bulk-all-matching');
  var clear = document.getElementById('bulk-clear');
  var submit = document.getElementById('bulk-submit');
  var matching = parseInt(form.dataset.matching, 10);
  var label = form.dataset.actionLabel;

  function selected() { return rows.filter(function (r) { return r.checked; }).length; }
  function total() { return mode.value === 'filter' ? matching : selected(); }
  function render() {
    var n = total(), onPage = selected();
    page.checked = rows.length > 0 && onPage === rows.length;
    count.textContent = mode.value === 'filter'
      ? 'All ' + matching + ' jobs matching this filter are selected'
      : (n ? n + ' selected on this page' : 'No jobs selected');
    allLink.classList.toggle('d-none', !(mode.value === 'ids' && page.checked && matching > rows.length));
    clear.classList.toggle('d-none', n === 0);
    submit.disabled = n === 0;
  }
  function reset() { mode.value = 'ids'; rows.forEach(function (r) { r.checked = false; }); render(); }

  rows.forEach(function (r) { r.addEventListener('change', function () { mode.value = 'ids'; render(); }); });
  page.addEventListener('change', function () { mode.value = 'ids'; rows.forEach(function (r) { r.checked = page.checked; }); render(); });
  allLink.addEventListener('click', function (e) { e.preventDefault(); mode.value = 'filter'; render(); });
  clear.addEventListener('click', function (e) { e.preventDefault(); reset(); });
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
