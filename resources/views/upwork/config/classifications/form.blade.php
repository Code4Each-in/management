@extends('layout')

@section('title', $classification->exists ? 'Edit Classification' : 'New Classification')

@section('content')
@php $ro = !$canManage; @endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')
  <form method="POST" action="{{ $classification->exists ? route('upwork.config.classifications.update', $classification) : route('upwork.config.classifications.store') }}" class="uw-card" style="max-width:640px;">
    @csrf @if($classification->exists) @method('PUT') @endif
    <div class="uw-h">{{ $classification->exists ? $classification->code : 'New classification' }}</div>
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label small fw-semibold mb-1">Code</label>
        @if($classification->exists)
          <input class="form-control form-control-sm" value="{{ $classification->code }}" disabled><div class="uw-help">Fixed: rules and stored jobs use it.</div>
        @else
          <input name="code" class="form-control form-control-sm" value="{{ old('code') }}" placeholder="e.g. MOBILE_BACKEND" required {{ $ro ? 'disabled' : '' }}>
        @endif</div>
      <div class="col-md-6"><label class="form-label small fw-semibold mb-1">Label</label>
        <input name="label" class="form-control form-control-sm" value="{{ old('label', $classification->label) }}" required {{ $ro ? 'disabled' : '' }}></div>
      <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Kind</label>
        <select name="kind" class="form-select form-select-sm" {{ $ro ? 'disabled' : '' }}>
          @foreach(['archetype' => 'Archetype (kind of opportunity)', 'category' => 'Category (technology / domain)'] as $v => $l)<option value="{{ $v }}" {{ old('kind', $classification->kind) === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
        </select></div>
      <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Colour</label>
        <input type="color" name="color" class="form-control form-control-sm form-control-color" value="{{ old('color', $classification->color) }}" {{ $ro ? 'disabled' : '' }}></div>
      <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Sort</label>
        <input type="number" name="sort" class="form-control form-control-sm" value="{{ old('sort', $classification->sort) }}" {{ $ro ? 'disabled' : '' }}></div>
      <div class="col-12"><label class="form-label small fw-semibold mb-1">Description</label>
        <textarea name="description" rows="2" class="form-control form-control-sm" {{ $ro ? 'disabled' : '' }}>{{ old('description', $classification->description) }}</textarea></div>
      <div class="col-12">
        <div class="form-check"><input type="hidden" name="enabled" value="0"><input class="form-check-input" type="checkbox" name="enabled" value="1" id="en" {{ old('enabled', $classification->enabled) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}><label class="form-check-label small" for="en">Enabled (shown on jobs and in filters)</label></div>
        <div class="form-check"><input type="hidden" name="surface" value="0"><input class="form-check-input" type="checkbox" name="surface" value="1" id="sf" {{ old('surface', $classification->surface) ? 'checked' : '' }} {{ $ro ? 'disabled' : '' }}><label class="form-check-label small" for="sf">Surface: keep jobs with this label in the default list even below REVIEW</label></div>
      </div>
    </div>
    <div class="d-flex gap-2 mt-3">
      @if($canManage)<button class="btn btn-primary btn-sm">Save</button>@endif
      <a href="{{ route('upwork.config.classifications.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
  </form>
</div>
@endsection
