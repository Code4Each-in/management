@extends('layout')
@section('title', 'Edit Assigned Devices')
@section('subtitle', 'Edit Assigned Devices')
@section('content')

<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-lg-12 ">
    <div class="card">
        <div class="card-body mt-4">
           
            <form method="post" action="{{route('devices.assigned.update', $assignedDevice->id)}}" enctype="multipart/form-data">
                @csrf
                            <!-- <div class="alert alert-danger" style="display:none"></div> -->
                            <div class="row mb-3">
                                <label for="edit_device_id" class="col-sm-3 col-form-label required">Edit Device</label>
                                <div class="col-sm-9">
                                <select name="edit_device_id" class="form-select form-control" id="edit_device_id">
                                  <option value="">Select Device</option>
                                  @if (!empty($freeDevices))
                                        <optgroup label="Free Devices">
                                            @foreach ($freeDevices as $data)
                                                <option value="{{ $data->id }}" {{ $data->id == $assignedDevice->device_id ? 'selected' : '' }}>
                                                    {{ $data->name }} - {{ $data->device_model }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    
                                    @if (!empty($inUseDevices))
                                        <optgroup label="In Use Devices">
                                            @foreach ($inUseDevices as $data)
                                                <option value="{{ $data->id }}" {{ $data->id == $assignedDevice->device_id ? 'selected' : 'disabled' }}>
                                                    {{ $data->name }} - {{ $data->device_model }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="edit_user_id" class="col-sm-3 col-form-label required" >Edit Assign To</label>
                                <div class="col-sm-9">
                                <select name="edit_user_id" class="form-select form-control" id="edit_user_id">
                                    <option value="">Select User</option>
                                        @foreach ($users as $data)
                                        <option value="{{$data->id}}" {{$data->id == $assignedDevice->user_id  ? 'selected' : ''}}>
                                            {{$data->first_name.' '.$data->last_name}} - {{$data->department->name ?? ''}}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="edit_assigned_from" class="col-sm-3 col-form-label required">Edit Assigned From</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" name="edit_assigned_from" id="edit_assigned_from" value="{{$assignedDevice->from}}">
                                </div>
                                @if ($errors->has('edit_assigned_from'))
                                <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_assigned_from') }}</span>
                                @endif
                            </div>

                            <div class="row mb-3">
                                <label for="edit_assigned_to" class="col-sm-3 col-form-label"> Edit Assigned To</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" name="edit_assigned_to" id="edit_assigned_to" value="{{$assignedDevice->to}}">
                                </div>
                                @if ($errors->has('edit_assigned_to'))
                                <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_assigned_to') }}</span>
                                @endif
                            </div>

                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
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

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    function deleteTicketAssign(id) {
        var TicketId = $('#hidden_id').val();
        if (confirm("Are you sure ?") == true) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/delete/ticket')}}",
                data: {
                    id: id,
                    TicketId: TicketId,
                },
                success: (data) => {
                    location.reload();

                    // if (data.user != null) {
                    //     $('#edit_assign').find('option').remove().end();
                    //     $.each(data.user, function(key, value) {
                    //         $('#edit_assign').append('<option value="' + value.id + '">' + value
                    //             .first_name + '</option>');
                    //     });
                    // }
                    // if (data.AssignData.length == 0) {

                    //     $('#Ticketsdata').hide();
                    // }
                }

            });
        }
    }

    function deleteUploadedFile(id) {
        var TicketId = $('#hidden_id').val();
        if (confirm("Are you sure ?") == true) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/delete/ticket/file')}}",
                data: {
                    id: id,
                    TicketId: TicketId,
                },
                success: (data) => {
                    location.reload();
                }

            });
        }

    }

</script>

@endsection