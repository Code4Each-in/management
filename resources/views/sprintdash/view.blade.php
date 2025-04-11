@extends('layout')
@section('title', 'Sprint Detail')
@section('subtitle', 'Edit Sprint')
@section('content')
<div class="row">
    <div class="row mb-2">
        <div class="col-md-2">
            <a class="btn btn-primary mt-3" href="{{ route('tickets.create') }}">Add Ticket</a>
        </div>
    </div>
    @php
    $donePercent = $totalTicketsCount > 0 ? round(($doneTicketsCount / $totalTicketsCount) * 100) : 0;
@endphp

<div class="accordion mt-4 mb-3" id="sprintAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingInfo">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInfo" aria-expanded="true" aria-controls="collapseInfo">
                <strong>Sprint Info</strong>
            </button>
        </h2>
        <div id="collapseInfo" class="accordion-collapse collapse show" aria-labelledby="headingInfo" data-bs-parent="#sprintAccordion">
            <div class="accordion-body">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center p-3">
                        <svg width="100" height="100" viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg"
                                  d="M18 2.0845
                                     a 15.9155 15.9155 0 0 1 0 31.831
                                     a 15.9155 15.9155 0 0 1 0 -31.831"
                                  fill="none"
                                  stroke="#eee"
                                  stroke-width="2.5"/>
                            <path class="circle"
                                  stroke-dasharray="{{ $donePercent }}, 100"
                                  d="M18 2.0845
                                     a 15.9155 15.9155 0 0 1 0 31.831
                                     a 15.9155 15.9155 0 0 1 0 -31.831"
                                  fill="none"
                                  stroke="#0d6efd"
                                  stroke-width="2.5"/>
                            <text x="18" y="20.35" class="percentage" text-anchor="middle" font-size="6" fill="#0d6efd">{{ $donePercent }}%</text>
                        </svg>
                        <p class="mt-2 mb-0">Done ({{ $doneTicketsCount }}) / Total ({{ $totalTicketsCount }})</p>
                    </div>
                    <div class="col-md-9">
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label fw-bold">Sprint Name</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $sprint->name }}</p>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label fw-bold">Project</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $sprints->project_name }}</p>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label fw-bold">Client</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $clients->client_name }}</p>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label fw-bold">Start Date</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ \Carbon\Carbon::parse($sprint->start_date)->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label fw-bold">End Date</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ \Carbon\Carbon::parse($sprint->eta)->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                    </div> 
                </div> 
            </div>
        </div>
    </div>
</div>
    <div class="col-md-12">
        <div class="card recent-sales overflow-auto">
        <div class="card-body mt-2">
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
