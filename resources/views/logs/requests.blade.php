@extends('layout')
@section('title', 'Project Logs Requests')
@section('content')
<div class="card">
<div class="card-body">

<table class="table table-bordered table-striped">

<thead>
<tr>
<th>Project</th>
<th>Client</th>
<th>Requested Action</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>

@forelse($requests as $request)

@php
$action = $request->requested_enabled ? 'Enable Logs' : 'Disable Logs';
@endphp

<tr>

<td>{{ $request->project->project_name ?? 'N/A' }}</td>

<td>{{ $request->user->first_name ?? 'Client' }}</td>

<td>
<span class="badge {{ $request->requested_enabled ? 'bg-success' : 'bg-danger' }}">
{{ $action }}
</span>
</td>
<td>
@if($request->request_status == 'approved')
    <span class="badge bg-success">Approved</span>

@elseif($request->request_status == 'rejected')
    <span class="badge bg-danger">Rejected</span>

@else
    <span class="badge bg-warning text-dark">Pending</span>
@endif
</td>

<td>

@if($request->request_status == 'pending')

<form action="{{ route('log.approve',$request->id) }}" method="POST" style="display:inline;">
@csrf
<button class="btn btn-sm btn-success">
Approve
</button>
</form>

<form action="{{ route('log.reject',$request->id) }}" method="POST" style="display:inline;">
@csrf
<button class="btn btn-sm btn-danger">
Reject
</button>
</form>

@else

<span class="text-muted" style="font-style: oblique; font-weight: bold;">Action Completed</span>

@endif

</td>

</tr>

@empty

<tr>
<td colspan="5" class="text-center">
No pending requests
</td>
</tr>

@endforelse

</tbody>

</table>

</div>
</div>

@endsection
