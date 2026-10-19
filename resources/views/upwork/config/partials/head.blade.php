{{-- Shared head for Upwork configuration pages: styles, sub-navigation, flash messages, read-only notice. --}}
@include('upwork.partials.styles')
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
