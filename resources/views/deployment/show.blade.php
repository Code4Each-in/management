@extends('layout')

@section('title', $ticket->deployment_code . ' — ' . $ticket->deployment_name)

@section('content')
<div style="margin: 2.5rem auto; font-family: 'Nunito', sans-serif;">

  <a href="{{ route('deployment.tickets.index') }}"
     class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"
     style="font-weight: 600; font-size: 13px;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Back to tickets
  </a>

  @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 small py-2 px-3 mb-3" style="font-weight: 600;">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3" style="font-weight: 600;">{{ session('error') }}</div>
  @endif

  {{-- Header --}}
  @php
    $statusMeta = match($ticket->status) {
      'draft'      => ['label' => 'Draft',      'color' => '#6b7280', 'bg' => 'rgba(107,114,128,.1)'],
      'qa_review'      => ['label' => 'Deplyoment pending',      'color' => 'var(--bs-primary)', 'bg' => 'rgba(var(--bs-primary-rgb),.1)'],
      'needs_fix'  => ['label' => 'Needs fix',  'color' => 'var(--bs-warning)', 'bg' => 'rgba(var(--bs-warning-rgb),.12)'],
      'approved'   => ['label' => 'Approved',   'color' => 'var(--bs-success)', 'bg' => 'rgba(var(--bs-success-rgb),.12)'],
      'deployed'   => ['label' => 'Deployed',   'color' => '#0f172a', 'bg' => 'rgba(15,23,42,.06)'],
      'rolled_back'=> ['label' => 'Rolled back','color' => '#dc2626', 'bg' => 'rgba(220,38,38,.1)'],
      default      => ['label' => $ticket->status, 'color' => '#6b7280', 'bg' => 'rgba(107,114,128,.1)'],
    };
  @endphp

    <div class="d-flex align-items-center justify-content-between gap-3 mb-2">

    <span class="badge rounded-pill fw-bold py-2 px-3 flex-shrink-0"
            style="font-size:12px;color:{{ $statusMeta['color'] }};background:{{ $statusMeta['bg'] }}">
        {{ $statusMeta['label'] }}
    </span>

    @if($ticket->status === 'deployed' && auth()->user()->role_id == 1)
    <form method="POST" action="{{ route('deployment.tickets.rollback', $ticket) }}">
        @csrf
        <button class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-counterclockwise"></i> Undo Deployment
        </button>
    </form>
    @endif

    </div>
  <div class="mb-4"></div>

  {{-- Info card --}}
  <div class="card border rounded-3 shadow-none mb-3">
    <div class="row g-0" style="font-size:14px;">
      @foreach([
        ['Project',    $ticket->project->project_name ?? '—'],
        ['Priority',   $ticket->priority],
        ['Developers', $ticket->developers->pluck('first_name')->implode(', ') ?: '—'],
        ['QA',         $ticket->qa->first_name ?? '—'],
      ] as [$label, $value])
      <div class="col-md-6 px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size:11px;letter-spacing:.05em;">{{ $label }}</div>
        <div style="font-weight: 600;">{{ $value }}</div>
      </div>
      @endforeach

      @foreach([
        ['Changes done',      $ticket->changes_done],
        ['Files modified',    $ticket->files_modified],
        ['Testing done',      $ticket->testing_done],
        ['Deployment notes',  $ticket->deployment_notes],
      ] as [$label, $value])
      @if($value)
      <div class="col-12 px-3 py-2 border-top">
        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size:11px;letter-spacing:.05em;">{{ $label }}</div>
        <div @if($label === 'Files modified') class="font-monospace" style="font-size:13px;" @endif>{{ $value }}</div>
      </div>
      @endif
      @endforeach
    </div>
  </div>

  {{-- Action buttons --}}
  @php
    $isAssignedDeveloper = $ticket->developers->pluck('id')->contains(auth()->id());
    $isAssignedQA = $ticket->qa_id == auth()->id();
  @endphp

  <div class="d-flex gap-2 flex-wrap mb-4">
    @if($ticket->status === 'draft'  && $isAssignedDeveloper)
    <form method="POST" action="{{ route('deployment.tickets.submit', $ticket) }}">
        @csrf
        <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                style="width:auto; display:inline-flex; font-weight:600;">
          <i class="bi bi-send"></i> Submit for Review
        </button>
    </form>
    @endif

    @if(($ticket->status === 'qa_review' || $ticket->status === 'rolled_back') && $isAssignedQA)
    <form method="POST" action="{{ route('deployment.tickets.approve', $ticket) }}">
        @csrf
        <button class="btn btn-success btn-sm d-inline-flex align-items-center gap-1" style="font-weight: 700;">
          <i class="bi bi-check-circle"></i> Approve
        </button>
    </form>

    <button class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1" style="font-weight: 700;"
            data-bs-toggle="modal" data-bs-target="#bugModal">
        <i class="bi bi-bug"></i> Raise bug
    </button>
    @endif

    @if(($ticket->status === 'needs_fix' || $ticket->status === 'rolled_back') && $isAssignedDeveloper)
    <form method="POST" action="{{ route('deployment.tickets.submit', $ticket) }}">
        @csrf<button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                style="width:auto; display:inline-flex; font-weight:600;">
          <i class="bi bi-send"></i> Resubmit For Review
        </button>
    </form>
    @endif

    @if($ticket->status === 'approved' && $isAssignedQA)
    <form method="POST" action="{{ route('deployment.tickets.deploy', $ticket) }}">
        @csrf<button class="btn btn-dark btn-sm" style="font-weight: 700;">Mark Deployed</button>
    </form>
    @endif
  </div>

  {{-- Bugs --}}
  <div class="card border rounded-3 overflow-hidden shadow-none mb-3">
    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background:var(--bs-light);">
      <div class="d-flex align-items-center justify-content-center rounded-2 border bg-white" style="width:26px;height:26px;color:#6b7280;">
        <i class="bi bi-bug" style="font-size:13px;"></i>
      </div>
      <span class="fw-bold small">Bugs</span>
      @php $openBugs = $ticket->bugs->where('status','open')->count(); @endphp
      @if($openBugs > 0)
        <span class="badge bg-danger bg-opacity-10 text-danger ms-auto" style="font-size:11px; font-weight: 700;">{{ $openBugs }} open</span>
      @else
        <span class="text-muted ms-auto" style="font-size:11px; font-weight: 700;">{{ $ticket->bugs->count() }} total</span>
      @endif
    </div>

    @forelse($ticket->bugs as $bug)
    @php
        $sevClass = match ($bug->severity) {
            'High' => 'bg-danger text-white',
            'Medium' => 'bg-warning text-dark',
            'Low' => 'bg-success text-white',
            default => 'bg-secondary text-white',
        };
    @endphp

    <div class="d-flex align-items-start gap-3 px-3 py-3 border-bottom" style="font-size:14px;">
      <div class="flex-grow-1 min-w-0">
        <div class="fw-bold mb-1">{{ $bug->title }}</div>
        <div class="text-muted small mb-2">{{ $bug->description }}</div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge rounded-2 fw-bold {{ $sevClass }}" style="font-size:11px;">{{ $bug->severity }}</span>
          <span class="badge rounded-2 fw-bold bg-light text-secondary border" style="font-size:11px;">{{ $bug->status }}</span>
        </div>
      </div>
      <div class="flex-shrink-0">
        @if($bug->status === 'open' && $isAssignedDeveloper)
            <form method="POST" action="{{ route('deployment.bugs.fixed', $bug) }}">
                @csrf<button class="btn btn-sm btn-outline-primary" style="font-weight: 700;">Mark Fixed</button>
            </form>
        @elseif($bug->status === 'fixed' && $isAssignedQA)
            <form method="POST" action="{{ route('deployment.bugs.close', $bug) }}">
                @csrf<button class="btn btn-sm btn-outline-success" style="font-weight: 700;">Close</button>
            </form>
        @endif
      </div>
    </div>
    @empty
    <div class="text-center text-muted py-4" style="font-size:14px;">No bugs raised.</div>
    @endforelse
  </div>

  {{-- Activity Log — timeline style --}}
  <div class="card border rounded-3 overflow-hidden shadow-none mb-4">
    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background:var(--bs-light);">
      <div class="d-flex align-items-center justify-content-center rounded-2 border bg-white" style="width:26px;height:26px;color:#6b7280;">
        <i class="bi bi-clock-history" style="font-size:13px;"></i>
      </div>
      <span class="fw-bold small">Activity log</span>
      <span class="text-muted ms-auto" style="font-size:11px; font-weight: 700;">{{ $ticket->logs->count() }} {{ Str::plural('event', $ticket->logs->count()) }}</span>
    </div>

    <div class="px-3 py-3">
      @forelse($ticket->logs as $log)
      @php
        $statusMap = [
          'qa_review'     => ['label' => 'Deplyoment pending',     'color' => '#0d6efd', 'icon' => 'bi-search'],
          'needs_fix' => ['label' => 'Needs fix', 'color' => '#f59e0b', 'icon' => 'bi-tools'],
          'approved'  => ['label' => 'Approved',  'color' => '#198754', 'icon' => 'bi-check-circle'],
          'deployed'  => ['label' => 'Deployed',  'color' => '#0f172a', 'icon' => 'bi-rocket-takeoff'],
          'draft'     => ['label' => 'Draft',     'color' => '#6b7280', 'icon' => 'bi-pencil'],
          'rolled_back' => ['label' => 'Rolled back', 'color' => '#dc2626', 'icon' => 'bi-arrow-counterclockwise'],
        ];
        $newMeta = $statusMap[$log->new_status] ?? ['label' => Str::headline($log->new_status), 'color' => '#9ca3af', 'icon' => 'bi-arrow-right-circle'];
        $oldMeta = $log->old_status ? ($statusMap[$log->old_status] ?? ['label' => Str::headline($log->old_status), 'color' => '#9ca3af']) : null;

        $actorName = $log->user
          ? trim($log->user->first_name . ' ' . $log->user->last_name)
          : 'System';

        // If this log moved the ticket to "needs_fix", try to find the bug that triggered it
        // (created within a few seconds of this log entry — they're written in the same request).
        $relatedBug = null;
        if ($log->new_status === 'needs_fix') {
          $relatedBug = $ticket->bugs->first(function ($bug) use ($log) {
            return abs($bug->created_at->diffInSeconds($log->created_at)) <= 5;
          });
        }

        // Human-readable sentence for what happened
        $verb = match($log->new_status) {
          'qa_review'     => $oldMeta ? 'submitted this for QA review' : 'created this ticket and sent it for QA Review',
          'needs_fix' => $relatedBug ? 'raised a bug' : 'sent this back for fixes',
          'approved'  => 'approved this ticket',
          'deployed'  => 'marked this as deployed',
          'draft'     => 'moved this back to draft',
          'rolled_back' => 'rolled back this deployment',
          default     => 'updated the status',
        };

        // Override the icon shown for this entry when it's actually a bug report
        if ($relatedBug) {
          $newMeta['icon'] = 'bi-bug';
        }
      @endphp

      <div class="d-flex gap-3 {{ !$loop->last ? 'pb-3 mb-3' : '' }}" style="position:relative;">

        {{-- timeline rail --}}
        @if(!$loop->last)
        <div style="position:absolute; left:16px; top:34px; bottom:0; width:2px; background:#eef0f2;"></div>
        @endif

        {{-- status icon --}}
        <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center"
             style="width:34px;height:34px; background:{{ $newMeta['color'] }}1A; color:{{ $newMeta['color'] }}; z-index:1;"
             title="{{ $actorName }}">
          <i class="bi {{ $newMeta['icon'] ?? 'bi-circle-fill' }}" style="font-size:14px;"></i>
        </div>

        {{-- content --}}
        <div class="flex-grow-1" style="font-size:14px; min-width:0;">

          <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
              <span class="fw-bold">{{ $actorName }}</span>
              <span class="text-muted">{{ $verb }}</span>
            </div>
            <span class="text-muted flex-shrink-0" style="font-size:12px; font-weight:600;">
              {{ $log->created_at->format('d M Y, h:i A') }}
            </span>
          </div>

          @if($relatedBug)
          <div class="mt-2 p-2 rounded-2 border" style="font-size:12.5px; background:#fff5f5; border-color:#fecaca !important;">
            <span class="badge rounded-2 fw-bold text-danger bg-danger bg-opacity-10 me-1" style="font-size:10.5px;">{{ $relatedBug->severity }}</span>
            <span class="fw-bold">{{ $relatedBug->title }}</span>
          </div>
          @endif

        </div>
      </div>
      @empty
      <div class="text-center text-muted py-3" style="font-size:14px;">No activity yet.</div>
      @endforelse
    </div>
  </div>

