@extends('layout')

@section('title', 'Upwork Technology Catalog')

@section('content')
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-2">
    <span class="uw-help me-1">{{ $technologies->count() }} technologies</span>
    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or alias" class="form-control form-control-sm" style="max-width:220px;">
    <select name="tier" class="form-select form-select-sm" style="width:auto;">
      <option value="">All tiers</option>
      @foreach(\App\Models\UpworkTechnology::TIERS as $tier => $label)<option value="{{ $tier }}" {{ ($filters['tier'] ?? '') === $tier ? 'selected' : '' }}>{{ $label }}</option>@endforeach
    </select>
    <button class="btn btn-sm btn-outline-primary">Filter</button>
    @if($canManage)<a href="{{ route('upwork.config.technologies.create') }}" class="btn btn-primary btn-sm ms-auto">Add technology</a>@endif
  </form>
  <div class="uw-help mb-2">PROVEN = backed by recorded evidence · ADJACENT = close to proven work · EMERGING = learning / no shipped project yet · EXCLUDED = not offered (can hard-reject jobs).</div>

  <div class="uw-card p-0" style="overflow-x:auto;">
    <table class="uw-table">
      <thead><tr><th>Technology</th><th>Tier</th><th>Category</th><th>Aliases</th><th>Relations</th><th>Evidence</th><th></th></tr></thead>
      <tbody>
        @foreach($technologies as $t)
          <tr class="{{ $t->enabled ? '' : 'uw-muted-row' }}">
            <td><a href="{{ route('upwork.config.technologies.edit', $t) }}" style="font-weight:600;">{{ $t->name }}</a> <span class="uw-help">{{ $t->slug }}</span>
              @unless($t->enabled)<span class="badge bg-secondary ms-1">disabled</span>@endunless</td>
            <td><span class="uw-tier uw-tier-{{ $t->tier }}">{{ strtoupper($t->tier) }}</span></td>
            <td>{{ $t->category ?? '—' }}</td>
            <td>{{ $t->aliases_count }}</td>
            <td>{{ $t->relations_count }}</td>
            <td class="uw-help" style="max-width:320px;">{{ \Illuminate\Support\Str::limit($t->evidence ?? '—', 110) }}</td>
            <td class="text-end">
              @if($canManage)
                <form method="POST" action="{{ route('upwork.config.technologies.toggle', $t) }}" class="d-inline">@csrf
                  <button class="btn btn-sm btn-outline-secondary">{{ $t->enabled ? 'Disable' : 'Enable' }}</button></form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
