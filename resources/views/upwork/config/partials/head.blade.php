{{-- Shared head for Upwork configuration pages: styles, sub-navigation, flash messages, read-only notice. --}}
<style>
  .uw-wrap { margin: 1rem auto 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
  .uw-card { background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04); padding:1.25rem; margin-bottom:1rem; }
  .uw-h { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280; margin-bottom:0.75rem; }
  .uw-table { width:100%; font-size:0.85rem; }
  .uw-table th { padding:0.5rem 0.625rem; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; background:#f9fafb; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
  .uw-table td { padding:0.5rem 0.625rem; border-bottom:1px solid #f3f4f6; vertical-align:top; }
  .uw-help { font-size:0.75rem; color:#6b7280; }
  .uw-tier { display:inline-block; padding:0.1rem 0.45rem; border-radius:0.25rem; font-size:0.7rem; font-weight:700; }
  .uw-tier-proven { background:#dcfce7; color:#166534; } .uw-tier-adjacent { background:#e0f2fe; color:#075985; }
  .uw-tier-emerging { background:#fef3c7; color:#92400e; } .uw-tier-excluded { background:#fee2e2; color:#991b1b; }
  .uw-muted-row td { color:#9ca3af; }
  .uw-tabs .nav-link { font-size:0.85rem; padding:0.4rem 0.8rem; }
</style>
@php
  $tabs = [
      'upwork.config.index' => 'Overview',
      'upwork.config.profiles.index' => 'Search profiles',
      'upwork.config.technologies.index' => 'Technologies',
      'upwork.config.rulesets.index' => 'Scoring',
      'upwork.config.classifications.index' => 'Classifications',
      'upwork.config.targeting.edit' => 'Targeting',
      'upwork.config.settings.edit' => 'Agent & discovery',
      'upwork.config.ai.edit' => 'AI',
  ];
  $current = request()->route()->getName();
@endphp
<ul class="nav nav-tabs uw-tabs mb-3">
  @foreach($tabs as $route => $label)
    @php
      $prefix = preg_replace('/\.(index|edit)$/', '', $route);
      $active = $route === 'upwork.config.index' ? $current === $route : str_starts_with($current, $prefix);
    @endphp
    <li class="nav-item"><a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($route) }}">{{ $label }}</a></li>
  @endforeach
</ul>
@if(!$canManage)
  <div class="alert alert-secondary border-0 small py-2 px-3">Read-only: changing the Upwork configuration needs Super Admin or the <code>upwork.config.manage</code> module.</div>
@endif
@if(session('success'))
  <div class="alert alert-success border-0 small py-2 px-3" style="font-weight:600;">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger border-0 small py-2 px-3" style="font-weight:600;">{{ session('error') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger border-0 small py-2 px-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
