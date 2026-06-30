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
  <div class="row g-3 mb-4">
    @foreach([
      ['Total',      $stats['total'],     '#0d6efd'],
      ['QA Review',      $stats['qa_review'], '#0dcaf0'],
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
      <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">{{ $tickets->total() }} tickets</span>
      <input type="search" class="form-control form-control-sm" placeholder="Search tickets…" style="width: 220px; font-family: inherit; font-size: 0.875rem; padding: 0.375rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; outline: none; transition: border-color 0.15s ease;">
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
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">QA</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Status</th>
                <th style="padding: 0.625rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Priority</th>
                <th style="padding: 0.625rem 1.25rem; text-align: right; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($tickets as $ticket)
            @php
                $statusStyles = [
                'qa_review'   => ['bg' => '#e3f2fd', 'color' => '#0d6efd'],
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
      <span style="font-size: 0.875rem; color: #6b7280;">Showing {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}</span>
      <div style="font-size: 0.875rem;">{{ $tickets->links() }}</div>
    </div>
  </div>

</div>
@endsection
