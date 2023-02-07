@extends('layout')
@section('title', 'Departments')
@section('subtitle', 'Departments')
@section('content')
<center>
    <h4>Attendance</h4>
</center>
<br>


<form method="post" action="{{ route('attendance.store')}}">
    @csrf
    <div class="row mb-3">
        <div class="col-sm-2">
            <select name="intime" class="form-select" id="">
                <option value="">In Time<span style="color:red">*</span></option>
                @for ($i =1; $i <= 24; $i++) <option value="{{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00">
                    {{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00</option>
                    @endfor
            </select>
            @if ($errors->has('intime'))
            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('intime') }}</span>
            @endif
        </div>
        <div class="col-sm-2">
            <select name="outtime" class="form-select" id="">
                <option value="">Out Time<span style="color:red">*</span></option>
                @for ($i =1; $i <= 24; $i++) <option value="{{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00">
                    {{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00</option>
                    @endfor
            </select>
            @if ($errors->has('outtime'))
            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('outtime') }}</span>
            @endif
        </div>
        <div class="col-sm-4">
            <textarea name="notes" rows="1" class="form-control" id=""></textarea>
        </div>


        <div class="col-sm-4">
            <button type="submit" class="btn btn-primary" href="javascript:void(0)">Attendance</button>
        </div>
    </div>
</form>
@if(session()->has('message'))
<div class="alert alert-success message">
    {{ session()->get('message') }}
</div>
@endif

</div>
<hr>
<div class="box-body table-responsive" style="margin-bottom: 5%">
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
            @forelse($attendanceData as $data)
            <tr>
                <td>{{ auth()->user()->first_name ?? " " }}</td>
                <!-- <td>{{$data->created_at}}</td> -->
                <td>{{date("d-m-Y H:s a", strtotime($data->created_at));}} </td>

                <td>{{ date("H:s a", strtotime($data->in_time));}}</td>
                <td>{{date("H:s a", strtotime( $data->out_time));}}</td>
                <td>{{ $data->notes}}</td>
            </tr>
            @empty
            @endforelse
</div>
</tbody>
</table>
</div>
</div>
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