@extends('layout')

@section('title', 'Notifications')

@section('content')

    <div class="col-12">

        <div class="d-flex justify-content-end mb-3">
            <form method="POST" action="{{ route('deployment.notifications.readAll') }}">
                @csrf
                <button class="btn btn-outline-secondary btn-sm">Mark All As Read</button>
            </form>
        </div>

        <div class="card border-0 shadow-sm"> 
            <div class="list-group list-group-flush">
                @forelse ($notifications as $notification)
                    <div class="list-group-item d-flex justify-content-between align-items-start {{ $notification->isUnread() ? 'bg-light' : '' }}">
                        <div>
                            <div class="fw-semibold">
                                {{ $notification->title }}
                                @if ($notification->isUnread())
                                    <span class="badge bg-primary ms-1">New</span>
                                @endif
                            </div>
                            @if ($notification->message)
                                <div class="text-muted small">{{ $notification->message }}</div>
                            @endif
                            <div class="small text-muted mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                                @if ($notification->ticket)
                                    - <a href="{{ route('deployment.tickets.show', $notification->ticket) }}">{{ $notification->ticket->deployment_code }}</a>
                                @endif
                                @if ($notification->bug)
                                    - <a href="{{ route('deployment.bugs.show', $notification->bug) }}">{{ $notification->bug->bug_code }}</a>
                                @endif
                            </div>
                        </div>
                        @if ($notification->isUnread())
                            <form method="POST" action="{{ route('deployment.notifications.read', $notification) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">Mark Read</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">No notifications yet.</div>
                @endforelse
            </div>
            <div class="card-footer bg-white">
                {{ $notifications->links() }}
            </div>
        </div>

    </div>

@endsection
