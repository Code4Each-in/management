@extends('layout')
@section('title', 'Edit Reminder')
@section('subtitle', 'Edit Reminder')
@section('content')
<style>
    .desc_class {
        display: flex;
        align-items: left;
    }
    .hidden {
        display: none;
    }
    .margin-bottom{
        margin-bottom: 20px;
    }
</style>

<div class="max-w-xl  bg-white  reminder-design">
    <form action="{{ route('reminders.update', $reminder->id) }}" method="POST" class="margin-up">
        @csrf
        @method('PUT')

        <div class="row mb-5 mt-4">
            <label for="type" class="col-sm-3 col-form-label required">Reminder Type</label>
            <div class="col-sm-9">
                <select name="type" id="type" class="form-select form-control" onchange="toggleFields()">
                    <option value="">Select a reminder type</option>
                    <option value="daily" {{ $reminder->type == 'daily' ? 'selected' : '' }}>Daily</option>
                    <option value="weekly" {{ $reminder->type == 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="biweekly" {{ $reminder->type == 'biweekly' ? 'selected' : '' }}>Bi-weekly</option>
                    <option value="monthly" {{ $reminder->type == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="custom" {{ $reminder->type == 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
                @if ($errors->has('type'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('type') }}</span>
                @endif
            </div>
        </div>

        <div id="weeklyFields" class="row mb-5 mt-4 hidden">
            <label class="col-sm-3 col-form-label required">Weekly Day</label>
            <div class="col-sm-9">
                <select name="weekly_day" class="form-select form-control">
                    <option value="" {{ $reminder->weekly_day == '' ? 'selected' : '' }}>Select a day</option>
                    @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                    <option value="{{ $day }}" {{ $reminder->weekly_day == $day ? 'selected' : '' }}>{{ $day }}</option>
                    @endforeach
                </select>
                @if ($errors->has('weekly_day'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('weekly_day') }}</span>
                @endif
            </div>
        </div>

        <div id="monthlyFields" class="row mb-5 mt-4 hidden">
            <label class="col-sm-3 col-form-label required">Monthly Date</label>
            <div class="col-sm-9">
                <select name="monthly_date" class="form-select form-control">
                    <option value="">Select a date</option>
                    @for ($i = 1; $i <= 31; $i++)
                        <option value="{{ $i }}" {{ $reminder->monthly_date == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                </select>
                @if ($errors->has('monthly_date'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('monthly_date') }}</span>
                @endif
            </div>
        </div>
        <div id="customDateField" class="row mb-5 mt-4 hidden">
            <label for="custom_date" class="col-sm-3 col-form-label required">Custom Date</label>
            <div class="col-sm-9">
                <input type="date" name="custom_date" id="custom_date" class="form-control" value="{{ $reminder->custom_date }}">
                @if ($errors->has('custom_date'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('custom_date') }}</span>
                @endif
            </div>
        </div>
          <!-- Show Assign to User field only if user is Super Admin or Manager -->
        @if (auth()->user()->role->name === 'Super Admin' || auth()->user()->role->name === 'Manager')
    <div class="row mb-5 mt-4">
        <label for="user_id" class="col-sm-3 col-form-label">Assign to User</label>
        <div class="col-sm-9">
            <select name="user_id" class="form-select form-control">
                <option value="{{ auth()->id() }}" {{ $reminder->user_id == auth()->id() ? 'selected' : '' }}>
                    {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                </option>

                @foreach (\App\Models\Users::where('status', 1)
                    ->whereNull('client_id')
                    ->where('id', '!=', auth()->id())
                    ->get() as $user)
                    <option value="{{ $user->id }}" {{ $reminder->user_id == $user->id ? 'selected' : '' }}>
                        {{ $user->first_name }} {{ $user->last_name }}
                    </option>
                @endforeach

            </select>
        </div>
    </div>
@endif

        <div class="row mb-5">
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
                <div id="editor" style="height: 300px; border: 1px solid #ccc; border-radius: 5px;">{{ $reminder->description }}</div>

                <!-- Hidden input that will hold the HTML description -->
                <input type="hidden" name="description" id="description-hidden">

                <!-- Error message -->
                @if ($errors->has('description'))
                <span style="font-size: 14px;" class="text-danger">{{ $errors->first('description') }}</span>
                @endif
            </div>
        </div>

        <div class="text-center margin-bottom">
            <button type="submit" class="btn btn-primary">Update Reminder</button>
        </div>
    </form>
</div>

<script>
    function toggleFields() {
        const type = document.getElementById('type').value;
        document.getElementById('weeklyFields').classList.add('hidden');
        document.getElementById('monthlyFields').classList.add('hidden');
        document.getElementById('customDateField').classList.add('hidden');
        if (type === 'weekly' || type === 'biweekly') {
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

        // Set old value from Laravel (escaped properly for JS)
        const oldContent = `{!! addslashes(old('description')) !!}`;
        if (oldContent && oldContent !== '') {
            quill.root.innerHTML = oldContent;
        }

        // Function to clean up the HTML content (remove empty <p> tags and <br> tags inside <p>)
        function cleanHtml(html) {
            const div = document.createElement('div');
            div.innerHTML = html;

            // Remove empty <p> tags and clean <br> tags
            const paragraphs = div.querySelectorAll('p');
            paragraphs.forEach(p => {
                if (!p.innerHTML.trim()) {
                    p.remove(); // Remove empty <p> tags
                } else {
                    // Remove <br> tags inside non-empty <p> tags
                    p.innerHTML = p.innerHTML.replace(/<br>/g, '');
                }
            });

            // Remove <p> tags entirely
            const content = div.innerHTML.trim().replace(/<\/?p>/g, '');

            return content;
        }


        // Sync Quill content to hidden input before form submit
        document.querySelector('form').addEventListener('submit', function() {
            let html = quill.root.innerHTML.trim();
            html = cleanHtml(html); // Clean up the HTML content
            document.getElementById('description-hidden').value = html;
        });
    });
</script>

@endsection
