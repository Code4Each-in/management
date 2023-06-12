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

            <form action="" id="intervalsFilterForm" method="get">
                <div class="row my-4">
                <div class="col-md-3 form-group">
                        <label for="intervalsFilterselectBox">Date Range</label>
                        <select class="form-control" id="intervalsFilterselectBox" name="intervals_filter">
                            <option value="" selected disabled>Select Date Range</option>
                            <option value="today" {{ request()->input('intervals_filter') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ request()->input('intervals_filter') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="last_week" {{ request()->input('intervals_filter') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="last_month" {{ request()->input('intervals_filter') == 'last_month' ? 'selected' : '' }} >Last Month</option>
                            <option value="custom_intervals" {{ request()->input('intervals_filter') == 'custom_intervals' ? 'selected' : '' }}>Custom Date Range</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="userFilterselectBox">Team Member</label>
                        <select class="form-control" id="userFilterselectBox" name="team_member_filter">
                            <option value="" selected disabled>Select TeamMember</option>
                            @foreach ($users as $user)
                            <option value="{{$user->id}}" {{ request()->input('team_member_filter') == $user->id ? 'selected' : '' }}>{{$user->first_name}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('date_from'))
                            <span style="font-size: 10px;" class="text-danger">{{ $errors->first('date_from') }}</span>
                            @endif
                    </div>
                    <div class="col">

                    </div>
                </div>
                <div class="row my-4">
                <div class="col-md-2 form-group custom-intervals" style = "{{ request()->input('intervals_filter') !== 'custom_intervals' ? 'display: none;' : '' }}">
                        <label for="date_from">Date From</label>
                        <input type="date" name="date_from" class="form-control custom-date"   value="{{ request()->input('intervals_filter') === 'custom_intervals' ? (request()->has('date_from') ? request()->input('date_from') : '') : '' }}">
                        @if ($errors->has('date_from'))
                            <span style="font-size: 10px;" class="text-danger">{{ $errors->first('date_from') }}</span>
                            @endif
                    </div>
                    <div class="col-md-2 form-group custom-intervals" style="{{ request()->input('intervals_filter') !== 'custom_intervals' ? 'display: none;' : '' }}">
                        <label for="date_to">Date To</label>
                        <input type="date" name="date_to" class="form-control custom-date"   value="{{ request()->input('intervals_filter') === 'custom_intervals' ? (request()->has('date_to') ? request()->input('date_to') : '') : '' }}">
                        @if ($errors->has('date_to'))
                            <span style="font-size: 10px;" class="text-danger">{{ $errors->first('date_to') }}</span>
                            @endif
                    </div>
                    <div class="col-md-2 form-group custom-intervals" style="{{ request()->input('intervals_filter') !== 'custom_intervals' ? 'display: none;' : '' }}">
                        <input type="submit" class="btn btn-primary custom-search" value="Search" style="margin-top: 19px;" id="searchIntervalButton">
                    </div>
                </div>
            </form>
                
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
                            <td>{{ html_entity_decode(strip_tags($data->notes)) }}</td>
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
                            <label for="edit_intime" class="required">In Time:</label>
                            <input type="time" id="edit_intime" class="form-control" name="edit_intime">
                            @if ($errors->has('edit_intime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_intime') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <label for="edit_outtime" class="required">Out Time:</label>
                            <input type="time" id="edit_outtime" class="form-control" name="edit_outtime">
                            @if ($errors->has('edit_outtime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_outtime') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <!-- <div class="col-sm-4 mb-2"> -->
                            <label for="tinymce_textarea">Notes:</label>
                            <textarea name="notes" rows="4" col="3" class="form-control" id="tinymce_textarea"></textarea>
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
                $(tinymce.get('tinymce_textarea').getBody()).html(res.attendance.notes);
                // $('#tinymce_textarea').val(res.attendance.notes);
                // tinymce.get('tinymce_textarea').getBody().innerHTML = '<p>This is my new content!</p>';

                $('#edit_intime').val(res.attendance.in_time);
                $('#edit_outtime').val(res.attendance.out_time);
            }
        });
    }

    function edit(id) {
        var AttendanceId = $('#attendance_id').val();
        var InTime = $('#edit_intime').val();
        var outTime = $('#edit_outtime').val();
        var notes = tinyMCE.get('tinymce_textarea').getContent();
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
    var intervalsFilterselectBox = document.getElementById('intervalsFilterselectBox');
    var customIntervalsOption = document.querySelector('option[value="custom_intervals"]');
    var customIntervalsSection = document.querySelectorAll('.custom-intervals');

    intervalsFilterselectBox.addEventListener('change', function() {
        if (this.value === customIntervalsOption.value) {
            for (var i = 0; i < customIntervalsSection.length; i++) {
                customIntervalsSection[i].style.display = 'block';
            }
        } else {
            for (var i = 0; i < customIntervalsSection.length; i++) {
                customIntervalsSection[i].style.display = 'none';
            }
        }
        // Submit the form when any select option is changed
        document.getElementById('intervalsFilterForm').submit();

    });

    //Submit form on change the value of Team Member
    document.getElementById("userFilterselectBox").addEventListener("change", function() {
        document.getElementById("intervalsFilterForm").submit();
    });

    function checkFormCompletion() {
        var dateFrom = document.getElementById("date_from").value;
        var dateTo = document.getElementById("date_to").value;
        var submitButton = document.getElementById("searchIntervalButton");

        if (dateFrom && dateTo) {
            submitButton.disabled = false;
        } else {
            submitButton.disabled = true;
        }

        // Remove name attribute if the value is empty
        document.getElementById("dateFrom").name = dateFrom ? "date_from" : "";
        document.getElementById("dateTo").name = dateTo ? "date_to" : "";
    }


</script>
@endsection