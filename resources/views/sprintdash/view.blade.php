@extends('layout')
@section('title', 'Sprint Detail')
@section('subtitle', 'Edit Sprint')
@section('content')
<div class="row">
    <div class="row mb-2">
    <div class="col-md-2">
        <input type="hidden" name="project_id" value="{{ $sprint->project }}">
        <a href="{{ route('tickets.create', ['sprint_id' => $sprint->id]) }}" class="btn btn-primary w-100" style="background: #4154f1; margin-top: 10px;">
            Add Ticket
        </a>
    </div>
    <div class="col-md-2">
        <a href="{{ route('sprint.edit', $sprint->id) }}" class="btn btn-primary w-100" style="background: #4154f1;margin-top: 10px;">
            <i class="fa-solid fa-pen-to-square"></i> Edit Sprint
        </a>
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
                <div class="row">
                    <div class="button-design2">
                        <button id="resetChartBtn" class="btn btn-light" title="Reset Chart">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>                        
                    </div>
                    @php
    $total = $totalTicketsCount > 0 ? $totalTicketsCount : 1; 

    $progressPercent = round(($progress / $total) * 100, 1);
    $todoPercent     = round(($todo / $total) * 100, 1);
    $completePercent = round(($complete / $total) * 100, 1);
    $readyPercent = round(($ready / $total) * 100, 1);
    $deployedPercent = round(($deployed / $total) * 100, 1);
    $progressDeg = ($progressPercent / 100) * 360;
    $todoDeg     = ($todoPercent / 100) * 360;
    $completeDeg = ($completePercent / 100) * 360;
        @endphp
<div class="col-md-8">
<div class="text-center">
    <div id="pieChart" style="min-height: 300px;"></div>
    <dfiv class="row mt-0 justify-content-center gap-2">
        <div class="col-auto">
            <span class="badge bg-purple text-white status-filter" style="background-color: #948979;" data-status="to_do">To Do: {{ $todo }}</span>
        </div>
        <div class="col-auto">
            <span class="badge text-white status-filter" data-status="in_progress" style="background-color: #3fa6d7;">In Progress: {{ $progress }}</span>
        </div>
        <div class="col-auto">
            <span class="badge text-white status-filter" data-status="ready" style="background-color: #e09f3e;">Ready: {{ $ready }}</span>
        </div>
        <div class="col-auto">
            <span class="badge text-white status-filter" data-status="deployed" style="background-color: #e76f51;">Deployed: {{ $deployed }}</span>
        </div>
        <div class="col-auto">
            <span class="badge status-filter" data-status="complete" style="background-color: #2a9d8f;">Complete: {{ $complete }}</span>
        </div>
    </dfiv>
