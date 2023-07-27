@extends('layout')
@section('title', 'Team Leaves')
@section('subtitle', 'Team Leaves')
@section('content')
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
        <button class="btn btn-primary mt-3" onClick="openleavesModal()" href="javascript:void(0)">Add Team's
                Leave</button>
            <div class="box-header with-border" id="filter-box">
                <br>
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless" id="leavestable">
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
                            @forelse($teamLeaves as $data)
                            <tr>
                                <td>{{ $data->first_name}}</td>
                                <td>{{date("d-m-Y", strtotime($data->from));}}</td>
                                <td>{{date("d-m-Y", strtotime($data->to));}}</td>
                                <td>{{$data->type }}</td>
                                <td>{{$data->notes ?? '---' }}</td>
                                <td class="text-center">{{$data->half_day ?? '---' }}</td>
                                <td>{{$data->leave_day_count ?? '---' }}</td>
                                <td>
                                     @php
                                    $leaveStatusData = $leaveStatus->where('leave_id', $data->id)->first();
                                    @endphp
                                    @if ($data->to >= date('Y-m-d'))
                                    <select style="width:150px;" user-leave-id="{{$data->id}}" name="leave_status"
                                        class="form-select leave_status" id="leave_status">
                                        <option value="requested"
                                            {{$data->leave_status == "requested"  ? 'selected' : ''}}>
                                            requested</option>
                                        <option value="approved"
                                            {{$data->leave_status == "approved"  ? 'selected' : ''}}>
                                            approved</option>
                                        <option value="declined" {{$data->leave_status ==  "declined" ? 'selected' : ''}}>
                                            declined</option>
                                    </select>
                                    @elseif ($data->leave_status == 'approved')
                                        <span class="badge rounded-pill approved">Approved</span>
                                    @elseif($data->leave_status == 'declined')
                                        <span class="badge rounded-pill denied">Declined</span>
                                    @else
                                        <span class="badge rounded-pill requested">Requested</span>
                                    @endif   
                                    @if (!empty($leaveStatusData))
                                    <p class="small mt-1" style="font-size: 11px;font-weight:600; margin-left:6px;">  By: {{ $leaveStatusData->first_name ?? '' }} </p>
                                    @endif
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
</div>

<!--start: Add Team's leaves Modal -->
<div class="modal fade" id="addteamsleave" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Team's Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    
                    <div class="row mb-3">
                        <label for="member_id" class="col-sm-3 col-form-label required">Team Member</label>
                        <div class="col-sm-9">
                        <select class="form-select form-control" id="member_id" name="member_id" data-placeholder="Select Member">
                                <option value="0" >Select Member</option>
                                         @foreach ($members as $member)
                                        <option value="{{$member->id}}">
                                         {{$member->first_name ?? ''}} {{$member->last_name ?? ''}}
                                        </option>
                                        @endforeach
                                </select>
                        </div>
                    </div>


                    <div class="row mb-3">
                        <label for="from" class="col-sm-3 col-form-label required">From</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="from" id="from">
                        </div>
                        @if ($errors->has('from'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('from') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="to" class="col-sm-3 col-form-label required">To</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="to" id="to">
                        </div>
                        @if ($errors->has('to'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('to') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3" id="halfDayDiv" style="display: none;">
                        <label for="halfday" class="col-sm-3 col-form-label ">Half Day</label>
                        <div class="col-sm-9">
                            <!-- <input type="checkbox" class="form-check-input" id="is_halfday" name="is_halfday"> -->
                            <select name="halfday" class="form-select" id="halfday">
                                <option value="">-- Select Half Day --</option>
                                <option value="first_half">First Half</option>
                                <option value="second_half">Second Half</option>
                            </select>
                        </div>
                        @if ($errors->has('halfday'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('halfday') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label required">Type</label>
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
                            href="javascript:void(0)">Add Team's Leave</button>
                    </div>
            </form>
        </div>
    </div>
</div>
</div>
<!--end: Add Team's Leaves Modal -->



@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('.message').fadeOut("slow");
    }, 2000);
    $(".leave_status").change(function() {
        var LeavesId = $(this).attr('user-leave-id');
        var LeavesStatus = $(this).children("option:selected").val();
        $.ajax({
            type: "POST",
            url: "{{ url('/update/leaves') }}",
            data: {
                LeavesId: LeavesId,
                LeavesStatus: LeavesStatus,
            },
            dataType: 'json',
            success: function(res) {
                if (res.errors) {} else {
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
$('#addteamsleave').modal('show');
$(document).ready(function() {
    // Listen for changes in the date inputs
    $('#from, #to').on('change', function() {
        const fromDate = $('#from').val();
        const toDate = $('#to').val();
        
        // Compare the dates and update the "Half Day" checkbox visibility
        const isHalfDay = $('#halfDayDiv');
        if (fromDate === toDate) {
            isHalfDay.show();
        } else {
            isHalfDay.hide();
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
    const HalfDay = $('#halfday').val();
    
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

    // Check if half day should be considered
    if (HalfDay != ""  && totalDays === 0) {
        totalDays = 0.5;
    }else{
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
    half_day:  $('#halfday').val(),
    total_days: totalDays,
    type : $('#type').val(),
    notes : $('#notes').val(),
    member_id : $('#member_id').val(),
    };
  $.ajax({
    url: "{{ url('/leaves/team/add')}}",
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
          $("#addteamsleave").modal('hide');
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