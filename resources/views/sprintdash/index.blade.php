@extends('layout')
@section('title', 'Sprint Dashboard')
@section('subtitle', 'Sprint')
@section('content')
<div class="row row-design">
  <div class="col-md-2">
      <button class="btn btn-primary m-3" onClick="opensprintModal()" href="javascript:void(0)">Add Sprint</button>
  </div>
    <div class="col-md-2">
      <label for="projectFilterselectBox">Filter By Project</label>
      <select class="form-control" id="projectFilterselectBox" name="project_filter">
          <option value="" {{ request()->input('project_filter') == '' ? 'selected' : '' }}>All Projects</option>
          @foreach ($projects as $project)
              <option value="{{ $project->id }}" {{ request()->input('project_filter') == $project->id ? 'selected' : '' }}>
                  {{ $project->project_name }}
              </option>
          @endforeach
      </select>      
      @if ($errors->has('project_filter'))
          <span style="font-size: 10px;" class="text-danger">{{ $errors->first('project_filter') }}</span>
      @endif
    </div>
</div>
<div class="row ">
    <div class="sprint-section">
        <div class="sprint-header production">
          <div class="section-left">
            <div class="section-icon bg-production" style="background-color: #297bab;">A</div>
            <div class="section-title" style="color: #297bab;">Active Sprints</div>
            <div class="section-title">• {{ $totalSprintCount ?? 0 }} Sprints</div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="styled-sprint-table sprint-table">
            <thead>
              <tr style="color: #297bab;">
                <th>S.No</th>
                <th>Name</th>
                <th>Project</th>
                
                @if ($role_id != 6)
                  <th>Time Left</th>
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
                $total     = $sprint->tickets_count ?? 0;
                $completed = $sprint->completed_tickets_count ?? 0;
                $progress  = $total > 0 ? ($completed / $total) * 100 : 0;
                  $eta = $sprint->eta ? \Carbon\Carbon::parse($sprint->eta) : null;
                  $start = $sprint->start_date ? \Carbon\Carbon::parse($sprint->start_date) : null;
                  $now = \Carbon\Carbon::now('Asia/Kolkata');
                  $isPast = $eta ? $now->greaterThan($eta) : false;
                  $daysDiff = $eta ? $eta->diffInDays($now) : 0;
                  $daysLeft = $isPast ? -$daysDiff : $daysDiff;

                @endphp
                  <tr onclick="if (!event.target.closest('.actions-cell')) window.open('{{ url('/view/sprint/'.$sprint->id) }}', '_blank');" style="cursor: pointer;">
                    <td>{{ $serial++ }}</td>
                    <td>{{ $sprint->name }}</td>
                    <td>{{ $sprint->projectDetails->project_name ?? '---' }}</td>
                    @php
                    $firstRole = explode(' ', $role_id)[0] ?? 0;
                  @endphp
                @if ($firstRole != 6)
                <td>
                    @php
                        // Calculate only if eta is present
                        $daysLeft = $eta ? $now->diffInDays($eta, false) : null;
                    @endphp

                    @if (!is_null($daysLeft))
                        <p>
                            @if ($daysLeft < 0)
                                <i class="fas fa-exclamation-circle" style="color: red;" title="Task is overdue!"></i>
                                Overdue: {{ abs($daysLeft) }} days
                            @elseif ($daysLeft <= 2 && $daysLeft >= 0)
                                <i class="fas fa-exclamation-circle" style="color: red;" title="Task is approaching!"></i>
                                Days Left: {{ $daysLeft }}
                            @else
                                Days Left: {{ $daysLeft }}
                            @endif
                        </p>
                    @else
                        Ongoing
                    @endif
                </td>
                    <td>{{ $start ? $start->format('d/m/Y') : '---' }}</td>
                    <td>{{ $eta ? $eta->format('d/m/Y') : '---' }}</td>
                @endif
                <td class="actions-cell" style="text-align: center;">
                  <a href="{{ url('/view/sprint/'.$sprint->id) }}">
                      <i class="fa fa-eye fa-fw pointer"></i>
                  </a>
                  <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                    <i class="fa fa-edit fa-fw pointer"></i>
                </a>
                  @if ($firstRole != 6) 
                      
                      <i class="fa fa-trash fa-fw pointer" onclick="deleteSprint('{{ $sprint->id }}')"></i>
                  @endif
              </td>                       
                    <td>
                      <span class="badge {{ $sprint->status == 1 ? 'active' : 'inactive' }}">
                        {{ $sprint->status == 1 ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td style="text-align: center;">
                      <div class="d-flex justify-content-center status-group">
                          <div class="status-box text-white" title="To Do" style="background-color: #948979;">
                              {{ $sprint->todo_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-info text-white" title="In Progress" style="background-color: #3fa6d7 !important;">
                              {{ $sprint->in_progress_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-success text-white" title="Ready" style="background-color: #e09f3e !important;">
                              {{ $sprint->ready_tickets_count ?? 0 }}
                          </div>
                          <div class="status-box bg-info text-white" title="Deployed" style="background-color: #e76f51 !important;">
                            {{ $sprint->deployed_tickets_count ?? 0 }}
                        </div>
                          <div class="status-box bg-warning text-white" title="Complete" style="background-color: #2a9d8f !important;">
                              {{ $sprint->completed_tickets_count ?? 0 }}
                          </div>
                      </div>
                  </td>                                         
                  </tr>
              @endforeach
              @if($sprints->isEmpty())
              <tr>
                  <td colspan="8" class="text-center">No records to show</td>
              </tr>
              @endif
            </tbody>
          </table>              
        </div>     
      </div>
      @if($inactivesprints && $inactivesprints->count() > 0)
      <div class="sprint-section mt-5">
        <div class="sprint-header staged">
          <div class="section-left">
            <div class="section-icon" style="background-color: #b00000d1;">I</div>
            <div class="section-title" style="color: #b00000d1;">In-Active Sprints</div>
            <div class="section-title">• {{ $totalinSprintCount ?? 0 }} Sprints</div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="styled-sprint-table sprint-table">
            <thead>
              <tr style="color: #b00000d1;">
                <th>S.No</th> <!-- Added S.No column -->
                <th>Name</th>
                <th>Project</th>
                @if ($role_id != 6)
                    <th>Time Left</th>
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
            $eta = $sprint->eta ? \Carbon\Carbon::parse($sprint->eta) : null;
            $start = $sprint->start_date ? \Carbon\Carbon::parse($sprint->start_date) : null;
            $now = \Carbon\Carbon::now('Asia/Kolkata');
            $firstRole = explode(' ', $role_id)[0] ?? 0;
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $sprint->name }}</td>
            <td>{{ $sprint->projectDetails->project_name ?? '---' }}</td>

            @if ($firstRole != 6)
                <td>
                    @php
                        $daysLeft = $eta ? $now->diffInDays($eta, false) : null;
                    @endphp

                    @if (!is_null($daysLeft))
                        <p>
                            @if ($daysLeft < 0)
                                <i class="fas fa-exclamation-circle" style="color: red;" title="Task is overdue!"></i>
                                Overdue: {{ abs($daysLeft) }} days
                            @elseif ($daysLeft <= 2 && $daysLeft >= 0)
                                <i class="fas fa-exclamation-circle" style="color: red;" title="Task is approaching!"></i>
                                Days Left: {{ $daysLeft }}
                            @else
                                Days Left: {{ $daysLeft }}
                            @endif
                        </p>
                    @elseif ($eta == null || $start == null)
                        Ongoing
                    @else
                        ---
                    @endif
                </td>

                <td>{{ $start ? $start->format('d/m/Y') : '---' }}</td>
                <td>{{ $eta ? $eta->format('d/m/Y') : '---' }}</td>
            @endif

            <td class="actions-cell" style="text-align: center;">
                <a href="{{ url('/view/sprint/'.$sprint->id) }}">
                    <i class="fa fa-eye fa-fw pointer"></i>
                </a>
                <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                    <i class="fa fa-edit fa-fw pointer"></i>
                </a>
                @if ($firstRole != 6)
                    <i class="fa fa-trash fa-fw pointer" onclick="deleteSprint('{{ $sprint->id }}')"></i>
                @endif
            </td>

            <td>
                <span class="badge {{ $sprint->status == 1 ? 'active' : 'inactive' }}">
                    {{ $sprint->status == 1 ? 'Active' : 'Inactive' }}
                </span>
            </td>

            <td style="text-align: center;">
                <div class="d-flex justify-content-center status-group">
                    <div class="status-box text-white" title="To Do" style="background-color: #948979;">
                        {{ $sprint->todo_tickets_count ?? 0 }}
                    </div>
                    <div class="status-box bg-info text-white" title="In Progress" style="background-color: #3fa6d7 !important;">
                        {{ $sprint->in_progress_tickets_count ?? 0 }}
                    </div>
                    <div class="status-box bg-success text-white" title="Ready" style="background-color: #e09f3e !important;">
                        {{ $sprint->ready_tickets_count ?? 0 }}
                    </div>
                    <div class="status-box bg-info text-white" title="Deployed" style="background-color: #e76f51 !important;">
                        {{ $sprint->deployed_tickets_count ?? 0 }}
                    </div>
                    <div class="status-box bg-warning text-white" title="Complete" style="background-color: #2a9d8f !important;">
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
          <div class="section-icon" style="background-color: #3f996b">C</div>
          <div class="section-title" style="color: #3f996b">Completed Sprints</div>
          <div class="section-title">• {{ count($completedsprints) }} Sprint{{ count($completedsprints) > 1 ? 's' : '' }}</div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="styled-sprint-table sprint-table">
          <thead>
            <tr style="color: #3f996b">
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
                  <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                    <i class="fa fa-edit fa-fw pointer"></i>
                  </a>
                  @if ($firstRole != 6)
                  
                  <i class="fa fa-trash fa-fw pointer"
                    onclick="deleteSprint('{{ $sprint->id }}')"></i>
                    @endif
                </td>
                <td>
                  <span class="badge 
                    {{ $sprint->status == 1 ? 'active' : ($sprint->status == 2 ? 'completed' : 'inactive') }}">
                    {{ $sprint->status == 1 ? 'Active' : ($sprint->status == 2 ? 'Completed' : 'Inactive') }}
                  </span>
                </td>
                <td style="text-align: center;">
                  <div class="d-flex justify-content-center status-group">
                      <div class="status-box text-white" title="To Do" style="background-color: #948979;">
                          {{ $sprint->todo_tickets_count ?? 0 }}
                      </div>
                      <div class="status-box bg-info text-white" title="In Progress" style="background-color: #3fa6d7 !important;">
                          {{ $sprint->in_progress_tickets_count ?? 0 }}
                      </div>
                      <div class="status-box bg-success text-white" title="Ready" style="background-color: #e09f3e !important;">
                          {{ $sprint->ready_tickets_count ?? 0 }}
                      </div>
                      <div class="status-box bg-info text-white" title="Deployed" style="background-color: #e76f51 !important;">
                        {{ $sprint->deployed_tickets_count ?? 0 }}
                    </div>
                      <div class="status-box bg-warning text-white" title="Complete" style="background-color: #2a9d8f !important;">
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
</div>
@endif
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
                                <label for="etaDateTime" class="col-sm-3 col-form-label">End Date</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date">
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
                            
                            <!-- <div class="row mb-3">
                                <label for="client" class="col-sm-3 col-form-label required">Client Name</label>
                                <div class="col-sm-9">
                                    <select name="client" class="form-select form-control" id="client">
                                      @if ($role_id != 6)
                                        <option value="" disabled selected>Select Client</option>
                                        @endif
                                        @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>  
                                </div>
                            </div> -->
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select form-control" id="status" name="status">
                                        <option value="" disabled selected>Select Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                        <option value="2">Completed</option>
                                    </select>
                                </div>
                            </div>                            
                            <div class="row mb-3">
                              <label class="col-sm-3 col-form-label required">Description</label>
                              <div class="col-sm-9">
                                  <!-- Quill Toolbar -->
                                  <div id="toolbar-container">
                                      <span class="ql-formats">
                                          <select class="ql-font"></select>
                                          <select class="ql-size"></select>
                                      </span>
                                      <span class="ql-formats">
                                          <button class="ql-bold"></button>
                                          <button class="ql-italic"></button>
                                          <button class="ql-underline"></button>
                                          <button class="ql-strike"></button>
                                      </span>
                                      <span class="ql-formats">
                                          <select class="ql-color"></select>
                                          <select class="ql-background"></select>
                                      </span>
                                      <span class="ql-formats">
                                          <button class="ql-script" value="sub"></button>
                                          <button class="ql-script" value="super"></button>
                                      </span>
                                      <span class="ql-formats">
                                          <button class="ql-header" value="1"></button>
                                          <button class="ql-header" value="2"></button>
                                          <button class="ql-blockquote"></button>
                                          <button class="ql-code-block"></button>
                                      </span>
                                      <span class="ql-formats">
                                          <button class="ql-list" value="ordered"></button>
                                          <button class="ql-list" value="bullet"></button>
                                          <button class="ql-indent" value="-1"></button>
                                          <button class="ql-indent" value="+1"></button>
                                      </span>
                                      <span class="ql-formats">
                                          <button class="ql-direction" value="rtl"></button>
                                          <select class="ql-align"></select>
                                      </span>
                                      <span class="ql-formats">
                                          <button class="ql-link"></button>
                                          <button class="ql-image"></button>
                                          <button class="ql-video"></button>
                                          <button class="ql-formula"></button>
                                      </span>
                                      <span class="ql-formats">
                                          <button class="ql-clean"></button>
                                      </span>
                                  </div>
                          
                                  <div id="editor" name="description" style="height: 300px;"></div>
                                  <input type="hidden" name="description" id="description_input">
                                  
                                  @if ($errors->has('description'))
                                      <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                                  @endif
                              </div>
                          </div>                          
                          <div class="row mb-3">
                            <label for="add_document" class="col-sm-3 col-form-label">Attach Documents</label>
                            <div class="col-sm-9">
                                <input type="file" class="form-control" name="add_document[]" id="add_document" multiple>
                                @if ($errors->has('add_document'))
                                <span style="font-size: 12px;" class="text-danger">{{ $errors->first('add_document') }}</span>
                            @endif
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
   
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading.......">
</div>
<script>
    document.querySelectorAll('tr.clickable-row').forEach(row => {
  row.addEventListener('click', (event) => {
    if (!event.target.closest('.actions-cell')) {
      const sprintUrl = row.dataset.url; 
      window.open(sprintUrl, '_blank');
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
        $('#description_input').val(quill.root.innerHTML);
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
    $(".alert-danger").text(xhr.responseJSON.message).fadeIn();

setTimeout(() => {
    $(".alert-danger").fadeOut();
}, 4000);

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
<script>
  document.getElementById('projectFilterselectBox').addEventListener('change', function() {
      var projectId = this.value;
      var url = new URL(window.location.href);
      url.searchParams.set('project_filter', projectId);
      window.location.href = url.href;
  });
  </script> 
@endsection
@section('js_scripts')