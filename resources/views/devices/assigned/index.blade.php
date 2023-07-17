@extends('layout')
@section('title', 'Assigned Devices')
@section('subtitle', 'Assigned Devices')
@section('content')
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <button class="btn btn-primary mt-3" onClick="openassignDeviceModel()" href="javascript:void(0)">Assign Device</button>
                </div>
            </div>

             <form id="filter-data" method="GET" action="{{ route('devices.assigned.index') }}">
                <div class="row mt-3 mx-auto">
                    <div class="col-md-6 filtersContainer d-flex p-0">
                        <div style="margin-right:20px;">
                            
                            <input type="checkbox" class="form-check-input" name="all_devices" id="all_devices"
                                {{ $allDevicesFilter == 'on' ? 'checked' : '' }}>  
                                <label for="all_devices">All </label>
                        </div>
                        
                    </div>
                </div>
            </form> 

            <div class="box-header with-border" id="filter-box">
                <br>
                <!-- filter -->
                <div class="box-header with-border mt-4" id="filter-box">
                    <div class="box-body table-responsive" style="margin-bottom: 5%">
                        <table class="table table-borderless dashboard" id="tickets">
                            <thead>
                                <tr>
                                    <th>#</id>
                                    <th>Device Name</th>
                                    <th>Model Name</th>
                                    <!-- <th>Serial Number</th> -->
                                    <th>Assigned To</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Recover Device</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignedDevices as $index => $data)
                                <tr>
                                    <td>
                                       {{ $index + 1 }}
                                    </td>
                                    <td>{{($data->device->name ?? '' )}}</td>
                                    <td>{{($data->device->device_model ?? '' )}}</td>
                                    <!-- <td>{{($data->device->serial_number ?? '')}}</td> -->
                                    <td>{{ $data->user->first_name ?? '' }} {{ $data->user->last_name ?? '' }}</td>
                                    <td>{{date("d-m-Y", strtotime($data->from))}}</td>
                                    <td>
                                        @if ($data->to)
                                            {{date("d-m-Y", strtotime($data->to)) }}
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td>
                                      @if ($data->status == 1)
                                      <div class="text-center">
                                        <input type="checkbox" onClick="Showdata(this)" data-user-id="{{ $data->id}}"
                                            class="form-check-input" id="{{'active_user_'.$data->id}}"
                                            {{$data->status == 0 ? 'checked' : ''}}>
                                        <label class="form-check-label" for="active_user"></label>
                                        <!-- <a href="{{ url('/edit/assigned-device/'.$data->id)}}"><i style="color:#4154f1;" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i></a> -->
                                        </div>
                                         @endif
                                    </td>
                                    <td>
                                    @if ($data->status == 0)
                                    <span class="badge rounded-pill bg-success">Recovered</span>
                                    @else
                                    <span class="badge rounded-pill bg-primary">Assigned</span>
                                    @endif    
                                    </td>
                                    <td> 
                                        <i style="color:#4154f1;" onClick="deleteAssignedDevice('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                       
                                    </td>
                                </tr>
                                @empty
                                @endforelse
                        </table>
                    </div>
                </div>
                <div>
                </div>
            </div>
        </div>

        <!----Assign Device--->
        <div class="modal fade" id="assignDevice" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Assign Device</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="assignDeviceForm" enctype="multipart/form-data">
                     @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="device_id" class="col-sm-3 col-form-label required">Device</label>
                                <div class="col-sm-9">
                                <select name="device_id" class="form-select form-control" id="device_id">
                                  <option value="">Select Device</option>
                                        @foreach ($devices as $data)
                                        <option value="{{$data->id}}">
                                            {{$data->name}} - {{$data->device_model}}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="user_id" class="col-sm-3 col-form-label required">Assign To</label>
                                <div class="col-sm-9">
                                <select name="user_id" class="form-select form-control" id="user_id">
                                    <option value="">Select User</option>
                                        @foreach ($users as $data)
                                        <option value="{{$data->id}}">
                                            {{$data->first_name.' '.$data->last_name}} - {{$data->department->name ?? ''}}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- <div class="row mb-3">
                                <label for="assigned_from" class="col-sm-3 col-form-label required">Assigned From</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" name="assigned_from" id="assigned_from">
                                </div>
                                @if ($errors->has('assigned_from'))
                                <span style="font-size: 12px;" class="text-danger">{{ $errors->first('assigned_from') }}</span>
                                @endif
                            </div> -->

                            <!-- <div class="row mb-3">
                                <label for="assigned_to" class="col-sm-3 col-form-label">Assigned To</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" name="assigned_to" id="assigned_to">
                                </div>
                                @if ($errors->has('assigned_to'))
                                <span style="font-size: 12px;" class="text-danger">{{ $errors->first('assigned_to') }}</span>
                                @endif
                            </div> -->


                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" href="javascript:void(0)">Assign Device</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!---end Assign Device modal-->
        @endsection
        @section('js_scripts')
        <script>
            $(document).ready(function() {
                setTimeout(function() {
                    $('.message').fadeOut("slow");
                }, 2000);
                $('#tickets').DataTable({
                    "order": []

                });
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });


                $("#assignDeviceForm").submit(function(event) {
                    event.preventDefault();
                    var formData = new FormData(this);
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('/add/assigned-device')}}",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: (data) => {
                            if (data.errors) {
                                $('.alert-danger').html('');
                                $.each(data.errors, function(key, value) {
                                    $('.alert-danger').show();
                                    $('.alert-danger').append('<li>' + value + '</li>');
                                })

                            } else {
                                $("#assignDevice").modal('hide');
                                location.reload();
                            }
                        },
                        error: function(data) {}
                    });
                });

                $('#editTicketsForm').submit(function(event) {
                    event.preventDefault();
                    var formData = new FormData(this);

                    $.ajax({
                        type: "POST",
                        url: "{{ url('/update/tickets') }}",
                        data: formData,
                        dataType: 'json',
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            if (res.errors) {
                                $('.alert-danger').html('');
                                $.each(res.errors, function(key, value) {
                                    $('.alert-danger').show();
                                    $('.alert-danger').append('<li>' + value + '</li>');
                                })
                            } else {
                                $('.alert-danger').html('');
                                $("#editTickets").modal('hide');
                                location.reload();
                            }
                        }
                    });
                });
            });

       
            function openassignDeviceModel() {
                document.getElementById("assignDeviceForm").reset();
                $('#assignDevice').modal('show');
            }

          
            function deleteAssignedDevice(id) {
                $('#ticket_id').val(id);
                // var id = $('#department_name').val();

                if (confirm("Are you sure You Want To Delete Assigned Device?") == true) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('/delete/assigned-device') }}",
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(res) {
                            location.reload();
                        }
                    });
                }
            }

            function Showdata(ele) {
                var dataId = $(ele).attr("data-user-id");

                var status = 1;
                if ($("#active_user_" + dataId).prop('checked') == true) {
                    status = 0;
                }
                $.ajax({
                    type: 'POST',
                    url: "{{ url('/update/assigned-device')}}",
                    data: {
                        id: dataId,
                        status: status,
                    },
                    cache: false,
                    success: (data) => {
                        if (data.status == 200) {
                            location.reload();
                        }
                    },
                    error: function(data) {}
                });

            }


              // Event listener for checkbox changes
        $("#filter-data input:checkbox").change(function() {
                // Submit the form
                $("#filter-data").submit();
            });

        </script>
        @endsection