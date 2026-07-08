@extends('layout')

@section('title', 'Deployments')

@section('content')
<div style="margin: 2rem auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

  {{-- Page header --}}
   @if(auth()->check() && auth()->user()->role_id == 3)
        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('deployment.tickets.create') }}" target="_blank"
            class="btn btn-primary"
            style="font-family: inherit; font-size: 0.875rem; font-weight: 500; padding: 0.5rem 1.25rem; border-radius: 0.375rem;">
                + New Deployment
            </a>
        </div>
    @endif
  {{-- Stat cards --}}
    <div class="row g-3 mb-4" id="stats-row">
    @foreach([
        ['Total',      $stats['total'],     '#0d6efd'],
        ['Deployment', $stats['deplyoment_pending'], '#0dcaf0'],
        ['Needs Fix',  $stats['needs_fix'], '#ffc107'],
        ['Approved',   $stats['approved'],  '#198754'],
        ['Deployed',   $stats['deployed'],  '#6c757d'],
        ['Open Bugs',  $stats['open_bugs'], '#dc3545'],
    ] as [$label, $value, $color])
    <div class="col-md-2 col-6">
        <div style="background: #fff; border-radius: 0.5rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
        <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; margin-bottom: 0.25rem;">{{ $label }}</div>
        <div style="font-size: 1.625rem; font-weight: 600; color: {{ $color }}; line-height: 1.2;">{{ $value }}</div>
        </div>
    </div>
    @endforeach
    </div>

  {{-- Tickets table --}}
  <div style="background: #fff; border-radius: 0.5rem; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        {{-- Table toolbar --}}
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 0.5rem;">

        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
            <span id="ticket-count-label" style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">{{ $tickets->total() }} tickets</span>

            <select id="project-filter" class="form-control form-control-sm" style="font-family: inherit; font-size: 0.875rem; padding: 0.375rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; outline:none;">
            <option value="">All Projects</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}" {{ (string)$projectId === (string)$project->id ? 'selected' : '' }}>
                {{ $project->project_name }}
                </option>
            @endforeach
            </select>

            <input type="search" id="ticket-filter-text" value="{{ $filterText ?? '' }}" placeholder="Filter by module / file / migration…"
                class="form-control form-control-sm"
                style="width: 240px; font-family: inherit; font-size: 0.875rem; padding: 0.375rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; outline: none;">

            <button type="button" id="clear-filters" style="font-size:0.8rem; color:#6b7280; background:transparent; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.375rem 0.75rem; cursor:pointer;">
            Clear
            </button>
        </div>

        <input type="search" id="ticket-search" value="{{ $search ?? '' }}" placeholder="Search tickets…"
                class="form-control form-control-sm"
                style="width: 220px; font-family: inherit; font-size: 0.875rem; padding: 0.375rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; outline: none;">

        </div>

    {{-- Table --}}
    <div class="table-responsive">
        <div style="overflow-x: auto;">
        <table class="table table-striped  table-bordered"  style="width: 100%; border-collapse: collapse; font-size: 0.875rem; color: #1f2937;">
            <thead>
            <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Code</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Name</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Project</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Developer</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Reviewer</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Status</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Priority</th>
                <th style="padding: 0.625rem 1.25rem; text-align: right; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Action</th>
            </tr>
            </thead>
            <tbody id="tickets-tbody">
            @foreach($tickets as $ticket)
            @php
                $statusStyles = [
                'deplyoment_pending'   => ['bg' => '#e3f2fd', 'color' => '#0d6efd'],
                'needs_fix'   => ['bg' => '#fff3cd', 'color' => '#856404'],
                'approved'    => ['bg' => '#d1e7dd', 'color' => '#0f5132'],
                'deployed'    => ['bg' => '#e2e3e5', 'color' => '#41464b'],
                'open_bugs'   => ['bg' => '#f8d7da', 'color' => '#842029'],
                ];
                $status = $statusStyles[$ticket->status] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];

                $priorityColors = [
                'high'   => '#dc3545',
                'medium' => '#ffc107',
                'low'    => '#198754',
                ];
                $priorityColor = $priorityColors[strtolower($ticket->priority)] ?? '#6c757d';

                $statusLabel = str_replace('_', ' ', $ticket->status);
            @endphp
            <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.1s ease;">
                <td style="padding: 0.75rem 1.25rem; font-size: 0.813rem;">
                    <a href="{{ route('deployment.tickets.show', $ticket) }}" target="_blank"
                    style="color: #0d6efd; text-decoration: none; font-weight: 600;">
                        {{ $ticket->deployment_code }}
                    </a>
                </td>
                <td style="padding: 0.75rem 1.25rem; font-weight: 500;">{{ $ticket->deployment_name }}</td>
                <td style="padding: 0.75rem 1.25rem; color: #6b7280;">{{ $ticket->project->project_name ?? '—' }}</td>
                <td style="padding: 0.75rem 1.25rem; color: #6b7280;">{{ $ticket->developers->pluck('first_name')->implode(', ') ?: '—' }}</td>
                <td style="padding: 0.75rem 1.25rem; color: #6b7280;">{{ $ticket->qa->first_name ?? '—' }}</td>
                <td style="padding: 0.75rem 1.25rem;">
                <span style="display: inline-block; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem; background: {{ $status['bg'] }}; color: {{ $status['color'] }}; text-transform: capitalize;">{{ $statusLabel }}</span>
                </td>
                <td style="padding: 0.75rem 1.25rem;">
                <span style="display: inline-flex; align-items: center; gap: 0.375rem; font-weight: 500;">
                    <span style="display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 50%; background: {{ $priorityColor }};"></span>
                    {{ ucfirst($ticket->priority) }}
                </span>
                </td>
                <td style="padding: 0.75rem 1.25rem; text-align: right;">
                <a href="{{ route('deployment.tickets.show', $ticket) }}" target="_blank" style="display: inline-block; padding: 0.25rem 0.875rem; font-size: 0.813rem; font-weight: 500; color: #0d6efd; background: transparent; border: 1px solid #0d6efd; border-radius: 0.25rem; text-decoration: none; transition: all 0.15s ease-in-out;">
                    View
                </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    {{-- Table footer --}}
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; flex-wrap: wrap; gap: 0.5rem; background: #f9fafb;">
        <span id="ticket-footer-label" style="font-size: 0.875rem; color: #6b7280;">Showing {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}</span>
        <div id="ticket-pagination" style="font-size: 0.875rem;">{{ $tickets->links() }}</div>    </div>
  </div>

