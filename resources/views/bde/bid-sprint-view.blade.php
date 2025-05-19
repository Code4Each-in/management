@extends('layout')
@section('title', 'BDE Sprint Details')
@section('subtitle', 'Show')
@section('content')

<div class="row row-design mb-4 mt-2">
  <div class="col-md-4 d-flex justify-content-start gap-3">
    <button class="btn btn-primary" onclick="addtask()">Add Record</button>
     <a href="{{ url('bid-sprints') }}" class="btn btn-primary">Back to Sprint</a>
  </div>
</div>
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
                        $total = $total > 0 ? $total : 1; 
                        $appliedPercent = round(($applied / $total) * 100, 1);
                        $viewedPercent  = round(($viewed / $total) * 100, 1);
                        $repliedPercent = round(($replied / $total) * 100, 1);
                        $successPercent = round(($success / $total) * 100, 1);
                    @endphp

                    <div class="col-md-8">
                        <div class="text-center">
                            <div id="pieChart" style="min-height: 300px;"></div>
                            <div class="row mt-3 justify-content-center gap-2">
                                <div class="col-auto">
                                    <span class="badge bg-info text-white">Applied: {{ $applied }}</span>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-primary text-white">Viewed: {{ $viewed }}</span>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-warning text-dark">Replied: {{ $replied }}</span>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-success text-white">Success: {{ $success }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row mb-2">
                            <label class="col-sm-4 fw-bold">Sprint Name</label>
                            <div class="col-sm-8"><p class="mb-1">{{ $bdeSprint->name ?? '----' }}</p></div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-sm-4 fw-bold">Status</label>
                            <div class="col-sm-8">
                                <p class="mb-1">
                                    @php
                                        $statusText = match($bdeSprint->status) {
                                            1 => 'Active',
                                            0 => 'Inactive',
                                            2 => 'Completed',
                                            default => '----',
                                        };
                                    @endphp
                                    {{ $statusText }}
                                </p>
                            </div>
                        </div>


                        <div class="row mb-2">
                            <label class="col-sm-4 fw-bold">Start Date</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $bdeSprint->start_date ? \Carbon\Carbon::parse($bdeSprint->start_date)->format('M d, Y') : '----' }}</p>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-sm-4 fw-bold">End Date</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ $bdeSprint->end_date ? \Carbon\Carbon::parse($bdeSprint->end_date)->format('M d, Y') : '----' }}</p>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-sm-4 fw-bold">Description</label>
                            <div class="col-sm-8">
                                <p class="mb-1">{{ strip_tags($bdeSprint->description ?? '----') }}</p>
                            </div>
                        </div>
                    </div> <!-- col-md-4 -->
                </div> <!-- row -->
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body pb-4">
      <table class="styled-sprint-table sprint-table custom">
    <thead>
        <tr style="color: #297bab;">
            <th>S.No</th>
            <th>Job Title</th>
            <th>Job Link</th>
            <th>Source</th>
            <th>Profile</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @php
            $rowsCount = $tasks->count();
            $minRows = 5;
        @endphp

        @foreach ($tasks as $index => $task)
            <tr onclick="window.open('{{ url('/view/task/'.$task->id) }}', '_blank')" style="cursor: pointer;">
                <td>{{ $index + 1 }}</td>
                <td>{{ $task->job_title }}</td>
                <td>
                    @if($task->job_link)
                        <a href="{{ $task->job_link }}" target="_blank" rel="noopener noreferrer">Link</a>
                    @else
                        ---
                    @endif
                </td>
                <td>{{ $task->source ?? '---' }}</td>
                <td>{{ $task->profile ?? '---' }}</td>
               <td>
                @php
                    $statusClasses = [
                        'applied' => 'bg-primary',
                        'viewed' => 'bg-info',
                        'replied' => 'bg-warning',
                        'success' => 'bg-success',
                    ];
                    $badgeClass = $statusClasses[$task->status] ?? 'bg-secondary';
                @endphp

                <div class="dropdown">
                    <span class="badge rounded-pill dropdown-toggle {{ $badgeClass }}"
                        data-bs-toggle="dropdown"
                        role="button"
                        aria-expanded="false"
                        style="cursor: pointer;">
                        {{ ucfirst($task->status) }}
                    </span>
                    <ul class="dropdown-menu status-options" data-task-id="{{ $task->id }}">
                        @foreach(['applied', 'viewed', 'replied', 'success'] as $status)
                            <li>
                                <a class="dropdown-item" href="#" data-value="{{ $status }}">
                                    {{ ucfirst($status) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </td>
                <td>
                    <a href="{{ route('tasks.show', $task->id) }}" title="View Task">
                        <i class="fa fa-eye fa-fw pointer text-primary"></i>
                    </a>
                    <a href="{{ route('tasks.edit', $task->id) }}" title="Edit">
                        <i class="fa fa-edit fa-fw pointer"></i>
                    </a>
                    <a href="#" onclick="deleteTask({{ $task->id }})" title="Delete">
                        <i class="fa fa-trash fa-fw pointer text-danger"></i>
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
    </div>
</div>
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 630px;">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskLabel">Add Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTaskForm">
                @csrf
                
                <div class="modal-body">
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label required">Job Title</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control text-dark" name="job_title">
                            <input type="hidden" name="bdesprint_id" value="{{ $bdeSprint->id }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Job Link</label>
                        <div class="col-sm-9">
                            <input type="url" class="form-control text-dark" name="job_link">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Source</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control text-dark" name="source">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Profile</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control text-dark" name="profile">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="status">
                                <option value="applied" selected>Applied</option>
                                <option value="viewed">Viewed</option>
                                <option value="replied">Replied</option>
                                <option value="success">Success</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addTaskModal = document.getElementById('addTaskModal');
    const addTaskForm = document.getElementById('addTaskForm');

    addTaskModal.addEventListener('hidden.bs.modal', function () {
        addTaskForm.reset(); // Clear all inputs and selects
    });
});
</script>
<script>
    function addtask() {
        const sprintId = {{ $sprint->id ?? $bdeSprint->id ?? 'null' }}; 
        $('#taskModal input[name="bdesprint_id"]').val(sprintId); 
        $('#taskModal').modal('show');
    }
