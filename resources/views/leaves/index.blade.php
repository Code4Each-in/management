@extends('layout')
@section('title', 'My Leaves')
@section('subtitle', 'My Leaves')
@section('content')
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openleavesModal()" href="javascript:void(0)">Add
                Leave</button>
            <div class="box-header with-border" id="filter-box">
                <br>
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless datatable" id="leavestable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Type</th>
                                <th>Notes</th>
                                <th>Half Day</th>
                                <th>Day Count</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leavesData as $data)
                            <tr>
                                <td>{{ auth()->user()->first_name ?? " " }}</td>
                                <td>{{date("d-m-Y", strtotime($data->from));}}</td>
                                <td>{{date("d-m-Y", strtotime($data->to));}}</td>
                                <td>{{$data->type }}</td>
                                <td>{{$data->notes ?? '---'}}</td>
                                <td class="text-center">{{$data->half_day ?? '---' }}</td>
                                <td>{{$data->leave_day_count ?? '---' }}</td>
                                @if($data->leave_status == 'approved')
                                <td><span class="badge rounded-pill approved">Approved</span></td>
                                @elseif($data->leave_status == 'declined')
                                <td><span class="badge rounded-pill denied">Declined</span></td>
                                @else
                                <td><span class="badge rounded-pill requested">Requested</span></td>
                                @endif
                            </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!--start: Add users Modal -->
