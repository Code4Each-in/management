@extends('layout')
@section('title', 'All Notifications')
@section('subtitle', 'Show')
@section('content')
<div class="card">
    <div class="card-body pb-4">
        <table class="table table-striped  table-bordered" id="notification">
        <thead>
            <tr>
                <th>Notification</th>
                <th>Project Name</th>
                <th>Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notifications as $index => $notification)
                <tr>
                    <td>{{ $notification->message }}</td>
                    <td>{{ $notification->ticket->project->project_name }}</td>
                    <td>
                        <a href="{{ url('/view/ticket/'.$notification->ticket_id) }}" target="_blank">
                            <i class="fa fa-eye text-primary"></i> 
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