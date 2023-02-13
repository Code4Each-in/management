@extends('layout')
@section('title', 'Team Attendance')
@section('subtitle', 'Team Attendance')
@section('content')

@if(session()->has('message'))
<div class="alert alert-success message">
    {{ session()->get('message') }}
</div>
@endif
<div class="box-body table-responsive mt-3" style="margin-bottom: 5%">
    <table class="table table-hover" id="attendance">
        <thead>
            <tr>
                <th>Name</th>
                <th>Date</th>
                <th>In Time</th>
                <th>Out Time</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teamAttendance as $data)
            <tr>
                <td>{{ $data->first_name}}</td>
                <!-- <td>{{$data->created_at}}</td> -->
                <td>{{date("d-m-Y H:s a", strtotime($data->created_at));}} </td>
                <td>{{ date("h:s A", strtotime($data->in_time));}}</td>
                <td>{{date("h:s A", strtotime( $data->out_time));}}</td>
                <td>{{ $data->notes}}</td>
            </tr>
            @empty
            @endforelse
</div>
</tbody>
</table>
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('.message').fadeOut("slow");
    }, 2000);
    $('#attendance').DataTable({
        "order": []
        //"columnDefs": [ { "orderable": false, "targets": 7 }]
    });
});
</script>
@endsection