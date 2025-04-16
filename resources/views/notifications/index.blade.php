@extends('layout')

@section('title', 'All Notifications')
@section('subtitle', 'All Notifications')

@section('content')
<div class="container mt-4">
    <ul class="list-group">
        @forelse($notifications as $notif)
            <li class="list-group-item">
                <strong>{{ ucfirst(str_replace('_', ' ', $notif->type)) }}</strong><br>
                {{ $notif->message }}<br>
                <small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small>
            </li>
        @empty
            <li class="list-group-item text-muted">No notifications found.</li>
        @endforelse
    </ul>
</div>
@endsection

@section('js_scripts')
@endsection

