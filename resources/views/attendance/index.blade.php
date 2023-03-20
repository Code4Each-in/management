@extends('layout')
@section('title', 'Attendance')
@section('subtitle', 'Attendance')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('attendance.store')}}">
                @csrf
                <div class="row mb-3 mt-4">
                    <div class="col-sm-2">
                        <select name="intime" class="form-select" id="intime">
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
                        <textarea name="notes" rows="1" class="form-control" id="notes"></textarea>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-primary" href="javascript:void(0)">ADD</button>
                    </div>
                </div>
            </form>
            @if(session()->has('message'))
            <div class="alert alert-success message">
                {{ session()->get('message') }}
            </div>
            @endif
            <div class="box-body table-responsive" style="margin-bottom: 5%">
                <table class="table table-borderless dashboard" id="attendance">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Date</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceData as $data)
                        <tr>
                            <td>{{ auth()->user()->first_name ?? " " }}</td>
                            <!-- <td>{{$data->created_at}}</td> -->
                            <td>{{date("d-m-Y H:s a", strtotime($data->created_at));}} </td>

                            <td>{{ date("h:s A", strtotime($data->in_time));}}</td>
                            <td>{{date("h:s A", strtotime( $data->out_time));}}</td>
                            <td>{{ $data->notes}}</td>
                            <td>
                                <i style="color:#4154f1;" onClick="editAttendance('{{ $data->id }}')"
                                    data-user-id="{{ $data->id}}" href="javascript:void(0)"
                                    class="fa fa-edit fa-fw pointer"></i>

                                <i style="color:#4154f1;" href="javascript:void(0)"
                                    class="fa fa-trash fa-fw pointer"></i>
                            </td>
                        </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="ShowAttendance" tabindex="-1" aria-labelledby="ShowAttendance" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row leaveUserContainer mt-2 ">
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <select name="intime" class="form-select" id="">
                                <option value="">In Time<span style="color:red">*</span></option>
                                @for ($i =1; $i <= 24; $i++) <option value="{{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00">
                                    {{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00</option>
                                    @endfor
                            </select>
                            <!-- @if ($errors->has('intime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('intime') }}</span>
                            @endif -->
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-12">
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
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <!-- <div class="col-sm-4 mb-2"> -->

                            <textarea name="notes" rows="4" col="3" class="form-control" id="edit_notes"
                                Placeholder="Notes"></textarea>

                            <!-- / </div> -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onClick="edit('{{ $data->id }}')"
                                    data-bs-dismiss="modal">Save</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});



// function edit(id) {
//     // alert("sdfd");
//     $.ajax({
//         type: "POST",
//         url: "{{ url('/edit/attendance') }}",
//         data: {
//             id: id
//         },
//         dataType: 'json',
//         success: function(res) {}
//     });
// }

// function openAttendanceModal() {


// }

function editAttendance(id) {
    $.ajax({
        type: "POST",
        url: "{{ url('/edit/attendance') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: (res) => {
            $('#ShowAttendance').modal('show');
            $('#edit_notes').val(res.attendance.notes);
            $('#intime option[value="' + res.attendance.id + '"]').attr('selected', 'selected');

            // if (res.intime != null) {
            //     $.each(res.intime, function(key, value) {
            //         $('#intime option[value="' + value.parent_user_id + '"]').attr(
            //             'selected', 'selected');
            //     })
            // }
        }
    });
}
</script>
@endsection