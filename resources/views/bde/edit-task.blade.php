@extends('layout')
@section('title', 'Edit Task')
@section('subtitle', 'Edit Task')
@section('content')

<form id="editTaskForm" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="card-body">

            <div class="alert alert-danger" style="display: none;"></div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label required">Job Title</label>
                <div class="col-sm-9">
                    <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $task->job_title) }}" required>
                    <span class="text-danger small" id="error-job_title"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Job Link</label>
                <div class="col-sm-9">
                    <input type="url" name="job_link" class="form-control" value="{{ old('job_link', $task->job_link) }}">
                    <span class="text-danger small" id="error-job_link"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Source</label>
                <div class="col-sm-9">
                    <input type="text" name="source" class="form-control" value="{{ old('source', $task->source) }}">
                    <span class="text-danger small" id="error-source"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Profile</label>
                <div class="col-sm-9">
                    <input type="text" name="profile" class="form-control" value="{{ old('profile', $task->profile) }}">
                    <span class="text-danger small" id="error-profile"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label required">Select Sprint</label>
                <div class="col-sm-9">
                    <select class="form-select" name="bdesprint_id" required>
                        <option value="" disabled>-- Select Sprint --</option>
                        @foreach($bdeSprints as $bsprint)
                            <option value="{{ $bsprint->id }}" @if($bsprint->id == old('bdesprint_id', $task->bdesprint_id)) selected @endif>{{ $bsprint->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger small" id="error-bdesprint_id"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Status</label>
                <div class="col-sm-9">
                    <select class="form-select" name="status">
                        <option value="applied" @if(old('status', $task->status) == 'applied') selected @endif>Applied</option>
                        <option value="viewed" @if(old('status', $task->status) == 'viewed') selected @endif>Viewed</option>
                        <option value="replied" @if(old('status', $task->status) == 'replied') selected @endif>Replied</option>
                        <option value="success" @if(old('status', $task->status) == 'success') selected @endif>Success</option>
                    </select>
                    <span class="text-danger small" id="error-status"></span>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('bdeSprint.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Task</button>
            </div>

        </div>
    </div>
</form>

@endsection

@section('js_scripts')
<script>
$(document).ready(function () {
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    $('#editTaskForm').submit(function (e) {
        e.preventDefault();

        $('.alert-danger').hide().html('');
        $('.text-danger.small').text('');

        let formData = new FormData(this);
        let taskId = "{{ $task->id }}";

        $.ajax({
            url: `/tasks/${taskId}/update`,  // Adjust route to your update URL
            type: 'POST',  // Use POST as per your request
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.errors) {
                    $('.alert-danger').show();
                    let errorHtml = '<ul>';
                    $.each(response.errors, function (key, value) {
                        $(`#error-${key}`).text(value[0]);
                        errorHtml += `<li>${value[0]}</li>`;
                    });
                    errorHtml += '</ul>';
                    $('.alert-danger').html(errorHtml);
                } else {
                    // Redirect to tasks listing or wherever you want
                    window.location.href = "{{ route('bdeSprint.index') }}";
                }
            },
            error: function (xhr) {
                $('.alert-danger').show().html('Something went wrong.');
                console.error(xhr.responseText);
            }
        });
    });
});
</script>
@endsection
