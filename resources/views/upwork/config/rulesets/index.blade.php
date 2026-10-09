@extends('layout')

@section('title', 'Upwork Scoring')

@section('content')
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="uw-card">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="uw-h">Scoring in use</div>
        @if($current)
          <div style="font-size:1.4rem; font-weight:600;">Ruleset v{{ $current->version }}</div>
          <div class="uw-help">Published {{ optional($current->published_at)->format('d M Y H:i') }}{{ $current->publisher ? ' by ' . $current->publisher->first_name . ' ' . $current->publisher->last_name : '' }} · {{ $current->rules()->count() }} rules · APPLY ≥ {{ $current->thresholds['apply'] }}, REVIEW ≥ {{ $current->thresholds['review'] }}</div>
          <a href="{{ route('upwork.config.rulesets.show', $current->version) }}" class="small">View v{{ $current->version }}</a>
        @else
          <div class="text-muted">No published ruleset. Run the UpworkIntelligenceSeeder.</div>
        @endif
      </div>
      @if($canManage)
        <div>
          @if($draft)
            <a href="{{ route('upwork.config.rulesets.draft.edit') }}" class="btn btn-primary btn-sm">Continue draft</a>
          @else
            <form method="POST" action="{{ route('upwork.config.rulesets.draft.create') }}">@csrf<button class="btn btn-primary btn-sm">Start a draft from v{{ optional($current)->version }}</button></form>
          @endif
        </div>
      @endif
    </div>
    <div class="uw-help mt-2">Published versions never change. Edit a draft, then publish it as the next version. Stored opportunity scores keep the version they were scored with.</div>
  </div>

  <div class="uw-card p-0">
    <div class="uw-h px-3 pt-3">Version history</div>
    <table class="uw-table">
      <thead><tr><th>Version</th><th>Status</th><th>Published</th><th>By</th><th>Jobs scored</th><th>Notes</th></tr></thead>
      <tbody>
        @foreach($versions as $v)
          <tr>
            <td><a href="{{ route('upwork.config.rulesets.show', $v->version) }}">v{{ $v->version }}</a></td>
            <td>{{ $v->status }}</td>
            <td>{{ optional($v->published_at)->format('d M Y H:i') }}</td>
            <td>{{ $v->publisher ? $v->publisher->first_name . ' ' . $v->publisher->last_name : 'System' }}</td>
            <td>{{ $scored[$v->version] ?? 0 }}</td>
            <td class="uw-help">{{ \Illuminate\Support\Str::limit($v->notes, 120) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
