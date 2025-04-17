@extends('layout')
@section('title', 'All Notifications')
@section('subtitle', 'All Notifications')
@section('content')
<div class="container mt-4">
        <div class="card">
            <div class="card-body mt-2">
    <table class="table  notifications" id="notification_table">
        <thead class="thead-light">
            <tr>
                <th>Type</th>
                <th>Message</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notifications as $notif)
                <tr class="mark-notification-read" data-id="{{ $notif->id }}"
                    onclick="window.location='{{ url('/view/ticket/'.$notif->ticket_id) }}'" style="cursor:pointer;">
                    <td>{{ ucfirst(str_replace('_', ' ', $notif->type)) }}</td>
                    <td>{{ $notif->message }}</td>
                    <td><small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted text-center">No notifications found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
            </div>
        </div>
</div>
@endsection

@section('js_scripts')
<script>
   $(document).ready(function() {
    $('#notification_table').DataTable({
        "order": []
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});
</script>
@endsection
