@extends('layout')
@section('title', 'Pending Approvals')
@section('subtitle', 'Show')
@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="sprint-section">
            <div class="sprint-header production">
                <div class="section-left">
                    <div class="section-icon bg-production" style="background-color: #297bab;">P</div>
                    <div class="section-title" style="color: #297bab;">Total Pending Requests</div>
                    <div class="section-title">
                    • {{ $ticketsCount ?? 0 }} Total {{ ($ticketsCount ?? 0) > 1 ? 'Tickets' : 'Ticket' }}
                </div>
                </div>
            </div>

            <div class="box-header with-border" id="filter-box">
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="styled-sprint-table sprint-table" id="pendingApprovalsTable">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Ticket Title</th>
                                    <th>Project</th>
                                    <th>Sprint</th>
                                    <th>Time Estimation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr class="pointer" onclick="if (!event.target.closest('.actions-cell')) window.open('{{ url('/ticket/'.$ticket->id) }}', '_blank');">
                                        <td>{{ $ticket->id }}</td>
                                        <td>{{ $ticket->title }}</td>
                                        <td>{{ $ticket->project->project_name ?? 'N/A' }}</td>
                                        <td>{{ $ticket->sprintDetails->name ?? 'N/A' }}</td>
                                        <td>{{ $ticket->time_estimation }} hr</td>
                                        <td class="actions-cell">
                                            @if($ticket->time_estimation && in_array(Auth::user()->role_id, [1, 6]))
                                                <a href="{{ route('ticket.approveEstimation', $ticket->id) }}" class="badge bg-success text-white text-decoration-none">
                                                    <i class="fa-solid fa-check-circle me-1 white-icon"></i> Approve
                                                </a>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No pending approvals found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div> 
                </div> 
            </div> 
        </div> 
    </div> 
</div>
@endsection
@section('js_scripts')
 <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#approvalsTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
            });
        });
    </script>
@endsection