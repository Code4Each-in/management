@extends('layout')
@section('title', 'Email')
@section('subtitle', 'Email')

@section('content')
<div class="container">
    <form id="emailForm" action="{{ route('emailall.send') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row mb-5 mt-4">
            <label for="subject" class="col-sm-3 col-form-label ">Subject</label>
            <div class="col-sm-9">
                <input type="text" name="subject" id="subject" class="form-control" >
            @if ($errors->has('subject'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('subject') }}</span>
                @endif
            </div>
        </div>

        <div class="row mb-5">
            <label class="col-sm-3 col-form-label">Message</label>
            <div class="col-sm-9">
                <!-- Quill Toolbar -->
                <div id="toolbar-container" style="background-color: #fff;">

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
                <div id="message" style="height: 300px; border: 1px solid #ccc; border-radius: 5px; background-color: #fff;"></div>
                <input type="hidden" name="message" id="description-hidden">
                <!-- Error message -->
                @if ($errors->has('message'))
                    <span style="font-size: 14px;" class="text-danger">{{ $errors->first('message') }}</span>
                @endif
            </div>
        </div>
        <div class="row mb-5">
            <label class="col-sm-3 col-form-label">Attachments</label>
            <div class="col-sm-9">
                <input type="file" name="attachments[]" multiple class="form-control">
            </div>
        </div>
        <div class="row mb-5 mt-4">
            <label class="col-sm-3 col-form-label">Select Employees to Email</label>
            <div class="col-sm-9">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="select_all">
                    <label class="form-check-label" for="select_all">Select All</label>
                </div>
                @foreach($employees as $employee)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="emails[]" value="{{ $employee->email }}" id="email_{{ $employee->id }}">
                        <label class="form-check-label" for="email_{{ $employee->id }}">
                            {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->email }})
                        </label>
                    </div>
                @endforeach
                @if ($errors->has('emails'))
                    <span style="font-size: 14px;" class="text-danger">{{ $errors->first('emails') }}</span>
                @endif
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary mt-2" style="background-color: #4154f1;">Send Emails</button>
        </div>
    </form>
</div>



<script>
document.addEventListener("DOMContentLoaded", function () {
    const quill = new Quill('#message', {
        theme: 'snow',
        modules: {
            toolbar: '#toolbar-container'
        }
    });

    // Restore old content after failed validation
    const oldContent = {!! json_encode(old('message')) !!};
    if (oldContent) {
        quill.root.innerHTML = oldContent;
    }

    document.querySelector('form').addEventListener('submit', function () {
        const html = quill.root.innerHTML.trim();
        document.getElementById('description-hidden').value = html;
    });
});
</script>
@endsection
