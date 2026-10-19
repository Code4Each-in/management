{{-- Shared Upwork module styles: configuration pages, Opportunities and Radar. Bootstrap 5 does the rest. --}}
@once
<style>
  .uw-wrap { margin: 1rem auto 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
  .uw-card { background:#fff; border-radius:0.5rem; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04); padding:1.25rem; margin-bottom:1rem; }
  .uw-h { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280; margin-bottom:0.75rem; }
  .uw-table { width:100%; font-size:0.85rem; }
  .uw-table th { padding:0.5rem 0.625rem; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; background:#f9fafb; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
  .uw-table td { padding:0.5rem 0.625rem; border-bottom:1px solid #f3f4f6; vertical-align:top; }
  .uw-help { font-size:0.75rem; color:#6b7280; }
  .uw-tier { display:inline-block; padding:0.1rem 0.45rem; border-radius:0.25rem; font-size:0.7rem; font-weight:700; }
  .uw-tier-proven { background:#dcfce7; color:#166534; } .uw-tier-adjacent { background:#e0f2fe; color:#075985; }
  .uw-tier-emerging { background:#fef3c7; color:#92400e; } .uw-tier-excluded { background:#fee2e2; color:#991b1b; }
  .uw-muted-row td { color:#9ca3af; }
  .uw-tabs .nav-link { font-size:0.85rem; padding:0.4rem 0.8rem; }

  .uw-wrap a:focus-visible, .uw-wrap button:focus-visible, .uw-wrap summary:focus-visible,
  .uw-wrap input:focus-visible, .uw-wrap select:focus-visible { outline:2px solid #0d6efd; outline-offset:2px; }

  /* Page header */
  .uw-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:0.75rem 1rem; margin-bottom:1rem; }
  .uw-title { font-size:1.35rem; font-weight:700; color:#111827; margin:0; line-height:1.25; }
  .uw-lede { font-size:0.85rem; color:#6b7280; margin:0.2rem 0 0; max-width:46rem; }
  .uw-actions { display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem; }

  /* View tabs: scroll sideways on small screens; "More" sits outside the scroller so its menu is not clipped */
  .uw-views { display:flex; align-items:flex-end; gap:0.25rem; border-bottom:1px solid #e5e7eb; margin-bottom:0.75rem; }
  .uw-views-scroll { display:flex; gap:0.125rem; overflow-x:auto; flex:1 1 auto; min-width:0; scrollbar-width:thin; }
  .uw-view { display:inline-flex; align-items:center; gap:0.375rem; padding:0.5rem 0.7rem; font-size:0.84rem; font-weight:500; color:#4b5563;
             text-decoration:none; border:0; border-bottom:2px solid transparent; margin-bottom:-1px; white-space:nowrap; background:none; }
  .uw-view:hover { color:#111827; }
  .uw-view.active { color:#0d6efd; border-bottom-color:#0d6efd; font-weight:600; }
  .uw-count { font-size:0.7rem; font-weight:600; padding:0.05rem 0.4rem; border-radius:999px; background:#f3f4f6; color:#4b5563; }
  .uw-view.active .uw-count { background:#e7f1ff; color:#0d6efd; }
  .uw-count-alert { background:#fee2e2 !important; color:#991b1b !important; }

  /* One-line summary metrics */
  .uw-metrics { display:flex; flex-wrap:wrap; align-items:center; gap:0.25rem 0.9rem; font-size:0.8rem; margin-bottom:0.75rem; }
  .uw-metrics-label { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; }
  .uw-metric { color:#4b5563; text-decoration:none; white-space:nowrap; }
  .uw-metric strong { font-weight:600; margin-left:0.2rem; }
  .uw-metric:hover { color:#111827; } .uw-metric:hover strong { text-decoration:underline; }
  .uw-sep { width:1px; height:1rem; background:#e5e7eb; }

  /* List panel: filters, toolbar, table, footer */
  .uw-panel { background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 1px 2px rgba(0,0,0,0.04); }
  .uw-filters { padding:0.75rem 1rem; border-bottom:1px solid #e5e7eb; }
  .uw-fields { display:flex; flex-wrap:wrap; align-items:flex-end; gap:0.5rem 0.625rem; }
  .uw-field { display:flex; flex-direction:column; gap:0.2rem; flex:1 1 9.5rem; max-width:13rem; min-width:0; }
  .uw-field-search { flex:2 1 15rem; max-width:24rem; }
  .uw-field-xs { flex:0 1 6.5rem; }
  .uw-field label { font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; margin:0; }
  .uw-field .form-control, .uw-field .form-select { height:calc(1.5em + 0.5rem + 2px); }
  .uw-fields-actions { display:flex; align-items:flex-end; gap:0.375rem; margin-left:auto; }
  .uw-advanced { margin-top:0.625rem; padding-top:0.625rem; border-top:1px dashed #e5e7eb; }
  .uw-chips { display:flex; flex-wrap:wrap; align-items:center; gap:0.375rem; margin-top:0.625rem; font-size:0.75rem; }
  .uw-chip { display:inline-flex; align-items:center; gap:0.3rem; padding:0.125rem 0.25rem 0.125rem 0.55rem; border:1px solid #cfe2ff; background:#f0f6ff;
             color:#084298; border-radius:999px; text-decoration:none; line-height:1.4; }
  .uw-chip:hover { background:#e2edff; color:#052c65; }
  .uw-chip .bi { font-size:0.85rem; opacity:0.7; }
  .uw-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem 1rem; padding:0.5rem 1rem; border-bottom:1px solid #e5e7eb; font-size:0.82rem; }
  .uw-toolbar .form-select { width:auto; }
  .uw-bulk { display:flex; flex-wrap:wrap; align-items:center; gap:0.375rem 0.75rem; padding:0.4rem 1rem; border-bottom:1px solid #e5e7eb; background:#f9fafb; font-size:0.8rem; }
  .uw-bulk.is-active { background:#f0f6ff; }
  .uw-linkbtn { border:0; background:none; padding:0; color:#0d6efd; font-size:inherit; text-decoration:none; }
  .uw-linkbtn:hover { text-decoration:underline; }

  .uw-list { width:100%; margin:0; font-size:0.83rem; color:#1f2937; border-collapse:separate; border-spacing:0; }
  .uw-list th { padding:0.5rem 0.75rem; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280;
                background:#f9fafb; border-bottom:1px solid #e5e7eb; white-space:nowrap; text-align:left; }
  .uw-list td { padding:0.6rem 0.75rem; border-bottom:1px solid #f1f3f5; vertical-align:top; }
  .uw-list tbody tr:last-child td { border-bottom:0; }
  .uw-list tbody tr:hover td { background:#fafbfc; }
  .uw-list tbody tr.is-selected td { background:#f0f6ff; }
  .uw-list .uw-r { text-align:right; }
  .uw-nowrap { white-space:nowrap; }
  .uw-job { min-width:18rem; max-width:34rem; }
  .uw-job-title { font-weight:600; color:#0d6efd; text-decoration:none; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .uw-job-title:hover { text-decoration:underline; }
  .uw-sub { font-size:0.74rem; color:#6b7280; margin-top:0.15rem; line-height:1.4; }
  .uw-sub .bi { font-size:0.72rem; }
  .uw-score { font-size:1.05rem; font-weight:700; line-height:1.1; font-variant-numeric:tabular-nums; }
  .uw-stack > * + * { margin-top:0.25rem; }
  .uw-dl { display:grid; grid-template-columns:auto auto; gap:0.05rem 0.5rem; font-size:0.78rem; white-space:nowrap; }
  .uw-dl dt { font-weight:400; color:#6b7280; } .uw-dl dd { margin:0; font-variant-numeric:tabular-nums; }
  .uw-row-actions { display:inline-flex; gap:0.25rem; justify-content:flex-end; }
  .uw-row-actions .btn { padding:0.2rem 0.45rem; line-height:1.2; }
  .uw-empty { padding:2.5rem 1rem; text-align:center; color:#6b7280; }
  .uw-empty .bi { display:block; font-size:1.6rem; color:#9ca3af; margin-bottom:0.4rem; }
  .uw-footer { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.5rem 1rem; padding:0.6rem 1rem;
               border-top:1px solid #e5e7eb; background:#f9fafb; border-radius:0 0 0.5rem 0.5rem; font-size:0.82rem; color:#6b7280; }
  .uw-footer .pagination { margin:0; flex-wrap:wrap; }
  .uw-footer .page-link { padding:0.25rem 0.55rem; font-size:0.8rem; }
  .uw-perpage { display:flex; align-items:center; gap:0.4rem; margin:0; }
  .uw-perpage label { margin:0; white-space:nowrap; }
  .uw-perpage .form-select { width:auto; }

  /* Radar */
  .uw-funnel { display:grid; grid-template-columns:repeat(5, minmax(0, 1fr)); }
  .uw-step { display:block; padding:0.75rem 1rem; color:inherit; text-decoration:none; border-left:1px solid #f1f3f5; }
  .uw-step:first-child { border-left:0; }
  .uw-step:hover { background:#fafbfc; }
  .uw-step-label { font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; }
  .uw-step-value { font-size:1.5rem; font-weight:700; line-height:1.2; font-variant-numeric:tabular-nums; }
  .uw-step-note { font-size:0.72rem; color:#6b7280; line-height:1.35; }
  .uw-kv { display:flex; justify-content:space-between; gap:0.75rem; font-size:0.84rem; padding:0.15rem 0; }
  .uw-kv strong { font-variant-numeric:tabular-nums; }
  .uw-card-sm { padding:0.9rem 1rem; }
  .uw-details > summary { cursor:pointer; list-style:none; display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; font-weight:600; color:#374151; }
  .uw-details > summary::-webkit-details-marker { display:none; }
  .uw-details > summary .bi-chevron-right { transition:transform 0.15s; }
  .uw-details[open] > summary .bi-chevron-right { transform:rotate(90deg); }
  .uw-status-dot { display:inline-block; width:0.5rem; height:0.5rem; border-radius:50%; margin-right:0.25rem; vertical-align:middle; }

  @media (max-width: 991.98px) {
    .uw-funnel { grid-template-columns:repeat(3, minmax(0, 1fr)); }
    .uw-step:nth-child(4) { border-left:0; }
    .uw-step:nth-child(n+4) { border-top:1px solid #f1f3f5; }
  }
  @media (max-width: 575.98px) {
    .uw-field, .uw-field-search { flex:1 1 100%; max-width:none; }
    .uw-field-half { flex:1 1 calc(50% - 0.625rem); }
    .uw-fields-actions { margin-left:0; width:100%; }
    .uw-fields-actions .btn { flex:1 1 auto; }
    .uw-funnel { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    .uw-step { border-left:0; border-top:1px solid #f1f3f5; }
    .uw-step:nth-child(-n+2) { border-top:0; }
    .uw-step:nth-child(even) { border-left:1px solid #f1f3f5; }
  }
</style>
@endonce
