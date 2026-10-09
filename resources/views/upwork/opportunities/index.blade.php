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

  {{-- List views --}}
  <ul class="nav nav-pills mb-3 flex-wrap" style="font-size:0.85rem;">
    <li class="nav-item"><a class="nav-link py-1 px-2 {{ $view ? '' : 'active' }}" href="{{ route('upwork.opportunities.index') }}">All relevant</a></li>
    @foreach(\App\Services\Upwork\RadarService::VIEWS as $name => $label)
      <li class="nav-item"><a class="nav-link py-1 px-2 {{ $view === $name ? 'active' : '' }}" href="{{ route('upwork.opportunities.index', ['view' => $name]) }}">{{ $label }} <span class="badge {{ $view === $name ? 'bg-light text-dark' : 'bg-secondary' }}">{{ $viewCounts[$name] }}</span></a></li>
    @endforeach
    <li class="nav-item ms-auto"><a class="nav-link py-1 px-2" href="{{ route('upwork.radar') }}"><i class="bi bi-broadcast"></i> Radar</a></li>
  </ul>

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

      <input type="number" name="min_score" min="0" max="100" step="1" value="{{ $filters['min_score'] ?? '' }}"
             placeholder="Min score" class="form-control form-control-sm" style="width:110px;">

      <select name="sort" class="form-select form-select-sm" style="width:auto;">
        <option value="score" {{ $sort === 'score' ? 'selected' : '' }}>Sort: score</option>
        <option value="posted_at" {{ $sort === 'posted_at' ? 'selected' : '' }}>Sort: posted date</option>
        <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Sort: recently added</option>
        <option value="expected_value" {{ $sort === 'expected_value' ? 'selected' : '' }}>Sort: expected value (est.)</option>
        <option value="strategic" {{ $sort === 'strategic' ? 'selected' : '' }}>Sort: strategic value</option>
      </select>

      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="{{ route('upwork.opportunities.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
      <a href="{{ route('upwork.clients.index') }}" class="btn btn-link btn-sm ms-auto">Client intelligence</a>
    </form>

    {{-- Table --}}
    <div class="table-responsive">
      <table class="table table-striped table-bordered mb-0" style="font-size:0.84rem; color:#1f2937;">
        <thead>
          <tr style="background:#f9fafb;">
            <th style="{{ $th }}">Score</th>
            <th style="{{ $th }}">Rec.</th>
            <th style="{{ $th }}">Job</th>
            <th style="{{ $th }}">Budget / Rate</th>
            <th style="{{ $th }}">Client</th>
            <th style="{{ $th }}">Rating</th>
            <th style="{{ $th }}">Spend</th>
            <th style="{{ $th }}">Hire rate</th>
            <th style="{{ $th }}">Proposals</th>
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
              @if(!empty($opportunity->hard_filters))
                <div style="font-size:0.72rem; color:#842029; margin-top:0.125rem;"><i class="bi bi-exclamation-triangle"></i> {{ implode('; ', $opportunity->hard_filters) }}</div>
              @endif
              @if(!empty($opportunity->classifications))
                <div style="margin-top:0.25rem; font-size:0.85em;">@include('upwork.opportunities.partials.classification-chips', ['codes' => $opportunity->classifications, 'catalog' => $catalog, 'kind' => 'archetype'])</div>
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
            <td style="{{ $td }}">{{ $opportunity->connects_required ?? '—' }}</td>
            <td style="{{ $td }} white-space:nowrap;" title="{{ optional($opportunity->posted_at)->format('d M Y H:i') }}">{{ $opportunity->posted_at ? $opportunity->posted_at->diffForHumans() : '—' }}</td>
            <td style="{{ $td }}">@include('upwork.opportunities.partials.status-badge')</td>
            <td style="{{ $td }} text-align:right; white-space:nowrap;">
              @can('approve', $opportunity)
                @if($opportunity->canTransitionTo(\App\Models\UpworkOpportunity::STATUS_APPROVED))
                  <form method="POST" action="{{ route('upwork.opportunities.approve', $opportunity) }}" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success" title="Approve (does not contact Upwork)"><i class="bi bi-check-lg"></i></button>
                  </form>
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
          <tr><td colspan="13" style="padding:1.5rem; text-align:center; color:#6b7280;">No opportunities found.</td></tr>
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
@endsection
