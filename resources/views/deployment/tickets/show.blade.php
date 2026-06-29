@extends('layout')
@section('title', $ticket->deployment_code . ' - ' . $ticket->deployment_name)
@section('content')

<div class="container-fluid px-4 py-3">
    {{-- TOP BANNER / HEADER --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4">
        <div>
            <div class="d-flex gap-2 align-items-center mt-2">
                <span class="badge bg-{{ $ticket->statusBadgeClass() }}-subtle text-{{ $ticket->statusBadgeClass() }} border border-{{ $ticket->statusBadgeClass() }}-subtle">{{ $ticket->status }}</span>

                @php
                    $priorityClass = match($ticket->priority) {
                        'Low' => 'success',
                        'Medium' => 'info',
                        'High' => 'warning',
                        'Critical' => 'danger',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $priorityClass }}-subtle text-{{ $priorityClass }} border border-{{ $priorityClass }}-subtle">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>{{ $ticket->priority }} Priority
                </span>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            @if ($ticket->isAssignedDeveloper() || $ticket->isSuperAdmin())
                <a href="{{ route('deployment.tickets.edit', $ticket) }}" class="btn btn-white border shadow-sm btn-sm px-3">
                    <i class="bi bi-pencil me-1 text-muted"></i> Edit
                </a>
            @endif
            <a href="{{ route('deployment.tickets.index') }}" class="btn btn-light border btn-sm px-3 text-muted">Back to List</a>
        </div>
    </div>

    {{-- SYSTEM ALERTS --}}
    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- MAIN CONTENT GRID --}}
    <div class="row g-4">
        {{-- LEFT SIDE: Information & Interactive Tabs --}}
        <div class="col-lg-8">
            {{-- Deployment Details Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light fw-bold text-secondary text-uppercase fs-7 tracking-wider">
                    <i class="bi bi-file-earmark-text me-2 text-primary"></i>Deployment Specifications
                </div>
                <div class="card-body py-4">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Changes Done</label>
                            <div class="p-3 bg-light rounded text-dark fs-6 style-plaintext">{!! nl2br(e($ticket->changes_done)) ?: '-' !!}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Files Modified</label>
                            <div class="p-3 bg-light rounded text-dark font-monospace fs-7">{!! nl2br(e($ticket->files_modified)) ?: '-' !!}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Modules Affected</label>
                            <div class="p-3 bg-light rounded text-dark fs-6">{!! nl2br(e($ticket->modules_affected)) ?: '-' !!}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Testing Done</label>
                            <div class="p-3 bg-light rounded text-dark fs-6">{!! nl2br(e($ticket->testing_done)) ?: '-' !!}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Deployment Notes</label>
                            <div class="p-3 bg-light rounded text-dark fs-6">{!! nl2br(e($ticket->deployment_notes)) ?: '-' !!}</div>
                        </div>
                    </div>

                    {{-- DATABASE & VERSION SECTION --}}
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-database me-2 text-secondary"></i>Database & Version</h6>
                        <div class="p-3 rounded {{ $ticket->db_changes_required ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted' }}">
                            <div class="fw-semibold">
                                DB Changes Required: {{ $ticket->db_changes_required ? 'Yes' : 'No' }}
                            </div>
                            @if ($ticket->db_changes_required)
                                <div class="mt-2 pt-2 border-top border-warning-subtle text-dark">
                                    <strong class="d-block small text-uppercase text-muted mb-1">Migration Details:</strong>
                                    <div class="font-monospace fs-7 style-plaintext">{{ $ticket->migration_details ?: '-' }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- INTERACTIVE WORKFLOW TABS --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white pb-0 pt-3 border-0">
                    <ul class="nav nav-tabs card-header-tabs border-bottom-0 gap-1" id="ticketTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-semibold text-secondary" id="review-tab" data-bs-toggle="tab" data-bs-target="#review-pane" type="button">
                                <i class="bi bi-code-slash me-1"></i> Code Review
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-semibold text-secondary" id="qa-tab" data-bs-toggle="tab" data-bs-target="#qa-pane" type="button">
                                <i class="bi bi-bug me-1"></i> Testing & Bugs
                                <span class="badge bg-danger-subtle text-danger rounded-pill ms-1 fs-8">{{ $ticket->bugs->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-semibold text-secondary" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments-pane" type="button">
                                <i class="bi bi-chat-left-text me-1"></i> Comments
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body tab-content p-4">
                    {{-- CODE REVIEW PANE --}}
                    <div class="tab-pane fade show active" id="review-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">Review Progress</h6>
                            <span class="text-muted small">Attempts: <strong class="text-dark">{{ $ticket->review_attempts }}</strong></span>
                        </div>

                        @if ($ticket->code_review_approved)
                            <div class="alert alert-success d-flex align-items-center py-2 border-0 shadow-sm mb-3">
                                <i class="bi bi-patch-check-fill me-2 fs-5"></i>
                                <div class="fw-semibold small">Code Review Approved</div>
                            </div>
                        @endif

                        @if (in_array($ticket->status, ['Draft', 'Changes Requested', 'Review Rejected']) && $ticket->isAssignedDeveloper())
                            <form method="POST" action="{{ route('deployment.tickets.submitForReview', $ticket) }}" class="mb-3">
                                @csrf
                                <button class="btn btn-primary btn-sm">
                                    <i class="bi bi-send me-1"></i> {{ $ticket->status === 'Draft' ? 'Submit For Review' : 'Resubmit For Review' }}
                                </button>
                            </form>
                        @endif

                        {{-- Reviewer Form --}}
                        @if (in_array($ticket->status, ['Review Pending', 'Review In Progress']) && $ticket->isAssignedReviewer())
                            <form method="POST" action="{{ route('deployment.tickets.review', $ticket) }}" class="p-3 border rounded bg-light row g-2 mb-3">
                                @csrf
                                <div class="col-12">
                                    <textarea name="comments" class="form-control form-control-sm" placeholder="Review comments (stored permanently)" rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="time_spent_minutes" class="form-control form-control-sm" placeholder="Time spent (minutes)">
                                </div>
                                <div class="col-md-8 d-flex gap-2 justify-content-md-end">
                                    <button type="submit" name="action" value="Approved" class="btn btn-success btn-sm">Approve</button>
                                    <button type="submit" name="action" value="Changes Requested" class="btn btn-warning btn-sm text-white">Request Changes</button>
                                    <button type="submit" name="action" value="Rejected" class="btn btn-danger btn-sm">Reject</button>
                                </div>
                            </form>
                        @endif

                        <h6 class="mt-3 small fw-bold text-uppercase text-muted tracking-wider">Review History</h6>
                        <div class="vstack gap-2">
                            @forelse ($ticket->reviewHistory as $rh)
                                <div class="p-2 border-bottom d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-{{ $rh->badgeClass() }} me-2">{{ $rh->action }}</span>
                                        <strong class="text-dark small">{{ $rh->reviewer->name ?? '-' }}</strong>
                                        @if ($rh->comments)
                                            <div class="text-muted small mt-1 bg-light p-2 rounded style-plaintext">{{ $rh->comments }}</div>
                                        @endif
                                    </div>
                                    <small class="text-muted font-monospace fs-8">attempt #{{ $rh->attempt_number }} - {{ $rh->created_at->format('d M Y H:i') }}</small>
                                </div>
                            @empty
                                <p class="text-muted small py-2 mb-0">No review activity yet.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- QA & BUGS PANE --}}
                    <div class="tab-pane fade" id="qa-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Testing Dashboard</h6>
                            @if ($ticket->qa_approved) <span class="badge bg-success-subtle text-success border border-success-subtle">QA Approved</span> @endif
                        </div>

                        @if ($ticket->status === 'Review Approved' && $ticket->isAssignedQaTester())
                            <form method="POST" action="{{ route('deployment.tickets.startTesting', $ticket) }}" class="mb-3">
                                @csrf
                                <button class="btn btn-primary btn-sm"><i class="bi bi-play-fill"></i> Start Testing</button>
                            </form>
                        @endif

                        @if ($ticket->status === 'Testing In Progress' && $ticket->isAssignedQaTester())
                            <form method="POST" action="{{ route('deployment.tickets.testing', $ticket) }}" class="p-3 bg-light rounded border mb-3">
                                @csrf
                                <textarea name="notes" class="form-control mb-2 form-control-sm" rows="2" placeholder="Testing notes"></textarea>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#createBugModal">
                                        <i class="bi bi-bug me-1"></i> Report Bug
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="result" value="Pass" class="btn btn-success btn-sm px-3">Pass Testing</button>
                                        <button type="submit" name="result" value="Fail" class="btn btn-danger btn-sm px-3">Fail Testing</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <h6 class="mt-3 small fw-bold text-uppercase text-muted tracking-wider">Bug Tasks ({{ $ticket->bugs->count() }})</h6>
                        <div class="table-responsive border rounded mt-2">
                            <table class="table table-hover align-middle mb-0 fs-7">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bug Manifest</th>
                                        <th>Severity</th>
                                        <th>Assigned Developer</th>
                                        <th class="text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ticket->bugs as $bug)
                                        <tr>
                                            <td>
                                                <a href="{{ route('deployment.bugs.show', $bug) }}" target="_blank" rel="noopener noreferrer" class="fw-bold text-decoration-none">{{ $bug->bug_code }}</a> - {{ $bug->title }}
                                                <div class="fs-8 text-muted">reported by {{ $bug->reporter->first_name ?? '-' }} {{ $bug->reporter->last_name ?? '-' }}</div>
                                            </td>
                                            <td><span class="badge bg-{{ $bug->severityBadgeClass() }}-subtle text-{{ $bug->severityBadgeClass() }} border border-{{ $bug->severityBadgeClass() }}-subtle">{{ $bug->severity }}</span></td>
                                            <td><span class="text-secondary fw-semibold">{{ $bug->developer->first_name ?? '-' }} {{ $bug->developer->last_name ?? '-' }}</span></td>
                                            <td class="text-end"><span class="badge bg-{{ $bug->statusBadgeClass() }}">{{ $bug->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No bugs reported.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- COMMENTS PANE --}}
                    <div class="tab-pane fade" id="comments-pane" role="tabpanel">
                        @if ($ticket->isInvolved())
                            <form method="POST" action="{{ route('deployment.tickets.comments.store', $ticket) }}" class="mb-4">
                                @csrf
                                <div class="input-group">
                                    <textarea name="comment" class="form-control form-control-sm" placeholder="Add a comment..." required rows="1"></textarea>
                                    <button class="btn btn-primary btn-sm px-3">Post Comment</button>
                                </div>
                            </form>
                        @endif

                        <div class="vstack gap-3">
                            @forelse ($ticket->comments as $comment)
                                <div class="d-flex gap-2 border-bottom pb-3">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold text-uppercase fs-7" style="width:32px; height:32px; flex-shrink: 0;">
                                        {{ substr($comment->user->first_name ?? 'U', 0, 2) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark small">{{ $comment->user->first_name ?? '-' }}</strong>
                                            <small class="text-muted font-monospace fs-8">{{ $comment->created_at->format('d M Y H:i') }}</small>
                                        </div>
                                        <div class="text-secondary small style-plaintext">{{ $comment->comment }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small text-center py-3 mb-0">No comments yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT SIDEBAR: Deployment Gates, Metadata, Attachments & Logs --}}
        <div class="col-lg-4">
            {{-- Deployment Approval Gates --}}
            <div class="card border-0 shadow-sm mb-4 bg-light-subtle">
                <div class="card-header bg-white py-3 border-bottom border-light fw-bold text-secondary text-uppercase fs-7 tracking-wider">
                    <i class="bi bi-shield-check me-2 text-success"></i>Deployment Approval
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush rounded border mb-3 fs-7">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                            <span class="small text-secondary fw-semibold">Code Review Approved</span>
                            <span>{!! $ticket->code_review_approved ? '✅' : '⬜' !!}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                            <span class="small text-secondary fw-semibold">QA Approved</span>
                            <span>{!! $ticket->qa_approved ? '✅' : '⬜' !!}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                            <span class="small text-secondary fw-semibold">All Bugs Closed</span>
                            <span>{!! !$ticket->hasOpenBugs() ? '✅' : '⬜' !!}</span>
                        </li>
                    </ul>

                    {{-- Dynamic Actions Workflow Forms --}}
                    <div class="vstack gap-2">
                        @if ($ticket->status === 'Testing Passed' && $ticket->isEligibleForDeploymentApproval() && ($ticket->isAssignedDeveloper() || $ticket->isAssignedQaTester()))
                            <form method="POST" action="{{ route('deployment.tickets.markReady', $ticket) }}">
                                @csrf
                                <button class="btn btn-primary w-100 fw-semibold btn-sm py-2">Mark Ready For Deployment</button>
                            </form>
                        @endif

                        @if ($ticket->status === 'Ready For Deployment' && $ticket->isSuperAdmin())
                            <form method="POST" action="{{ route('deployment.tickets.approveDeployment', $ticket) }}" class="border p-2.5 rounded bg-white">
                                @csrf
                                <textarea name="remarks" class="form-control form-control-sm mb-2" rows="2" placeholder="Remarks (optional)"></textarea>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="decision" value="Approved" class="btn btn-success btn-sm flex-fill fw-semibold">Approve</button>
                                    <button type="submit" name="decision" value="Rejected" class="btn btn-danger btn-sm flex-fill">Reject</button>
                                </div>
                            </form>
                        @endif

                        @if ($ticket->status === 'Deployment Approved' && $ticket->isSuperAdmin())
                            <form method="POST" action="{{ route('deployment.tickets.markDeployed', $ticket) }}">
                                @csrf
                                <button class="btn btn-success w-100 fw-semibold btn-sm py-2">Mark As Deployed</button>
                            </form>
                        @endif

                        @if (in_array($ticket->status, ['Deployed']) && ($ticket->isAssignedDeveloper() || $ticket->isSuperAdmin()))
                            <form method="POST" action="{{ route('deployment.tickets.rollback', $ticket) }}" class="border p-2.5 rounded bg-white">
                                @csrf
                                <input type="hidden" name="stage" value="Rollback Required">
                                <textarea name="reason" class="form-control form-control-sm mb-2" rows="2" placeholder="Rollback reason" required></textarea>
                                <button class="btn btn-outline-warning btn-sm w-100 fw-semibold">Flag Rollback Required</button>
                            </form>
                        @endif

                        @if ($ticket->status === 'Rollback Required' && ($ticket->isAssignedDeveloper() || $ticket->isSuperAdmin()))
                            <form method="POST" action="{{ route('deployment.tickets.rollback', $ticket) }}">
                                @csrf
                                <input type="hidden" name="stage" value="Rolled Back">
                                <button class="btn btn-dark btn-sm w-100 fw-semibold py-2">Confirm Rolled Back</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Basic Stakeholder Information Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light fw-bold text-secondary text-uppercase fs-7 tracking-wider">
                    <i class="bi bi-info-circle me-2 text-muted"></i>Basic Information
                </div>
                <div class="card-body fs-7 py-3">
                    <div class="mb-3">
                        <span class="text-muted d-block small mb-0.5">Project:</span>
                        <strong class="text-dark">{{ $ticket->project->project_name ?? '-' }}</strong>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted d-block small mb-0.5">Related Tickets:</span>
                        @if($relatedTicket)
                            <a href="{{ url('view/ticket/' . $relatedTicket->id) }}" target="_blank" class="text-decoration-none fw-semibold">
                                <i class="bi bi-link-45deg"></i>{{ $relatedTicket->title }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                    <hr class="text-black-50 my-2.5">
                    <div class="row g-2">
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block small">Created By</span>
                            <span class="text-dark fw-semibold">{{ $ticket->creator->first_name ?? '-' }} {{ $ticket->creator->last_name ?? '-' }}</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block small">Assigned Dev</span>
                            <span class="text-dark fw-semibold">{{ $ticket->developer->first_name ?? '-' }} {{ $ticket->developer->last_name ?? '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">Reviewer</span>
                            <span class="text-dark fw-semibold">{{ $ticket->reviewer->first_name ?? '-' }} {{ $ticket->reviewer->last_name ?? '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">QA Tester</span>
                            <span class="text-dark fw-semibold">{{ $ticket->qaTester->first_name ?? '-' }} {{ $ticket->qaTester->last_name ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attachments Sidebar Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light fw-bold text-secondary text-uppercase fs-7 tracking-wider">
                    <i class="bi bi-paperclip me-2 text-muted"></i>Attachments
                </div>
                <div class="card-body py-1 fs-7">
                    @forelse ($ticket->attachments as $file)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div class="text-truncate style-max-w-200">
                                <span class="badge bg-secondary me-1 fs-8 text-uppercase">{{ $file->type }}</span>
                                <a href="{{ $file->url() }}" target="_blank" class="text-decoration-none text-dark fw-semibold">{{ $file->original_name }}</a>
                                <div class="fs-8 text-muted">by {{ $file->uploader->name ?? '-' }}</div>
                            </div>
                            <form method="POST" action="{{ route('deployment.attachments.destroy', $file) }}" onsubmit="return confirm('Remove this file?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm text-danger p-0 border-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted small text-center py-3 mb-0">No attachments yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Admin Override Tool --}}
            @if ($ticket->isSuperAdmin())
                <div class="card border-0 shadow-sm mb-4 border-start border-3 border-dark">
                    <div class="card-header bg-white py-3 border-bottom border-light fw-bold text-secondary text-uppercase fs-7 tracking-wider">
                        <i class="bi bi-shield-lock me-2 text-dark"></i>Admin Override
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('deployment.tickets.overrideStatus', $ticket) }}">
                            @csrf
                            <select name="status" class="form-select form-select-sm mb-2">
                                @foreach (\App\Models\DeploymentTicket::statusOptions() as $status)
                                    <option value="{{ $status }}" @selected($status === $ticket->status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Reason for override" required>
                            <button class="btn btn-outline-dark btn-sm w-100 fw-semibold">Force Status</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Operational History Audit / Activity Log --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light fw-bold text-secondary text-uppercase fs-7 tracking-wider">
                    <i class="bi bi-clock-history me-2 text-muted"></i>Activity Log
                </div>
                <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                    <div class="list-group list-group-flush small">
                        @forelse ($ticket->activityLogs as $log)
                            <div class="list-group-item p-3 border-bottom-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark">{{ $log->action }}</span>
                                    <small class="text-muted font-monospace fs-8">{{ $log->created_at->format('d M Y H:i') }}</small>
                                </div>
                                <div class="text-muted mb-1 fs-8">
                                    by <span class="fw-semibold text-secondary">{{ $log->user->name ?? 'System' }}</span>
                                </div>
                                @if ($log->description)
                                    <div class="p-2 bg-light rounded text-secondary fs-7 border-start border-3 border-secondary-subtle mt-1">
                                        {{ $log->description }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">
                                <span class="small">No activity logs recorded yet.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div> {{-- END RIGHT SIDEBAR --}}
    </div>
</div>

{{-- CREATE BUG MODAL --}}
<div class="modal fade" id="createBugModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="{{ route('deployment.bugs.store', $ticket) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-bug-fill me-2"></i>Report Bug</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 fs-7">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Bug Title *</label>
                        <input type="text" name="title" class="form-control form-control-sm" required placeholder="Brief caption of issue space...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Describe the anomaly context..."></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Severity *</label>
                            <select name="severity" class="form-select form-select-sm" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Assigned Developer</label>
                            <select name="assigned_developer_id" class="form-select form-select-sm">
                                <option value="">-- Use ticket's developer --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Steps To Reproduce</label>
                        <textarea name="steps_to_reproduce" class="form-control form-control-sm" rows="2" placeholder="1. Go to path...&#10;2. Click target dynamic payload..."></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-semibold text-secondary">Screenshot</label>
                        <input type="file" name="screenshot" class="form-control form-control-sm" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-light border btn-sm text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">Create Bug</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Scope-isolated clean helper definitions */
    .fs-7 { font-size: 0.85rem !important; }
    .fs-8 { font-size: 0.75rem !important; }
    .tracking-wider { letter-spacing: 0.05em; }
    .style-max-w-200 { max-width: 200px; }
    .style-plaintext { white-space: pre-wrap; font-size: 0.9rem; line-height: 1.5; }
    .nav-tabs .nav-link { border: 1px solid transparent; border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem; padding: 0.6rem 1.2rem; }
    .nav-tabs .nav-link.active { border-color: #dee2e6 #dee2e6 #fff; color: var(--bs-primary) !important; background-color: #fff; }
    .border-bottom-light { border-bottom: 1px solid rgba(0,0,0,0.06); }
</style>
@endsection
