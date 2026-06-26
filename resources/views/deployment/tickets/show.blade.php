@extends('layout')
@section('title', $ticket->deployment_code . ' - ' . $ticket->deployment_name)
@section('content')

    <div class="col-12">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="badge bg-{{ $ticket->statusBadgeClass() }}">{{ $ticket->status }}</span>
                <span class="badge bg-secondary">{{ $ticket->priority }} Priority</span>
            </div>
            <div>
                @if ($ticket->isAssignedDeveloper() || $ticket->isSuperAdmin())
                    <a href="{{ route('deployment.tickets.edit', $ticket) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endif
                <a href="{{ route('deployment.tickets.index') }}" class="btn btn-outline-secondary">Back to List</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3">
            {{-- LEFT: Details --}} 
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Basic Information</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-2"><strong>Project:</strong> {{ $ticket->project->project_name ?? '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>Related Tickets:</strong> {{ $ticket->related_ticket_ids ?: '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>Created By:</strong> {{ $ticket->creator->first_name ?? '-' }} {{ $ticket->creator->last_name ?? '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>Assigned Developer:</strong> {{ $ticket->developer->first_name ?? '-' }} {{ $ticket->developer->last_name ?? '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>Reviewer:</strong> {{ $ticket->reviewer->first_name ?? '-' }} {{ $ticket->reviewer->last_name ?? '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>QA Tester:</strong> {{ $ticket->qaTester->first_name ?? '-' }} {{ $ticket->qaTester->last_name ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Deployment Details</div>
                    <div class="card-body">
                        <p><strong>Changes Done</strong><br>{{ $ticket->changes_done ?: '-' }}</p>
                        <p><strong>Files Modified</strong><br>{{ $ticket->files_modified ?: '-' }}</p>
                        <p><strong>Modules Affected</strong><br>{{ $ticket->modules_affected ?: '-' }}</p>
                        <p><strong>Testing Done</strong><br>{{ $ticket->testing_done ?: '-' }}</p>
                        <p class="mb-0"><strong>Deployment Notes</strong><br>{{ $ticket->deployment_notes ?: '-' }}</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Database & Version</div>
                    <div class="card-body row">
                        <div class="col-md-4 mb-2"><strong>DB Changes Required:</strong> {{ $ticket->db_changes_required ? 'Yes' : 'No' }}</div>
                        @if ($ticket->db_changes_required)
                            <div class="col-12"><strong>Migration Details</strong><br>{{ $ticket->migration_details ?: '-' }}</div>
                        @endif
                    </div>
                </div>

                {{-- Attachments --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Attachments</div>
                    <div class="card-body">
                        @forelse ($ticket->attachments as $file)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <span class="badge bg-secondary">{{ $file->type }}</span>
                                    <a href="{{ $file->url() }}" target="_blank">{{ $file->original_name }}</a>
                                    <small class="text-muted">by {{ $file->uploader->name ?? '-' }}</small>
                                </div>
                                <form method="POST" action="{{ route('deployment.attachments.destroy', $file) }}" onsubmit="return confirm('Remove this file?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No attachments yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Code Review --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                        <span>Code Review</span>
                        <span>
                            Attempts: {{ $ticket->review_attempts }}
                            @if ($ticket->code_review_approved)
                                <span class="badge bg-success ms-2">Approved</span>
                            @endif
                        </span>
                    </div>
                    <div class="card-body">

                        {{-- Developer: submit / resubmit --}}
                        @if (in_array($ticket->status, ['Draft', 'Changes Requested', 'Review Rejected']) && $ticket->isAssignedDeveloper())
                            <form method="POST" action="{{ route('deployment.tickets.submitForReview', $ticket) }}" class="mb-3">
                                @csrf
                                <button class="btn btn-primary btn-sm">
                                    <i class="bi bi-send"></i> {{ $ticket->status === 'Draft' ? 'Submit For Review' : 'Resubmit For Review' }}
                                </button>
                            </form>
                        @endif

                        {{-- Reviewer: review actions --}}
                        @if (in_array($ticket->status, ['Review Pending', 'Review In Progress']) && $ticket->isAssignedReviewer())
                            <form method="POST" action="{{ route('deployment.tickets.review', $ticket) }}" class="row g-2 mb-3">
                                @csrf
                                <div class="col-12">
                                    <textarea name="comments" class="form-control" placeholder="Review comments (stored permanently)" rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="time_spent_minutes" class="form-control" placeholder="Time spent (minutes)">
                                </div>
                                <div class="col-md-8 d-flex gap-2">
                                    <button type="submit" name="action" value="Approved" class="btn btn-success btn-sm">Approve</button>
                                    <button type="submit" name="action" value="Changes Requested" class="btn btn-warning btn-sm">Request Changes</button>
                                    <button type="submit" name="action" value="Rejected" class="btn btn-danger btn-sm">Reject</button>
                                </div>
                            </form>
                        @endif

                        <h6 class="mt-3">Review History</h6>
                        @forelse ($ticket->reviewHistory as $rh)
                            <div class="border-bottom py-2">
                                <span class="badge bg-{{ $rh->badgeClass() }}">{{ $rh->action }}</span>
                                <strong>{{ $rh->reviewer->name ?? '-' }}</strong>
                                <small class="text-muted">attempt #{{ $rh->attempt_number }} - {{ $rh->created_at->format('d M Y H:i') }}</small>
                                @if ($rh->comments)
                                    <div class="small mt-1">{{ $rh->comments }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted">No review activity yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Testing & Bugs --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                        <span>Testing & Bugs</span>
                        @if ($ticket->qa_approved)
                            <span class="badge bg-success">QA Approved</span>
                        @endif
                    </div>
                    <div class="card-body">

                        @if ($ticket->status === 'Review Approved' && $ticket->isAssignedQaTester())
                            <form method="POST" action="{{ route('deployment.tickets.startTesting', $ticket) }}" class="mb-3">
                                @csrf
                                <button class="btn btn-primary btn-sm"><i class="bi bi-play"></i> Start Testing</button>
                            </form>
                        @endif

                        @if ($ticket->status === 'Testing In Progress' && $ticket->isAssignedQaTester())
                            <form method="POST" action="{{ route('deployment.tickets.testing', $ticket) }}" class="row g-2 mb-3">
                                @csrf
                                <div class="col-12">
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Testing notes"></textarea>
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" name="result" value="Pass" class="btn btn-success btn-sm">Pass Testing</button>
                                    <button type="submit" name="result" value="Fail" class="btn btn-danger btn-sm">Fail Testing</button>
                                </div>
                            </form>

                            <button class="btn btn-sm btn-outline-danger mb-3" data-bs-toggle="modal" data-bs-target="#createBugModal">
                                <i class="bi bi-bug"></i> Report Bug
                            </button>
                        @endif

                        <h6 class="mt-3">Bug Tasks ({{ $ticket->bugs->count() }})</h6>
                        @forelse ($ticket->bugs as $bug)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <a href="{{ route('deployment.bugs.show', $bug) }}">{{ $bug->bug_code }}</a> - {{ $bug->title }}
                                    <span class="badge bg-{{ $bug->severityBadgeClass() }}">{{ $bug->severity }}</span>
                                    <br>
                                    <small class="text-muted">Assigned to {{ $bug->developer->first_name ?? '-' }} {{ $bug->developer->last_name ?? '-' }}- reported by {{ $bug->reporter->first_name ?? '-' }} {{ $bug->reporter->last_name ?? '-' }}</small>
                                </div>
                                <span class="badge bg-{{ $bug->statusBadgeClass() }}">{{ $bug->status }}</span>
                            </div>
                        @empty
                            <p class="text-muted">No bugs reported.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Comments --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Comments</div>
                    <div class="card-body">
                        @if ($ticket->isInvolved())
                            <form method="POST" action="{{ route('deployment.tickets.comments.store', $ticket) }}" class="mb-3">
                                @csrf
                                <textarea name="comment" class="form-control mb-2" rows="2" placeholder="Add a comment..." required></textarea>
                                <button class="btn btn-sm btn-primary">Post Comment</button>
                            </form>
                        @endif
                        @forelse ($ticket->comments as $comment)
                            <div class="border-bottom py-2">
                                <strong>{{ $comment->user->name ?? '-' }}</strong>
                                <small class="text-muted">{{ $comment->created_at->format('d M Y H:i') }}</small>
                                <div>{{ $comment->comment }}</div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No comments yet.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- RIGHT: Workflow actions, approval, logs --}}
            <div class="col-lg-4">

                {{-- Deployment approval gate --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Deployment Approval</div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-3">
                            <li>{!! $ticket->code_review_approved ? '✅' : '⬜' !!} Code Review Approved</li>
                            <li>{!! $ticket->qa_approved ? '✅' : '⬜' !!} QA Approved</li>
                            <li>{!! !$ticket->hasOpenBugs() ? '✅' : '⬜' !!} All Bugs Closed</li>
                        </ul>

                        @if ($ticket->status === 'Testing Passed' && $ticket->isEligibleForDeploymentApproval() && ($ticket->isAssignedDeveloper() || $ticket->isAssignedQaTester()))
                            <form method="POST" action="{{ route('deployment.tickets.markReady', $ticket) }}" class="mb-2">
                                @csrf
                                <button class="btn btn-primary btn-sm w-100">Mark Ready For Deployment</button>
                            </form>
                        @endif

                        @if ($ticket->status === 'Ready For Deployment' && $ticket->isSuperAdmin())
                            <form method="POST" action="{{ route('deployment.tickets.approveDeployment', $ticket) }}" class="mb-2">
                                @csrf
                                <textarea name="remarks" class="form-control form-control-sm mb-2" rows="2" placeholder="Remarks (optional)"></textarea>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="decision" value="Approved" class="btn btn-success btn-sm flex-fill">Approve</button>
                                    <button type="submit" name="decision" value="Rejected" class="btn btn-danger btn-sm flex-fill">Reject</button>
                                </div>
                            </form>
                        @endif

                        @if ($ticket->status === 'Deployment Approved' && $ticket->isSuperAdmin())
                            <form method="POST" action="{{ route('deployment.tickets.markDeployed', $ticket) }}" class="mb-2">
                                @csrf
                                <button class="btn btn-success btn-sm w-100">Mark As Deployed</button>
                            </form>
                        @endif

                        @if (in_array($ticket->status, ['Deployed']) && ($ticket->isAssignedDeveloper() || $ticket->isSuperAdmin()))
                            <form method="POST" action="{{ route('deployment.tickets.rollback', $ticket) }}" class="mb-2">
                                @csrf
                                <input type="hidden" name="stage" value="Rollback Required">
                                <textarea name="reason" class="form-control form-control-sm mb-2" rows="2" placeholder="Rollback reason"></textarea>
                                <button class="btn btn-outline-warning btn-sm w-100">Flag Rollback Required</button>
                            </form>
                        @endif

                        @if ($ticket->status === 'Rollback Required' && ($ticket->isAssignedDeveloper() || $ticket->isSuperAdmin()))
                            <form method="POST" action="{{ route('deployment.tickets.rollback', $ticket) }}" class="mb-2">
                                @csrf
                                <input type="hidden" name="stage" value="Rolled Back">
                                <button class="btn btn-dark btn-sm w-100">Confirm Rolled Back</button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Admin override --}}
                @if ($ticket->isSuperAdmin())
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white fw-semibold">Admin Override</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('deployment.tickets.overrideStatus', $ticket) }}">
                                @csrf
                                <select name="status" class="form-select form-select-sm mb-2">
                                    @foreach (\App\Models\DeploymentTicket::statusOptions() as $status)
                                        <option value="{{ $status }}"  <?php if($status === $ticket->status) echo 'selected="selected"'; ?>>{{ $status }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Reason for override">
                                <button class="btn btn-outline-dark btn-sm w-100">Force Status</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Activity log --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Activity Log</div>
                    <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                        @forelse ($ticket->activityLogs as $log)
                            <div class="border-bottom py-2 small">
                                <strong>{{ $log->action }}</strong><br>
                                <span class="text-muted">{{ $log->user->name ?? 'System' }} - {{ $log->created_at->format('d M Y H:i') }}</span>
                                @if ($log->description)
                                    <div>{{ $log->description }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">No activity yet.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

        {{-- Create Bug Modal --}}
        <div class="modal fade" id="createBugModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('deployment.bugs.store', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Report Bug</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="form-label">Bug Title *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Severity *</label>
                                <select name="severity" class="form-select" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Assigned Developer</label>
                                <select name="assigned_developer_id" class="form-select">
                                    <option value="">-- Use ticket's developer --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Steps To Reproduce</label>
                                <textarea name="steps_to_reproduce" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Screenshot</label>
                                <input type="file" name="screenshot" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Create Bug</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

@endsection
