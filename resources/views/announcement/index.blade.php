@extends('layout')
@section('title', 'Announcements')
@section('subtitle', 'Announcements')

@section('content')

<style>
    #announcementsTable thead {
        background: #f6f6fe;
    }
</style>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">

    <!-- Add Button -->
    <div class="mb-3">
        <a href="{{ route('announcement.create') }}" class="btn btn-primary mt-3" style="background:#4154f1;">
            Add Announcement
        </a>
    </div>

    <div class="table-responsive">
        <table id="announcementsTable" class="table table-borderless dashboard">
            <thead>
                <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Status</th>
                <th>End Date</th> 
                <th>Client Visible</th> 
                <th>Created</th>
                <th>Actions</th>
            </tr>
            </thead>

            <tbody>
                @foreach($announcements as $item)
                <tr>
                    <td>{{ $item->title }}</td>

                    <td style="max-width: 300px;">
                        {!! \Illuminate\Support\Str::limit(strip_tags($item->message), 80) !!}
                    </td>

                    <!-- Active Status -->
                    <td>
                        <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $item->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    <!-- ✅ End Date -->
                    <td>
                        @if($item->end_date)
                            {{ \Carbon\Carbon::parse($item->end_date)->format('Y-m-d') }}
                        @else
                            <span class="text-muted">No Expiry</span>
                        @endif
                    </td>

                    <!-- ✅ Show to Client -->
                    <td>
                        @if($item->show_to_client)
                            <span class="badge bg-primary">Yes</span>
                        @else
                            <span class="badge bg-light text-dark">No</span>
                        @endif
                    </td>

                    <!-- Created -->
                    <td>
                        {{ $item->created_at->timezone('Asia/Kolkata')->format('Y-m-d') }}
                    </td>

                    <!-- Actions -->
                    <td>
                        <a href="{{ route('announcement.edit', $item->id) }}" class="text-primary">
                            <i class="fa fa-edit fa-fw pointer"></i>
                        </a>

                        <a href="javascript:void(0)" onclick="deleteAnnouncement('{{ $item->id }}')" class="text-danger">
                            <i class="fa fa-trash fa-fw pointer"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
                </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    $('#announcementsTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "responsive": true,
        "language": {
            "emptyTable": "No announcements available"
        }
    });

});

function deleteAnnouncement(id) {
    if(confirm('Are you sure you want to delete this announcement?')) {
        fetch(`/announcement/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('Deleted successfully');
                location.reload();
            } else {
                alert('Failed to delete');
            }
        })
        .catch(() => {
            alert('Something went wrong');
        });
    }
}
</script>

@endsection