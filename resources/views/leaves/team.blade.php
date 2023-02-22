@extends('layout')
@section('title', 'Team Leaves')
@section('subtitle', 'Team Leaves')
@section('content')


<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <div class="box-header with-border" id="filter-box">
                <br>
                @if(session()->has('message'))
                <div class="alert alert-success message">

                    {{ session()->get('message') }}
                </div>
                @endif
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless" id="leavestable">
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
                            @forelse($teamLeaves as $data)
                            <tr>
                                <td>{{ $data->first_name}}</td>
                                <td>{{date("d-m-Y", strtotime($data->from));}}</td>
                                <td>{{date("d-m-Y", strtotime($data->to));}}</td>
                                <td>{{$data->type }}</td>
                                <td>{{$data->notes }}</td>
                                <td>
                                    <select style="width:150px;" user-leave-id="{{$data->id}}" name="leave_status"
                                        class="form-select leave_status" id="leave_status">
                                        <option value="">Leave status</option>
                                        <option value="requested"
                                            {{$data->leave_status == "requested"  ? 'selected' : ''}}>
                                            requested</option>
                                        <option value="approved"
                                            {{$data->leave_status == "approved"  ? 'selected' : ''}}>
                                            approved</option>
                                        <option value="decline" {{$data->leave_status ==  "decline" ? 'selected' : ''}}>
                                            decline</option>
                                    </select>
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

<!--end: Add department Modal -->
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

function addleaves() {

    $.ajax({
        type: 'POST',
        url: "{{ url('/add/leaves')}}",
        data: $('#addLeavesForm').serialize(),
        cache: false,
        success: (data) => {
            console.log(data);
            if (data.errors) {
                $('.alert-danger').html('');

                $.each(data.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                })
            } else {
                $('.alert-danger').html('');
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