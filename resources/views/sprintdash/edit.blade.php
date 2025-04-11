@extends('layout')
@section('title', 'Edit Sprint')
@section('subtitle', 'Edit Sprint')
@section('content')
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
        <form id="editSprintsForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="sprint_id" value="{{ $sprint->id }}">
            <div class="form-group mb-3 mt-4">
                <label for="name">Sprint Name</label>
                <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $sprint->name) }}" required>
                @error('name')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div> 
            <div class="form-group mb-3">
                <label for="project">Project</label>
                <select name="project" class="form-select form-control" id="project" required>
                    <option value="" disabled selected>Select your project</option>
                    @foreach ($projects as $data)
                        <option value="{{ $data->id }}" {{ $sprint->project == $data->id ? 'selected' : '' }}>{{ $data->project_name }}</option>
                    @endforeach
                </select>
                @error('project')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div> 
            <div class="form-group mb-3">
                <label for="client">Client</label>
                <select name="client" class="form-select form-control" id="client" required>
                    <option value="" disabled selected>Select Clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" {{ $sprint->client == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
                @error('client')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>        
            <div class="form-group mb-3">
                <label for="start_date">Start Date</label>
                <input type="datetime-local" class="form-control" name="start_date" id="start_date" value="{{ old('start_date', \Carbon\Carbon::parse($sprint->start_date)->format('Y-m-d\TH:i')) }}" required>
                @error('start_date')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group mb-3">
                <label for="eta">End Date</label>
                <input type="datetime-local" class="form-control" name="eta" id="eta" value="{{ old('eta', \Carbon\Carbon::parse($sprint->eta)->format('Y-m-d\TH:i')) }}" required>
                @error('eta')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group mb-3">
                <label for="status" class="col-sm-3 col-form-label required">Status</label>
                
                    <select name="status" class="form-select form-control" id="status">
                        <option value="1" {{ old('status', $sprint->status) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $sprint->status) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>                
            </div> 
            <div class="form-group mb-3">
                <label for="tinymce_textarea" class="col-sm-3 col-form-label">Description</label>
                <div class="col-sm-9">
                    <textarea name="description" class="form-control" id="tinymce_textarea">{{ old('description', $sprint->description ?? '') }}</textarea>
                </div>
            </div>             
            <button type="submit" class="btn btn-primary mt-3">Update Sprint</button>
        </form>
    </div>
   </div>
</div>
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading.......">
</div>
@endsection

@section('js_scripts')
<script>
    $(document).ready(function () {
        $('#editSprintsForm').on('submit', function (e) {
            e.preventDefault(); 
            var formData = new FormData(this);
            $('#loader').show(); 
            $.ajax({
                url: "{{ route('sprint.update', $sprint->id) }}", 
                type: "POST",
                data: formData,
                contentType: false, 
                processData: false, 
                success: function (response) {
                    if (response.status == 'success') {
                        var sprintId = $('input[name="sprint_id"]').val();
                        setTimeout(function() {
                            window.location.href = "{{ route('sprint.edit', ':sprintId') }}".replace(':sprintId', sprintId);
                        }, 1000);
                    } else {
                        alert('Error: ' + response.errors.join(', ')); 
                    }
                    
                    setTimeout(function() {
                        $('#loader').hide(); 
                    }, 1000);
                },
                error: function (xhr, status, error) {
                    $('#loader').hide(); 
                    alert("An error occurred. Please try again.");
                }
            });
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
    
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
</script>
@endsection
