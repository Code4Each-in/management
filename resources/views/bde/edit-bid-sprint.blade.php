@extends('layout')
@section('title', 'Edit Bid Sprint')
@section('subtitle', 'Edit Bid Sprint')
@section('content')

<form id="editBidSprintForm" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="card-body">

            <div class="alert alert-danger" style="display: none;"></div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label required">Name</label>
                <div class="col-sm-9">
                    <input type="text" name="name" class="form-control" value="{{ old('name', $sprint->name) }}">
                    <span class="text-danger small" id="error-name"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label required">Start Date</label>
                <div class="col-sm-9">
                    <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', \Carbon\Carbon::parse($sprint->start_date)->format('Y-m-d\TH:i')) }}">
                    <span class="text-danger small" id="error-start_date"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">End Date</label>
                <div class="col-sm-9">
                    <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date', $sprint->end_date ? \Carbon\Carbon::parse($sprint->end_date)->format('Y-m-d\TH:i') : '') }}">
                    <span class="text-danger small" id="error-end_date"></span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Status</label>
                <div class="col-sm-9">
                    <select name="status" class="form-select">
                        <option value="1" {{ $sprint->status == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ $sprint->status == 0 ? 'selected' : '' }}>Inactive</option>
                        <option value="2" {{ $sprint->status == 2 ? 'selected' : '' }}>Completed</option>
                    </select>
                    <span class="text-danger small" id="error-status"></span>
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

        <!-- Quill Editor -->
        <div id="editor" style="height: 300px;">{!! old('description', $sprint->description) !!}</div>
        <input type="hidden" name="description" id="description_input">

        <!-- Validation Error Placeholder -->
        <span class="text-danger small" id="error-description"></span>
    </div>
</div>


            <div class="text-end">
                <a href="{{ route('bdeSprint.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>

        </div>
    </div>
</form>
@endsection

@section('js_scripts')
<script>
    $(document).ready(function () {
    $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

    $('#editBidSprintForm').submit(function (e) {
        e.preventDefault();

        const description = quill.root.innerHTML;
        $('#description_input').val(description);
        const formData = new FormData(this);
        const sprintId = "{{ $sprint->id }}";

        $('.alert-danger').hide().html('');
        $('.text-danger.small').text('');

        $.ajax({
            url: `/update/bid-sprint/${sprintId}`,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.errors) {
                    $('.alert-danger').show();
                    $.each(response.errors, function (key, value) {
                        $(`#error-${key}`).text(value[0]);
                    });
                } else {
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
