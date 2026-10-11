{{-- Rules grouped by type with plain-English conditions. Expects $rules, $describer, $editable (bool). --}}
@php $labels = \App\Models\UpworkRuleset::COMPONENTS; @endphp
@foreach(\App\Models\UpworkRule::TYPES as $type => $typeLabel)
  @php $group = $rules->where('type', $type)->sortBy('sort'); @endphp
  <div class="uw-card p-0 mb-3">
    <div class="d-flex justify-content-between align-items-center px-3 pt-3">
      <div class="uw-h mb-2">{{ $typeLabel }}s ({{ $group->count() }})
        @if($type === 'hard_filter')<span class="text-muted fw-normal" style="text-transform:none;">— run before scoring; a match rejects the job</span>@endif</div>
      @if($editable)<a href="{{ route('upwork.config.rulesets.rules.create', ['type' => $type]) }}" class="btn btn-sm btn-outline-primary mb-2">Add {{ strtolower($typeLabel) }}</a>@endif
    </div>
    <table class="uw-table">
      <thead><tr><th>Rule</th><th>{{ $type === 'score' ? 'Points' : ($type === 'classification' ? 'Assigns' : 'Reason') }}</th><th>When</th>@if($editable)<th></th>@endif</tr></thead>
      <tbody>
        @foreach($group as $rule)
          <tr class="{{ $rule->enabled ? '' : 'uw-muted-row' }}">
            <td style="min-width:180px;"><strong>{{ $rule->name }}</strong> @unless($rule->enabled)<span class="badge bg-secondary">disabled</span>@endunless<div class="uw-help"><code>{{ $rule->key }}</code></div></td>
            <td style="min-width:140px;">
              @if($type === 'score')<span style="font-weight:700; color:{{ $rule->points >= 0 ? '#166534' : '#991b1b' }};">{{ $rule->points >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($rule->points, 2), '0'), '.') }}</span> <span class="uw-help">{{ $labels[$rule->component][1] ?? $rule->component }}</span>
              @elseif($type === 'classification')<code>{{ $rule->classification }}</code>
              @else<span class="uw-help">{{ $rule->reason }}</span>@endif
            </td>
            <td class="small">{{ $describer->describe($rule->conditions) }}</td>
            @if($editable)
              <td class="text-end" style="white-space:nowrap;">
                <a href="{{ route('upwork.config.rulesets.rules.edit', $rule) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form method="POST" action="{{ route('upwork.config.rulesets.rules.toggle', $rule) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $rule->enabled ? 'Disable' : 'Enable' }}</button></form>
                <form method="POST" action="{{ route('upwork.config.rulesets.rules.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('Remove this rule from the draft?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form>
              </td>
            @endif
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endforeach
