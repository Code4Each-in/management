@extends('layout')
@section('title', 'All Notifications')
@section('subtitle', 'Show')
@section('content')
<div class="card">
    <div class="card-body pb-4">
        <table class="table table-striped  table-bordered" id="notification">
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Notification</th>
                <th>Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notifications as $index => $notification)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $notification->message }}</td>
                    <td>
                        <a href="{{ url('/view/ticket/'.$notification->ticket_id) }}" target="_blank">
                            <i class="fa fa-eye text-primary"></i> {{ $notification->ticket_id }}
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>
@endsection
@section('js_scripts')
<script>
    $(document).ready(function() {
        $('#notification').DataTable(); 
    });
</script>
@endsection