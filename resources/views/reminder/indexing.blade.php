@extends('layout')
@section('title', 'Set Reminder')
@section('subtitle', 'Set Reminder')
@section('content')
<style>
    .hidden {
        display: none;
    }

    .desc_class {
        display: flex;
        align-items: center;
    }

    form.margin-up {
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>
<div class="max-w-xl mx-auto bg-white p-6 rounded shadow ">
    <form action="{{ route('reminders.store') }}" method="POST" class="margin-up">
        @csrf

        <div class="row mb-5 mt-4">
            <label for="type" class="col-sm-3 col-form-label ">Reminder Type</label>
            <div class="col-sm-9">
                <select name="type" id="type" class="form-select form-control" onchange="toggleFields()">
                    <option value="">Select a reminder type</option>
                    <option value="daily" {{ old('type') == 'daily' ? 'selected' : '' }}>Daily</option>
                    <option value="weekly" {{ old('type') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ old('type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
                @if ($errors->has('type'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('type') }}</span>
                @endif
            </div>

        </div>

        <div id="weeklyFields" class="row mb-5 mt-4 hidden">
            <label class="col-sm-3 col-form-label">Weekly Day</label>
            <div class="col-sm-9">
                <select name="weekly_day" class="form-select form-control">
                    <option value="" {{ old('weekly_day') == '' ? 'selected' : '' }}>Select a day</option>
                    @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                    <option value="{{ $day }}" {{ old('weekly_day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                    @endforeach
                </select>
                @if ($errors->has('weekly_day'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('weekly_day') }}</span>
                @endif
            </div>
        </div>

        <div id="monthlyFields" class="row mb-5 mt-4 hidden">
            <label class="col-sm-3 col-form-label">Monthly Date</label>
            <div class="col-sm-9">
                <select name="monthly_date" class="form-select form-control">
                    <option value="">Select a date</option>
                    @for ($i = 1; $i <= 31; $i++)
                        <option value="{{ $i }}" {{ old('monthly_date') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                </select>
                @if ($errors->has('monthly_date'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('monthly_date') }}</span>
                @endif
            </div>
        </div>
        <!-- Show Assign to User field only if user is Super Admin or Manager -->
        @if (auth()->user()->role->name === 'Super Admin' || auth()->user()->role->name === 'Manager')
    <div class="row mb-5 mt-4">
        <label for="user_id" class="col-sm-3 col-form-label">Assign to User</label>
        <div class="col-sm-9">
            <select name="user_id" class="form-select form-control">
                <option value="{{ auth()->id() }}" selected>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</option>
                @foreach (\App\Models\Users::where('status', 1)
                        ->whereNull('client_id')
                        ->where('id', '!=', auth()->id())
                        ->get() as $user)
                    <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
@endif
        <div id="customDateField" class="row mb-5 mt-4 hidden">
            <label for="custom_date" class="col-sm-3 col-form-label">Custom Date</label>
            <div class="col-sm-9">
                <input type="date" name="custom_date" id="custom_date" class="form-control">
                @if ($errors->has('custom_date'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('custom_date') }}</span>
                @endif
            </div>
        </div>
        <div class="row mb-5">
            <label class="col-sm-3 col-form-label">Description</label>
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
                <div id="editor" style="height: 300px; border: 1px solid #ccc; border-radius: 5px;"></div>

                <!-- Hidden input that will hold the HTML description -->
                <input type="hidden" name="description" id="description-hidden">

                <!-- Error message -->
                @if ($errors->has('description'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('description') }}</span>
                @endif
            </div>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary " style="background: #4154f1;">Create Reminder</button>
        </div>

    </form>
</div>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
   function toggleFields() {
    const type = document.getElementById('type').value;
    document.getElementById('weeklyFields').classList.add('hidden');
    document.getElementById('monthlyFields').classList.add('hidden');
    document.getElementById('customDateField').classList.add('hidden');
    if (type === 'weekly') {
        document.getElementById('weeklyFields').classList.remove('hidden');
    } else if (type === 'monthly') {
        document.getElementById('monthlyFields').classList.remove('hidden');
    } else if (type === 'custom') {
        document.getElementById('customDateField').classList.remove('hidden');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
});
document.addEventListener("DOMContentLoaded", function() {
            // Initialize Quill
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: '#toolbar-container'
        }
    });

    // Safe way to insert old content from Laravel
    const oldContent = {!! json_encode(old('description')) !!};
    if (oldContent && oldContent !== '') {
        quill.root.innerHTML = oldContent;
    }

    function cleanHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html;

        const paragraphs = div.querySelectorAll('p');
        paragraphs.forEach(p => {
            if (!p.innerHTML.trim()) {
                p.remove();
            } else {
                p.innerHTML = p.innerHTML.replace(/<br>/g, '');
            }
        });

        const content = div.innerHTML.trim().replace(/<\/?p>/g, '');

        return content;
    }

    document.querySelector('form').addEventListener('submit', function() {
        let html = quill.root.innerHTML.trim();
        html = cleanHtml(html);
        document.getElementById('description-hidden').value = quill.getText().trim();
    });
});
</script>
@endsection
