@extends('layout')

@section('title', 'Feedback List')
@section('subtitle', 'Feedback List')

@section('content')

<div class="card p-4">

    <!-- FILTERS -->
   <form id="filter-data" method="GET" action="{{ route('ticketfeedback.index') }}">

    <div class="row mt-3 mx-auto ticket-design mb-3 gap-3">

        <!-- Project Filter -->
        <div class="col-md-3 form-group p-0">
            <label for="projectFilterselectBox">By Project</label>
            <select class="form-control" id="projectFilterselectBox" name="project_filter">
                <option value="">Select Project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}"
                        {{ request()->input('project_filter') == $project->id ? 'selected' : '' }}>
                        {{ $project->project_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Assigned Developer -->
        <div class="col-md-3 form-group p-0">
            <label for="assigneeFilterselectBox">By User</label>
            <select class="form-control" id="assigneeFilterselectBox" name="assigned_to_filter">
                <option value="">Select Assignee</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}"
                        {{ request()->input('assigned_to_filter') == $u->id ? 'selected' : '' }}>
                        {{ $u->first_name }} {{ $u->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>
</form>

    <!-- TABLE -->
    <div class="table-responsive">
        <table class="table table-borderless" id="feedbackTable">
            <thead>
                <tr>
                    <th style="width: 30px">Ticket</th>
                    <th>Project</th>
                    <th>Developer</th>
                    <th>Rating</th>
                    <th>Comments</th>
                    <th>Submitted By</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                @foreach($feedbacks as $fb)
                <tr>
                    <td>
                        <a href="{{ url('/view/ticket/'.$fb->ticket_id) }}" target="_blank">
                            #{{ $fb->ticket_id }}
                        </a>
                    </td>

                    <td>
                        {{ $fb->ticket->project->project_name ?? '-' }}
                    </td>

                    <td>
                    @php
                        $devIds = $fb->assigned_dev_id ?? [];
                    @endphp

                    @if(!empty($devIds))
                        @foreach($devIds as $id)
                            <span class="badge bg-primary">
                                {{ $allUsers[$id] ?? 'Unknown' }}
                            </span>
                        @endforeach
                    @else
                        -
                    @endif
                    </td>

                    <!-- ⭐ Stars -->
                    <td>
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $fb->rating)
                                <span style="color:#ffc107;">★</span>
                            @else
                                <span style="color:#ccc;">★</span>
                            @endif
                        @endfor
                    </td>

                    <!-- Comments -->
                   <td>
                        <span class="short-text">
                            {{ Str::limit($fb->comments, 80) }}
                        </span>

                        @if(strlen($fb->comments) > 80)
                            <a href="#" class="read-more text-primary">Read more</a>
                        @endif
                    </td>
                    <td>
                        {{ $allUsers[$fb->feedback_by] ?? 'Client' }}
                    </td>

                    <td>
                        {{ $fb->created_at->format('d M Y H:i') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection

@section('js_scripts')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {

    // DataTable
    $('#feedbackTable').DataTable({
        "order": []
    });

    // ✅ Auto submit on Project change
    $('#projectFilterselectBox').on('change', function () {
        $('#filter-data').submit();
    });

    // ✅ Auto submit on User change
    $('#assigneeFilterselectBox').on('change', function () {
        $('#filter-data').submit();
    });

});
</script>

@endsection