</div>
<script>
(function () {
  const searchInput = document.getElementById('ticket-search');
  const projectFilter = document.getElementById('project-filter');
  const filterTextInput = document.getElementById('ticket-filter-text');
  const clearBtn = document.getElementById('clear-filters');
  const statsRow = document.getElementById('stats-row');
  const tbody = document.getElementById('tickets-tbody');
  const countLabel = document.getElementById('ticket-count-label');
  const footerLabel = document.getElementById('ticket-footer-label');
  const paginationDiv = document.getElementById('ticket-pagination');
  const indexUrl = "{{ route('deployment.tickets.index') }}";
  let debounceTimer;

  const statusColors = {
    deplyoment_pending:  { bg: '#e3f2fd', color: '#0d6efd' },
    needs_fix:  { bg: '#fff3cd', color: '#856404' },
    approved:   { bg: '#d1e7dd', color: '#0f5132' },
    deployed:   { bg: '#e2e3e5', color: '#41464b' },
    open_bugs:  { bg: '#f8d7da', color: '#842029' },
  };
  const priorityColors = { high: '#dc3545', medium: '#ffc107', low: '#198754' };
  const statCards = [
    ['Total', 'total', '#0d6efd'],
    ['Deployment', 'deplyoment_pending', '#0dcaf0'],
    ['Needs Fix', 'needs_fix', '#ffc107'],
    ['Approved', 'approved', '#198754'],
    ['Deployed', 'deployed', '#6c757d'],
    ['Open Bugs', 'open_bugs', '#dc3545'],
  ];

  function escapeHtml(str) {
    if (str === null || str === undefined || str === '') return '—';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

    function buildUrl(page) {
        const params = new URLSearchParams();
        if (searchInput.value) params.set('search', searchInput.value);
        if (filterTextInput.value) params.set('filter_text', filterTextInput.value);
        if (projectFilter.value) params.set('project_id', projectFilter.value);
        if (page) params.set('page', page);
        const qs = params.toString();
        return qs ? `${indexUrl}?${qs}` : indexUrl;
    }

  function renderStats(stats) {
    statsRow.innerHTML = statCards.map(([label, key, color]) => `
      <div class="col-md-2 col-6">
        <div style="background:#fff;border-radius:0.5rem;padding:1rem 1.25rem;border:1px solid #e5e7eb;box-shadow:0 1px 2px rgba(0,0,0,0.04);">
          <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#6b7280;margin-bottom:0.25rem;">${label}</div>
          <div style="font-size:1.625rem;font-weight:600;color:${color};line-height:1.2;">${stats[key] ?? 0}</div>
        </div>
      </div>`).join('');
  }

  function renderRows(tickets) {
    if (!tickets.length) {
      tbody.innerHTML = `<tr><td colspan="8" style="padding:1.5rem; text-align:center; color:#6b7280;">No tickets found.</td></tr>`;
      return;
    }
    tbody.innerHTML = tickets.map(t => {
      const status = statusColors[t.status] || { bg: '#f3f4f6', color: '#6b7280' };
      const pColor = priorityColors[(t.priority || '').toLowerCase()] || '#6c757d';
      const priorityLabel = t.priority ? t.priority.charAt(0).toUpperCase() + t.priority.slice(1) : '—';
      return `
        <tr style="border-bottom:1px solid #f3f4f6;">
          <td style="padding:0.75rem 1.25rem; font-size:0.813rem;">
            <a href="${t.show_url}" target="_blank" style="color:#0d6efd; text-decoration:none; font-weight:600;">${escapeHtml(t.code)}</a>
          </td>
          <td style="padding:0.75rem 1.25rem; font-weight:500;">${escapeHtml(t.name)}</td>
          <td style="padding:0.75rem 1.25rem; color:#6b7280;">${escapeHtml(t.project)}</td>
          <td style="padding:0.75rem 1.25rem; color:#6b7280;">${escapeHtml(t.developers)}</td>
          <td style="padding:0.75rem 1.25rem; color:#6b7280;">${escapeHtml(t.qa)}</td>
          <td style="padding:0.75rem 1.25rem;">
            <span style="display:inline-block; padding:0.25rem 0.625rem; font-size:0.75rem; font-weight:600; border-radius:0.25rem; background:${status.bg}; color:${status.color}; text-transform:capitalize;">${escapeHtml(t.status_label)}</span>
          </td>
          <td style="padding:0.75rem 1.25rem;">
            <span style="display:inline-flex; align-items:center; gap:0.375rem; font-weight:500;">
              <span style="display:inline-block; width:0.5rem; height:0.5rem; border-radius:50%; background:${pColor};"></span>
              ${priorityLabel}
            </span>
          </td>
          <td style="padding:0.75rem 1.25rem; text-align:right;">
            <a href="${t.show_url}" target="_blank" style="display:inline-block; padding:0.25rem 0.875rem; font-size:0.813rem; font-weight:500; color:#0d6efd; background:transparent; border:1px solid #0d6efd; border-radius:0.25rem; text-decoration:none;">View</a>
          </td>
        </tr>`;
    }).join('');
  }

  function renderFooter(pagination) {
    countLabel.textContent = `${pagination.total} tickets`;
    footerLabel.textContent = pagination.total > 0
      ? `Showing ${pagination.first_item}–${pagination.last_item} of ${pagination.total}`
      : 'No tickets found.';
    paginationDiv.innerHTML = pagination.links.map(l => {
      const label = l.label.replace('&laquo;', '«').replace('&raquo;', '»');
      if (!l.url) return `<span style="padding:0.25rem 0.5rem; color:#cbd5e1;">${label}</span>`;
      return `<a href="#" data-url="${l.url}" class="page-link-ajax" style="padding:0.25rem 0.5rem; text-decoration:none; ${l.active ? 'font-weight:700; color:#0d6efd;' : 'color:#6b7280;'}">${label}</a>`;
    }).join(' ');
    attachPaginationHandlers();
  }

  function attachPaginationHandlers() {
    paginationDiv.querySelectorAll('.page-link-ajax').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const url = new URL(this.dataset.url, window.location.origin);
        const page = url.searchParams.get('page');
        fetchAndRender(buildUrl(page));
      });
    });
  }

  function fetchAndRender(url) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => res.json())
      .then(data => {
        renderStats(data.stats);
        renderRows(data.tickets);
        renderFooter(data.pagination);
        history.replaceState(null, '', url.replace(window.location.origin, ''));
      })
      .catch(err => console.error('Search failed:', err));
  }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchAndRender(buildUrl()), 350);
    });

    filterTextInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchAndRender(buildUrl()), 350);
    });

    projectFilter.addEventListener('change', function () {
        fetchAndRender(buildUrl());
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        filterTextInput.value = '';
        projectFilter.value = '';
        fetchAndRender(indexUrl);
    });

  attachPaginationHandlers();
})();
</script>
@endsection