</script>
<script>
function addtask() {
    $('#addTaskModal').modal('show');
}

$('#addTaskForm').submit(function(e) {
    e.preventDefault();

    $.ajax({
        type: "POST",
        url: "{{ route('task.store') }}",
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                $('#addTaskModal').modal('hide');
                alert(response.message);
                location.reload(); 
            }
        },
        error: function(xhr) {
            alert('Something went wrong.');
        }
    });
});

 function deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task?')) return;

        $.ajax({
            url: `/tasks/${taskId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                location.reload(); 
            },
            error: function(xhr) {
                alert('Failed to delete task.');
                console.error(xhr.responseText);
            }
        });
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const chart = echarts.init(document.getElementById('pieChart'));

        chart.setOption({
            title: {
                text: 'Task Status Overview',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                bottom: '0%',
                left: 'center'
            },
            series: [
                {
                    name: 'Tasks',
                    type: 'pie',
                    radius: '50%',
                    data: [
                        { value: {{ $applied }}, name: 'Applied' },
                        { value: {{ $viewed }}, name: 'Viewed' },
                        { value: {{ $replied }}, name: 'Replied' },
                        { value: {{ $success }}, name: 'Success' },
                    ],
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });

        document.getElementById('resetChartBtn').addEventListener('click', function () {
            chart.setOption({ series: [{ data: [] }] }); // Reset chart
            setTimeout(() => location.reload(), 300); // Or reinit with full data
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var chart = echarts.init(document.getElementById('pieChart'));

        var option = {
            tooltip: {
                trigger: 'item'
            },
            legend: {
                top: 'bottom'
            },
            series: [
                {
                    name: 'Status',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    avoidLabelOverlap: false,
                    label: {
                        show: true,
                        position: 'outside'
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        { value: {{ $applied ?? 0 }}, name: 'Applied', itemStyle: { color: '#0dcaf0' } },   // bg-info
                        { value: {{ $viewed ?? 0 }}, name: 'Viewed', itemStyle: { color: '#0d6efd' } },    // bg-primary
                        { value: {{ $replied ?? 0 }}, name: 'Replied', itemStyle: { color: '#ffc107' } },  // bg-warning
                        { value: {{ $success ?? 0 }}, name: 'Success', itemStyle: { color: '#198754' } }   // bg-success
                    ]
                }
            ]
        };

        chart.setOption(option);
    });
</script>
<script>
    $(document).ready(function() {
        $('.styled-sprint-table').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true
        });
    });
    document.querySelectorAll('.status-options a').forEach(function(item) {
    item.addEventListener('click', function(e) {
        e.preventDefault();

        const newStatus = this.getAttribute('data-value');
        const taskId = this.closest('.dropdown-menu').getAttribute('data-task-id');

        fetch(`/tasks/${taskId}/update-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector(`.dropdown-menu[data-task-id="${taskId}"]`).previousElementSibling;
                badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                badge.className = `badge rounded-pill dropdown-toggle bg-${newStatus === 'success' ? 'success' : (newStatus === 'replied' ? 'warning' : (newStatus === 'viewed' ? 'info' : 'primary'))}`;
            }
        })
        .catch(err => console.error(err));
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
@endsection

