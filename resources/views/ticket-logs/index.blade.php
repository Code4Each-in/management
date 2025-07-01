@extends('layout')
@section('title', 'Ticket Logs')
@section('subtitle', 'Logs')
<style>
    .custom-table {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        background-color: #ffffff;
        table-layout: fixed; 
        width: 100%;
    }

    .custom-table thead th {
        background-color: #297bab !important;
        color: #ffffff !important;
        font-size: 14px !important;
    }

    .custom-table tbody tr {
        transition: background-color 0.2s ease-in-out;
    }

    .custom-table tbody tr:hover {
        background-color: #f1f3f5;
    }

    .custom-table td, .custom-table th {
        padding: 0.75rem;
        border: 1px solid #dee2e6 !important;
    }

    .custom-table .btn {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }

    .status-table {
        box-shadow: 6px 6px 5px #28242429;
        background: white;
        padding: 10px 10px 10px 10px;
        border: 2px solid transparent;
        border-radius: 5px;
        margin: 0px auto;
    }

    .text-secondary {
        font-size: 18px !important;
        font-weight: 500 !important;
        font-family: "Poppins", sans-serif !important;
    }

    .custom-table th,
    .custom-table td {
        width: 16.66%; /* 100% / 6 columns = ~16.66% per column */
        word-wrap: break-word;
        text-align: center;
        vertical-align: middle;
    }

</style>

