@extends('layout')
@section('title', 'Client Access Requests')
@section('subtitle', 'Manage Requests')

@section('content')
<div class="card">
    <div class="card-body pb-4">
        @if($requests->isEmpty())
            <div class="text-center text-muted py-5">
                <h5>No access requests found.</h5>
            </div>
        @else
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Sr No</th>
                    <th>Client Name</th>
                    <th>Email</th>
                    <th>Requested At</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $index => $request)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $request->user->first_name }} {{ $request->user->last_name }}</td>
                    <td>{{ $request->user->email }}</td>
                    <td>{{ $request->created_at->setTimezone('Asia/Kolkata')->format('d M Y h:i A') }}</td>
                    <td>
                        @if($request->is_approved)
                            <span class="badge bg-success">Approved</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            @if(!$request->is_approved)
                                <form method="POST" action="{{ route('client-access-requests.approve', $request->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('client-access-requests.destroy', $request->id) }}" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
