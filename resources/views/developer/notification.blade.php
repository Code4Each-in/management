@extends('layout')
@section('title', 'All Comments')
@section('subtitle', 'Show')
@section('content')
<style>
.fa-bell {
    animation: swing 1.5s ease-in-out infinite;
    transform-origin: top center;
}

@keyframes swing {
    0%   { transform: rotate(0deg); }
    20%  { transform: rotate(15deg); }
    40%  { transform: rotate(-10deg); }
    60%  { transform: rotate(5deg); }
    80%  { transform: rotate(-5deg); }
    100% { transform: rotate(0deg); }
}
</style>
@php
    $projectsWithComments = collect();

    foreach ($projectMap as $projectId => $projectName) {
        if ($groupedNotifications->has($projectId) && $groupedNotifications->get($projectId)->isNotEmpty()) {
            $projectsWithComments->put($projectId, $projectName);
        }
    }

@endphp

<div class="row">
    @foreach($projectsWithComments as $projectId => $projectName)
        @php
            $projectComments = $groupedNotifications->get($projectId, collect());

            $groupedByDate = $projectComments->groupBy(function ($comment) {
                $commentDate = $comment->created_at->copy()->setTimezone('Asia/Kolkata')->startOfDay();
                $today = now('Asia/Kolkata')->startOfDay();
                $yesterday = now('Asia/Kolkata')->subDay()->startOfDay();

                if ($commentDate->eq($today)) return 'Today';
                if ($commentDate->eq($yesterday)) return 'Yesterday';
                return $commentDate->format('d-M-Y');
            });

            $accordionId = 'accordionItem' . $projectId;
        @endphp

        <div class="col-md-6 mb-4">
            <div class="accordion" id="accordion{{ $projectId }}">
                <div class="accordion-item border rounded shadow-sm">
                    <h2 class="accordion-header" id="heading{{ $accordionId }}">
                        <button class="accordion-button collapsed text-white fw-bold" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse{{ $accordionId }}"
                                aria-expanded="true" aria-controls="collapse{{ $accordionId }}"
                                style="background: #297bab;">
                            {{ $projectName }}
                        </button>
                    </h2>
                    <div id="collapse{{ $accordionId }}" class="accordion-collapse collapse show"
                         aria-labelledby="heading{{ $accordionId }}" data-bs-parent="#accordion{{ $projectId }}">
                        <div class="accordion-body" style="max-height: 300px; overflow-y: auto;">
                            @foreach($groupedByDate as $label => $comments)
                                <div class="text-center mb-2">
                                    <span class="badge px-3 py-1 rounded-pill" style="background-color: #e0e0e0; color: #333; font-weight: 600; font-size: 12px;">
                                        {{ $label }}
                                    </span>
                                </div>

                                @foreach($comments as $notification)
                                    @php
                                        $userName = $notification->user->first_name ?? 'Unknown User';
                                        $ticketId = $notification->ticket_id ?? 'N/A';
                                        $ticketUrl = url('/view/ticket/' . $ticketId);
                                    @endphp

                                    <div class="notification-entry mb-3 pb-2 border-bottom">
                                        <i class="fa-solid fa-bell notification-icon animate-bounce text-warning me-2"></i>
                                        <a href="{{ $ticketUrl }}" target="_blank" class="text-decoration-none text-dark d-block fw-semibold" style="transition: color 0.3s;">
                                            <small>
                                                You received a new comment on 
                                                <span class="text-primary">#{{ $ticketId }}</span> in project 
                                                <strong>{{ $projectName }}</strong> by 
                                                <span class="fw-bold">{{ $userName }}</span> on 
                                                <span class="text-muted">{{ $notification->created_at->setTimezone('Asia/Kolkata')->format('d-M-Y h:i A') }}</span>.
                                            </small>
                                        </a>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
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