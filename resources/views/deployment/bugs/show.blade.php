@extends('layout')

@section('title', $bug->bug_code . ' - ' . $bug->title)

@section('content')
    <div class="col-12">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center border-bottom pb-4 mb-4 gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-2 shadow-sm status-badge
                        bg-{{ $bug->statusBadgeClass() }}-subtle
                        text-{{ $bug->statusBadgeClass() }}
                        border border-{{ $bug->statusBadgeClass() }}">
                        <i class="bi bi-circle-fill me-1" style="font-size:6px;vertical-align:middle;"></i>
                        {{ $bug->status }}
                    </span>

                    <span class="badge rounded-pill px-3 py-2 shadow-sm severity-badge
                        bg-{{ $bug->severityBadgeClass() }}-subtle
                        text-{{ $bug->severityBadgeClass() }}
                        border border-{{ $bug->severityBadgeClass() }}">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        {{ $bug->severity }}
                    </span>
                </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 shadow-sm rounded">
                @if ($bug->isAssignedDeveloper() || $bug->isReporter())
                    <a href="{{ route('deployment.bugs.edit', $bug) }}" class="btn btn-white btn-sm border-end px-3">
                        <i class="bi bi-pencil me-1.5 text-muted"></i> Edit
                    </a>
                @endif
                <a href="{{ route('deployment.tickets.show', $bug->ticket) }}"
                    class="btn btn-light border-0 shadow-sm rounded-pill px-4 py-2 fw-semibold">
                        <i class="bi bi-arrow-left-circle-fill text-primary me-2"></i>
                        Back to
                        <span class="text-primary">{{ $bug->ticket->deployment_code }}</span>
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 fw-bold text-secondary text-uppercase tracking-wider small">
                        <i class="bi bi-card-text me-2"></i>Bug Details
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-semibold mb-1">Description</label>
                            <div class="bg-light p-3 rounded text-dark lh-base style-text">
                                {!! nl2br(e($bug->description ?: '-')) !!}
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-semibold mb-1">Steps To Reproduce</label>
                            <div class="bg-light p-3 rounded text-dark lh-base style-text">
                                {!! nl2br(e($bug->steps_to_reproduce ?: '-')) !!}
                            </div>
                        </div>

                        <div class="row g-3 pt-2 border-top">
                            <div class="col-md-6">
                                <span class="text-muted small d-block mb-1">Assigned Developer</span>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-secondary bg-opacity-10 text-secondary rounded-circle me-2 d-flex align-items-center justify-content-center fw-semibold small" style="width: 28px; height: 28px;">
                                        {{ substr($bug->developer->first_name ?? 'U', 0, 1) }}
                                    </div>
                                    <span class="fw-medium text-dark">{{ $bug->developer->first_name ?? '-' }} {{ $bug->developer->last_name ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block mb-1">Reported By</span>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-secondary bg-opacity-10 text-secondary rounded-circle me-2 d-flex align-items-center justify-content-center fw-semibold small" style="width: 28px; height: 28px;">
                                        {{ substr($bug->reporter->first_name ?? 'U', 0, 1) }}
                                    </div>
                                    <span class="fw-medium text-dark">{{ $bug->reporter->first_name ?? '-' }} {{ $bug->reporter->last_name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($bug->screenshotUrl())
                            <div class="mt-4 pt-3 border-top">
                                <label class="form-label text-muted small fw-semibold mb-2"><i class="bi bi-image me-1"></i> Screenshot</label>
                                <div class="position-relative overflow-hidden rounded border bg-light d-inline-block">
                                    <img src="{{ $bug->screenshotUrl() }}" class="img-fluid rounded border mt-2" style="max-height: 400px; transition: transform .2s;" onmouseover="this.style.transform='scale(1.01)'" onmouseout="this.style.transform='scale(1)'">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 fw-bold text-secondary text-uppercase tracking-wider small">
                        <i class="bi bi-gear me-2"></i>Bug Workflow
                    </div>
                    <div class="card-body px-4 pb-4">
                        @if (in_array($bug->status, ['Open', 'Reopened', 'Retest Required']) && $bug->isAssignedDeveloper())
                            <form method="POST" action="{{ route('deployment.bugs.changeStatus', $bug) }}" class="row g-2 mb-2">
                                @csrf
                                <input type="hidden" name="status" value="In Progress">
                                <div class="col-12">
                                    <button class="btn btn-info btn-sm px-3 shadow-sm text-white fw-medium">
                                        <i class="bi bi-play-fill me-1"></i> Mark In Progress
                                    </button>
                                </div>
                            </form>
                        @endif

                        @if ($bug->status === 'In Progress' && $bug->isAssignedDeveloper())
                            <form method="POST" action="{{ route('deployment.bugs.changeStatus', $bug) }}" class="row g-2 mb-2">
                                @csrf
                                <div class="col-12">
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="Add code modifications or notes here before sending back..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="status" value="Ready For Retest" class="btn btn-primary btn-sm px-3 mt-2 shadow-sm fw-medium">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Mark Ready For Retest
                                    </button>
                                </div>
                            </form>
                        @endif

                        @if ($bug->status === 'Ready For Retest' && $bug->isReporter())
                            <form method="POST" action="{{ route('deployment.bugs.verify', $bug) }}" class="row g-2 mb-2">
                                @csrf
                                <div class="col-12">
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="Verify code updates, add staging link metrics or QA notes..."></textarea>
                                </div>
                                <div class="col-12 d-flex gap-2 mt-2">
                                    <button type="submit" name="decision" value="Closed" class="btn btn-success btn-sm px-3 shadow-sm fw-medium">
                                        <i class="bi bi-check2-all me-1"></i> Close Bug
                                    </button>
                                    <button type="submit" name="decision" value="Reopened" class="btn btn-danger btn-sm px-3 shadow-sm fw-medium">
                                        <i class="bi bi-exclamation-octagon me-1"></i> Reopen Bug
                                    </button>
                                </div>
                            </form>
                        @endif

                        @if ($bug->status === 'Closed')
                            <div class="d-flex align-items-center bg-success bg-opacity-10 text-success p-3 rounded border border-success border-opacity-25 mb-0">
                                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Verified & Resolved</h6>
                                    <p class="small text-muted mb-0">This issue has been closed and successfully passed all verification checks.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 fw-bold text-secondary text-uppercase tracking-wider small">
                        <i class="bi bi-clock-history me-2"></i>Bug History
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="position-relative ps-3 border-start ms-2 py-1">
                            @forelse ($bug->history as $h)
                                <div class="position-relative mb-4">
                                    <div class="position-absolute bg-white border border-primary rounded-circle" style="width: 12px; height: 12px; left: -20px; top: 4px;"></div>

                                    <div class="small">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge bg-light text-secondary border fw-medium">{{ $h->old_status ?? 'Created' }}</span>
                                            <i class="bi bi-arrow-right text-muted small"></i>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-medium">{{ $h->new_status }}</span>
                                        </div>
                                        <div class="text-muted mt-1 small">
                                            <span class="fw-semibold text-dark">{{ $h->changedBy->name ?? '-' }}</span> &bull;
                                            <span>{{ $h->created_at->format('d M Y H:i') }}</span>
                                        </div>
                                        @if ($h->remarks)
                                            <div class="bg-light p-2 rounded mt-2 border-start border-3 text-secondary italic-text">
                                                {{ $h->remarks }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="position-absolute bg-white border border-secondary rounded-circle" style="width: 12px; height: 12px; left: -20px; top: 4px;"></div>
                                <p class="text-muted small mb-0">No history events generated yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 fw-bold text-secondary text-uppercase tracking-wider small">
                        <i class="bi bi-chat-square-text me-2"></i>Comments Activity
                    </div>
                    <div class="card-body px-4 pb-4">
                        @if ($bug->isAssignedDeveloper() || $bug->isReporter())
                            <form method="POST" action="{{ route('deployment.bugs.comments.store', $bug) }}" class="mb-4">
                                @csrf
                                <textarea name="comment" class="form-control mb-2" rows="3" placeholder="Collaborate on a fix or post runtime variables..." required></textarea>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-primary px-3 shadow-sm fw-medium">
                                        <i class="bi bi-send me-1"></i> Post Comment
                                    </button>
                                </div>
                            </form>
                        @endif

                        <div class="d-flex flex-column gap-3">
                            @forelse ($bug->comments as $comment)
                                <div class="bg-light p-3 rounded border border-light shadow-sm-hover">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                        <strong class="text-dark small d-flex align-items-center">
                                            <i class="bi bi-person-circle text-muted me-1.5 fs-6"></i>
                                            {{ $comment->user->name ?? '-' }}
                                        </strong>
                                        <small class="text-muted fw-normal" style="font-size: 0.75rem;">
                                            <i class="bi bi-clock me-1"></i>{{ $comment->created_at->format('d M Y H:i') }}
                                        </small>
                                    </div>
                                    <div class="text-secondary lh-base small style-text" style="white-space: pre-wrap;">{{ $comment->comment }}</div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-chat-left-dots fs-2 d-block mb-2 text-black-50"></i>
                                    <p class="small mb-0">No comments posted yet. Start the conversation.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 fw-bold text-secondary text-uppercase tracking-wider small">
                        <i class="bi bi-folder-symlink me-2"></i>Parent Ticket
                    </div>
                    <div class="card-body px-4 pb-4">
                        <a href="{{ route('deployment.tickets.show', $bug->ticket) }}" target="_blank" rel="noopener noreferrer" class="fw-semibold text-decoration-none h6 text-primary d-block mb-2 hover-underline">
                            {{ $bug->ticket->deployment_code }} <i class="bi bi-box-arrow-up-right small ms-0.5" style="font-size: 0.7rem;"></i>
                        </a>
                        <div class="text-secondary small mb-3 fw-medium">
                            {{ $bug->ticket->deployment_name }}
                        </div>
                        <div class="pt-2 border-top">
                            <span class="badge rounded-pill px-3 py-2 shadow-sm
                                bg-{{ $bug->ticket->statusBadgeClass() }}-subtle
                                text-{{ $bug->ticket->statusBadgeClass() }}
                                border border-{{ $bug->ticket->statusBadgeClass() }}">
                                <i class="bi bi-circle-fill me-1" style="font-size:6px;vertical-align:middle;"></i>
                                {{ $bug->ticket->status }}
                            </span>
                        </div>
                    </div>
                </div>

                @if ($bug->isSuperAdmin())
                    <div class="card border-0 shadow-sm border-start border-3 border-danger mb-4">
                        <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 fw-bold text-danger text-uppercase tracking-wider small">
                            <i class="bi bi-exclamation-triangle me-2"></i>Danger Zone
                        </div>
                        <div class="card-body px-4 pb-4">
                            <p class="text-muted small mb-3">Permanently wipe this tracking node item, including records and associated comment assets.</p>
                            <form method="POST" action="{{ route('deployment.bugs.destroy', $bug) }}" onsubmit="return confirm('Delete this bug permanently?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm w-100 fw-medium">
                                    <i class="bi bi-trash3 me-1"></i> Delete Bug Permanently
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>
@endsection
