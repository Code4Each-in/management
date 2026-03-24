@extends('layout')

@section('title', 'Edit Announcement')
@section('subtitle', 'Edit Announcement')

@section('content')

<style>
    .margin-bottom {
        margin-bottom: 20px;
    }
</style>

@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="max-w-xl bg-white reminder-design p-6 rounded shadow">
    <form action="{{ route('announcement.update', $announcement->id) }}" method="POST" class="margin-up">
        @csrf
        @method('PUT')

        <!-- Title -->
        <div class="row mb-5 mt-4">
            <label class="col-sm-3 col-form-label required">Title</label>
            <div class="col-sm-9">
                <input type="text" name="title" class="form-control"
                    value="{{ old('title', $announcement->title) }}">
                
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
    value="{{ old('end_date', $announcement->end_date ? \Carbon\Carbon::parse($announcement->end_date)->format('Y-m-d') : '') }}">
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
                <input type="checkbox" name="is_active"
                    {{ old('is_active', $announcement->is_active) ? 'checked' : '' }}>
            </div>
        </div>

        <!-- Submit -->
        <div class="text-center margin-bottom">
            <button type="submit" class="btn btn-primary">Update Announcement</button>
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

    // SET OLD VALUE PROPERLY (FIXED)
    const oldContent = {!! json_encode(old('description', $announcement->message)) !!};
    if (oldContent) {
        quill.root.innerHTML = oldContent;
    }

    function cleanHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html;

        div.querySelectorAll('p').forEach(p => {
            if (!p.innerHTML.trim()) p.remove();
            else p.innerHTML = p.innerHTML.replace(/<br>/g, '');
        });

        return div.innerHTML.trim();
    }

    document.querySelector('form').addEventListener('submit', function () {
        let html = cleanHtml(quill.root.innerHTML);
        document.getElementById('description-hidden').value = html;
    });

});
</script>

@endsection