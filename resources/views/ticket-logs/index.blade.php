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
        $badgeColors = [
            'to_do' => 'info',
            'in_progress' => 'warning',
            'ready' => 'primary',
            'complete' => 'success',
        ];
        $ticketLabels = [
            'to_do' => 'To Do',
            'in_progress' => 'In Progress',
            'ready' => 'Ready',
            'complete' => 'Completed',
        ];
    @endphp

    @foreach ($sprintData as $sprintStatusKey => $sprints)
        @php
            $groupId = 'group_' . $sprintStatusKey;
        @endphp

        {{-- Embed per-group JSON data for JS --}}
        <script>
            window["{{ $groupId }}_data"] = @json($sprints);
        </script>

        <div class="mb-5">
            <h5 class="mb-4">
                {{ $statusTitles[$sprintStatusKey] ?? ucfirst($sprintStatusKey) }}
                <span class="badge bg-dark">{{ $sprints->count() }} Sprints</span>
            </h5>

            {{-- Cards --}}
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                @foreach (['to_do', 'in_progress', 'ready', 'complete'] as $status)
                    @php
                        $count = $sprints->sum("{$status}_tickets_count");
                    @endphp
                    <div class="col">
                        <div class="card h-100 shadow-sm border-start border-4 border-{{ $badgeColors[$status] }} card-filter"
                             data-status="{{ $status }}" data-group="{{ $groupId }}" style="cursor:pointer;">
                            <div class="card-body">
                                <h6 class="card-title">{{ $ticketLabels[$status] }}</h6>
                                <p class="fs-4 fw-bold text-{{ $badgeColors[$status] }}">{{ $count }}</p>
                                <p class="text-muted small mb-0">Tickets across {{ $sprints->count() }} sprints</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Ticket Table --}}
            <div id="{{ $groupId }}_table" class="mt-4 d-none">
                <h6 class="text-secondary mb-3">Tickets (<span class="text-uppercase" id="{{ $groupId }}_status_label"></span>)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Ticket Title</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="{{ $groupId }}_table_body">
                            {{-- Filled dynamically --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

</div>
@endsection

@section('js_scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const allCards = document.querySelectorAll('.card-filter');

        allCards.forEach(card => {
            card.addEventListener('click', function () {
                const status = card.dataset.status;
                const group = card.dataset.group;

                // Remove highlight from all in same group
                document.querySelectorAll(`.card-filter[data-group="${group}"]`).forEach(c => {
                    c.classList.remove('border-4', 'border-dark-subtle', 'shadow');
                });

                // Highlight current
                card.classList.add('border-4', 'border-dark-subtle', 'shadow');

                // Update table label
                const labelMap = {
                    to_do: 'To Do',
                    in_progress: 'In Progress',
                    ready: 'Ready',
                    complete: 'Completed'
                };
                document.getElementById(`${group}_status_label`).innerText = labelMap[status];

                // Hide all other tables
                document.querySelectorAll(`[id$="_table"]`).forEach(table => table.classList.add('d-none'));

                // Load the correct data
                const allTickets = window[`${group}_data`];

                const filtered = [];
                for (const sprint of allTickets) {
                    if (!sprint.tickets) continue;
                    for (const ticket of sprint.tickets) {
                        if (ticket.status === status) {
                            filtered.push({
                                id: ticket.id,
                                title: ticket.title,
                                status: ticket.status
                            });
                        }
                    }
                }

                // Populate table
                const tbody = document.getElementById(`${group}_table_body`);
                tbody.innerHTML = '';

                if (filtered.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">No tickets found.</td></tr>`;
                } else {
                    filtered.forEach((t, i) => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${i + 1}</td>
                                <td>${t.title}</td>
                                <td>${t.status.replace('_', ' ').toUpperCase()}</td>
                                <td><a href="/view/ticket/${t.id}" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                        `;
                    });
                }

                // Show correct table
                document.getElementById(`${group}_table`).classList.remove('d-none');
            });
        });
    });
</script>
@endsection
