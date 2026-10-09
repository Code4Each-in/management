@extends('layout')

@section('title', 'Upwork Client Intelligence')

@section('content')
@php
    $M = \App\Models\UpworkOpportunity::class;
    $th = 'padding:0.625rem 0.75rem; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; white-space:nowrap;';
    $td = 'padding:0.625rem 0.75rem; vertical-align:middle;';
@endphp
<div style="margin: 1rem auto 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <a href="{{ route('upwork.opportunities.index') }}" class="text-muted small text-decoration-none" style="font-weight:600;"><i class="bi bi-arrow-left"></i> Opportunities</a>
  </div>

  <form method="GET" action="{{ route('upwork.clients.index') }}" class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <span style="font-size:0.875rem; color:#6b7280; font-weight:500; margin-right:0.5rem;">{{ $clients->total() }} clients</span>
    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Upwork client id or country" class="form-control form-control-sm" style="max-width:240px;">
    <select name="sort" class="form-select form-select-sm" style="max-width:180px;">
      <option value="last_seen" {{ ($sort === 'last_seen') ? "selected" : "" }}>Sort: last seen</option>
      <option value="jobs" {{ ($sort === 'jobs') ? "selected" : "" }}>Sort: most jobs</option>
      <option value="spend" {{ ($sort === 'spend') ? "selected" : "" }}>Sort: total spend</option>
    </select>
    <button class="btn btn-primary btn-sm">Filter</button>
  </form>

  <div style="background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; overflow-x:auto;">
    <table class="mb-0" style="width:100%; font-size:0.85rem;">
      <thead style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
        <tr>
          <th style="{{ $th }}">Client</th><th style="{{ $th }}">Jobs seen</th><th style="{{ $th }}">Rating</th>
          <th style="{{ $th }}">Spend</th><th style="{{ $th }}">Hire rate</th><th style="{{ $th }}">Client score</th>
          <th style="{{ $th }}">Approved / won</th><th style="{{ $th }}">Last seen</th>
        </tr>
      </thead>
      <tbody>
        @forelse($clients as $client)
          <tr style="border-bottom:1px solid #f3f4f6;">
            <td style="{{ $td }}"><a href="{{ route('upwork.clients.show', $client) }}" style="font-weight:600;">{{ $client->external_id }}</a><div class="text-muted" style="font-size:0.72rem;">{{ $client->country ?? '—' }}</div></td>
            <td style="{{ $td }}">{{ $client->opportunities_count }}</td>
            <td style="{{ $td }}">{{ $client->rating !== null ? number_format($client->rating, 2) : '—' }} @if($client->reviews_count !== null)<span class="text-muted">({{ $client->reviews_count }})</span>@endif</td>
            <td style="{{ $td }}">{{ $M::money($client->total_spent) }}</td>
            <td style="{{ $td }}">{{ $client->hire_rate !== null ? round($client->hire_rate) . '%' : '—' }}</td>
            <td style="{{ $td }}">{{ $client->last_client_score !== null ? round($client->last_client_score) : '—' }}</td>
            <td style="{{ $td }}">{{ $client->approved_count }} / {{ $client->won_count }}</td>
            <td style="{{ $td }}">{{ optional($client->last_seen_at)->diffForHumans() ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="8" style="{{ $td }}" class="text-muted text-center py-4">No clients identified yet. Clients appear once the agent sends full job details with Upwork's client id.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-3">{{ $clients->links() }}</div>
</div>
@endsection