</div>    
        @php
            $hasDocument = false;
            foreach ($ProjectDocuments as $doc) {
                if (!empty($doc->document)) {
                    $hasDocument = true;
                    break;
                }
            }
        @endphp

        @if ($hasDocument)
            <div class="row mb-2 mt-5">
                <div class="col-auto">
                    <label class="col-form-label fw-bold mb-0">Uploaded Documents:</label>
                </div>
                    <div class="col d-flex align-items-center flex-wrap gap-2" id="Projectsdata">
                        @if ($ProjectDocuments->isEmpty())
                        No Uploaded Document Found
                        @else  
                            @foreach ($ProjectDocuments as $data)
                                @if (!empty($data->document))
                                    <button type="button" class="btn btn-outline-primary btn-sm mb-2">
                                        @php
                                            $extension = pathinfo($data->document, PATHINFO_EXTENSION);
                                            $iconClass = '';
                            
                                            switch ($extension) {
                                                case 'pdf':
                                                    $iconClass = 'bi-file-earmark-pdf';
                                                    break;
                                                case 'doc':
                                                case 'docx':
                                                    $iconClass = 'bi-file-earmark-word';
                                                    break;
                                                case 'xls':
                                                case 'xlsx':
                                                    $iconClass = 'bi-file-earmark-excel';
                                                    break;
                                                case 'jpg':
                                                case 'jpeg':
                                                case 'png':
                                                    $iconClass = 'bi-file-earmark-image';
                                                    break;
                                                default:
                                                    $iconClass = 'bi-file-earmark';
                                                    break;
                                            }
                                        @endphp
                                        <i class="bi {{ $iconClass }} mr-1" onclick="window.open('{{ asset('assets/img/' . $data->document) }}', '_blank')"></i>
                                        <i class="bi bi-x pointer ticketfile text-danger" onclick="deleteSprintFile('{{ $data->id }}')"></i>
                                    </button>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>      
        @endif
        </div>    
            
                    <div class="col-md-4">
                        <div class="row mb-2" style="align-items:center">
                            <label class="col-sm-4 col-form-label fw-bold">Sprint Name</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $sprint->name ?? '----' }}</p>
                            </div>
                        </div>

                        <div class="row mb-2" style="align-items:center">
                            <label class="col-sm-4 col-form-label fw-bold">Project</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $sprints->project_name ?? '----' }}</p>
                            </div>
                        </div>

                        <div class="row mb-2" style="align-items:center">
                            <label class="col-sm-4 col-form-label fw-bold">Description</label>
                            <div class="col-sm-8">
                                <p class="mb-1" style="word-break: break-word; overflow-wrap: break-word;">
                                    {{ $sprint->description ? strip_tags(str_replace('&nbsp;', ' ', $sprint->description)) : '----' }}
                                </p>
                            </div>
                        </div>

                        <div class="row mb-2" style="align-items:center">
                            <label class="col-sm-4 col-form-label fw-bold">Client</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $clients->client_name ?? '----' }}</p>
                            </div>
                        </div>
                        @if ($role_id != 6)
                        <div class="row mb-2" style="align-items:center">
                            <label class="col-sm-4 col-form-label fw-bold">Start Date</label>
                            <div class="col-sm-8">
                                <p class="mb-1">
                                    {{ $sprint->start_date ? \Carbon\Carbon::parse($sprint->start_date)->format('M d, Y h:i A') : '----' }}
                                </p>
                            </div>
                        </div>

                        <div class="row mb-2" style="align-items:center">
                            <label class="col-sm-4 col-form-label fw-bold">End Date</label>
                            <div class="col-sm-8">
                                <p class="mb-1">
                                    {{ $sprint->eta ? \Carbon\Carbon::parse($sprint->eta)->format('M d, Y h:i A') : '----' }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div> 
                </div> 
            </div>
        </div>
    </div>
</div>
    <div class="col-md-12">
        <div class="card recent-sales overflow-auto ">
        <div class="card-body mt-2">
            <table class="table table-borderless datatable" id="allsprint">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th>                   
                        <th scope="col">ETA (d/m/y)</th>
                        <th scope="col">Ticket Number</th>
                        <th scope="col">Status</th>
                        <th scope="col">Assigned To</th>
                        <th scope="col">Actions</th>
                        
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr class="ticket-row" data-status="{{ $ticket->status }}" onclick="if (!event.target.closest('.actions-cell')) { window.open('{{ url('/view/ticket/'.$ticket->id) }}', '_blank'); }" style="cursor: pointer;">
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
                            <td>
                                {{ $ticket->eta ? \Carbon\Carbon::parse($ticket->eta)->format('d/m/Y') : '---' }}
                            </td>  
                            <td>{{ $ticket->id }}</td>                     
                            <td class="actions-cell">
                                @php
                                    $statusColors = [
                                        'to_do' => '#948979',
                                        'in_progress' => '#3fa6d7',
                                        'ready' => '#e09f3e',
                                        'deployed' => '#e76f51',
                                        'complete' => '#2a9d8f',
                                    ];
                                    $color = $statusColors[$ticket->status] ?? ''; 
                                @endphp
                            
                                @if(Auth::user()->role_id != 6)
                                    <div class="dropdown">
                                        <span class="badge rounded-pill dropdown-toggle"
                                              data-bs-toggle="dropdown"
                                              role="button"
                                              aria-expanded="false"
                                              style="cursor: pointer; background-color: {{ $color }};">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                        <ul class="dropdown-menu status-options" data-ticket-id="{{ $ticket->id }}">
                                            @foreach(['to_do', 'in_progress', 'ready', 'deployed', 'complete'] as $status)
                                                <li>
                                                    <a class="dropdown-item" href="#" data-value="{{ $status }}">
                                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <span class="badge rounded-pill"
                                          style="background-color: {{ $color }};">
                                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                    </span>
                                @endif
                            </td>                                                          
                                <td>
                                    @foreach($assignedUsers->where('ticket_id', $ticket->id) as $user)
                                    {{ $user->assigned_user_name }} ({{ $user->designation }})
                                    @endforeach
                                </td>
                            <td class="actions-cell">
                                @php
                                $firstRole = explode(' ', $role_id)[0] ?? 0;
                                @endphp
                                <a href="{{ url('/view/ticket/'.$ticket->id) }}"  target="_blank">
                                    <i style="color:#4154f1;" class="fa fa-eye fa-fw pointer"></i>
                                </a>
                                <a href="{{ url('/edit/ticket/'.$ticket->id) }}?source=sprint">
                                    <i style="color:#4154f1;" class="fa fa-edit fa-fw pointer"></i>
                                </a>
                                @if ($firstRole != 6)
                              
                                <i style="color:#4154f1;" onClick="deleteTickets('{{ $ticket->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                @endif
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
                $('#resetChartBtn').on('click', function() {
                    location.reload();
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
            location.reload(true);
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
            }else if (newStatus === 'deployed') {
              badge.classList.add('bg-warning');
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
  <script>
    document.addEventListener("DOMContentLoaded", () => {
        const chart = echarts.init(document.querySelector("#pieChart"));
    
        const chartOptions = {
            title: {
                text: 'Ticket Status Overview',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                orient: 'vertical',
                left: 'left'
            },
            series: [{
                name: 'Tickets',
                type: 'pie',
                radius: '50%',
                data: [
                    { value: {{ $todo }}, name: 'To Do', itemStyle: { color: '#948979' }, status: 'to_do' },
                    { value: {{ $progress }}, name: 'In Progress', itemStyle: { color: '#3fa6d7' }, status: 'in_progress' },
                    { value: {{ $ready }}, name: 'Ready', itemStyle: { color: '#e09f3e' }, status: 'ready' },
                    { value: {{ $deployed }}, name: 'Deployed', itemStyle: { color: '#e76f51' }, status: 'deployed' },
                    { value: {{ $complete }}, name: 'Complete', itemStyle: { color: '#2a9d8f' }, status: 'complete' }
                ],
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }]
        };
    
        chart.setOption(chartOptions);
        chart.on('click', function (params) {
            const clickedStatus = params.data.status;
            filterTableByStatus(clickedStatus);
        });
        document.querySelectorAll('.status-filter').forEach(badge => {
            badge.addEventListener('click', function () {
                const selectedStatus = this.getAttribute('data-status');
                filterTableByStatus(selectedStatus);
            });
        });
        function filterTableByStatus(status) {
            document.querySelectorAll('.ticket-row').forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                row.style.display = (rowStatus === status) ? '' : 'none';
            });
        }
    });
    </script>    
    <script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.status-filter').forEach(badge => {
                badge.addEventListener('click', function () {
                    const selectedStatus = this.getAttribute('data-status');
    
                    // Hide all rows first
                    document.querySelectorAll('.ticket-row').forEach(row => {
                        const status = row.getAttribute('data-status');
                        row.style.display = (status === selectedStatus) ? '' : 'none';
                    });
                });
            });
        });
    </script>  
    <script>
        function deleteSprintFile(id) {
            var sprintId = {{ $sprint->id }};
         if (confirm("Are you sure ?") == true) {
             $.ajax({
                 type: 'DELETE',
                 url: "{{ url('/delete/sprint/file') }}",
                 data: {
                     id: id,
                     sprintId: sprintId,
                     _token: '{{ csrf_token() }}'
                 },
                 success: function (data) {
                     location.reload();
                 }
             });
         }
     }
         </script>   
@endsection
