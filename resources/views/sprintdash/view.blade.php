@extends('layout')
@section('title', 'Detail Sprint')
@section('subtitle', 'Edit Sprint')
@section('content')
@php
    $donePercent = $totalTicketsCount > 0 ? round(($doneTicketsCount / $totalTicketsCount) * 100) : 0;
    $remainingPercent = 100 - $donePercent;
@endphp

<div class="progress" style="height: 25px; font-size: 0.9rem;">
    <div class="progress-bar bg-success" role="progressbar"
         style="width: {{ $donePercent }}%"
         aria-valuenow="{{ $donePercent }}" aria-valuemin="0" aria-valuemax="100">
        Done ({{ $doneTicketsCount }})
    </div>
    <div class="progress-bar bg-danger" role="progressbar"
         style="width: {{ $remainingPercent }}%"
         aria-valuenow="{{ $remainingPercent }}" aria-valuemin="0" aria-valuemax="100">
        Remaining ({{ $totalTicketsCount - $doneTicketsCount }})
    </div>
</div>
<div class="row mb-2">
    <div class="col-md-2">
        <a class="btn btn-primary mt-3"  href="{{ route('tickets.create') }}">Add
    Ticket</a>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card recent-sales overflow-auto">
        <div class="card-body">
            <h5 class="card-title">All Tickets related to <u>{{ $sprint->name  ?? '---' }}</u> sprint</h5>
            <table class="table table-borderless datatable" id="allsprint">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th>                   
                        <th scope="col">ETA (d/m/y)</th>
                        <th scope="col">Status</th>
                        <th scope="col">Assigned To</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->title }}</td>
                            <td>
                                @if(strlen($ticket->description) >= 100)
                                <span class="description">
                                    @php
                                    $plainTextDescription = strip_tags(htmlspecialchars_decode($ticket->description));
                                    $limitedDescription = substr($plainTextDescription, 0, 100) . '...';
                                    echo $limitedDescription;
                                    @endphp
                                </span>
                                <span class="fullDescription" style="display: none;">
                                 @php
                                    echo $ticket->description;
                                    @endphp
                                </span>
                                <a href="#" class="readMoreLink">Read More</a>
                                <a href="#" class="readLessLink" style="display: none;">Read Less</a>
                                @else
                                {!! $ticket->description !!}                                       
                                 @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($ticket->eta)->format('d/m/Y') }}</td>
                            <td>@if($ticket->status == 'to_do')
                                <span class="badge rounded-pill bg-primary">To do</span>
                                @elseif($ticket->status == 'in_progress')
                                <span class="badge rounded-pill bg-warning text-dark">In Progress</span>
                                @elseif($ticket->status == 'ready')
                                <span class="badge bg-info text-dark">Ready</span>
                                @else
                                <span class="badge rounded-pill  bg-success">Complete</span>
                                @endif</td>
                                <td>
                                    @foreach($assignedUsers->where('ticket_id', $ticket->id) as $user)
                                    {{ $user->assigned_user_name }} ({{ $user->designation }})
                                    @endforeach
                                </td>
                            <td>
                                <a href="{{ url('/view/ticket/'.$ticket->id) }}"  target="_blank">
                                    <i style="color:#4154f1;" class="fa fa-eye fa-fw pointer"></i>
                                </a>
                                <a href="{{ url('/edit/ticket/'.$ticket->id) }}">
                                    <i style="color:#4154f1;" class="fa fa-edit fa-fw pointer"></i>
                                </a>
                                <i style="color:#4154f1;" onClick="deleteTickets('{{ $ticket->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                            </td>                            
                        </tr>
                    @endforeach
                </tbody>
            </table>                      
        </div>
    </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
 $(document).ready(function() {
        $('#allsprint').DataTable({
            "order": []
            });
        });
        $(document).ready(function () {
        var table1 = $('#allsprint').DataTable();
        var table1Height = $('#allsprint').height();
        var maxHeight = Math.max(table1Height);
        $('#allsprint').height(maxHeight);

        $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

        });

        function deleteTickets(id) {
                $('#ticket').val(id);
               if (confirm("Are you sure ?") == true) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('/delete/tickets') }}",
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(res) {
                            location.reload();
                        }
                    });
                }
            }
            $('.readMoreLink').click(function(event) {
                event.preventDefault();
                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');
                description.text(fullDescription.text());
                $(this).hide();
                $(this).siblings('.readLessLink').show();
            });
            $('.readLessLink').click(function(event) {
                event.preventDefault();
                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');
                var truncatedDescription = fullDescription.text().substring(0, 100) + '...';
                description.text(truncatedDescription);
                $(this).hide();
                $(this).siblings('.readMoreLink').show();
            });
</script>
@endsection
