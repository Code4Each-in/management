@php
    $recStyles = [
        'apply'  => ['bg' => '#d1e7dd', 'color' => '#0f5132'],
        'review' => ['bg' => '#fff3cd', 'color' => '#856404'],
        'skip'   => ['bg' => '#e2e3e5', 'color' => '#41464b'],
    ];
    $rec = $recStyles[$opportunity->recommendation] ?? null;
@endphp
@if($rec)
<span style="display:inline-block; padding:0.25rem 0.625rem; font-size:0.75rem; font-weight:700; border-radius:0.25rem; text-transform:uppercase; letter-spacing:0.03em; background:{{ $rec['bg'] }}; color:{{ $rec['color'] }};">{{ $opportunity->recommendation_label }}</span>
@else
<span style="color:#9ca3af;">—</span>
@endif
