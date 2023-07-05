@extends('layout')
@section('title', 'Devices')
@section('subtitle', 'All Devices')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3 mb-4" onClick="openDeviceModel()" href="javascript:void(0)">Add
                Device</button>
            <!-- filter -->
            <div class="box-header with-border" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="module_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Device Name</th>
                                <th>Device Model</th>
                                <th>Brand</th>
                                <th>Buying Date</th>
                                <th>Status</th>
                                @if (auth()->user()->role->name == "Super Admin" ||auth()->user()->role->name == "HR Manager")
                                <th>Action</th>
                                @endif
                              
                            </tr>
                        </thead>

                        <tbody>
                            
                            @forelse($devices as $index => $data)
                            <tr>
                                <td> {{ $index + 1 }}</td>
                                <td>{{$data->name}}</td>
                                <td>{{$data->device_model ?? ''}}</td>
                                <td>{{$data->brand ?? ''}}</td>
                                <td>{{date("d-m-Y", strtotime($data->buying_date));}}</td>
                                <td>{{$data->status }}</td>
                                <!-- <td> {{$data->status}}</td> -->
                                @if (auth()->user()->role->name == "Super Admin" ||auth()->user()->role->name == "HR Manager")
                                <td>
                                    <i style="color:#4154f1;" onClick="editDevice('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>
                                    <i style="color:#4154f1;" onClick="deleteDevice('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                </td>
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

<!--start: Add Module Modal -->
<div class="modal fade" id="addDevice" tabindex="-1" aria-labelledby="addDeviceLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDeviceLabel">Add Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addDeviceForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="device_name" class="col-sm-3 col-form-label required">Device Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="device_name" id="device_name">
                        </div>
                        @if ($errors->has('device_name'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('device_name') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="device_model" class="col-sm-3 col-form-label">Device Model</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="device_model" id="device_model">
                        </div>
                        @if ($errors->has('device_model'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('device_model') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="brand" class="col-sm-3 col-form-label ">Brand</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="brand" id="brand">
                        </div>
                        @if ($errors->has('brand'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('brand') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="brand" class="col-sm-3 col-form-label required">Buying Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="buying_date" id="buying_date">
                        </div>
                        @if ($errors->has('buying_date'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('buying_date') }}</span>
                        @endif
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="addDevice()"
                        href="javascript:void(0)">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Add Module Modal -->

<!--start: Edit Module Modal -->
<div class="modal fade" id="editDevice" tabindex="-1" aria-labelledby="editDeviceLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDeviceLabel">Edit Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editDeviceForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="edit_device_name" class="col-sm-3 col-form-label required">Device Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_device_name" id="edit_device_name">
                        </div>
                        @if ($errors->has('edit_device_name'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_device_name') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="edit_device_model" class="col-sm-3 col-form-label">Device Model</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_device_model" id="edit_device_model">
                        </div>
                        @if ($errors->has('device_model'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('device_model') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="edit_brand" class="col-sm-3 col-form-label ">Brand</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_brand" id="edit_brand">
                        </div>
                        @if ($errors->has('brand'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('brand') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="edit_buying_date" class="col-sm-3 col-form-label required">Buying Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="edit_buying_date" id="edit_buying_date">
                        </div>
                        @if ($errors->has('edit_buying_date'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_buying_date') }}</span>
                        @endif
                    </div>

                    <input type="hidden" class="form-control" id="hidden_device_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="updateDevice()"
                        href="javascript:void(0)">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Edit Module Modal -->
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('.message').fadeOut("slow");
    }, 2000);
    $('#module_table').DataTable({
        "order": []
        //"columnDefs": [ { "orderable": false, "targets": 7 }]
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

function openDeviceModel() {
    $('#device_name').val('');
    $('#addDevice').modal('show');
}

function addDevice() {
    var deviceName = $('#device_name').val();
    var deviceModel = $('#device_model').val();
    var brand = $('#brand').val();
    var buyingDate = $('#buying_date').val();
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/device')}}",
        data: {
            deviceName: deviceName,
            deviceModel:deviceModel,
            brand: brand,
            buyingDate: buyingDate     
        },
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
                $("#addDevice").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}

function editDevice(id) {
    $('#hidden_device_id').val(id);

    $.ajax({
        type: "POST",
        url: "{{ url('/edit/device') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.device != null) {
                $('#editDevice').modal('show');
                $('#edit_device_name').val(res.device.name);
                $('#edit_device_model').val(res.device.device_model);
                $('#edit_brand').val(res.device.brand);
                $('#edit_buying_date').val(res.device.buying_date);

            }
        }
    });
}

function updateDevice() {
    var id = $('#hidden_device_id').val();
    var edit_device_name = $('#edit_device_name').val();
    var edit_device_model = $('#edit_device_model').val();
    var edit_brand = $('#edit_brand').val();
    var edit_buying_date = $('#edit_buying_date').val();


    $.ajax({
        type: "POST",
        url: "{{ url('/update/device') }}",
        data: {
            id: id,
            edit_device_name: edit_device_name,
            edit_device_model: edit_device_model,
            edit_brand: edit_brand,
            edit_buying_date: edit_buying_date,
        },
        dataType: 'json',
        success: function(res) {
            if (res.errors) {
                $('.alert-danger').html('');

                $.each(res.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                })
            } else {
                $('.alert-danger').html('');
                $("#editDevice").modal('hide');
                location.reload();
            }
        }
    });
}

    function deleteDevice(id) {
        if (confirm("Are you sure You Want To Delete Device?") == true) {
            $.ajax({
                type: "DELETE",
                url: "{{ url('/delete/device') }}",
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

    
</script>
@endsection