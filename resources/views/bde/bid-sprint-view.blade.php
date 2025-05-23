@extends('layout')
@section('title', 'BDE Sprint Details')
@section('subtitle', 'Show')
@section('content')

<div class="row row-design mb-4 mt-2">
  <div class="col-md-4 d-flex justify-content-start gap-3">
    <button class="btn btn-primary" onclick="addtask()">Add Record</button>
     <a href="{{ url('bid-sprints') }}" class="btn btn-primary">Back to Sprint</a>
  </div>

  <div class="row mt-3">
    <div class="col-md-3 form-group">
    <label for="dateFilterSelectBox"><strong>Filter By Date</strong></label>
    <select class="form-control" id="dateFilterSelectBox" name="date_filter" onchange="filterTasksByDateAndCreator()">
        <option value="" selected>Select Date Range</option>
        <option value="today" {{ request()->input('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
        <option value="this_week" {{ request()->input('date_filter') == 'this_week' ? 'selected' : '' }}>This Week</option>
        <option value="this_month" {{ request()->input('date_filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
        <option value="custom" {{ request()->input('date_filter') == 'custom' ? 'selected' : '' }}>Custom Range</option>
    </select>
    @if ($errors->has('date_filter'))
        <span style="font-size: 10px;" class="text-danger">{{ $errors->first('date_filter') }}</span>
    @endif
</div>

<!-- Custom Date Range Fields -->
<div class="col-md-3 form-group" id="customDateRange" style="display: none;">
    <label><strong>Start Date</strong></label>
    <input type="date" class="form-control" name="start_date" id="startDate" value="{{ request()->input('start_date') }}">

    <label class="mt-2"><strong>End Date</strong></label>
    <input type="date" class="form-control" name="end_date" id="endDate" value="{{ request()->input('end_date') }}">
    <button class="btn btn-primary mt-3" id="applyFilterBtn">Apply Filter</button>
</div>

    <div class="col-md-3 form-group">
    <label for="createdByFilterSelectBox"><strong>Created By</strong></label>
<select class="form-control" id="createdByFilterSelectBox" name="created_by_filter" onchange="filterTasksByDateAndCreator()">
        <option value="" selected>Select User</option>
        @foreach ($user as $u)
            <option value="{{ $u->id }}">{{ $u->first_name . ' ' . $u->last_name }}</option>
        @endforeach
    </select>
</div>

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
                  <div class="col-md-8">
                            <div class="text-center">
                                <div id="pieChart" style="min-height: 300px;"></div>

                                <div class="row mt-3 justify-content-center gap-2" id="statusCounts">
                                    <div class="col-auto">
                                        <span class="badge bg-info text-white" id="appliedCount">Applied: {{ $applied }}</span>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-primary text-white" id="viewedCount">Viewed: {{ $viewed }}</span>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-warning text-dark" id="repliedCount">Replied: {{ $replied }}</span>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-success text-white" id="successCount">Success: {{ $success }}</span>
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
            <th>Created By</th>
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
            <tr  data-created-at="{{ $task->created_at->format('Y-m-d') }}" data-created-by="{{ $task->created_by ? $task->created_by : '' }}" onclick="if (!event.target.closest('.actions-cell') && !event.target.closest('.status-group')) { window.open('{{ url('/view/task/'.$task->id) }}', '_blank'); }" style="cursor: pointer;">
                <td>{{ $index + 1 }}</td>
                <td>{{ $task->job_title }}</td>
                <td class="actions-cell">
                    @if($task->job_link)
                        <a href="{{ $task->job_link }}" target="_blank" rel="noopener noreferrer">Link</a>
                    @else
                        ---
                    @endif
                </td>
                <td>{{ $task->source ?? '---' }}</td>
                <td>{{ $task->profile ?? '---' }}</td>
                <td>{{ $task->creator ? $task->creator->first_name . ' ' . $task->creator->last_name : 'N/A' }}</td>
               <td class="actions-cell"> 
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
                <td class="actions-cell">
                    <a href="{{ route('tasks.show', $task->id) }}" title="View Task">
                        <i class="fa fa-eye fa-fw pointer" style="color:#4154f1;"></i>
                    </a>
                    <a href="{{ route('tasks.edit', $task->id) }}" title="Edit">
                        <i class="fa fa-edit fa-fw pointer"></i>
                    </a>
                    <a href="#" onclick="deleteTask({{ $task->id }})" title="Delete">
                        <i class="fa fa-trash fa-fw pointer" style="color:#4154f1;"></i>
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
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
<script>
let sprintTable;
function filterTasksByDateAndCreator() {
    sprintTable.draw();
}
function isSameDay(date1, date2) {
    return date1.toDateString() === date2.toDateString();
}
function isSameWeek(date1, date2) {
    const startOfWeek = new Date(date2);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    return date1 >= startOfWeek && date1 <= endOfWeek;
}
function isSameMonth(date1, date2) {
    return date1.getFullYear() === date2.getFullYear() && date1.getMonth() === date2.getMonth();
}
</script>
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
const chart = echarts.init(document.getElementById('pieChart'));

function updateChartAndBadges(data) {
    const total = data.total > 0 ? data.total : 1;

    // Update chart
    chart.setOption({
        series: [{
            data: [
                { value: data.applied, name: 'Applied' },
                { value: data.viewed, name: 'Viewed' },
                { value: data.replied, name: 'Replied' },
                { value: data.success, name: 'Success' }
            ]
        }]
    });

    // Update badges
    document.getElementById('appliedCount').innerText = `Applied: ${data.applied}`;
    document.getElementById('viewedCount').innerText = `Viewed: ${data.viewed}`;
    document.getElementById('repliedCount').innerText = `Replied: ${data.replied}`;
    document.getElementById('successCount').innerText = `Success: ${data.success}`;
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
    const tasksData = @json($tasksJson);
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chart = echarts.init(document.getElementById('pieChart'));

        const getFilteredTasks = () => {
            const creatorId = document.getElementById('createdByFilterSelectBox').value;
            const dateFilter = document.getElementById('dateFilterSelectBox').value;
            const today = new Date();

            return tasksData.filter(task => {
                const createdDate = new Date(task.created_at);
                let matchDate = true;

                if (dateFilter === 'today') {
                    matchDate = createdDate.toDateString() === today.toDateString();
                } else if (dateFilter === 'this_week') {
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay());
                    const endOfWeek = new Date(startOfWeek);
                    endOfWeek.setDate(startOfWeek.getDate() + 6);
                    matchDate = createdDate >= startOfWeek && createdDate <= endOfWeek;
                } else if (dateFilter === 'this_month') {
                    matchDate = createdDate.getFullYear() === today.getFullYear() &&
                                createdDate.getMonth() === today.getMonth();
                }

                const matchCreator = !creatorId || task.created_by == creatorId;

                return matchDate && matchCreator;
            });
        };

        const updateStatusCounts = (counts) => {
        document.getElementById('appliedCount').innerText = `Applied: ${counts.applied}`;
        document.getElementById('viewedCount').innerText = `Viewed: ${counts.viewed}`;
        document.getElementById('repliedCount').innerText = `Replied: ${counts.replied}`;
        document.getElementById('successCount').innerText = `Success: ${counts.success}`;
    };

const updateChart = () => {
    const filtered = getFilteredTasks();

    const counts = {
        applied: 0,
        viewed: 0,
        replied: 0,
        success: 0
    };

    filtered.forEach(task => {
        if (counts[task.status] !== undefined) {
            counts[task.status]++;
        }
    });

    const option = {
        title: {
            text: 'Task Status Overview',
            left: 'center'
        },
        tooltip: { trigger: 'item' },
        legend: { bottom: '0%', left: 'center' },
        series: [{
            name: 'Tasks',
            type: 'pie',
            radius: '50%',
            data: [
                { value: counts.applied, name: 'Applied', itemStyle: { color: '#0dcaf0' } },
                { value: counts.viewed, name: 'Viewed', itemStyle: { color: '#0d6efd' } },
                { value: counts.replied, name: 'Replied', itemStyle: { color: '#ffc107' } },
                { value: counts.success, name: 'Success', itemStyle: { color: '#198754' } }
            ]
        }]
    };

    chart.setOption(option);
    updateStatusCounts(counts);
};

updateChart();

document.getElementById('createdByFilterSelectBox').addEventListener('change', updateChart);
document.getElementById('dateFilterSelectBox').addEventListener('change', updateChart);
    });
</script>
<script>
    $(document).ready(function() {
        sprintTable = $('.styled-sprint-table').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true
        });

$.fn.dataTable.ext.search.push(
    function(settings, data, dataIndex) {
        const creatorFilter = document.getElementById('createdByFilterSelectBox').value;
        const dateFilter = document.getElementById('dateFilterSelectBox').value;

        const row = sprintTable.row(dataIndex).node();
        const rowCreatorId = row.getAttribute('data-created-by');
        const createdAt = row.getAttribute('data-created-at');
        if (!createdAt) return true;

        const createdDate = new Date(createdAt);
        const today = new Date();

        let showByDate = true;
        if (dateFilter === 'today') {
            showByDate = isSameDay(createdDate, today);
        } else if (dateFilter === 'this_week') {
            showByDate = isSameWeek(createdDate, today);
        } else if (dateFilter === 'this_month') {
            showByDate = isSameMonth(createdDate, today);
        }

        const showByCreator = !creatorFilter || rowCreatorId === creatorFilter;

        return showByDate && showByCreator;
    }
);

$('#createdByFilterSelectBox, #dateFilterSelectBox').on('change', function () {
    sprintTable.draw();
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
                location.reload();
            }
        })
        .catch(err => console.error(err));
    });
});
</script>
<script>
  function filterTasksByDateAndCreator() {
    const dateFilterSelectBox = document.getElementById('dateFilterSelectBox');
    const customDateDiv = document.getElementById('customDateRange');
    if (dateFilterSelectBox.value === 'custom') {
      customDateDiv.style.display = 'block';
    } else {
      customDateDiv.style.display = 'none';
    }
  }

 document.addEventListener('DOMContentLoaded', function () {
  const filterBtn = document.getElementById('applyFilterBtn');
  const startDateInput = document.getElementById('startDate');
  const endDateInput = document.getElementById('endDate');

  filterBtn.addEventListener('click', function () {
  const startDate = startDateInput?.value;
  const endDate = endDateInput?.value;

  if (!startDate || !endDate) {
    // Show all rows if dates are not selected
    document.querySelectorAll('.sprint-table tbody tr').forEach(row => {
      row.style.display = '';
    });
  } else {
    const start = new Date(`${startDate}T00:00:00`);
    const end = new Date(`${endDate}T23:59:59`);

    document.querySelectorAll('.sprint-table tbody tr').forEach(row => {
      const createdAtStr = row.getAttribute('data-created-at');
      const createdAt = new Date(`${createdAtStr}T00:00:00`);

      if (createdAt >= start && createdAt <= end) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  const counts = getStatusCountsForVisibleRows();
  updateChartAndBadges(counts);
});
});
function getStatusCountsForVisibleRows() {
  const rows = document.querySelectorAll('.sprint-table tbody tr');
  const counts = {
    applied: 0,
    viewed: 0,
    replied: 0,
    success: 0,
    total: 0,
  };

  rows.forEach(row => {
    if (row.style.display !== 'none') {
      const statusText = row.querySelector('.badge').textContent.trim().toLowerCase();
      if (counts[statusText] !== undefined) {
        counts[statusText]++;
      }
      counts.total++;
    }
  });

  return counts;
}

</script>
@endsection

