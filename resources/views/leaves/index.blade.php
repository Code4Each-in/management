@extends('layout')
@section('title', 'My Leaves')
@section('subtitle', 'My Leaves')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openleavesModal()" href="javascript:void(0)">ADD
                LEAVES</button>
            <div class="box-header with-border" id="filter-box">
                <br>
                @if(session()->has('message'))
                <div class="alert alert-success message">
                    {{ session()->get('message') }}
                </div>
                @endif
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless datatable" id="leavestable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Type</th>
                                <th>Notes</th>
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
                                <td>{{$data->notes }}</td>
                                @if($data->leave_status == 'approved')
                                <td><span class="badge rounded-pill approved">Approved</span></td>
                                @elseif($data->leave_status == 'decline')
                                <td><span class="badge rounded-pill denied">Decline</span></td>
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
                <h5 class="modal-title" id="role">Add Leaves</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addLeavesForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="user_name" class="col-sm-3 col-form-label required">From</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="from" id="from">
                        </div>
                        @if ($errors->has('in_time'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('from') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="last_name" class="col-sm-3 col-form-label required">To</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="to" id="to">
                        </div>
                        @if ($errors->has('in_time'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('to') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label ">Type</label>
                        <div class="col-sm-9">
                            <select name="type" class="form-select" id="type">
                                <option value="">-- Select type --</option>
                                <option value="urgentWork">Urgent work</option>
                                <option value="sickLeave">Sick Leave</option>
                                <option value="">
                                </option>
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
                            href="javascript:void(0)">Save</button>
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
}

function addleaves() {
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/leaves')}}",
        data: $('#addLeavesForm').serialize(),
        cache: false,
        success: (data) => {

            if (data.errors) {
                $('.alert-danger').html('');
                $.each(data.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                })
            } else {
                $('.alert-danger').html('');

                $("#addleaves").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}
</script>
@endsection