@extends('layout')
@section('title', 'Sprint Dashboard')
@section('subtitle', 'Sprint')
@section('content')
@if ($role_id != 6)
<div class="col-md-2">
    <button class="btn btn-primary m-3" onClick="opensprintModal()" href="javascript:void(0)">Add Sprint</button>
</div>
@endif
<div class="sprint-section">
    <div class="sprint-header production">
      <div class="section-left">
        <div class="section-icon bg-production">A</div>
        <div class="section-title" style="color: #2c2c2e;">Active Sprints</div>
        <div class="section-title">{{ $totalSprintCount ?? 0 }} Sprints</div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="styled-sprint-table sprint-table">
        <thead>
          <tr style="color: #2c2c2e;">
            <th>S.No</th>
            <th>Name</th>
            <th>Project</th>
            <th>Time Left</th>
            @if ($role_id != 6)
                <th>Started At</th>
                <th>End Date (d/m/y)</th>
            @endif
            <th>Actions</th>
            <th>Status</th>
            <th>Workflow Stage</th>
          </tr>
        </thead>
        <tbody>
          @php $serial = 1; @endphp
          @foreach ($sprints as $sprint)
            @php
              $eta       = \Carbon\Carbon::parse($sprint->eta);
              $start     = $sprint->start_date ? \Carbon\Carbon::parse($sprint->start_date) : null;
              $now       = \Carbon\Carbon::now('Asia/Kolkata');
              $daysLeft  = $eta->diffInDays($now);
              $total     = $sprint->tickets_count ?? 0;
              $completed = $sprint->completed_tickets_count ?? 0;
              $progress  = $total > 0 ? ($completed / $total) * 100 : 0;
            @endphp
      
            @if ($total === 0 || $completed < $total)
              <tr>
                <td>{{ $serial++ }}</td>
                <td>{{ $sprint->name }}</td>
                <td>{{ $sprint->projectDetails->project_name ?? '---' }}</td>
      
                <td style="text-align: center;">
                  @if($daysLeft <= 2 && $daysLeft >= 0)
                    <i class="fas fa-exclamation-circle text-danger" title="Sprint is approaching its end!"></i>
                  @endif
                  Days Left: {{ $daysLeft >= 0 ? $daysLeft : '0' }}
                </td>
                @php
                $firstRole = explode(' ', $role_id)[0] ?? 0;
               @endphp
            @if ($firstRole != 6)
                <td>{{ $start ? $start->format('d/m/Y') : '---' }}</td>
                <td>{{ $eta->format('d/m/Y') }}</td>
            @endif
            <td class="actions-cell" style="text-align: center;">
              <a href="{{ url('/view/sprint/'.$sprint->id) }}">
                  <i class="fa fa-eye fa-fw pointer"></i>
              </a>
          
              @if ($firstRole != 6) 
                  <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                      <i class="fa fa-edit fa-fw pointer"></i>
                  </a>
                  <i class="fa fa-trash fa-fw pointer text-danger" onclick="deleteSprint('{{ $sprint->id }}')"></i>
              @endif
          </td>                       
                <td>
                  <span class="badge {{ $sprint->status == 1 ? 'bg-success' : 'bg-secondary' }}">
                    {{ $sprint->status == 1 ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td style="text-align: center;">
                  <div class="d-flex justify-content-center status-group">
                      <div class="status-box text-white" title="To Do" style="background-color: #6f42c1;">
                          {{ $sprint->todo_tickets_count ?? 0 }}
                      </div>
                      <div class="status-box bg-info text-dark" title="In Progress">
                          {{ $sprint->in_progress_tickets_count ?? 0 }}
                      </div>
                      <div class="status-box bg-success text-white" title="Ready">
                          {{ $sprint->ready_tickets_count ?? 0 }}
                      </div>
                      <div class="status-box bg-info text-white" title="Deployed">
                        {{ $sprint->deployed_tickets_count ?? 0 }}
                    </div>
                      <div class="status-box bg-warning text-dark" title="Complete">
                          {{ $sprint->completed_tickets_count ?? 0 }}
                      </div>
                  </div>
              </td>                                         
              </tr>
            @endif
          @endforeach
        </tbody>
      </table>             
    </div>     
  </div>
  @if($inactivesprints && $inactivesprints->count() > 0)
  <div class="sprint-section mt-5">
    <div class="sprint-header staged">
      <div class="section-left">
        <div class="section-icon" style="background-color: #e91e63;">I</div>
        <div class="section-title" style="color: #e91e63;">In-Active Sprints</div>
        <div class="section-title">{{ $totalinSprintCount ?? 0 }} Sprints</div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="styled-sprint-table sprint-table">
        <thead>
          <tr style="color: #e91e63;">
            <th>S.No</th> <!-- Added S.No column -->
            <th>Name</th>
            <th>Project</th>
            <th>Time Left</th>
            @if ($role_id != 6)
                <th>Started At</th>
                <th>End Date (d/m/y)</th>
            @endif
            <th>Actions</th>
            <th>Status</th>
            <th>Workflow Stage</th>
          </tr>
        </thead>
        <tbody>
          @foreach($inactivesprints as $sprint)
            @php
              $eta      = \Carbon\Carbon::parse($sprint->eta);
              $start    = $sprint->start_date ? \Carbon\Carbon::parse($sprint->start_date) : null;
              $now      = \Carbon\Carbon::now('Asia/Kolkata');
              $daysLeft = $eta->diffInDays($now);
              $total = $sprint->tickets_count ?? 0;
              $completed = $sprint->completed_tickets_count ?? 0;
              $progress = $total > 0 ? ($completed / $total) * 100 : 0;
            @endphp
            <tr>
              <td>{{ $loop->iteration }}</td> <!-- S.No -->
              <td>{{ $sprint->name }}</td>
              <td>{{ $sprint->projectDetails->project_name ?? '---' }}</td>
              <td style="text-align: center;">
                @if($daysLeft <= 2 && $daysLeft >= 0)
                  <i class="fas fa-exclamation-circle text-danger" title="Sprint is approaching its end!"></i>
                @endif
                Days Left: {{ $daysLeft >= 0 ? $daysLeft : '0' }}
              </td>
              @php
              $firstRole = explode(' ', $role_id)[0] ?? 0;
             @endphp
          @if ($firstRole != 6)
              <td>{{ $start ? $start->format('d/m/Y') : '---' }}</td>
              <td>{{ $eta->format('d/m/Y') }}</td>
          @endif
              <td class="actions-cell" style="text-align: center;">
                <a href="{{ url('/view/sprint/'.$sprint->id) }}">
                  <i class="fa fa-eye fa-fw pointer"></i>
                </a>
                @if ($firstRole != 6)
                <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                  <i class="fa fa-edit fa-fw pointer"></i>
                </a>
                <i class="fa fa-trash fa-fw pointer text-danger" onclick="deleteSprint('{{ $sprint->id }}')"></i>
                @endif
              </td>
              <td>
                <span class="badge {{ $sprint->status == 1 ? 'bg-success' : 'bg-secondary' }}">
                  {{ $sprint->status == 1 ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td style="text-align: center;">
                <div class="d-flex justify-content-center status-group">
                    <div class="status-box text-white" title="To Do" style="background-color: #6f42c1;">
                        {{ $sprint->todo_tickets_count ?? 0 }}
                    </div>
                    <div class="status-box bg-info text-dark" title="In Progress">
                        {{ $sprint->in_progress_tickets_count ?? 0 }}
                    </div>
                    <div class="status-box bg-success text-white" title="Ready">
                        {{ $sprint->ready_tickets_count ?? 0 }}
                    </div>
                    <div class="status-box bg-warning text-dark" title="Complete">
                        {{ $sprint->completed_tickets_count ?? 0 }}
                    </div>
                </div>
            </td>
            </tr>
          @endforeach
        </tbody>
      </table>      
    </div>     
  </div>
  @endif
  @if(isset($completedsprints) && count($completedsprints) > 0)
<div class="sprint-section mt-5">
  <div class="sprint-header qa">
    <div class="section-left">
      <div class="section-icon" style="background-color: #4caf50;">C</div>
      <div class="section-title" style="color: #4caf50;">Completed Sprints</div>
      <div class="section-title">• {{ count($completedsprints) }} Sprint{{ count($completedsprints) > 1 ? 's' : '' }}</div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="styled-sprint-table sprint-table">
      <thead>
        <tr style="color: #4caf50;">
          <th>S.No</th>
          <th>Name</th>
          <th>Project</th>
          @if ($role_id != 6)
                <th>Started At</th>
                <th>End Date (d/m/y)</th>
            @endif
          <th>Actions</th>
          <th>Status</th>
          <th>Workflow Stage</th>
        </tr>
      </thead>
      <tbody>
        @foreach($completedsprints as $sprint)
          @php
            $eta      = \Carbon\Carbon::parse($sprint->eta);
            $start    = $sprint->start_date ? \Carbon\Carbon::parse($sprint->start_date) : null;
            $now      = \Carbon\Carbon::now('Asia/Kolkata');
            $daysLeft = $eta->diffInDays($now);
            $total    = $sprint->tickets_count ?? 0;
            $completed = $sprint->completed_tickets_count ?? 0;
            $progress = $total > 0 ? ($completed / $total) * 100 : 0;
          @endphp
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $sprint->name }}</td>
            <td>{{ $sprint->projectDetails->project_name ?? '–' }}</td>
            @php
              $firstRole = explode(' ', $role_id)[0] ?? 0;
             @endphp
          @if ($firstRole != 6)
              <td>{{ $start ? $start->format('d/m/Y') : '---' }}</td>
              <td>{{ $eta->format('d/m/Y') }}</td>
          @endif
            <td class="actions-cell" style="text-align: center;">
              <a href="{{ url('/view/sprint/'.$sprint->id) }}">
                <i class="fa fa-eye fa-fw pointer"></i>
              </a>
              @if ($firstRole != 6)
              <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                <i class="fa fa-edit fa-fw pointer"></i>
              </a>
              <i class="fa fa-trash fa-fw pointer text-danger"
                 onclick="deleteSprint('{{ $sprint->id }}')"></i>
                 @endif
            </td>
            <td>
              <span class="badge {{ $sprint->status == 1 ? 'bg-success' : 'bg-secondary' }}">
                {{ $sprint->status == 1 ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td style="text-align: center;">
              <div class="d-flex justify-content-center status-group">
                  <div class="status-box text-white" title="To Do" style="background-color: #6f42c1;">
                      {{ $sprint->todo_tickets_count ?? 0 }}
                  </div>
                  <div class="status-box bg-info text-dark" title="In Progress">
                      {{ $sprint->in_progress_tickets_count ?? 0 }}
                  </div>
                  <div class="status-box bg-success text-white" title="Ready">
                      {{ $sprint->ready_tickets_count ?? 0 }}
                  </div>
                  <div class="status-box bg-warning text-dark" title="Complete">
                      {{ $sprint->completed_tickets_count ?? 0 }}
                  </div>
              </div>
          </td>
          </tr>
        @endforeach
      </tbody>
    </table>     
  </div> 
</div>
@endif

<div class="col-lg-12">
    <div class="card">
        <div class="modal fade" id="addSprints" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Sprint</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addTicketsForm" enctype="multipart/form-data">
                     @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label required">Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name" id="title">
                                </div>
                            </div> 
                            
                            <div class="row mb-3">
                                <label for="startDateTime" class="col-sm-3 col-form-label required">Start Date</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="etaDateTime" class="col-sm-3 col-form-label required">End Date</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="eta" name="eta">
                                    <input type="hidden" class="form-control" name="sprint_id" id="sprint_id" value="{{ $sprint->id ?? '' }}">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <label for="project" class="col-sm-3 col-form-label required">Projects</label>
                                <div class="col-sm-9">
                                    <select name="project" class="form-select form-control" id="project">
                                        <option value="" disabled selected>Select your project</option>
                                        @foreach ($projects as $data)
                                            <option value="{{$data->id}}">
                                                {{$data->project_name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>  
                            
                            <div class="row mb-3">
                                <label for="client" class="col-sm-3 col-form-label required">Client Name</label>
                                <div class="col-sm-9">
                                    <select name="client" class="form-select form-control" id="client">
                                        <option value="" disabled selected>Select Client</option>
                                        @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>  
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select form-control" id="status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>                            
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                                <div class="col-sm-9">
                                    <textarea name="description" class="form-control" id="tinymce_textarea"></textarea>
                                </div>
                            </div>
                                                     
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" href="javascript:void(0)">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!---end Add modal-->
    </div>
</div>
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading.......">
</div>
<script>
     document.addEventListener('DOMContentLoaded', function () {
        const startDateInput = document.getElementById('start_date');
        const etaInput = document.getElementById('eta');

        startDateInput.addEventListener('change', function () {
            const startDate = new Date(this.value);
            if (!isNaN(startDate.getTime())) {
                const maxDate = new Date(startDate);
                maxDate.setDate(maxDate.getDate() + 14); 

                const formattedMax = maxDate.toISOString().slice(0, 16);
                const formattedStart = startDate.toISOString().slice(0, 16);

                etaInput.setAttribute('min', formattedStart);
                etaInput.setAttribute('max', formattedMax);
            }
        });
    });
function opensprintModal() {
                document.getElementById("addTicketsForm").reset();
                $('#addSprints').modal('show');
            }

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
        });
        

        $(document).ready(function() {
    $("#addTicketsForm").on("submit", function(e) {
        e.preventDefault(); 
        var formData = new FormData(this); 

        $.ajax({
            url: "{{ url('/add/sprint') }}",
            type: "POST",
            data: formData,
            contentType: false,  
            processData: false,  
            success: function(response) {
                if (response.status == 'success') {
                    $('#addSprints').modal('hide'); 
                    $('#loader').show(); 
                    setTimeout(function() {
                        location.reload();  
                    }, 1000);  
                } else {
                    $(".alert-danger").text(response.message).show();  
                }
            },
            error: function(xhr, status, error) {
                $(".alert-danger").text("An error occurred. Please try again.").show();  
            }
        });
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

    function deleteSprint(id) {
                $('#sprint_id').val(id);
                if (confirm("Are you sure ?") == true) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('/delete/sprint') }}",
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
</script>
@endsection
@section('js_scripts')