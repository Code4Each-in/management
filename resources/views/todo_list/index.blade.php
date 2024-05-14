@extends('layout')
@section('title', 'Todo')
@section('subtitle', 'ToDo List')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body mt-5">
            <div class="row">
                <form id="addTaskForm" method="POST" action="">
                    @csrf
                    <div class="col-md-12">
                        <div class="input-text-group mb-3">
                            <input type="text" class="form-control-text py-2" placeholder="WHAT NEEDS TO BE DONE?" name="title" id="taskTitle">
                            <button type="button" class="btn bg-primary text-white px-3" onClick="addRole()">Add</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <ul class="list-group list-group-flush">
                        @foreach(['open', 'hold', 'completed'] as $status)
                        @foreach($tasks->where('status', $status) as $task)
                        <li class="list-group-item {{ $task->status }} @if($task->status == 'completed') completed @elseif($task->status == 'hold') hold @endif" id="task_{{ $task->id }}">
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="check-task">
                                    <input class="form-check-input me-1 p-2 align-sub" type="checkbox" name="task_checkbox[]" value="{{ $task->id }}" onchange="toggleCompleted(this)">
                                    <span>{{ $task->title }}</span>
                                </div>
                                <div class="btn-reopen-hold">
                                    <button type="button" class="btn btn-warning btn-sm btn-hold me-1" onclick="holdTask({{ $task->id }})">Hold</button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="reopenTask({{ $task->id }})" style="display: none;">Reopen</button>
                                    <div class="icon-work">
                                        <i class="fas fa-edit text-dark cursor-pointer me-1" aria-hidden="true" onclick="editTask({{ $task->id }})"></i>
                                        <i class="fas fa-trash-alt text-danger cursor-pointer" aria-hidden="true" onClick="confirmDelete({{ $task->id }})"></i>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
    $(document).ready(function() {
        loadTasks();

        function initializeTaskUI(taskItem) {
            var checkbox = taskItem.find('input[type="checkbox"]');
            var holdButton = taskItem.find('.btn-hold');
            var reopenButton = taskItem.find('.btn-primary');

            if (taskItem.hasClass('completed')) {
                checkbox.prop('checked', true);
                holdButton.hide();
                reopenButton.show();
                taskItem.find('span').css('text-decoration', 'line-through');
            } else if (taskItem.hasClass('hold')) {
                checkbox.hide();
                holdButton.hide();
                reopenButton.show();
            } else {
                checkbox.prop('checked', false);
                holdButton.show();
                reopenButton.hide();
            }
        }

        $('.list-group-item').each(function() {
            initializeTaskUI($(this));
        });
        $('.list-group').on('click', '.btn-hold', function() {
            var taskId = $(this).closest('.list-group-item').attr('id').split('_')[1];
            var taskItem = $(this).closest('.list-group-item');
            holdTask(taskId, taskItem);
        });
        $('.list-group').on('click', '.btn-primary', function() {
            var taskId = $(this).closest('.list-group-item').attr('id').split('_')[1];
            var taskItem = $(this).closest('.list-group-item');
            reopenTask(taskId, taskItem);
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function reopenTask(taskId, taskItem) {
            $.ajax({
                type: 'PUT',
                url: "{{ url('/todo_list')}}/" + taskId + "/status",
                data: {
                    status: 'open',
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    // console.log("Success response:", response);
                    $('#task_' + taskId + ' input[type="checkbox"]').prop('checked', false);
                    taskItem.removeClass('completed').addClass('open').removeClass('hold');
                    taskItem.find('input[type="checkbox"]').show();
                    taskItem.find('.btn-reopen-hold .btn-hold').show();
                    taskItem.find('.btn-reopen-hold .btn-primary').hide();
                    taskItem.find('span').css('text-decoration', 'none');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }

        $('.btn-reopen-hold .btn-primary').click(function() {
            var taskId = $(this).closest('.list-group-item').attr('id').split('_')[1];
            var taskItem = $(this).closest('.list-group-item');
            reopenTask(taskId, taskItem);
        });

        function holdTask(taskId, taskItem) {
            $.ajax({
                type: 'PUT',
                url: "{{ url('/todo_list')}}/" + taskId + "/hold",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    // console.log(response);
                    taskItem.removeClass('completed').addClass('hold');
                    taskItem.find('input[type="checkbox"]').hide();
                    taskItem.find('.btn-reopen-hold .btn-hold').hide();
                    taskItem.find('.btn-reopen-hold .btn-primary').show();
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("Failed to put task on hold. Please check the console for details.");
                }
            });
        }

        $('.btn-reopen-hold .btn-hold').click(function() {
            var taskId = $(this).closest('.list-group-item').attr('id').split('_')[1];
            var taskItem = $(this).closest('.list-group-item');
            holdTask(taskId, taskItem);
        });

    });

    function loadTasks() {
        $.ajax({
            url: "{{ route('todo_list.index') }}",
            method: 'GET',
            success: function(response) {
                $('#taskList').html(response);
            }
        });
    }

    function addRole() {
        var taskId = $('input[name="task_id"]').val();
        if (taskId) {
            updateTask(taskId);
        } else {
            $.ajax({
                type: 'POST',
                url: "{{ url('/todo_list')}}",
                data: $('#addTaskForm').serialize(),
                cache: false,
                success: function(response) {
                    $('#taskTitle').val('');
                    var taskStatusClass = response.status == 'completed' ? 'completed' : (response.status == 'hold' ? 'hold' : '');
                    var taskItem = $('<li class="list-group-item ' + taskStatusClass + '" id="task_' + response.id + '"><div class="d-flex justify-content-between align-items-end"><div class="check-task"><input class="form-check-input me-1 p-2 align-sub" type="checkbox" name="task_checkbox[]" value="' + response.id + '" onchange="toggleCompleted(this)"><span>' + response.title + '</span></div><div class="btn-reopen-hold"><button type="button" class="btn btn-warning btn-sm btn-hold me-1" onclick="holdTask(' + response.id + ')">Hold</button><button type="button" class="btn btn-primary btn-sm" onclick="reopenTask(' + response.id + ')" style="display: none;">Reopen</button></div><div class="icon-work"><i class="fas fa-edit text-warning cursor-pointer me-1" aria-hidden="true" onclick="editTask(' + response.id + ')"></i><i class="fas fa-trash-alt text-danger cursor-pointer" aria-hidden="true" onClick="confirmDelete(' + response.id + ')"></i></div></div></li>');
                    $('.list-group').append(taskItem);
                    location.reload();
                },
                error: function(data) {
                    console.log(data);
                }
            });
        }
    }

    function editTask(id) {
        cancelEdit();
        var taskTitle = $('#task_' + id + ' span').text();
        $('#taskTitle').val(taskTitle);
        $('#addTaskForm button').text('Update');
        if ($('#addTaskForm button.btn-secondary').length === 0) {
            $('#addTaskForm').append('<button type="button" class="btn btn-secondary ms-2" onClick="cancelEdit()">Cancel</button>');
            $('#addTaskForm').append('<input type="hidden" name="task_id" value="' + id + '">');
        }
    }

    function cancelEdit() {
        $('#taskTitle').val('');
        $('#addTaskForm button').text('Add');
        $('#addTaskForm button.btn-secondary').remove();
        $('#addTaskForm input[name="task_id"]').remove();
    }

    function updateTask(taskId) {
        var taskTitle = $('#taskTitle').val();
        $.ajax({
            type: 'PUT',
            url: "{{ url('/todo_list')}}/" + taskId,
            data: {
                title: taskTitle,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                // console.log(response);
                $('#taskTitle').val('');
                $('#addTaskForm button').text('Add');
                cancelEdit();
                // $('#addTaskForm input[name="task_id"]').remove();
                $('#task_' + taskId + ' span').text(taskTitle);
            },
            error: function(data) {
                console.log(data);
            }
        });
    }

    function toggleCompleted(checkbox) {
        var taskId = $(checkbox).val();
        var taskItem = $('#task_' + taskId);
        var taskTitle = $('#task_' + taskId + ' span');
        var holdButton = taskItem.find('.btn-reopen-hold .btn-hold');
        var reopenButton = taskItem.find('.btn-reopen-hold .btn-primary');
        if ($(checkbox).is(':checked')) {
            taskTitle.css('text-decoration', 'line-through');
            reopenButton.show();
            holdButton.hide();
        } else {
            taskTitle.css('text-decoration', 'none');
            reopenButton.hide();
            holdButton.show();
        }

        $.ajax({
            type: 'PUT',
            url: "{{ url('/todo_list')}}/" + taskId + "/status",
            data: {
                status: $(checkbox).is(':checked') ? 'completed' : 'open',
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                taskItem.removeClass('completed');
                if ($(checkbox).is(':checked')) {
                    taskItem.addClass('completed');
                }
                location.reload();

            },
            error: function(xhr, status, error) {
                console.log("Error updating task status:", error);
            }
        });
    }

    function confirmDelete(id) {
        if (confirm("Are you sure you want to delete this task?")) {
            deleteTask(id);
        }
    }

    function deleteTask(id) {
        $.ajax({
            type: 'DELETE',
            url: "{{ url('/todo_list')}}/" + id,
            success: function(response) {
                // console.log("Task deleted successfully");
                $('#task_' + id).remove();
            },
            error: function(data) {
                console.log(data);
            }
        });
    }
</script>
@endsection