<div class="modal fade" id="addleaves" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addLeavesForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="from" class="col-sm-3 col-form-label required">From</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control text-dark" name="from" id="from" min="{{ date('Y-m-d') }}">
                        </div>
                        @if ($errors->has('from'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('from') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="to" class="col-sm-3 col-form-label required">To</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control text-dark" name="to" id="to" min="{{ date('Y-m-d') }}">
                        </div>
                        @if ($errors->has('to'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('to') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3" id="halfDayDiv" style="display: none;">
                        <label for="leaveType" class="col-sm-3 col-form-label ">Leave Type</label>
                        <div class="col-sm-9">
                            <!-- <input type="checkbox" class="form-check-input" id="is_halfday" name="is_halfday"> -->
                            <select name="leaveType" class="form-select" id="leaveType">
                                <option value="">-- Select Leave Type --</option>
                                <option value="first_half">First Half</option>
                                <option value="second_half">Second Half</option>
                                <option value="short_leave">Short Leave</option>
                            </select>
                        </div>
                        @if ($errors->has('leaveType'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('leaveType') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3" id="shortLeaveTimeDiv" style="display: none;">
                        <label class="col-sm-3 col-form-label required">Time (From - To)</label>
                        <div class="col-sm-4">
                            <input type="time" name="short_leave_from" id="short_leave_from" class="form-control text-dark" step="900">
                        </div>
                        <div class="col-sm-4">
                            <input type="time" name="short_leave_to" id="short_leave_to" class="form-control" step="900" readonly>
                        </div>
                        <span id="shortLeaveError" class="text-danger" style="font-size: 12px; display: none;"></span>
                    </div>

                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label ">Type</label>
                        <div class="col-sm-9">
                            <select name="type" class="form-select" id="type">
                                <option value="">-- Select type --</option>
                                <option value="urgent_work">Urgent Work</option>
                                <option value="sick_leave">Sick Leave</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="notes" class="col-sm-3 col-form-label">Notes</label>
                        <div class="col-sm-9">
                            <textarea name="notes" class="form-control" id="notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onClick="addleaves(this)"
                            href="javascript:void(0)">Add Leave</button>
                    </div>
            </form>
        </div>
    </div>
</div>
</div>
<!--end: Add department Modal -->
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    $('#leaveType').on('change', function() {
        if ($(this).val() === 'short_leave') {
            $('#shortLeaveTimeDiv').show();
        } else {
            $('#shortLeaveTimeDiv').hide();
            $('#shortLeaveError').hide();
        }
    });

    $('#short_leave_from').on('change', function () {
        let from = $(this).val();

        if (from) {
            let fromTime = new Date(`1970-01-01T${from}`);
            let toTime = new Date(fromTime.getTime() + 2 * 60 * 60 * 1000); // +2 hours

            // Format the new time to HH:MM
            let toHours = String(toTime.getHours()).padStart(2, '0');
            let toMinutes = String(toTime.getMinutes()).padStart(2, '0');
            let toFormatted = `${toHours}:${toMinutes}`;

            $('#short_leave_to').val(toFormatted);
            $('#shortLeaveError').hide();
        } else {
            $('#short_leave_to').val('');
            $('#shortLeaveError').show();
        }
    });

    setTimeout(function() {
        $('.message').fadeOut("slow");
    }, 2000);
    $(".is_approved").change(function() {
        var LeavesId = $(this).attr('user-leave-id');
        var LeavesStatus = 0;
        if ($(this).is(':checked') == true) {
            LeavesStatus = 1;
        }
        // alert(LeavesStatus);
        $.ajax({
            type: "POST",
            url: "{{ url('/update/leaves') }}",
            data: {
                LeavesId: LeavesId,
                LeavesStatus: LeavesStatus,
            },
            dataType: 'json',
            success: function(res) {
                if (res.errors) {

                } else {

                    location.reload();
                }
            }
        });
    });

    $('#leavestable').DataTable({
        "order": []

    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

});

function openleavesModal() {

    $('.alert-danger').html('');
    $('#from').val('');
    $('#addleaves').modal('show');
    $(document).ready(function() {
        // Listen for changes in the date inputs
        $('#from, #to').on('change', function() {
            const fromDate = $('#from').val();
            const toDate = $('#to').val();
            
            // Compare the dates and update the "Half Day" checkbox visibility
            const isLeaveType = $('#halfDayDiv');
            if (fromDate === toDate) {
                isLeaveType.show();
            } else {
                isLeaveType.hide();
            }
        });
    });
    
}


function addleaves() {
  var spinner = $('#loader');
  spinner.show();


    function updateTotalDays() {
    const fromDateStr = $('#from').val();
    const toDateStr = $('#to').val();
    const LeaveType = $('#leaveType').val();
    
    // Convert the date strings to Date objects
    const fromDate = new Date(fromDateStr);
    const toDate = new Date(toDateStr);

    // Check if the dates are valid
    if (isNaN(fromDate) || isNaN(toDate)) {
        console.error('Invalid date format');
        return null;
    }

    // Calculate the difference in milliseconds
    const diffInMilliseconds = toDate - fromDate;
    
    // Calculate the total days
    let totalDays = diffInMilliseconds / (1000 * 60 * 60 * 24);

    // Check for half-day or short leave
    if (LeaveType !== "" && totalDays === 0) {
        if (LeaveType === "short_leave") {
            totalDays = 0.25;
        } else {
            totalDays = 0.5;
        }
    } else {
        totalDays += 1;
    }

    return totalDays;
    }

    // Call the function to get the total days
    const totalDays = updateTotalDays();

    // Prepare the data object manually
    const data = {
    from: $('#from').val(),
    to: $('#to').val(),
    leave_type:  $('#leaveType').val(),
    total_days: totalDays,
    type : $('#type').val(),
    notes : $('#notes').val(),
    time_form : $('#short_leave_from').val(),
    time_to : $('#short_leave_to').val()
    };

  $.ajax({
    url: "{{ url('/add/leaves')}}",
    data: data, // Send the data object
    method: 'POST',
    dataType: 'JSON',
    cache: false,
    success: function(data) {
      // Introduce a delay before hiding the spinner
      setTimeout(function() {
        spinner.hide();

        if (data.errors) {
          $('.alert-danger').html('');
          $.each(data.errors, function(key, value) {
            $('.alert-danger').show();
            $('.alert-danger').append('<li>' + value + '</li>');
          });
        } else {
          $('.alert-danger').html('');
          $("#addleaves").modal('hide');
          location.reload();
        }
      }, 3000); // Adjust the duration (in milliseconds) as needed
    },
    error: function(data) {
      spinner.hide();
      console.log(data);
    }
  });
}

</script>

@endsection