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
{{-- <div class="col-lg-6 dashboard ">
    <div class="card" style="height:730px;">
        <div class="card-body ">


            <div class="alert alert-success Commentmessage mt-4" style="display:none">

            </div>
            <h5 class="card-title">Comments</h5>
            <div class="news commentSection">
                <div class="comments">
                    @if(count($CommentsData) !=0)
                    @foreach ($CommentsData as $data)
                    <div class="row">
                        @if(!empty($data->user->profile_picture))
                        <div class="col-md-2 comment-user-profile">
                            <img src="{{asset('assets/img/').'/'.$data->user->profile_picture}}" class="rounded-circle " alt="">
                        </div>
                        @else
                        <img src="{{asset('assets/img/blankImage')}}" alt="Profile" class="rounded-circle">
                        @endif
                        <div class="col-md-3">
                            <p>{{$data->user->first_name}}</p>
                            <p>{{date("M d h:s a", strtotime($data->created_at));}}</p>

                        </div>
                        <div class="col-md-7 ">
                            {{$data->comments}}
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="center text-center mt-2 ">
                        <span class="center" id="NoComments"> No Comments </span>
                    </div>
                    @endif
                </div>
            </div>
            <form method="post" id="commentsData" action="{{route('comments.add')}}">
                <div class=" post-item clearfix mb-3 mt-3">
                    <textarea class="form-control comment nt-3" name="comment" id="comment" placeholder="Enter your comment" rows="3"></textarea>
                </div>
                <div class="alert alert-danger" style="display:none"></div>
                <input type="hidden" class="form-control" id="hidden_id" value="{{$tickets->id}}">
                <button type="submit" style="float: right;" class="btn btn-primary"><i class="bi bi-send-fill"></i>
                    Comment</button>
            </form>
        </div>
    </div>
</div> --}}
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