@section('content')
<div class="container mt-4">

    @php
        $statusTitles = [
            'active' => 'Active Sprints',
            'inactive' => 'Inactive Sprints',
            'completed' => 'Completed Sprints',
            'invoice_done' => 'Invoice Done Sprints',
        ];
            $ticketStatusTitles = [
            'need_approval' => 'Tickets Pending Approval',
            'approved_not_started' => 'Approved But Not Started',
            'in_progress' => 'Running Tickets', 
            'to_do' => 'To Do Tickets',
            'invoice_done' => 'Invoice Done Tickets'
        ];
        $categoryLabels = [
            'Technical' => 'Technical',
            'Design' => 'Design',
            'Data Entry' => 'Data Entry',
            'Others' => 'Others'
        ];
        $badgeColors = [
            'Technical' => 'primary',
            'Design' => 'success',
            'Data Entry' => 'warning',
            'Others' => 'secondary'
        ];
    @endphp


    <div class="mb-4">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="project_filter" class="form-label">Filter by Project</label>
                <select name="project_filter" id="project_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Select Project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ request('project_filter') == $project->id ? 'selected' : '' }}>
                            {{ $project->project_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
<div id="sprint-content-wrapper" class="d-none">
    @php
            $hasTickets = false;

            foreach ($ticketData as $status => $categories) {
                foreach ($categories as $tickets) {
                    if (count($tickets) > 0) {
                        $hasTickets = true;
                        break 2;
                    }
                }
            }
        @endphp
    @if ($hasTickets)
    @foreach ($ticketData as $status => $categories)
        @php
            $totalTicketsInSection = collect($categories)->flatten()->count();
        @endphp
        <script>
            window["{{ $status }}_data"] = @json($categories);
        </script>
        @if ($totalTicketsInSection > 0)
            <div class="mb-5">
                <h4 class="mt-4">{{ $ticketStatusTitles[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</h4>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                    @foreach ($categories as $catKey => $tickets)
                        
                            <div class="col">
                                <div class="card h-100 shadow-sm border-start border-4 border-{{ $badgeColors[$catKey] ?? 'dark' }} card-filter"
                                     data-category="{{ $catKey }}" data-group="{{ $status }}" style="cursor:pointer;">
                                  <div class="card-body">
                                    <h6 class="card-title text-uppercase fw-semibold mb-3">
                                        {{ $categoryLabels[$catKey] ?? $catKey }}
                                    </h6>

                                    @php
                                        $totalEstimation = collect($tickets)
                                            ->pluck('time_estimation')
                                            ->filter()
                                            ->map(fn($val) => (float) trim($val, '{}'))
                                            ->sum();
                                    @endphp

                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="text-muted small">
                                            <i class="bi bi-collection"></i>
                                            {{ count($tickets) }} {{ count($tickets) > 1 ? 'Tickets' : 'Ticket' }}
                                        </div>

                                        <div class="text-end">
                                            <div class="fs-5 fw-bold text-{{ $badgeColors[$catKey] ?? 'primary' }}">
                                                <i class="bi bi-clock-history me-1"></i>
                                                {{ $totalEstimation > 0 ? $totalEstimation . ' hrs' : '---' }}
                                            </div>
                                            <div class="small text-muted">Estimated Time</div>
                                        </div>
                                    </div>

                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-{{ $badgeColors[$catKey] ?? 'primary' }}" 
                                            role="progressbar" 
                                            style="width: {{ min($totalEstimation, 100) }}%;" 
                                            aria-valuenow="{{ $totalEstimation }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>


                                </div>
                            </div>
                       
                    @endforeach
                </div>

                <div id="{{ $status }}_table" class="mt-4 d-none status-table">
                    <h6 class="text-secondary mb-2">Tickets (<span class="text-uppercase" id="{{ $status }}_status_label"></span>)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle custom-table">
                            <thead class="table text-white">
                                <tr>
                                    <th class="fs-6 fw-bold text-uppercase text-center">Ticket Title</th>
                                    <th class="fs-6 fw-bold text-uppercase text-center">Sprint Name</th>
                                    <th class="fs-6 fw-bold text-uppercase text-center">Project</th>
                                    <th class="fs-6 fw-bold text-uppercase text-center">Time Estimation</th>
                                    <th class="fs-6 fw-bold text-uppercase text-center">Ticket Status</th>
                                    <th class="fs-6 fw-bold text-uppercase text-center">Approval Status</th>
                                    <th class="fs-6 fw-bold text-uppercase text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="{{ $status }}_table_body">
                                {{-- Filled dynamically by JavaScript --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
    @else
        <div class="alert alert-info text-center">
            No tickets found for the selected project.
        </div>
    @endif
</div>
</div>
@endsection
@section('js_scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Check if ?project_filter is present in the URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('project_filter') && urlParams.get('project_filter') !== '') {
        const wrapper = document.getElementById('sprint-content-wrapper');
        if (wrapper) {
            wrapper.classList.remove('d-none');
        }
    }

    const cards = document.querySelectorAll('.card-filter');

    cards.forEach(card => {
        card.addEventListener('click', function () {
            const category = card.dataset.category;
            const group = card.dataset.group;
            const tableWrapper = document.getElementById(`${group}_table`);

            // Check if this card is already active (toggle logic)
            const isActive = card.classList.contains('active-card');

            // Remove active styles from all in this group
            document.querySelectorAll(`.card-filter[data-group="${group}"]`).forEach(c => 
                c.classList.remove('border-4', 'border-dark-subtle', 'shadow', 'active-card')
            );

            if (tableWrapper) {
                tableWrapper.classList.add('d-none');
            }

            // If the card was already active, just return after hiding
            if (isActive) {
                return;
            }

            card.classList.add('border-4', 'border-dark-subtle', 'shadow', 'active-card');

            // Set label text
            const labelMap = {
                'Technical': 'Technical',
                'Design': 'Design',
                'Data Entry': 'Data Entry',
                'Others': 'Others'
            };
            const label = labelMap[category] || category;
            const labelEl = document.getElementById(`${group}_status_label`);
            if (!labelEl) {
                console.warn(`Label element not found for: ${group}_status_label`);
            } else {
                labelEl.innerText = label;
            }

            const ticketGroups = window[`${group}_data`];
            if (!ticketGroups) {
                console.error(`No ticket group data found for ${group}_data`);
                return;
            }

            console.log('Available Categories:', Object.keys(ticketGroups));
            const tickets = ticketGroups[category] || [];

            console.log(`Tickets in category [${category}]:`, tickets);

            const tbody = document.getElementById(`${group}_table_body`);
            if (!tbody) {
                console.error(`Table body element not found for ${group}_table_body`);
                return;
            }

            tbody.innerHTML = '';

            if (tickets.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-muted text-center">No tickets found.</td></tr>`;
            } else {
                tickets.forEach((t, i) => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${t.title}</td>
                            <td>${t.sprint_name || '-'}</td>
                            <td>${t.project_name || '-'}</td>
                            <td>${formatTimeEstimation(t.time_estimation)}</td>
                            <td>
                                <span class="badge bg-info text-dark">${t.status.replace('_', ' ').toUpperCase()}</span>
                            </td>
                            <td>
                                ${t.is_estimation_approved
                                    ? '<span class="badge bg-success">Approved</span>'
                                    : '<span class="badge bg-warning text-dark">Not Approved</span>'}
                            </td>
                            <td class="text-center">
                                <a href="/view/ticket/${t.id}" title="View Ticket" target="_blank">
                                    <i class="fa fa-eye fa-fw pointer"></i>
                                </a>
                            </td>
                        </tr>
                    `;

                });
            }

            if (tableWrapper) {
                tableWrapper.classList.remove('d-none');
            }
        });
    });
});
function formatTimeEstimation(value) {
    if (!value || isNaN(value)) return '-';

    const floatVal = parseFloat(value);
    const hours = Math.floor(floatVal);
    const minutesDecimal = floatVal - hours;
    const minutes = Math.round(minutesDecimal * 100); // 0.20 → 20

    let result = '';

    if (hours > 0) {
        result += `${hours} ${hours === 1 ? 'Hour' : 'Hours'}`;
    }

    if (minutes > 0) {
        if (result) result += ' ';
        result += `${minutes} Mins`;
    }

    return result || '-';
}

</script>
@endsection