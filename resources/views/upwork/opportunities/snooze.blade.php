@extends('layout')

@section('title', 'Snooze review reminders')

@section('content')
@php
  $o = $opportunity;
  $card = 'background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04); padding:1.25rem; margin-bottom:1rem;';
@endphp
<div style="margin: 1rem auto 2rem; max-width: 760px;">
  <a href="{{ route('upwork.opportunities.show', $o) }}" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3" style="font-weight:600;">
    <i class="bi bi-arrow-left"></i> Open the job instead
  </a>

  <div style="{{ $card }}">
    <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280;">Snooze review reminders</div>
    <h5 style="font-weight:700; margin:0.5rem 0 0;">{{ $o->title }}</h5>
    <div class="small text-muted">Score {{ $o->score !== null ? round($o->score, 1) : '—' }} · {{ $o->budget_label }} · {{ $o->client_country ?? '—' }}</div>

    @if($o->status !== \App\Models\UpworkOpportunity::STATUS_NEW)
      <div class="alert alert-light border small mt-3 mb-0">This job has already been reviewed ({{ $o->status_label }}). There is nothing to snooze.</div>
    @elseif(!$review['tracked'])
      <div class="alert alert-light border small mt-3 mb-0">This job has no review deadline, so it sends no reminders.</div>
    @else
      <div class="small mt-3">Nothing has changed yet. Choose how long to silence reminders for <strong>this job only</strong>:</div>
      @include('upwork.opportunities.partials.review-sla', ['o' => $o, 'review' => $review, 'selected' => $preset])
    @endif
  </div>
</div>
@endsection
