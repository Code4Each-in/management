{{-- Review SLA status and per-job snooze controls. Every change is a POST; GET pages only display. --}}
@php
  $fmt = fn($at) => \App\Services\Upwork\Review\ReviewSlaService::format($at);
  $selected = $selected ?? null;
@endphp
@if($review['tracked'])
  <div class="mt-3 pt-3 border-top">
    <div class="d-flex align-items-center gap-2 flex-wrap small">
      @if(!$review['open'])
        <span class="badge" style="background:#e5e7eb; color:#374151;">Reviewed{{ $o->reviewed_at ? ' ' . $fmt($o->reviewed_at) : '' }}</span>
        <span class="text-muted">Review was due {{ $fmt($o->review_due_at) }}{{ $o->reviewed_at && $o->reviewed_at->gt($o->review_due_at) ? ' (late)' : '' }}.</span>
      @elseif($review['overdue'])
        <span class="badge" style="background:#fee2e2; color:#991b1b;">Review overdue</span>
        <span>Due {{ $fmt($o->review_due_at) }} ({{ $o->review_due_at->diffForHumans() }}). The job stays new until someone approves or rejects it.</span>
      @else
        <span class="badge" style="background:#fef3c7; color:#92400e;">Review due {{ $o->review_due_at->diffForHumans() }}</span>
        <span class="text-muted">{{ $fmt($o->review_due_at) }}</span>
      @endif
    </div>

    @if($review['open'])
      @if($review['snoozed'])
        <div class="small mt-2">
          Reminders snoozed until <strong>{{ $fmt($o->review_snoozed_until) }}</strong>{{ $o->reviewSnoozer ? ' by ' . $o->reviewSnoozer->first_name : '' }}. Other jobs still remind as usual.
        </div>
      @endif
      @can('snooze', $o)
        <div class="d-flex gap-2 flex-wrap align-items-end mt-2">
          @foreach(['1h' => '1 hour', '4h' => '4 hours', 'tomorrow' => 'Until ' . $fmt($review['presets']['tomorrow'])] as $preset => $label)
            <form method="POST" action="{{ route('upwork.opportunities.snooze', $o) }}" class="m-0">
              @csrf
              <input type="hidden" name="preset" value="{{ $preset }}">
              <button type="submit" class="btn btn-sm {{ $selected === $preset ? 'btn-primary' : 'btn-outline-secondary' }}" title="Until {{ $fmt($review['presets'][$preset]) }}">
                <i class="bi bi-bell-slash"></i> Snooze {{ $label }}
              </button>
            </form>
          @endforeach
          <form method="POST" action="{{ route('upwork.opportunities.snooze', $o) }}" class="m-0 d-flex gap-1 align-items-end">
            @csrf
            <input type="hidden" name="preset" value="custom">
            <div>
              <label class="form-label small mb-0" for="snooze_until_{{ $o->id }}" style="font-size:0.7rem;">Until ({{ $review['timezone'] }})</label>
              <input type="datetime-local" id="snooze_until_{{ $o->id }}" name="until" required class="form-control form-control-sm {{ $selected === 'custom' ? 'border-primary' : '' }}"
                     min="{{ $review['now_local']->copy()->addMinutes(5)->format('Y-m-d\TH:i') }}" value="{{ old('until') }}">
            </div>
            <button type="submit" class="btn btn-sm {{ $selected === 'custom' ? 'btn-primary' : 'btn-outline-secondary' }}">Snooze</button>
          </form>
          @if($review['snoozed'])
            <form method="POST" action="{{ route('upwork.opportunities.unsnooze', $o) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-bell"></i> Resume reminders</button>
            </form>
          @endif
        </div>
        <div class="text-muted mt-1" style="font-size:0.72rem;">Snoozing silences reminders for this job only. The review deadline does not move.</div>
      @endcan
    @endif
  </div>
@endif