</div>

{{-- Needs Fix Modal --}}
<div class="modal fade" id="fixModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('deployment.tickets.needsFix', $ticket) }}">
      @csrf
      <div class="modal-content border rounded-3 p-4" style="font-family: 'Nunito', sans-serif;">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="d-flex align-items-center justify-content-center rounded-2 border bg-light" style="width:26px;height:26px;color:#6b7280;">
            <i class="bi bi-tools" style="font-size:13px;"></i>
          </div>
          <h6 class="mb-0 fw-bold">Request changes</h6>
        </div>
        <textarea name="note" class="form-control form-control-sm mb-3" rows="4"
          placeholder="What needs to be fixed?" style="font-family: 'Nunito', sans-serif;"></textarea>
        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="font-weight:700;">Cancel</button>
          <button type="submit" class="btn btn-sm btn-warning" style="font-weight:700;">Submit</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Raise Bug Modal --}}
<div class="modal fade" id="bugModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('deployment.tickets.bugs.add', $ticket) }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-content border rounded-3 p-4" style="font-family: 'Nunito', sans-serif;">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="d-flex align-items-center justify-content-center rounded-2 border bg-light" style="width:26px;height:26px;color:#6b7280;">
            <i class="bi bi-bug" style="font-size:13px;"></i>
          </div>
          <h6 class="mb-0 fw-bold">Raise bug</h6>
        </div>
        <div class="d-flex flex-column gap-2 mb-3">
          <div>
            <label class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size:11px;letter-spacing:.05em">Title</label>
            <input type="text" name="title" class="form-control form-control-sm" placeholder="Short summary of the bug" required style="font-family: 'Nunito', sans-serif;">
          </div>
          <div>
            <label class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size:11px;letter-spacing:.05em">Description</label>
            <textarea name="description" class="form-control form-control-sm" rows="3" placeholder="Steps to reproduce, expected vs actual behavior" style="font-family: 'Nunito', sans-serif;"></textarea>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size:11px;letter-spacing:.05em">Severity</label>
              <select name="severity" class="form-select form-select-sm" style="font-family: 'Nunito', sans-serif;">
                <option>Low</option>
                <option selected>Medium</option>
                <option>High</option>
              </select>
            </div>
            <div class="col-6">
              <label class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size:11px;letter-spacing:.05em">Screenshot</label>
              <input type="file" name="screenshot" class="form-control form-control-sm" accept="image/*">
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="font-weight:700;">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger" style="font-weight:700;">Raise bug</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection
