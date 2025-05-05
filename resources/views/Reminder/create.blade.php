@extends('layout')

@section('title', 'Reminders List')
@section('subtitle', 'Reminders List')
@section('content')

<style>
   #remindersTable thead {
    background: #f6f6fe;
}
tr.odd {
    background-color: transparent;
}
</style>

<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">

    <div class="max-w-6xl mx-auto mb-6">
        <a href="{{ route('reminder.indexing') }}" class="btn btn-primary mt-3" style="margin-bottom: 18px;">
            Add Reminder
        </a>
    </div>
    <table id="remindersTable" class="display w-full">
        <thead>
            <tr>
                <th>Type</th>
                <th>Weekly Day</th>
                <th>Monthly Date</th>
                <th>Description</th>
                <th>Created At</th>
                <th>Reminder next run</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reminders as $reminder)
            <tr>
                <td>{{ ucfirst($reminder->type) }}</td>
                <td>{{ $reminder->type === 'weekly' ? $reminder->weekly_day : '-' }}</td>
                <td>{{ $reminder->type === 'monthly' ? $reminder->monthly_date : '-' }}</td>
                <td>{{ $reminder->description }}</td>
                <td>{{ $reminder->created_at->timezone('Asia/Kolkata')->format('Y-m-d') }}</td>
                <td>{{ $reminder->reminder_date->timezone('Asia/Kolkata')->format('Y-m-d') }}</td>
                <td>
                    <a href="{{ route('reminders.edit', $reminder->id) }}" class="text-primary">
                        <i class="fa fa-edit fa-fw pointer"></i>
                    </a>
                    <a href="javascript:void(0)" onclick="deleteReminder('{{ $reminder->id }}')" class="text-primary">
                        <i class="fa fa-trash fa-fw pointer"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#remindersTable').DataTable();
    });

    function deleteReminder(id) {
        if (confirm('Are you sure you want to delete this reminder?')) {
            fetch(`/reminder/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reminder deleted successfully');
                    location.reload();
                } else {
                    alert('Failed to delete reminder');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the reminder');
            });
        }
    }
</script>

@endsection
