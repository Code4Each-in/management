@extends('layout')

@section('title', $bug->bug_code . ' - ' . $bug->title)

@section('content')

    <div class="col-12">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="badge bg-{{ $bug->statusBadgeClass() }}">{{ $bug->status }}</span>
                <span class="badge bg-{{ $bug->severityBadgeClass() }}">{{ $bug->severity }}</span>
            </div>
            <div>
                @if ($bug->isAssignedDeveloper() || $bug->isReporter())
                    <a href="{{ route('deployment.bugs.edit', $bug) }}" class="btn btn-outline-secondary">Edit</a>
                @endif
                <a href="{{ route('deployment.tickets.show', $bug->ticket) }}" class="btn btn-outline-secondary">Back to Ticket {{ $bug->ticket->deployment_code }}</a>
            </div>
        </div> 

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Bug Details</div>
                    <div class="card-body">
                        <p><strong>Description</strong><br>{{ $bug->description ?: '-' }}</p>
                        <p><strong>Steps To Reproduce</strong><br>{{ $bug->steps_to_reproduce ?: '-' }}</p>
                        <div class="row">
                            <div class="col-md-6"><strong>Assigned Developer:</strong> {{ $bug->developer->first_name ?? '-' }} {{ $bug->developer->last_name ?? '-' }}</div>
                            <div class="col-md-6"><strong>Reported By:</strong> {{ $bug->reporter->first_name ?? '-' }} {{ $bug->reporter->last_name ?? '-' }}</div>
                        </div>
                        @if ($bug->screenshotUrl())
                            <div class="mt-3">
                                <strong>Screenshot</strong><br>
                                <img src="{{ $bug->screenshotUrl() }}" class="img-fluid rounded border mt-2" style="max-height: 400px;">
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Workflow actions --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Bug Workflow</div>
                    <div class="card-body">

                        @if (in_array($bug->status, ['Open', 'Reopened', 'Retest Required']) && $bug->isAssignedDeveloper())
                            <form method="POST" action="{{ route('deployment.bugs.changeStatus', $bug) }}" class="row g-2 mb-2">
                                @csrf
                                <input type="hidden" name="status" value="In Progress">
                                <div class="col-12">
                                    <button class="btn btn-info btn-sm">Mark In Progress</button>
                                </div>
                            </form>
                        @endif

                        @if ($bug->status === 'In Progress' && $bug->isAssignedDeveloper())
                            <form method="POST" action="{{ route('deployment.bugs.changeStatus', $bug) }}" class="row g-2 mb-2">
                                @csrf
                                <div class="col-12">
                                    <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Fix remarks"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="status" value="Ready For Retest" class="btn btn-primary btn-sm mt-2">
                                        Mark Ready For Retest
                                    </button>
                                </div>
                            </form>
                        @endif

                        @if ($bug->status === 'Ready For Retest' && $bug->isReporter())
                            <form method="POST" action="{{ route('deployment.bugs.verify', $bug) }}" class="row g-2 mb-2">
                                @csrf
                                <div class="col-12">
                                    <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="QA verification remarks"></textarea>
                                </div>
                                <div class="col-12 d-flex gap-2 mt-2">
                                    <button type="submit" name="decision" value="Closed" class="btn btn-success btn-sm">Close Bug</button>
                                    <button type="submit" name="decision" value="Reopened" class="btn btn-danger btn-sm">Reopen Bug</button>
                                </div>
                            </form>
                        @endif

                        @if ($bug->status === 'Closed')
                            <p class="text-success mb-0"><i class="bi bi-check-circle"></i> Bug closed and verified.</p>
                        @endif
                    </div>
                </div>

                {{-- History --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Bug History</div>
                    <div class="card-body">
                        @forelse ($bug->history as $h)
                            <div class="border-bottom py-2 small">
                                <strong>{{ $h->old_status ?? 'Created' }} → {{ $h->new_status }}</strong><br>
                                <span class="text-muted">{{ $h->changedBy->name ?? '-' }} - {{ $h->created_at->format('d M Y H:i') }}</span>
                                @if ($h->remarks)
                                    <div>{{ $h->remarks }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">No history yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Comments --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Comments</div>
                    <div class="card-body">
                        @if ($bug->isAssignedDeveloper() || $bug->isReporter())
                            <form method="POST" action="{{ route('deployment.bugs.comments.store', $bug) }}" class="mb-3">
                                @csrf
                                <textarea name="comment" class="form-control mb-2" rows="2" placeholder="Add a comment..." required></textarea>
                                <button class="btn btn-sm btn-primary">Post Comment</button>
                            </form>
                        @endif
                        @forelse ($bug->comments as $comment)
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

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Parent Deployment</div>
                    <div class="card-body">
                        <a href="{{ route('deployment.tickets.show', $bug->ticket) }}">
                            {{ $bug->ticket->deployment_code }} - {{ $bug->ticket->deployment_name }}
                        </a>
                        <div class="mt-2">
                            <span class="badge bg-{{ $bug->ticket->statusBadgeClass() }}">{{ $bug->ticket->status }}</span>
                        </div>
                    </div>
                </div>

                @if ($bug->isSuperAdmin())
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white fw-semibold text-danger">Danger Zone</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('deployment.bugs.destroy', $bug) }}" onsubmit="return confirm('Delete this bug permanently?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm w-100">Delete Bug</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
