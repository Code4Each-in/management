{{-- Open operational alerts (AlertService::open()). Shown until the problem clears. --}}
@if(!empty($alerts))
  <div class="alert alert-warning border-0 small py-2 px-3 mb-2" role="alert">
    <div style="font-weight:700;">⚠️ Open alert{{ count($alerts) > 1 ? 's' : '' }}</div>
    <ul class="mb-0 ps-3">
      @foreach($alerts as $alert)
        <li>
          <strong>{{ $alert['label'] }}:</strong> {{ $alert['title'] }}
          <span class="text-muted">— {{ $alert['count'] }}× since {{ optional($alert['first_at'])->setTimezone(config('app.timezone'))->format('d M H:i') }},
            last {{ optional($alert['last_at'])->diffForHumans() }}{{ $alert['notified'] ? '' : ' (not emailed yet)' }}</span>
        </li>
      @endforeach
    </ul>
  </div>
@endif
