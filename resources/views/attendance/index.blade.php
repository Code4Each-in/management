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
                <option value="">-In Time-</option>
                @for ($i =1; $i <= 24; $i++) <option value="{{str_pad($i, 2, '0', STR_PAD_LEFT);}}">
                    {{str_pad($i, 2, '0', STR_PAD_LEFT);}}</option>
                    @endfor
            </select>
            @if ($errors->has('intime'))
            <span class="text-danger">{{ $errors->first('') }}</span>
            @endif
        </div>
        <div class="col-sm-2">
            <select name="outtime" class="form-select" id="">
                <option value="">-Out Time-</option>
                @for ($i =1; $i <= 24; $i++) <option value="{{str_pad($i, 2, '0', STR_PAD_LEFT);}}">
                    {{str_pad($i, 2, '0', STR_PAD_LEFT);}}</option>
                    @endfor
            </select>
            @if ($errors->has('outtime'))
            <span class="text-danger">{{ $errors->first('outtime') }}</span>
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
</div>
<hr>
<div class="box-body table-responsive" style="margin-bottom: 5%">
    <table class="table table-hover" id="attendance">
        <thead>
            <tr>
                <th>Name</th>
                <th>Date</th>
                <th>IntimeTime</th>
                <th>OutTime</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
</div>
</tbody>
</table>
</div>
</div>
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {

    $('#attendance').DataTable({
        "order": []
        //"columnDefs": [ { "orderable": false, "targets": 7 }]
    });

});
</script>
@endsection