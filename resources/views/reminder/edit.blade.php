@extends('layout')
@section('title', 'Edit Reminder')
@section('subtitle', 'Edit Reminder')
@section('content')
<style>
    .desc_class {
        display: flex;
        align-items: center;
    }
    .hidden {
        display: none;
    }
</style>

<div class="max-w-xl mx-auto bg-white p-6 rounded shadow ">
    <form action="{{ route('reminders.update', $reminder->id) }}" method="POST" class="margin-up">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="type" class="block font-semibold mb-1">Reminder Type</label>
            <select name="type" id="type" class="w-full border rounded p-2" onchange="toggleFields()">
                <option value="">Select a reminder type</option>
                <option value="daily" {{ $reminder->type == 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ $reminder->type == 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ $reminder->type == 'monthly' ? 'selected' : '' }}>Monthly</option>
            </select>
        </div>

        <div id="weeklyFields" class="mb-4 hidden">
            <label class="block mb-1">Weekly Day</label>
            <select name="weekly_day" class="w-full border p-2 rounded mb-2">
                <option value="" {{ $reminder->weekly_day == '' ? 'selected' : '' }}>Select a day</option>
                @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                <option value="{{ $day }}" {{ $reminder->weekly_day == $day ? 'selected' : '' }}>{{ $day }}</option>
                @endforeach
            </select>
        </div>

        <div id="monthlyFields" class="mb-4 hidden">
            <label class="block mb-1">Monthly Date</label>
            <select name="monthly_date" class="w-full border p-2 rounded mb-2">
                <option value="">Select a date</option>
                @for ($i = 1; $i <= 31; $i++)
                    <option value="{{ $i }}" {{ $reminder->monthly_date == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </div>

        <div class="mb-4 desc_class">
            <label for="description" class="block font-semibold mb-1">Description</label>
            <textarea name="description" id="description" class="w-full border p-2 rounded" rows="3">{{ $reminder->description }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Reminder</button>
    </form>
</div>

<script>
    function toggleFields() {
        const type = document.getElementById('type').value;
        document.getElementById('weeklyFields').classList.add('hidden');
        document.getElementById('monthlyFields').classList.add('hidden');
        if (type === 'weekly') {
            document.getElementById('weeklyFields').classList.remove('hidden');
        } else if (type === 'monthly') {
            document.getElementById('monthlyFields').classList.remove('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();
    });
</script>

@endsection
