@extends('layout')

@section('title', 'Upwork Agent & Discovery Settings')

@section('content')
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  @php($cred = $api['credential'])
  <div class="uw-card" style="max-width:760px;">
    <div class="uw-h">Upwork API (read-only)</div>
    <div class="small mb-2">
      @if($cred && $cred->isConnected())
        <span class="badge bg-success">Connected</span>
        Access token until {{ optional($cred->expires_at)->format('d M H:i') }} · refresh window until {{ optional($cred->refresh_expires_at)->format('d M Y') }}
        · last refreshed {{ optional($cred->last_refreshed_at)->diffForHumans() ?? '—' }}
      @elseif($cred)
        <span class="badge bg-warning text-dark">Expired</span> The refresh window has passed. Reconnect to resume discovery.
      @else
        <span class="badge bg-secondary">Not connected</span>
      @endif
      @if($cred && $cred->last_error)<div class="text-danger mt-1">Last error: {{ $cred->last_error }}</div>@endif
    </div>
    <div class="uw-help mb-2">Search and job details only: this connection cannot submit proposals, send messages, save jobs or change profiles.
      API key: {{ $api['oauth_configured'] ? 'set in .env' : 'not set (UPWORK_CLIENT_ID / UPWORK_CLIENT_SECRET)' }} ·
      Scheduler: {{ $api['scheduler_enabled'] ? 'on' : 'off (UPWORK_SCHEDULER_ENABLED)' }}</div>
    @if($canManage)
      <div class="d-flex gap-2">
        @if($api['oauth_configured'])<a href="{{ route('upwork.config.api.connect') }}" class="btn btn-sm btn-primary">{{ $cred ? 'Reconnect' : 'Connect Upwork API' }}</a>@endif
        @if($cred)
          <form method="POST" action="{{ route('upwork.config.api.disconnect') }}" onsubmit="return confirm('Disconnect and delete the stored Upwork tokens?')">@csrf
            <button class="btn btn-sm btn-outline-danger">Disconnect</button></form>
        @endif
      </div>
    @endif
  </div>

  @if($openAlerts)<div style="max-width:760px;">@include('upwork.partials.open-alerts', ['alerts' => $openAlerts])</div>@endif
  @php($mc = $mcp['credential'])
  <div class="uw-card" style="max-width:760px;">
    <div class="uw-h">Upwork MCP, direct (read-only, no AI cost)</div>
    <div class="small mb-2">
      @if($mc && $mc->isConnected())
        <span class="badge bg-success">Connected</span>
        @php($acct = collect($mcp['accounts'])->firstWhere('org_uid', $mc->tenant_id))
        Searching as <strong>{{ $acct['name'] ?? ($mc->tenant_id ?: 'no account chosen') }}</strong>{{ $acct ? ' (' . $acct['role_label'] . ')' : '' }}
        · token until {{ optional($mc->expires_at)->format('d M H:i') }} · refresh window until {{ optional($mc->refresh_expires_at)->format('d M Y') }}
      @elseif($mc)
        <span class="badge bg-warning text-dark">Expired</span> Reconnect to resume.
      @else
        <span class="badge bg-secondary">Not connected</span>
      @endif
      @if($mc && $mc->last_error)<div class="text-danger mt-1">Last error: {{ $mc->last_error }}</div>@endif
    </div>
    <div class="uw-help mb-2">The PMS calls the Upwork MCP itself, with no AI model in between. Only <code>find_jobs</code> (search, get) and <code>list_accounts</code> are ever called; the MCP's write tools are refused in code.
      No API key needed: connecting registers the PMS with Upwork and asks you to approve access. Select <em>Upwork source: Upwork MCP</em> below to use it for discovery.</div>
    @if($canManage)
      <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ route('upwork.config.mcp.connect') }}" class="btn btn-sm btn-primary">{{ $mc ? 'Reconnect' : 'Connect Upwork MCP' }}</a>
        @if($mc && count($mcp['accounts']) > 1)
          <form method="POST" action="{{ route('upwork.config.mcp.account') }}" class="d-flex gap-1 m-0">@csrf
            <select name="org_uid" class="form-select form-select-sm">
              @foreach($mcp['accounts'] as $a)<option value="{{ $a['org_uid'] }}" {{ $mc->tenant_id === $a['org_uid'] ? 'selected' : '' }}>{{ $a['name'] }} ({{ $a['role_label'] }})</option>@endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Use</button></form>
        @endif
        @if($mc)
          <form method="POST" action="{{ route('upwork.config.mcp.disconnect') }}" class="m-0" onsubmit="return confirm('Disconnect and delete the stored Upwork MCP tokens?')">@csrf
            <button class="btn btn-sm btn-outline-danger">Disconnect</button></form>
        @endif
      </div>
      @if($mcp['awaiting_paste'])
        <form method="POST" action="{{ route('upwork.config.mcp.paste') }}" class="mt-3">@csrf
          <label class="form-label small mb-1" for="mcp-callback-url"><strong>Finish connecting:</strong> after approving on Upwork, your browser shows "This site can't be reached" at an address starting with <code>{{ \App\Services\Upwork\Api\UpworkMcpOAuth::LOOPBACK_REDIRECT_URI }}</code>. That is expected. Copy the full address from the address bar and paste it here.</label>
          <div class="d-flex gap-1">
            <input type="text" id="mcp-callback-url" name="callback_url" class="form-control form-control-sm" autocomplete="off" required placeholder="http://127.0.0.1:45127/upwork/mcp/callback?code=…&amp;state=…">
            <button class="btn btn-sm btn-primary">Finish</button>
          </div>
        </form>
      @endif
    @endif
  </div>

  <form method="POST" action="{{ route('upwork.config.settings.update') }}" class="uw-card" style="max-width:760px;">
    @csrf @method('PUT')
    <div class="uw-h">Agent & discovery</div>
    <div class="alert alert-light border small py-2">Discovery runs on this server (scheduler) and reads these settings every cycle. "Agent enabled" switches scheduled discovery on or off. Upwork access stays read-only whatever is set here.</div>

    <div class="mb-3">
      <label class="form-label small fw-semibold mb-1" for="discovery_client">Upwork source</label>
      <select id="discovery_client" name="discovery_client" class="form-select form-select-sm" style="max-width:320px;" {{ (!$canManage) ? "disabled" : "" }}>
        @foreach($clients as $c)
          <option value="{{ $c }}" {{ old('discovery_client', $values['discovery.client']) === $c ? 'selected' : '' }}>{{ ['graphql' => 'Upwork API (official, read-only)', 'mcp' => 'Upwork MCP, direct (read-only, no AI cost)', 'fixture' => 'Fixture data (development only)'][$c] ?? $c }}</option>
        @endforeach
      </select>
    </div>

    @foreach($fields as $field => [$key, $rules, $label, $help])
      <div class="mb-3">
        @if($field === 'agent_enabled')
          <div class="form-check form-switch">
            <input type="hidden" name="agent_enabled" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="agent_enabled" name="agent_enabled" value="1" {{ (old('agent_enabled', $values[$key])) ? "checked" : "" }} {{ (!$canManage) ? "disabled" : "" }}>
            <label class="form-check-label fw-semibold small" for="agent_enabled">{{ $label }}</label>
          </div>
        @else
          <label class="form-label small fw-semibold mb-1" for="{{ $field }}">{{ $label }}</label>
          <input type="number" step="any" class="form-control form-control-sm" style="max-width:180px;" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $values[$key]) }}" {{ (!$canManage) ? "disabled" : "" }}>
        @endif
        <div class="uw-help">{{ $help }}</div>
      </div>
    @endforeach

    <div class="mb-3">
      <label class="form-label small fw-semibold mb-1">Searches per run, by tier</label>
      <div class="row g-2">
        @foreach($tiers as $tier => $label)
          <div class="col-6 col-md-3"><label class="uw-help mb-0" for="tier_{{ $tier }}">{{ $label }}</label>
            <input type="number" id="tier_{{ $tier }}" name="tier_searches[{{ $tier }}]" min="0" max="30" class="form-control form-control-sm"
              value="{{ old("tier_searches.$tier", $values['discovery.tier_searches_per_run'][$tier] ?? 0) }}" {{ (!$canManage) ? "disabled" : "" }}></div>
        @endforeach
      </div>
      <div class="uw-help">Hot is planned first. Each tier's allowance is shared between its due profiles; the run cap and the daily cap above still apply. 0 pauses a tier.</div>
    </div>

    <div class="mb-3">
      <label class="form-label small fw-semibold mb-1">Notification channels</label>
      @foreach($channels as $channel => $label)
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="notification_channels[]" value="{{ $channel }}" id="ch_{{ $channel }}" {{ (in_array($channel, old('notification_channels', $values['notifications.channels'] ?? []), true)) ? "checked" : "" }} {{ (!$canManage) ? "disabled" : "" }}>
          <label class="form-check-label small" for="ch_{{ $channel }}">{{ $label }}</label>
        </div>
      @endforeach
      <div class="uw-help">Used for new APPLY / REVIEW opportunities (SKIP never notifies).</div>
      <label class="form-label small fw-semibold mb-1 mt-2" for="notification_mail_to">Email recipients</label>
      <textarea id="notification_mail_to" name="notification_mail_to" rows="2" class="form-control form-control-sm" placeholder="bde@code4each.com, sales@code4each.com" {{ (!$canManage) ? "disabled" : "" }}>{{ old('notification_mail_to', implode(', ', (array) ($values['notifications.mail_to'] ?? []))) }}</textarea>
    </div>

    <div class="mb-3">
      <div class="form-check form-switch">
        <input type="hidden" name="review_reminders_enabled" value="0">
        <input class="form-check-input" type="checkbox" role="switch" id="review_reminders_enabled" name="review_reminders_enabled" value="1" {{ old('review_reminders_enabled', $values['review.reminders_enabled'] ?? false) ? "checked" : "" }} {{ (!$canManage) ? "disabled" : "" }}>
        <label class="form-check-label fw-semibold small" for="review_reminders_enabled">Review reminders</label>
      </div>
      <div class="uw-help">New APPLY / REVIEW jobs should be reviewed within {{ config('upwork.review.deadline_minutes') }} minutes of first being seen. When on, reminders go to the channels above at {{ implode(', ', (array) config('upwork.review.reminder_minutes')) }} minutes, then every {{ config('upwork.review.repeat_minutes') }} minutes once overdue, until someone approves or rejects the job or snoozes it. Deadlines are tracked either way; nothing is approved, rejected or archived automatically. Needs the server scheduler (UPWORK_SCHEDULER_ENABLED).</div>
    </div>

    @if($canManage)<button class="btn btn-primary btn-sm">Save settings</button>@endif
  </form>
</div>
@endsection
