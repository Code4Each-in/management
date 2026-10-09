@extends('layout')

@section('title', 'Upwork Ruleset v' . $ruleset->version)

@section('content')
@php $weights = $ruleset->normalizedWeights(); @endphp
<div class="uw-wrap">
  @include('upwork.config.partials.head')

  <div class="uw-card">
    <div class="uw-h">Ruleset v{{ $ruleset->version }} · {{ $ruleset->status }} · read-only</div>
    <div class="uw-help mb-2">Published {{ optional($ruleset->published_at)->format('d M Y H:i') }}{{ $ruleset->publisher ? ' by ' . $ruleset->publisher->first_name . ' ' . $ruleset->publisher->last_name : '' }}. {{ $ruleset->notes }}</div>
    <div class="row g-3">
      <div class="col-md-6">
        <table class="uw-table"><thead><tr><th>Component</th><th>Weight</th><th>Normalized</th></tr></thead><tbody>
          @foreach(\App\Models\UpworkRuleset::COMPONENTS as $key => [$column, $label])
            <tr><td>{{ $label }}</td><td>{{ $ruleset->weights[$key] ?? 0 }}</td><td>{{ round($weights[$key], 2) }}</td></tr>
          @endforeach
        </tbody></table>
      </div>
      <div class="col-md-6">
        <div class="small"><strong>APPLY</strong> ≥ {{ $ruleset->thresholds['apply'] }} · <strong>REVIEW</strong> ≥ {{ $ruleset->thresholds['review'] }} · otherwise SKIP</div>
        <div class="uw-h mt-2">Settings</div>
        <pre class="small bg-light p-2 rounded" style="max-height:260px; overflow:auto;">{{ json_encode($ruleset->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
      </div>
    </div>
  </div>

  @include('upwork.config.rulesets.partials.rules-table', ['rules' => $ruleset->rules, 'describer' => $describer, 'editable' => false])
</div>
@endsection
