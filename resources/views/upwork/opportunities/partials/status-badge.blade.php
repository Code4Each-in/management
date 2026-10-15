@php
    $statusStyles = [
        'new'             => ['bg' => '#e3f2fd', 'color' => '#0d6efd'],
        'approved'        => ['bg' => '#d1e7dd', 'color' => '#0f5132'],
        'rejected'        => ['bg' => '#f8d7da', 'color' => '#842029'],
        'proposal_draft'  => ['bg' => '#fff3cd', 'color' => '#856404'],
        'submitted'       => ['bg' => '#e0e7ff', 'color' => '#3730a3'],
        'client_response' => ['bg' => '#cff4fc', 'color' => '#055160'],
        'won'             => ['bg' => '#198754', 'color' => '#ffffff'],
        'lost'            => ['bg' => '#e2e3e5', 'color' => '#41464b'],
        'archived'        => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
    ];
    $style = $statusStyles[$opportunity->status] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];
@endphp
<span style="display:inline-block; padding:0.25rem 0.625rem; font-size:0.75rem; font-weight:600; border-radius:0.25rem; white-space:nowrap; background:{{ $style['bg'] }}; color:{{ $style['color'] }};">{{ $opportunity->status_label }}</span>
