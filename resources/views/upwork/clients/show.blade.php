@extends('layout')

@section('title', 'Upwork Client')

@section('content')
@php
    $M = \App\Models\UpworkOpportunity::class;
    $card = 'background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04); padding:1.25rem; margin-bottom:1rem;';
    $h = 'font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280; margin-bottom:0.75rem;';
    $dt = 'font-size:0.75rem; color:#6b7280; font-weight:600;';
    $dd = 'font-size:0.9rem; color:#1f2937; margin-bottom:0.625rem;';
@endphp
<div style="margin: 1rem auto 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
  <a href="{{ route('upwork.clients.index') }}" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3" style="font-weight:600;"><i class="bi bi-arrow-left"></i> Client intelligence</a>

  <div class="row">
    <div class="col-lg-5">
      <div style="{{ $card }}">
        <div style="{{ $h }}">Upwork client {{ $client->external_id }}</div>
        <div class="row">
          <div class="col-6"><div style="{{ $dt }}">Country</div><div style="{{ $dd }}">{{ $client->country ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Timezone</div><div style="{{ $dd }}">{{ $client->timezone ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Rating</div><div style="{{ $dd }}">{{ $client->rating !== null ? number_format($client->rating, 2) : '—' }} @if($client->reviews_count !== null)<span class="text-muted">({{ $client->reviews_count }} reviews)</span>@endif</div></div>
          <div class="col-6"><div style="{{ $dt }}">Total spent</div><div style="{{ $dd }}">{{ $M::money($client->total_spent) }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Hire rate</div><div style="{{ $dd }}">{{ $client->hire_rate !== null ? round($client->hire_rate) . '%' : '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Jobs posted / hired</div><div style="{{ $dd }}">{{ $client->jobs_posted ?? '—' }} / {{ $client->jobs_hired ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Payment</div><div style="{{ $dd }}">{{ $client->payment_verified === null ? '—' : ($client->payment_verified ? 'Verified' : 'Not verified') }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Latest client score</div><div style="{{ $dd }}">{{ $client->last_client_score !== null ? round($client->last_client_score) . '/100' : '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">First seen</div><div style="{{ $dd }}">{{ optional($client->first_seen_at)->format('d M Y H:i') ?? '—' }}</div></div>
          <div class="col-6"><div style="{{ $dt }}">Last seen</div><div style="{{ $dd }}">{{ optional($client->last_seen_at)->format('d M Y H:i') ?? '—' }}</div></div>
        </div>
        <div class="text-muted" style="font-size:0.72rem;">Figures are the latest Upwork reported for this client. Upwork's client id is the only identity used.</div>
      </div>

      <div style="{{ $card }}">
        <div style="{{ $h }}">Reported figures over time</div>
        @if($snapshots->isEmpty())
          <div class="text-muted small">No snapshots yet.</div>
        @else
          <table class="w-100" style="font-size:0.8rem;">
            <thead><tr class="text-muted"><th>Recorded</th><th>Rating</th><th>Reviews</th><th>Spend</th><th>Hire rate</th><th>Posted / hired</th></tr></thead>
            <tbody>
              @foreach($snapshots as $s)
                <tr>
                  <td>{{ $s->recorded_at->format('d M Y H:i') }}</td>
                  <td>{{ $s->rating !== null ? number_format($s->rating, 2) : '—' }}</td>
                  <td>{{ $s->reviews_count ?? '—' }}</td>
                  <td>{{ $M::money($s->total_spent) }}</td>
                  <td>{{ $s->hire_rate !== null ? round($s->hire_rate) . '%' : '—' }}</td>
                  <td>{{ $s->jobs_posted ?? '—' }} / {{ $s->jobs_hired ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>

    <div class="col-lg-7">
      <div style="{{ $card }}">
        <div style="{{ $h }}">History</div>
        @include('upwork.clients.partials.history', ['client' => $client, 'history' => $history, 'link' => false, 'mode' => 'client'])
      </div>
    </div>
  </div>
</div>
@endsection
