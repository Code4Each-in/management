@extends('layout')
@section('title', 'Email to all')
@section('subtitle', 'Email')

@section('content')
<div class="container">
    <h2>Send Email to Employees</h2>

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

        <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea name="message" id="message" rows="5" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label for="footer" class="form-label">Footer</label>
            <textarea name="footer" id="tinymce_textarea" rows="3" class="form-control">
                <b>Thanks &amp; Regards</b><br>
                <i>HR Manager</i><br>
                <b>Code4Each</b><br>
                <b>Website:-</b> <a href="https://code4each.com/" style="color: #ff6666;">https://code4each.com/</a><br>
                <img src="https://hr.code4each.com/assets/img/code4each_logo.png" alt="Code4Each Logo" style="height:40px; margin-top:5px;">
            </textarea>

        </div>

        <h5>Select Employees to Email:</h5>

        <!-- Select All Checkbox -->
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="select_all">
            <label class="form-check-label" for="select_all">Select All</label>
        </div>

        <!-- List of Employees -->
        <div class="mb-3">
            @foreach($employees as $employee)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="emails[]" value="{{ $employee->email }}" id="email_{{ $employee->id }}">
                <label class="form-check-label" for="email_{{ $employee->id }}">
                    {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->email }})
                </label>
            </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary mt-2">Send Emails</button>
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
</script>
@endsection
