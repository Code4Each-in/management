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
                            <th>Worked Hours</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teamAttendance as $data)
                        <tr>
                            <td>{{ $data->first_name}}</td>
                            <!-- <td>{{$data->created_at}}</td> -->
                            <td>{{date("d-m-Y H:i a", strtotime($data->created_at));}} </td>
                            <td>{{ date("h:i A", strtotime($data->in_time));}}</td>
                            <td>{{date("h:i A", strtotime( $data->out_time));}}</td>
                            <td>
                                @php
                                $inTime = new DateTime($data->in_time);
                                $outTime = new DateTime($data->out_time);

                                $duration = $inTime->diff($outTime)->format('%h:%i');

                                echo $duration;
                                @endphp
                            </td>
                            <td>{{ $data->notes}}</td>
                            <td>
                                <i style="color:#4154f1;" onClick="editAttendance ('{{ $data->id }}')" data-user-id="{{ $data->id}}" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>

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
                            <label for="edit_intime">In Time:</label>
                            <input type="time" id="edit_intime" class="form-control" name="edit_intime">
                            @if ($errors->has('edit_intime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_intime') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <label for="edit_outtime">Out Time:</label>
                            <input type="time" id="edit_outtime" class="form-control" name="edit_outtime">
                            @if ($errors->has('edit_outtime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_outtime') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <!-- <div class="col-sm-4 mb-2"> -->
                            <textarea name="notes" rows="4" col="3" class="form-control" id="edit_notes" Placeholder="Notes"></textarea>
                            <!-- / </div> -->
                            <div class="modal-footer">
                                <input type="hidden" class="form-control" name="attendance_id" id="attendance_id" value="">
                                <button type="button" class="btn btn-primary" onClick="edit()" data-bs-dismiss="modal">Save</button>
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
                $('#edit_intime').val(res.attendance.in_time);
                $('#edit_outtime').val(res.attendance.out_time);
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
                edit_intime: InTime,
                edit_outtime: outTime,
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