@extends('layout')
@section('title', 'Ticket Logs')
@section('subtitle', 'Logs')

@section('content')
<div class="container mt-4">

    @php
        $statusTitles = [
            'active' => 'Active Sprints',
            'inactive' => 'Inactive Sprints',
            'completed' => 'Completed Sprints',
            'invoice_done' => 'Invoice Done Sprints',
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
    @foreach ($sprintData as $statusKey => $categories)
        <div class="mb-5">
            <h5 class="mb-4">{{ $statusTitles[$statusKey] ?? ucfirst($statusKey) }}</h5>

            @php
                $groupId = 'group_' . $statusKey;
            @endphp

            <script>
                window["{{ $groupId }}_data"] = @json($categories);
            </script>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                @foreach ($categories as $catKey => $tickets)
                    <div class="col">
                        <div class="card h-100 shadow-sm border-start border-4 border-{{ $badgeColors[$catKey] ?? 'dark' }} card-filter"
                             data-category="{{ $catKey }}" data-group="{{ $groupId }}" style="cursor:pointer;">
                            <div class="card-body">
                                <h6 class="card-title">{{ $categoryLabels[$catKey] ?? $catKey }}</h6>
                                @php
                                    $totalEstimation = collect($tickets)
                                        ->pluck('time_estimation')
                                        ->filter() // Remove nulls
                                        ->map(function ($val) {
                                            return (float) trim($val, '{}');
                                        })
                                        ->sum();
                                @endphp

                                <p class="fs-4 fw-bold text-{{ $badgeColors[$catKey] ?? 'dark' }}">
                                    {{ count($tickets) }} Ticket{{ count($tickets) > 1 ? 's' : '' }}
                                </p>
                                <p class="text-muted small mb-0">
                                    Estimation: {{ $totalEstimation > 0 ? $totalEstimation . ' hrs' : '---' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="{{ $groupId }}_table" class="mt-4 d-none">
                <h6 class="text-secondary mb-3">Tickets (<span class="text-uppercase" id="{{ $groupId }}_status_label"></span>)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Ticket Title</th>
                                <th>Sprint Name</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="{{ $groupId }}_table_body">
                            {{-- Filled dynamically by JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
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
                            <td>${i + 1}</td>
                            <td>${t.title}</td>
                            <td>${t.sprint_name || '-'}</td>
                            <td>${t.project_name || '-'}</td>
                            <td>${t.status.replace('_', ' ').toUpperCase()}</td>
                            <td><a href="/view/ticket/${t.id}" class="btn btn-sm btn-outline-primary">View</a></td>
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
</script>
@endsection