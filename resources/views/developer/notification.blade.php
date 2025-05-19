@extends('layout')
@section('title', 'All Comments')
@section('subtitle', 'Show')
@section('content')
<div class="card">
    <div class="card-body pb-4">
        <table class="table table-striped table-bordered" id="notification">
            <thead>
                <tr>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notifications as $notification)
                    @php
            
                        if(auth()->user()->role_id == 6) {
                            $projectName = $notification->ticket
                                ? ($projectMap[$notification->ticket->project_id] ?? 'Unknown Project')
                                : 'Unknown Project';
                        } else {
                            $projectName = $notification->ticket->project->project_name ?? 'Unknown Project';
                        }
            
                        $userName = $notification->user->first_name ?? 'Unknown User';
                        $ticketUrl = url('/view/ticket/' . $notification->ticket_id);
                    @endphp
                    <tr>
                        <td>
                       <a href="{{ $ticketUrl }}" class="text-decoration-none text-dark d-block">
                            You received a new comment on 
                            <strong class="text-primary">#{{ $notification->ticket_id }}</strong> 
                            in project <strong>{{ $projectName }}</strong> 
                            by <strong>{{ $userName }}</strong> 
                            on <strong>{{ $notification->created_at->format('d-m-Y H:i') }}</strong>.
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
        $('#notification').DataTable({
            "order": false 
        });
    });
</script>
@endsection