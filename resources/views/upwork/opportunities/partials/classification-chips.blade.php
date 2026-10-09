{{-- Classification chips styled from the catalog. Expects $codes (array), $catalog (code => UpworkClassification),
     optional $details (code => [{rule, name, why}]) for hover text, optional $kind to show only one kind. --}}
@foreach((array) $codes as $code)
  @php $c = $catalog[$code] ?? null; @endphp
  @continue(isset($kind) && $c && $c->kind !== $kind)
  @continue($c && !$c->enabled)
  @php
    $color = $c->color ?? '#065f46';
    $why = collect($details[$code] ?? [])->pluck('why')->implode(' | ');
  @endphp
  <span class="badge" style="background:{{ $color }}14; color:{{ $color }}; border:1px solid {{ $color }}40; font-weight:700; margin:0 0.25rem 0.25rem 0;"
        title="{{ $c->description ?? '' }}{{ $why ? ' — Why: ' . $why : '' }}">{{ $c->label ?? str_replace('_', ' ', $code) }}</span>
@endforeach
