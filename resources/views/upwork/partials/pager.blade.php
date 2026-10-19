{{--
  Footer of a paginated Upwork list: range summary, "Records per page", page links.
  $paginator, $options (allowed page sizes), $action (list URL), $keep (applied query
  params: filters, sorting, view/range), $noun. Changing the page size keeps $keep and
  goes back to page 1.
--}}
@php
  $current = $paginator->perPage();
  $choices = collect($options)->push($current)->unique()->sort()->values();
  $noun = $noun ?? 'records';
  $selectId = 'per-page-' . substr(md5($action), 0, 6);
@endphp
<div class="uw-footer">
  <div class="d-flex flex-wrap align-items-center gap-3">
    <span>@if($paginator->total() > 0)Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} {{ $noun }}@else No {{ $noun }}@endif</span>
    <form method="GET" action="{{ $action }}" class="uw-perpage">
      @foreach($keep as $key => $value)
        @if(is_scalar($value) && (string) $value !== '' && $key !== 'per_page' && $key !== 'page')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
      @endforeach
      <label for="{{ $selectId }}">Records per page</label>
      <select id="{{ $selectId }}" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
        @foreach($choices as $n)<option value="{{ $n }}" {{ $n === $current ? 'selected' : '' }}>{{ $n }}</option>@endforeach
      </select>
      <noscript><button type="submit" class="btn btn-sm btn-outline-secondary">Go</button></noscript>
    </form>
  </div>
  @if($paginator->hasPages()){{ $paginator->links('pagination::bootstrap-4') }}@endif
</div>
