@extends('layout')
@section('title', 'Team Attendance')
@section('subtitle', 'Team Attendance')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            @if(session()->has('message'))
            <div class="alert alert-success message">
                {{ session()->get('message') }}
            </div>
            @endif
            <div class="box-body table-responsive mt-3" style="margin-bottom: 5%">
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
                        @forelse($teamAttendance as $data)
                        <tr>
                            <td>{{ $data->first_name}}</td>
                            <!-- <td>{{$data->created_at}}</td> -->
                            <td>{{date("d-m-Y H:s a", strtotime($data->created_at));}} </td>
                            <td>{{ date("h:s A", strtotime($data->in_time));}}</td>
                            <td>{{date("h:s A", strtotime( $data->out_time));}}</td>
                            <td>{{ $data->notes}}</td>
                            <td>
                                <i style="color:#4154f1;" onClick="editAttendance ('{{ $data->id }}')"
                                    data-user-id="{{ $data->id}}" href="javascript:void(0)"
                                    class="fa fa-edit fa-fw pointer"></i>

                                <!-- <i style="color:#4154f1;" onClick="deleteAttendance('{{ $data->id }}')"
                                    href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i> -->
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
                            <select name="edit_intime" class="form-select" id="edit_intime">
                                <option value="">In Time<span style="color:red">*</span></option>
                                @for ($i =1; $i <= 24; $i++) <option value="{{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00">
                                    {{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00</option>
                                    @endfor
                            </select>
                            @if ($errors->has('edit_intime'))
                            <span style="font-size: 12px;"
                                class="text-danger">{{ $errors->first('edit_intime') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <select name="edit_outtime" class="form-select" id="edit_outtime">
                                <option value="">Out Time<span style="color:red">*</span></option>
                                @for ($i =1; $i <= 24; $i++) <option value="{{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00">
                                    {{str_pad($i, 2, '0', STR_PAD_LEFT);}}:00</option>
                                    @endfor
                            </select>
                            @if ($errors->has('edit_outtime'))
                            <span style="font-size: 12px;"
                                class="text-danger">{{ $errors->first('edit_outtime') }}</span>
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
                                <input type="hidden" class="form-control" name="attendance_id" id="attendance_id"
                                    value="">
                                <button type="button" class="btn btn-primary" onClick="edit()"
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

function openAttendanceModal() {}

function editAttendance(id) {
    $('#attendance_id').val(id);

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
            var InTime = res.attendance.in_time
            var InTimeData = InTime.split(":", 2).join(":");
            $('#edit_intime option[value="' + InTimeData + '"]').attr('selected',
                'selected');
            var OutTime = res.attendance.out_time
            var OutTimeData = OutTime.split(":", 2).join(":");

            $('#edit_outtime option[value="' + OutTimeData + '"]').attr('selected',
                'selected');
        }
    });
}

function edit(id) {
    var AttendanceId = $('#attendance_id').val();
    var InTime = $('#edit_intime').val();
    var outTime = $('#edit_outtime').val();
    var notes = $('#edit_notes').val();
    $.ajax({
        type: "POST",
        url: "{{ url('/update/attendance') }}",
        data: {
            id: AttendanceId,
            InTime: InTime,
            outTime: outTime,
            notes: notes,

        },
        dataType: 'json',
        success: function(res) {
            location.reload();
        }
    });
}

// function deleteAttendance(id) {
//     if (confirm("Are you sure ?") == true) {
//         $.ajax({
//             type: "DELETE",
//             url: "{{ url('/delete/attendance') }}",
//             data: {
//                 id: id
//             },
//             dataType: 'json',
//             success: function(res) {
//                 location.reload();
//             }
//         });
//     }
// }
</script>
@endsection