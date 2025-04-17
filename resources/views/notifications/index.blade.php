@extends('layout')

@section('title', 'All Notifications')
@section('subtitle', 'All Notifications')

@section('content')
<div class="container mt-4">
    <ul class="list-group">
        @forelse($notifications as $notif)
        <a href="{{ url('/view/ticket/'.$notif->ticket_id) }}"
            class="text-decoration-none text-dark mark-notification-read"
            data-id="{{ $notif->id }}">    
        <li class="list-group-item">
                <strong>{{ ucfirst(str_replace('_', ' ', $notif->type)) }}</strong><br>
                {{ $notif->message }}<br>
                <small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small>
            </li>
        </a>
        @empty
            <li class="list-group-item text-muted">No notifications found.</li>
        @endforelse
    </ul>
</div>
@endsection

@section('js_scripts')
@endsection

