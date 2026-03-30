@extends('layout')

@section('title', 'Create Announcement')
@section('subtitle', 'Create Announcement')

@section('content')

@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif
<style>
    .margin-bottom {
        margin-bottom: 20px;
    }
</style>
<div class="max-w-xl mx-auto bg-white reminder-design p-6 rounded shadow">
    <form action="{{ route('announcement.store') }}" method="POST" class="margin-up">
        @csrf

        <!-- Title -->
        <div class="row mb-5 mt-4">
            <label class="col-sm-3 col-form-label required">Title</label>
            <div class="col-sm-9">
                <input type="text" name="title" class="form-control" value="{{ old('title') }}">
                
                @error('title')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div class="row mb-5">
            <label class="col-sm-3 col-form-label required">Description</label>
            <div class="col-sm-9">

                <!-- Toolbar -->
                <div id="toolbar-container">
                    <span class="ql-formats">
                        <button class="ql-bold"></button>
                        <button class="ql-italic"></button>
                        <button class="ql-underline"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered"></button>
                        <button class="ql-list" value="bullet"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-link"></button>
                    </span>
                </div>

                <!-- Editor -->
                <div id="editor" style="height: 250px; border: 1px solid #ccc; border-radius: 5px;"></div>

                <input type="hidden" name="description" id="description-hidden">

                @error('description')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row mb-5">
            <label class="col-sm-3 col-form-label">End Date</label>
            <div class="col-sm-9">
                <input type="date" name="end_date" class="form-control"
                    value="{{ old('end_date', $announcement->end_date ?? '') }}">
            </div>
        </div>

        <div class="row mb-5">
            <label class="col-sm-3 col-form-label">Show to Client</label>
            <div class="col-sm-9">
                <input type="checkbox" name="show_to_client"
                    {{ old('show_to_client', $announcement->show_to_client ?? false) ? 'checked' : '' }}>
            </div>
        </div>

        <!-- Active -->
        <div class="row mb-5">
            <label class="col-sm-3 col-form-label">Active</label>
            <div class="col-sm-9">
                <input type="checkbox" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
            </div>
        </div>

        <!-- Submit -->
        <div class="text-center margin-bottom">
            <button class="btn btn-primary">Create Announcement</button>
        </div>
    </form>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: { toolbar: '#toolbar-container' }
    });

    // OLD VALUE FIX
    const oldContent = {!! json_encode(old('description')) !!};
    if (oldContent) {
        quill.root.innerHTML = oldContent;
    }

    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('description-hidden').value = quill.root.innerHTML.trim();
    });

});
</script>

@endsection