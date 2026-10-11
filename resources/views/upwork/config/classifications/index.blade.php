@extends('layout')

@section('title', 'Upwork Classifications')

@section('content')
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="uw-help">Labels that classification rules can assign. "Surface" keeps a job in the default list even when its score is below REVIEW.</div>
    @if($canManage)<a href="{{ route('upwork.config.classifications.create') }}" class="btn btn-primary btn-sm">New classification</a>@endif
  </div>
  <div class="uw-card p-0" style="overflow-x:auto;">
    <table class="uw-table">
      <thead><tr><th>Label</th><th>Code</th><th>Kind</th><th>Surface</th><th>Rules (current)</th><th>Jobs</th><th>Description</th></tr></thead>
      <tbody>
        @foreach($classifications as $c)
          <tr class="{{ $c->enabled ? '' : 'uw-muted-row' }}">
            <td>@include('upwork.opportunities.partials.classification-chips', ['codes' => [$c->code], 'catalog' => [$c->code => tap(clone $c, fn($x) => $x->enabled = true)]])
              @if($canManage)<a href="{{ route('upwork.config.classifications.edit', $c) }}" class="small ms-1">Edit</a>@endif
              @unless($c->enabled)<span class="badge bg-secondary ms-1">disabled</span>@endunless</td>
            <td><code>{{ $c->code }}</code></td>
            <td>{{ $c->kind }}</td>
            <td>{{ $c->surface ? 'Yes' : '—' }}</td>
            <td>{{ $ruleCounts[$c->code] ?? 0 }}@if(($ruleCounts[$c->code] ?? 0) === 0) <span class="text-danger uw-help">no rule assigns it</span>@endif</td>
            <td>{{ $jobCounts[$c->code] ?? 0 }}</td>
            <td class="uw-help">{{ $c->description }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
