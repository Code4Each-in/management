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
@if(session('reminder_notice'))
<div class="alert alert-success">
    {{ session('reminder_notice') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif
<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">

    <div class="max-w-6xl mx-auto mb-6">
        <a href="{{ route('reminder.index') }}" class="btn btn-primary mt-3" style="margin-bottom: 18px; background: #4154f1;">
            Add Reminder
        </a>
    </div>

    <div class="box-header with-border" id="filter-box">
        <br>
        <div class="box-body table-responsive" style="margin-bottom: 5%">
            <table id="remindersTable" class="table table-borderless dashboard">
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
                            @if(auth()->user()->role->name === 'Super Admin' || auth()->user()->role->name === 'Manager' || $reminder->user_id === auth()->id())
                            <a href="{{ route('reminders.edit', $reminder->id) }}" class="text-primary">
                                <i class="fa fa-edit fa-fw pointer"></i>
                            </a>
                            <a href="javascript:void(0)" onclick="deleteReminder('{{ $reminder->id }}')" class="text-primary">
                                <i class="fa fa-trash fa-fw pointer"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reminder deleted successfully');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to delete reminder');
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
