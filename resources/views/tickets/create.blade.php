@extends('layout')
@section('title', 'Add Ticket')
@section('subtitle', 'Add Ticket')

@section('content')
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form method="POST" id="addTicketsForm"  enctype="multipart/form-data">
                @csrf
                <div class="alert alert-danger mt-1" style="display: none;"></div>

                <div class="row mb-5 mt-4">
                    <label class="col-sm-3 col-form-label required">Title</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" oninput="checkWordCount()">
                        <small id="wordCountError" class="text-danger" style="display:none;">Please enter at least 15 characters.</small>
                        @error('title')
                        <span class="text-danger" style="font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label required">Description</label>
                    <div class="col-sm-9">
                        <textarea name="description"  class="form-control" id="tinymce_textarea">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label required">Project</label>
                    <div class="col-sm-9">
                        <select name="project_id" class="form-select form-control" id="project_id">
                            <option value="">Select Project</option>
                            @foreach ($projects as $data)
                                <option value="{{ $data->id }}" {{ old('project_id') == $data->id ? 'selected' : '' }}>
                                    {{ $data->project_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <span class="text-danger" style="font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label required">Sprint</label>
                    <div class="col-sm-9">
                        <select name="sprint_id" class="form-select form-control" id="sprint_id">
                            <option value="">Select Sprint</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label required">Assign Users</label>
                    <div class="col-sm-9">
                        <select name="assign[]" class="form-select" id="add_assign" multiple>
                            @foreach ($user as $data)
                                <option value="{{ $data->id }}" {{ collect(old('assign'))->contains($data->id) ? 'selected' : '' }}>
                                    {{ $data->first_name }} - {{ $data->designation }}
                                </option>
                            @endforeach
                        </select>
                        @error('assign')
                            <span class="text-danger" style="font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label">ETA</label>
                    <div class="col-sm-9">
                        <input type="datetime-local" class="form-control" id="eta" name="eta" value="{{ old('eta') }}">
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label required">Status</label>
                    <div class="col-sm-9">
                        <select name="status" class="form-select">
                            <option value="to_do">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="ready">Ready</option>
                            <option value="complete">Complete</option>
                        </select>
                        @error('status')
                            <span class="text-danger" style="font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label required">Priority</label>
                    <div class="col-sm-9">
                        <select name="priority" class="form-select">
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        @error('priority')
                            <span class="text-danger" style="font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label">Ticket State</label>
                    <div class="col-sm-9">
                        <select name="ticket_priority" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label">Upload Document</label>
                    <div class="col-sm-9">
                        <input type="file" name="document[]" class="form-control" multiple>
                        @error('document.*')
                            <span class="text-danger" style="font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js_scripts')
<script>
    document.getElementById('title').addEventListener('input', checkCharLength);
    function checkCharLength() {
    const titleInput = document.getElementById('title');
    const charCountError = document.getElementById('wordCountError');
    const charLength = titleInput.value.trim().length;

    if (charLength < 15) { 
        charCountError.style.display = 'block';  
    } else {
        charCountError.style.display = 'none';   
    }
}
     $('#project_id').on('change', function () {
        var projectId = $(this).val();
        $('#sprint_id').empty().append('<option value="">Loading...</option>');

        if (projectId) {
            $.ajax({
                url: '/get-sprints-by-project/' + projectId,
                type: 'GET',
                success: function (response) {
                    $('#sprint_id').empty().append('<option value="">Select Sprint</option>');
                    $.each(response, function (key, sprint) {
                        $('#sprint_id').append('<option value="' + sprint.id + '">' + sprint.name + '</option>');
                    });
                },
                error: function () {
                    $('#sprint_id').empty().append('<option value="">Error loading sprints</option>');
                }
            });
        } else {
            $('#sprint_id').empty().append('<option value="">Select Sprint</option>');
        }
    });
     $("#addTicketsForm").submit(function(event) {
                    event.preventDefault();  
                    tinymce.triggerSave();                 
                    var formData = new FormData(this);
                    $('#loader').show();
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('/add/tickets')}}",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: (data) => {
                            if (data.errors) {
                                $('.alert-danger').html('');
                                $.each(data.errors, function(key, value) {
                                    $('.alert-danger').show();
                                    $('.alert-danger').append('<li>' + value + '</li>');
                                })
                            } else {
                                $("#addTickets").modal('hide');
                                location.reload(true);
                            }
                            $('#loader').hide();
                        },
                        error: function(data) {
                            $('#loader').hide();
                        }
                    });
                });

    $(document).ready(function () {
        $('#add_assign').select2({ width: '100%', allowClear: true });

        $('#add_project_id').on('change', function () {
            var projectId = $(this).val();
            $('#add_sprint_id').empty().append('<option value="">Loading...</option>');

            if (projectId) {
                $.ajax({
                    url: '/get-sprints-by-project/' + projectId,
                    type: 'GET',
                    success: function (response) {
                        $('#add_sprint_id').empty().append('<option value="">Select Sprint</option>');
                        $.each(response, function (key, sprint) {
                            $('#add_sprint_id').append('<option value="' + sprint.id + '">' + sprint.name + '</option>');
                        });
                    },
                    error: function () {
                        $('#add_sprint_id').empty().append('<option value="">Error loading sprints</option>');
                    }
                });
            } else {
                $('#add_sprint_id').empty().append('<option value="">Select Sprint</option>');
            }
        });
    });
    document.addEventListener("DOMContentLoaded", function() {
    const currentDate = new Date();
    const dayOfWeek = currentDate.getDay();
    if (dayOfWeek === 5) { 
        currentDate.setHours(currentDate.getHours() + 72);
    } else if (dayOfWeek === 6 || dayOfWeek === 0) { 
        currentDate.setHours(currentDate.getHours() + (72 - currentDate.getHours() % 24));
    } else {
        currentDate.setHours(currentDate.getHours() + 48);
    }
    const year = currentDate.getFullYear();
    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
    const day = String(currentDate.getDate()).padStart(2, '0');
    const hours = String(currentDate.getHours()).padStart(2, '0');
    const minutes = String(currentDate.getMinutes()).padStart(2, '0');
    const maxEta = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById("eta").setAttribute("max", maxEta);
});
</script>
@endsection
