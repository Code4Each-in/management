@extends('layout')
@section('title', 'Todo')
@section('subtitle', 'ToDo List')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body mt-5">
            <div class="row">
                <form id="addTaskForm" method="POST" action="{{ route('todo_list.store') }}">
                    @csrf
                    <div class="col-md-12">
                        <div class="input-text-group mb-3">
                            <input type="text" class="form-control-text py-2" placeholder="WHAT NEEDS TO BE DONE?" style="
    width: 100%;
" name="title" id="taskTitle" required>
                            <button type="submit" class="btn bg-primary text-white px-3">Add</button>
                        </div>
                    </div>
                </form>

            </div>
            <div class="row">
                <div class="col-md-12">
                    <ul class="list-group list-group-flush">
                        @foreach($personalTasks as $task) {{-- 🟢 Only Manager's Own Tasks --}}
                        <li class="list-group-item {{ $task->status }}" id="task_{{ $task->id }}">
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="check-task">
                                    <input class="form-check-input me-1 p-2 align-sub" type="checkbox" value="{{ $task->id }}" onchange="toggleCompleted(this)">
                                    <span>{{ $task->title }}</span>
                                </div>
                                <div class="btn-reopen-hold">
                                    <button type="button" class="btn btn-warning btn-sm btn-hold up-hold me-1" onclick="holdTask({{ $task->id }})">Hold</button>
                                    <button type="button" class="btn btn-primary up-reopen btn-sm" onclick="reopenTask({{ $task->id }})" style="display: none;">Reopen</button>
                                    <div class="icon-work">
                                        <i class="fas fa-edit text-dark cursor-pointer me-1" onclick="editTask({{ $task->id }})"></i>
                                        <i class="fas fa-trash-alt text-danger cursor-pointer" onClick="confirmDelete({{ $task->id }})"></i>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ✅ Super Admin & Manager: Team Task Management --}}
