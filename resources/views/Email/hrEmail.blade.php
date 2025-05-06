@extends('layout')
@section('title', 'Email to all')
@section('subtitle', 'Email')

@section('content')
<div class="container">
    <!-- <h2>Send Email to Employees</h2> -->

    @if(session('success'))
    <div class="alert alert-success mt-2">
        {{ session('success') }}
    </div>
    @endif


    @if($errors->any())
    <div class="alert alert-danger mt-2">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form id="emailForm" action="{{ route('emailall.send') }}" method="POST">
        @csrf

        <div class="row mb-5 mt-4">
            <label for="subject" class="col-sm-3 col-form-label require">Subject</label>
            <div class="col-sm-9">
                <input type="text" name="subject" id="subject" class="form-control" required>
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
                @if ($errors->has('description'))
                <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                @endif
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
    </div>
</div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary mt-2" style="background-color: #4154f1;">Send Emails</button>
        </div>
    </form>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select All functionality
        const selectAll = document.getElementById('select_all');
        const checkboxes = document.querySelectorAll('input[name="emails[]"]');

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        });

        // Initialize TinyMCE
        tinymce.init({
            selector: '#message, #tinymce_textarea', // Initialize both fields
            menubar: false,
            plugins: 'advlist autolink lists link charmap anchor preview',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | outdent indent | link',
            height: 300,
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save(); // Push HTML to the textarea
                });
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const messageText = tinymce.get('message').getContent({
                format: 'text'
            });
            const footerText = tinymce.get('tinymce_textarea').getContent({
                format: 'text'
            });

            document.getElementById('message').value = messageText;
            document.getElementById('tinymce_textarea').value = footerText;

            form.submit();
        });

    });
    document.addEventListener("DOMContentLoaded", function () {
        const quill = new Quill('#message', {
            theme: 'snow',
            modules: {
                toolbar: '#toolbar-container'
            }
        });

        const oldContent = {!! json_encode(old('description')) !!};
        if (oldContent) {
            quill.root.innerHTML = oldContent;
        }

        document.querySelector('form').addEventListener('submit', function (e) {
            let html = quill.root.innerHTML.trim();
            const plainText = quill.getText().trim();

            // Prevent form submission if editor is empty
            if (!plainText) {
                e.preventDefault();
                alert('Description is required.');
                return;
            }

            // Save HTML to hidden input
            document.getElementById('description-hidden').value = html;
        });
    });
</script>
@endsection
