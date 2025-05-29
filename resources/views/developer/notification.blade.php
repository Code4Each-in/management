@extends('layout')
@section('title', 'All Comments')
@section('subtitle', 'Show')
@section('content')
                @php
                    $projectsWithComments = collect();

                    foreach ($projectMap as $projectId => $projectName) {
                        if ($groupedNotifications->has($projectId) && $groupedNotifications->get($projectId)->isNotEmpty()) {
                            $projectsWithComments->put($projectId, $projectName);
                        }
                    }
                @endphp
                <div class="row">
                @foreach($projectMap as $projectId => $projectName)
                    @php
                        $projectComments = $groupedNotifications->get($projectId, collect());
                    @endphp

                    @if($projectComments->isNotEmpty())
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ $projectName }}</h5>
                                </div>
                                <div class="card-body overflow-auto" style="max-height: 300px;">
                                    @foreach($projectComments as $notification)
                                        @php
                                            $userName = $notification->user->first_name ?? 'Unknown User';
                                            $ticketId = $notification->ticket_id ?? 'N/A';
                                            $ticketUrl = url('/view/ticket/' . $ticketId);
                                        @endphp

                                        <div class="mb-3 pb-2 border-bottom">
                                            <a href="{{ $ticketUrl }}" class="text-decoration-none text-dark d-block fw-semibold" style="transition: color 0.3s;">
                                                <small>
                                                You received a new comment on 
                                                <span class="text-primary">#{{ $ticketId }}</span> in project 
                                                <span class="fw-bold">{{ $projectName }}</span> by 
                                                <span class="fw-bold">{{ $userName }}</span> on 
                                                <span class="text-muted">{{ $notification->created_at->setTimezone('Asia/Kolkata')->format('d-M-Y H:i') }}</span>.
                                            </small>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @if($projectsWithComments->count() > 4)
            <div class="text-center mb-2">
                <a href="{{ url('/comments') }}" class="btn btn-primary px-4 py-2 fw-semibold rounded-pill" style="background-color:#4154F1; border: 2px solid #4154F1;">
                    See All
                </a>
            </div>
        @endif
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