@if (auth()->user()->role->name === 'Super Admin' || auth()->user()->role->name === 'Manager')
<div class="col-lg-12">
    <div class="row">
        <div class="team-task-head">
            <h4>{{ auth()->user()->role->name === 'Super Admin' ? 'All Employee Tasks' : 'Team Tasks' }}</h4>
        </div>
    </div>
    <div class="card">
        <div class="card-body mt-5">
            <div class="row">
                <form id="addTaskForm2" method="POST" action="{{ route('todo_list.store') }}">
                    @csrf
                    <div class="col-md-12">
                        <div class="input-text-group mb-3" style="display: flex; align-items: center;">
                            <input type="text" class="form-control-text py-2" placeholder="WHAT NEEDS TO BE DONE?" name="title" id="taskTitle1" required>

                            <select class="form-control" name="assigned_user_id" required>
                                <option value="" selected disabled>Select Team Member</option>
                                @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }}</option>
                                @endforeach
                            </select>

                            <button type="submit" class="btn bg-primary text-white px-3">Add</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered teamstasks">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Created At</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teamTasks as $task)
                            <tr id="taskk_{{ $task->id }}">
                                <td>{{ $task->title }}</td>
                                <td>{{ $task->created_at->format('d M Y') }}</td>
                                <td data-user-id="{{ $task->assignedTo->id ?? '' }}">{{ $task->assignedTo->first_name ?? 'Not Assigned' }}</td>

                                <td>
                                    <!-- <span class="badge @if($task->status == 'completed') bg-success @elseif($task->status == 'hold') bg-warning @endif"
                                        @if($task->status == 'hold') style="background-color:#ffc720 !important; border-radius:20px;" @endif>
                                        {{ ucfirst($task->status) }}
                                    </span> -->
                                    <span class="badge"
                                        @if($task->status == 'completed')
                                        style="background-color: green !important; border-radius: 20px;"
                                        @elseif($task->status == 'hold')
                                        style="background-color: #ffc720 !important; border-radius: 20px;"
                                        @else
                                        style="background-color: #4154f1 !important; border-radius: 20px;"
                                        @endif>
                                        {{ ucfirst($task->status) }}
                                    </span>
                                </td>

                                <td style="display: flex;align-items: center; gap: 10px;">
                                    @if($task->status == 'open')
                                    <button class="btn btn-warning btn-sm btn-hold me-1" data-task-id="{{ $task->id }}" onclick="holdTaskNew(this)">Hold</button>
                                    <button class="btn btn-primary btn-sm btn-reopen d-none" style="background: #4154f1 !important;  border-color:#4154f1;" data-task-id="{{ $task->id }}" onclick="reopenTaskNew(this)">Reopen</button>
                                    @else
                                    <button class="btn btn-warning btn-sm btn-hold me-1 d-none" data-task-id="{{ $task->id }}" onclick="holdTaskNew(this)">Hold</button>
                                    <button class="btn btn-primary btn-sm btn-reopen" data-task-id="{{ $task->id }}" style="background: #4154f1 !important; border-color:#4154f1 ;" onclick="reopenTaskNew(this)">Reopen</button>
                                    @endif



                                    <i class="fas fa-edit text-dark cursor-pointer me-1" onclick="handleEditTaskth({{ $task->id }})"></i>
                                    <i class="fas fa-trash-alt text-danger cursor-pointer" onclick="handleDeleteTaskth({{ $task->id }})"></i>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif




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

        function holdTask(taskId) {
            $.ajax({
                type: 'PUT',
                url: "{{ url('/todo_list') }}/" + taskId + "/hold",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    console.log("Task put on hold:", response);

                    let taskItem = $("#task_" + taskId); // ✅ Correctly select task row
                    taskItem.removeClass('completed').addClass('hold');

                    taskItem.find('input[type="checkbox"]').hide();
                    taskItem.find('.btn-reopen-hold .btn-hold').hide();
                    taskItem.find('.btn-reopen-hold .btn-primary').show();

                    location.reload(); // ✅ Refresh to reflect changes
                },
                error: function(xhr, status, error) {
                    console.error("Error:", xhr.responseText);
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

    // function editTask(id) {
    //     cancelEdit();
    //     var taskTitle = $('#task_' + id + ' span').text();
    //     $('#taskTitle').val(taskTitle);
    //     $('#addTaskForm button').text('Update');
    //     if ($('#addTaskForm button.btn-secondary').length === 0) {
    //         $('#addTaskForm').append('<button type="button" class="btn btn-secondary ms-2" onClick="cancelEdit()">Cancel</button>');
    //         $('#addTaskForm').append('<input type="hidden" name="task_id" value="' + id + '">');
    //     }
    // }

    // function cancelEdit() {
    //     $('#taskTitle').val('');
    //     $('#addTaskForm button').text('Add');
    //     $('#addTaskForm button.btn-secondary').remove();
    //     $('#addTaskForm input[name="task_id"]').remove();
    // }

    // function updateTask(taskId) {
    //     var taskTitle = $('#taskTitle').val();
    //     $.ajax({
    //         type: 'PUT',
    //         url: "{{ url('/todo_list')}}/" + taskId,
    //         data: {
    //             title: taskTitle,
    //             _token: "{{ csrf_token() }}"
    //         },
    //         success: function(response) {
    //             // console.log(response);
    //             $('#taskTitle').val('');
    //             $('#addTaskForm button').text('Add');
    //             cancelEdit();
    //             // $('#addTaskForm input[name="task_id"]').remove();
    //             $('#task_' + taskId + ' span').text(taskTitle);
    //         },
    //         error: function(data) {
    //             console.log(data);
    //         }
    //     });
    // }

//     $('#addTaskForm').submit(function (e) {
//         e.preventDefault(); // Prevent default form submission

//         var taskTitle = $('#taskTitle').val();
//         var taskId = $('input[name="task_id"]').val(); // Get task_id if exists
//         var formAction = taskId ? "{{ url('/todo_list') }}/" + taskId : "{{ route('todo_list.store') }}";
//         var method = taskId ? 'PUT' : 'POST';

//         $.ajax({
//             type: method,
//             url: formAction,
//             data: {
//                 title: taskTitle,
//                 _token: "{{ csrf_token() }}",
//                 _method: method
//             },
//             success: function (response) {
//                 if (taskId) {
//                     // Update task in UI without reloading
//                     $('#task_' + taskId + ' span').text(taskTitle);
//                 } else {
//                     // Append new task to list
//                     location.reload(); // Or manually append the new task
//                 }
//                 cancelEdit();
//             },
//             error: function (error) {
//                 console.log(error);
//             }
//         });
//     });


// function editTask(id) {
//     cancelEdit();
//     var taskTitle = $('#task_' + id + ' span').text();
//     $('#taskTitle1').val(taskTitle);
//     $('#addTaskForm button[type="submit"]').text('Update');

//     if ($('#addTaskForm input[name="task_id"]').length === 0) {
//         $('#addTaskForm').append('<input type="hidden" name="task_id" value="' + id + '">');
//     }
// }

// function cancelEdit() {
//     $('#taskTitle').val('');
//     $('#addTaskForm button[type="submit"]').text('Add');
//     $('#addTaskForm input[name="task_id"]').remove();
// }


//     function toggleCompleted(checkbox) {
//         var taskId = $(checkbox).val();
//         var taskItem = $('#task_' + taskId);
//         var taskTitle = $('#task_' + taskId + ' span');
//         var holdButton = taskItem.find('.btn-reopen-hold .btn-hold');
//         var reopenButton = taskItem.find('.btn-reopen-hold .btn-primary');
//         if ($(checkbox).is(':checked')) {
//             taskTitle.css('text-decoration', 'line-through');
//             reopenButton.show();
//             holdButton.hide();
//         } else {
//             taskTitle.css('text-decoration', 'none');
//             reopenButton.hide();
//             holdButton.show();
//         }

//         $.ajax({
//             type: 'PUT',
//             url: "{{ url('/todo_list')}}/" + taskId + "/status",
//             data: {
//                 status: $(checkbox).is(':checked') ? 'completed' : 'open',
//                 _token: "{{ csrf_token() }}"
//             },
//             success: function(response) {
//                 taskItem.removeClass('completed');
//                 if ($(checkbox).is(':checked')) {
//                     taskItem.addClass('completed');
//                 }
//                 location.reload();

//             },
//             error: function(xhr, status, error) {
//                 console.log("Error updating task status:", error);
//             }
//         });
//     }

//     function confirmDelete(id) {
//         if (confirm("Are you sure you want to delete this task?")) {
//             deleteTask(id);
//         }
//     }

//     function deleteTask(id) {
//         $.ajax({
//             type: 'DELETE',
//             url: "{{ url('/todo_list')}}/" + id,
//             success: function(response) {
//                 // console.log("Task deleted successfully");
//                 $('#task_' + id).remove();
//             },
//             error: function(data) {
//                 console.log(data);
//             }
//         });
//     }

    function holdTaskNew(button) {
        let btnHold = $(button);
        let taskId = btnHold.data("task-id");

        $.ajax({
            type: "PUT",
            url: "{{ url('/todo_list') }}/" + taskId + "/hold",
            data: {
                _token: "{{ csrf_token() }}",
                status: "hold"
            },
            success: function(response) {
                console.log("Task put on hold:", response);

                let taskRow = btnHold.closest("tr");
                let btnReopen = taskRow.find(".btn-reopen");
                let statusBadge = taskRow.find(".task-status"); // Find status badge

                // Update status text & color
                statusBadge.text("Hold");
                statusBadge.removeClass("bg-primary bg-success").addClass("bg-warning");

                // Ensure visibility toggle works correctly
                btnHold.addClass("d-none");
                btnReopen.removeClass("d-none");
                location.reload()
            },
            error: function(xhr, status, error) {
                console.error("Error:", xhr.responseText);
                alert("Failed to put task on hold.");
            }
        });
    }

    function reopenTaskNew(button) {
        let btnReopen = $(button);
        let taskId = btnReopen.data("task-id");

        $.ajax({
            type: "PUT",
            url: "{{ url('/todo_list')}}/" + taskId + "/status",
            data: {
                _token: "{{ csrf_token() }}",
                status: "open"
            },
            success: function(response) {
                console.log("Task reopened:", response);

                let taskRow = btnReopen.closest("tr");
                let btnHold = taskRow.find(".btn-hold");
                let statusBadge = taskRow.find(".task-status"); // Find status badge

                // Update status text & color
                statusBadge.text("Open");
                statusBadge.removeClass("bg-warning bg-success").addClass("bg-primary");

                // Ensure visibility toggle works correctly
                btnReopen.addClass("d-none");
                btnHold.removeClass("d-none");
                location.reload()
            },
            error: function(xhr, status, error) {
                console.error("Error:", xhr.responseText);
                alert("Failed to reopen task.");
            }
        });
    }


//     $(document).ready(function () {
//             $('#addTaskForm2').submit(handleTaskFormSubmission);
//         });

//         function handleTaskFormSubmission(e) {
//             e.preventDefault();

//             var taskTitle = $('#taskTitle').val();
//             var taskId = getTaskId();
//             var formAction = getFormAction(taskId);
//             var method = taskId ? 'PUT' : 'POST';

//             sendTaskRequestth(method, formAction, taskTitle, taskId);
//         }

//         function getTaskId() {
//             return $('input[name="task_id"]').val() || null;
//         }

//         function getFormAction(taskId) {
//             return taskId ? "{{ url('/todo_list') }}/" + taskId : "{{ route('todo_list.store') }}";
//         }

//         function sendTaskRequestth(method, url, title, taskId) {
//             $.ajax({
//                 type: method,
//                 url: url,
//                 data: {
//                     title: title,
//                     _token: "{{ csrf_token() }}",
//                     _method: method
//                 },
//                 success: function () {
//                     updateTaskUIth(taskTitle, taskId);
//                     cancelEdit();
//                     location.reload();
//                 },
//                 error: function (error) {
//                     console.log(error);
//                 }
//             });
//         }

//         function updateTaskUIth(taskTitle, taskId) {
//     if (taskId) {
//         $('#task_' + taskId).find('span').text(taskTitle); // Correct selection
//     } else {
//         location.reload(); // Or manually append the new task
//     }
// }

//         function handleEditTaskth(id) {
//             editTaskth(id);
//         }

//         function handleDeleteTaskth(id) {
//             if (confirm("Are you sure you want to delete this task?")) {
//                 $.ajax({
//                     type: "DELETE",
//                     url: "{{ url('/todo_list') }}/" + id,
//                     data: {
//                         _token: "{{ csrf_token() }}",
//                         _method: "DELETE"
//                     },
//                     success: function () {
//                         $('#task_' + id).remove();

//                     },
//                     error: function (error) {
//                         console.log(error);
//                     }
//                 });
//             }
//         }

// function editTaskth(id) {
//     cancelEditth();
//     var taskTitle = $('#task_' + id).find('span').text(); // Correct selection
//     $('#taskTitle').val(taskTitle);
//     $('#addTaskForm2 button[type="submit"]').text('Update');

//     if ($('#addTaskForm2 input[name="task_id"]').length === 0) {
//         $('#addTaskForm2').append('<input type="hidden" name="task_id" value="' + id + '">');

//     }

// }


//         function cancelEditth() {
//             $('#taskTitle').val('');
//             $('#addTaskForm2 button[type="submit"]').text('Add');
//             $('#addTaskForm2 input[name="task_id"]').remove();
//         }


























// $(document).ready(function () {
//     $('#addTaskForm2').submit(function (e) {
//         e.preventDefault();

//         var taskTitle = $('#taskTitle1').val();
//         var assignedUserId = $('select[name="assigned_user_id"]').val();
//         var taskId = $('input[name="task_id"]').val();
//         var formAction = taskId ? "{{ url('/todo_list') }}/" + taskId : "{{ route('todo_list.store') }}";
//         var method = taskId ? 'PUT' : 'POST';

//         $.ajax({
//             type: method,
//             url: formAction,
//             data: {
//                 title: taskTitle,
//                 assigned_user_id: assignedUserId,
//                 _token: "{{ csrf_token() }}",
//                 _method: method
//             },
//             success: function (response) {
//                 if (taskId) {
//                     $('#taskk_' + taskId + ' td:first').text(taskTitle);
//                 } else {
//                     location.reload();
//                 }
//                 cancelEdit();
//             },
//             error: function (error) {
//                 console.log(error);
//             }
//         });
//     });
// });

// function handleEditTaskth(id) {
//     cancelEditth();
//     var taskTitle = $('#taskk_' + id + ' td:first').text();
//     $('#taskTitle1').val(taskTitle);
//     $('#addTaskForm2 button[type="submit"]').text('Update');

//     if ($('#addTaskForm2 input[name="task_id"]').length === 0) {
//         $('#addTaskForm2').append('<input type="hidden" name="task_id" value="' + id + '">');
//     }
// }

// function cancelEditth() {
//     $('#taskTitle1').val('');
//     $('#addTaskForm2 button[type="submit"]').text('Add');
//     $('#addTaskForm2 input[name="task_id"]').remove();
// }

// function handleDeleteTaskth(id) {
//     if (confirm("Are you sure you want to delete this task?")) {
//         $.ajax({
//             type: 'DELETE',
//             url: "{{ url('/todo_list')}}/" + id,
//             data: {
//                 _token: "{{ csrf_token() }}"
//             },
//             success: function (response) {
//                 $('#taskk_' + id).remove();
//             },
//             error: function (data) {
//                 console.log(data);
//             }
//         });
//     }
// }

// function toggleCompleted(checkbox) {
//     var taskId = $(checkbox).val();
//     var taskItem = $('#taskk_' + taskId);
//     var taskTitle = $('#taskk_' + taskId + ' td:first');
//     var holdButton = taskItem.find('.btn-hold');
//     var reopenButton = taskItem.find('.btn-reopen');

//     if ($(checkbox).is(':checked')) {
//         taskTitle.css('text-decoration', 'line-through');
//         reopenButton.show();
//         holdButton.hide();
//     } else {
//         taskTitle.css('text-decoration', 'none');
//         reopenButton.hide();
//         holdButton.show();
//     }

//     $.ajax({
//         type: 'PUT',
//         url: "{{ url('/todo_list')}}/" + taskId + "/status",
//         data: {
//             status: $(checkbox).is(':checked') ? 'completed' : 'open',
//             _token: "{{ csrf_token() }}"
//         },
//         success: function(response) {
//             taskItem.toggleClass('completed', $(checkbox).is(':checked'));
//             location.reload();
//         },
//         error: function(xhr, status, error) {
//             console.log("Error updating task status:", error);
//         }
//     });
// }


$(document).ready(function () {
    // ✅ Handle Adding and Editing Tasks (Personal & Team)
    $('#addTaskForm, #addTaskForm2').submit(function (e) {
        e.preventDefault(); // Prevent default form submission

        var formId = $(this).attr('id'); // Identify which form is being submitted
        var taskTitle = formId === "addTaskForm" ? $('#taskTitle').val() : $('#taskTitle1').val();
        var assignedUserId = formId === "addTaskForm2" ? $('select[name="assigned_user_id"]').val() : null;
        var taskId = $('input[name="task_id"]').val(); // Get task_id if exists
        var formAction = taskId ? "{{ url('/todo_list') }}/" + taskId : "{{ route('todo_list.store') }}";
        var method = taskId ? 'PUT' : 'POST';

        var requestData = {
            title: taskTitle,
            _token: "{{ csrf_token() }}",
            _method: method
        };

        if (assignedUserId) {
            requestData.assigned_user_id = assignedUserId; // Add assigned user ID for team tasks
        }

        $.ajax({
            type: method,
            url: formAction,
            data: requestData,
            success: function (response) {
                if (taskId) {
                    // ✅ Update Personal Task
                    $('#task_' + taskId + ' span').text(taskTitle);
                    // ✅ Update Team Task
                    $('#taskk_' + taskId + ' td:first-child').text(taskTitle);
                } else {
                    location.reload(); // Or manually append the new task
                }
                cancelEdit();
            },
            error: function (error) {
                console.log(error);
            }
        });
    });

    // ✅ Edit Personal Task
    window.editTask = function (id) {
        cancelEdit();
        var taskTitle = $('#task_' + id + ' span').text();
        $('#taskTitle').val(taskTitle);
        $('#addTaskForm button[type="submit"]').text('Update');

        if ($('#addTaskForm input[name="task_id"]').length === 0) {
            $('#addTaskForm').append('<input type="hidden" name="task_id" value="' + id + '">');
        }
    };

    // ✅ Edit Team Task
    // window.handleEditTaskth = function (id) {
    //     cancelEdit();
    //     var taskTitle = $('#taskk_' + id + ' td:first-child').text();
    //     $('#taskTitle1').val(taskTitle);
    //     $('#addTaskForm2 button[type="submit"]').text('Update');

    //     if ($('#addTaskForm2 input[name="task_id"]').length === 0) {
    //         $('#addTaskForm2').append('<input type="hidden" name="task_id" value="' + id + '">');
    //     }
    // };



    window.handleEditTaskth = function (id) {
    cancelEdit();

    // Get task details
    var taskRow = $('#taskk_' + id);
    var taskTitle = taskRow.find('td:first-child').text().trim();
    var assignedUserId = taskRow.find('td:nth-child(3)').attr('data-user-id'); // Get assigned user ID from the row

    // Populate task title
    $('#taskTitle1').val(taskTitle);
    $('#addTaskForm2 button[type="submit"]').text('Update');

    // ✅ Set the correct assigned user in the dropdown
    $('#addTaskForm2 select[name="assigned_user_id"]').val(assignedUserId);

    // Add hidden task ID field if not present
    if ($('#addTaskForm2 input[name="task_id"]').length === 0) {
        $('#addTaskForm2').append('<input type="hidden" name="task_id" value="' + id + '">');
    }

    $('#addTaskForm2').off('submit').on('submit', function (e) {
        e.preventDefault();

        var updatedTitle = $('#taskTitle1').val();
        var updatedAssignedUser = $('#addTaskForm2 select[name="assigned_user_id"]').val();
        var formAction = "{{ url('/todo_list') }}/" + id;
        var method = 'PUT';

        $.ajax({
            type: method,
            url: formAction,
            data: {
                title: updatedTitle,
                assigned_user_id: updatedAssignedUser,
                _token: "{{ csrf_token() }}",
                _method: method
            },
            success: function (response) {
                location.reload(); // ✅ Reload page after successful edit
            },
            error: function (error) {
                console.log(error);
                alert("Error updating task.");
            }
        });
    });
};



    // ✅ Cancel Edit for Both Forms
    function cancelEdit() {
        $('#taskTitle, #taskTitle1').val('');
        $('#addTaskForm button[type="submit"], #addTaskForm2 button[type="submit"]').text('Add');
        $('#addTaskForm input[name="task_id"], #addTaskForm2 input[name="task_id"]').remove();
    }

    // ✅ Delete Personal Task
    window.confirmDelete = function (id) {
        if (confirm("Are you sure you want to delete this personal task?")) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/todo_list') }}/" + id,
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#task_' + id).remove();
                    alert("Personal task deleted successfully!");
                },
                error: function(error) {
                    console.log(error);
                    alert("Error deleting personal task.");
                }
            });
        }
    };

    // ✅ Delete Team Task
    window.handleDeleteTaskth = function (id) {
        if (confirm("Are you sure you want to delete this team task?")) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/todo_list') }}/" + id,
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#taskk_' + id).remove();
                    alert("Team task deleted successfully!");
                },
                error: function(error) {
                    console.log(error);
                    alert("Error deleting team task.");
                }
            });
        }
    };
});


</script>
@endsection
