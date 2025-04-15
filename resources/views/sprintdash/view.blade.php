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
                    @php
    $total = $totalTicketsCount > 0 ? $totalTicketsCount : 1; // prevent division by zero

    $progressPercent = round(($progress / $total) * 100, 1);
    $todoPercent     = round(($todo / $total) * 100, 1);
    $completePercent = round(($complete / $total) * 100, 1);
    $readyPercent = round(($ready / $total) * 100, 1);
    $progressDeg = ($progressPercent / 100) * 360;
    $todoDeg     = ($todoPercent / 100) * 360;
    $completeDeg = ($completePercent / 100) * 360;
        @endphp
<div class="col-md-6">
<div class="text-center">
    <svg viewBox="0 0 36 36" class="circular-chart" width="200" height="200">
        <circle cx="18" cy="18" r="15.9155" fill="#f0f0f0" />

        <!-- Complete -->
        <path
            stroke="#28a745"
            stroke-width="3"
            fill="none"
            stroke-dasharray="{{ $completePercent }} {{ 100 - $completePercent }}"
            d="M18 2.0845
            a 15.9155 15.9155 0 0 1 0 31.831
            a 15.9155 15.9155 0 0 1 0 -31.831"
        />

        <!-- In Progress -->
        <path
            stroke="#0dcaf0"
            stroke-width="3"
            fill="none"
            stroke-dasharray="{{ $progressPercent }} {{ 100 - $progressPercent }}"
            stroke-dashoffset="-{{ $completePercent }}"
            d="M18 2.0845
            a 15.9155 15.9155 0 0 1 0 31.831
            a 15.9155 15.9155 0 0 1 0 -31.831"
        />

        <!-- To Do -->
        <path
            stroke="#ffc107"
            stroke-width="3"
            fill="none"
            stroke-dasharray="{{ $todoPercent }} {{ 100 - $todoPercent }}"
            stroke-dashoffset="-{{ $completePercent + $progressPercent }}"
            d="M18 2.0845
            a 15.9155 15.9155 0 0 1 0 31.831
            a 15.9155 15.9155 0 0 1 0 -31.831"
        />

        <!-- Ready -->
        <path
            stroke="#6f42c1"
            stroke-width="3"
            fill="none"
            stroke-dasharray="{{ $readyPercent }} {{ 100 - $readyPercent }}"
            stroke-dashoffset="-{{ $completePercent + $progressPercent + $todoPercent }}"
            d="M18 2.0845
            a 15.9155 15.9155 0 0 1 0 31.831
            a 15.9155 15.9155 0 0 1 0 -31.831"
        />

        <text x="18" y="20.35" class="percentage" text-anchor="middle" font-size="5" fill="#333">
            Total: {{ $totalTicketsCount }}
        </text>
    </svg>

    <div class="row mt-3 justify-content-center">
        <div class="col-auto">
            <span class="badge bg-success">Complete: {{ $complete }}</span>
        </div>
        <div class="col-auto">
            <span class="badge bg-info text-dark">In Progress: {{ $progress }}</span>
        </div>
        <div class="col-auto">
            <span class="badge bg-warning text-dark">To Do: {{ $todo }}</span>
        </div>
        <div class="col-auto">
            <span class="badge bg-purple text-white" style="background-color: #6f42c1;">Ready: {{ $ready }}</span>
        </div>
    </div>
</div>       </div>       
                    <div class="col-md-6">
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
                            <label class="col-sm-4 col-form-label fw-bold">Description</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ strip_tags(str_replace('&nbsp;', ' ', $sprints->description)) }}</p>
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
                            <td>
                                <div class="dropdown">
                                  <span class="badge rounded-pill dropdown-toggle
                                    {{ $ticket->status == 'to_do' ? 'bg-primary' : '' }}
                                    {{ $ticket->status == 'in_progress' ? 'bg-warning text-dark' : '' }}
                                    {{ $ticket->status == 'ready' ? 'bg-info text-dark' : '' }}
                                    {{ $ticket->status == 'complete' ? 'bg-success' : '' }}"
                                    data-bs-toggle="dropdown" role="button" aria-expanded="false"
                                    style="cursor: pointer;"
                                  >
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                  </span>
                                  <ul class="dropdown-menu status-options" data-ticket-id="{{ $ticket->id }}">
                                    @foreach(['to_do', 'in_progress', 'ready', 'complete'] as $status)
                                      <li>
                                        <a class="dropdown-item" href="#" data-value="{{ $status }}">
                                          {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </a>
                                      </li>
                                    @endforeach
                                  </ul>
                                </div>
                              </td>                              
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  
      document.body.addEventListener('click', function (e) {
        const item = e.target.closest('.dropdown-item');
        if (!item) return;
  
        e.preventDefault();
  
        const newStatus = item.getAttribute('data-value');
        const ticketId = item.closest('.status-options')?.getAttribute('data-ticket-id');
        const badge = item.closest('.dropdown').querySelector('.badge');
  
        if (!ticketId || !newStatus || !badge) return;
  
        fetch(`/tickets/${ticketId}/update-status`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({ status: newStatus }),
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            badge.textContent = newStatus.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
  
            // Remove existing status color classes
            badge.classList.remove('bg-primary', 'bg-warning', 'text-dark', 'bg-info', 'bg-success');
  
            // Apply new one
            if (newStatus === 'to_do') {
              badge.classList.add('bg-primary');
            } else if (newStatus === 'in_progress') {
              badge.classList.add('bg-warning', 'text-dark');
            } else if (newStatus === 'ready') {
              badge.classList.add('bg-info', 'text-dark');
            } else if (newStatus === 'complete') {
              badge.classList.add('bg-success');
            }
  
          } else {
            alert('Failed to update status.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Something went wrong.');
        });
      });
    });
  </script>
  
@endsection
