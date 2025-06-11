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
                <input type="hidden" name="sprint_id" value="{{ $sprint_id }}">
                <input type="hidden" id="pre_project_id" value="{{ $project_id ?? '' }}">
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
                        <div id="editor" style="height: 300px;">{!! old('description') !!}</div>
                        <input type="hidden" name="description" id="description_input">
                        
                        @if ($errors->has('description'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                        @endif
                    </div>
                </div>       
                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label required">Project</label>
                    <div class="col-sm-9">
                        <select name="project_id" class="form-select form-control" id="project_id">
                            <option value="">Select Project</option>
                            @foreach ($projects as $data)
                                <option value="{{ $data->id }}"
                                    {{ (old('project_id') == $data->id || (isset($project_id) && $project_id == $data->id)) ? 'selected' : '' }}>
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
                    <label class="col-sm-3 col-form-label">Sprint</label>
                    <div class="col-sm-9">
                        <select name="sprint_id_ticket" class="form-select form-control" id="sprint_id_ticket">
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
                    <label class="col-sm-3 col-form-label">Status</label>
                    <div class="col-sm-9">
                        <select name="status" class="form-select">
                            <option value="to_do">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="ready">Ready</option>
                            <option value="deployed">Deployed</option>
                            <option value="complete">Complete</option>
                        </select>
                        @error('status')
                            <span class="text-danger" style="font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label">Priority</label>
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
                    <label class="col-sm-3 col-form-label">Ticket Category</label>
                    <div class="col-sm-9">
                        <select name="ticket_category" class="form-select">
                            <option value="">Select Category</option>
                            <option value="Technical">Technical</option>
                            <option value="Design">Design</option>
                            <option value="Data Entry">Data Entry</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>
                
                @php
                    $role_id = auth()->user()->role_id;
                @endphp

                @if ($role_id != 6)
                <div class="row mb-5">
                    <label class="col-sm-3 col-form-label">Time Estimation</label>
                    <div class="col-sm-9">
                        <input type="number" name="time_estimation" class="form-control" min="0" step="0.25" placeholder="Enter estimated time (e.g. 1.5 for 1h 30m)">
                    </div>
                </div>
                @endif

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
                    <button type="submit" class="btn btn-primary" style=" background: #4154f1;">Create Ticket</button>
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
     $("#addTicketsForm").submit(function(event) {
                    event.preventDefault();    
                    $('#description_input').val(quill.root.innerHTML);             
                    var formData = new FormData(this);
                    $('#loader').show();
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('/add/tickets')}}",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            $('#loader').hide();

                            if (data.errors) {
                                $('.alert-danger').html('');
                                $.each(data.errors, function(key, value) {
                                    $('.alert-danger').show().append('<li>' + value + '</li>');
                                });
                            } else if (data.redirect) {
                                console.log(data.redirect);
                                // Redirect to the sprint view page
                                window.location.href = data.redirect;
                            } else {
                                // Fallback if no redirect
                                $("#addTickets").modal('hide');
                                location.reload(true);
                            }
                        },
                        error: function(data) {
                            $('#loader').hide();
                        }
                    });
                });

    $(document).ready(function () {
        $('#add_assign').select2({ width: '100%', allowClear: true });

        const initialProjectId = $('#project_id').val();
        const preselectedSprintId = "{{ $sprint_id ?? '' }}";

        // Load sprints automatically if a project is already selected
        if (initialProjectId) {
            loadSprints(initialProjectId, preselectedSprintId);
        }

        // Load sprints on project change (reset sprint selection)
        $('#project_id').on('change', function () {
            loadSprints($(this).val());
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
function loadSprints(projectId, preselectedSprintId = null) {
        $('#sprint_id_ticket').empty().append('<option value="">Loading...</option>');

        if (projectId) {
            $.ajax({
                url: '/get-sprints-by-project/' + projectId,
                type: 'GET',
                success: function (response) {
                    $('#sprint_id_ticket').empty().append('<option value="">Select Sprint</option>');
                    $.each(response, function (key, sprint) {
                        $('#sprint_id_ticket').append('<option value="' + sprint.id + '">' + sprint.name + '</option>');
                    });

                    // Select the sprint automatically if provided
                    if (preselectedSprintId) {
                        $('#sprint_id_ticket').val(preselectedSprintId);
                    }

                    if (projectId && preselectedSprintId) {
                        $('#project_id').attr('disabled', true).css('pointer-events', 'none').css('background-color', '#e9ecef');
                        $('#sprint_id_ticket').attr('disabled', true).css('pointer-events', 'none').css('background-color', '#e9ecef');

                        // Hidden inputs
                        if ($('#hidden_project_id').length === 0) {
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'hidden_project_id',
                                name: 'project_id',
                                value: projectId
                            }).appendTo('form');
                        }

                        if ($('#hidden_sprint_id_ticket').length === 0) {
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'hidden_sprint_id_ticket',
                                name: 'sprint_id_ticket',
                                value: preselectedSprintId
                            }).appendTo('form');
                        }
                    }

                },
                error: function () {
                    $('#sprint_id_ticket').empty().append('<option value="">Error loading sprints</option>');
                }
            });
        } else {
            $('#sprint_id_ticket').empty().append('<option value="">Select Sprint</option>');
        }
    }
</script>
@endsection
