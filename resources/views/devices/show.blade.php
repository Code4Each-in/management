@extends('layout')
@section('title', 'Devices')
@section('subtitle', 'Show')
@section('content')

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Device Name</label>
                    <div class="col-sm-9">
                            {{$device->name}}
                        </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Device Model</label>
                    <div class="col-sm-9">
                            {{$device->device_model}}
                        </div>
                </div>

                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Brand</label>
                    <div class="col-sm-9">
                            {{$device->brand }}
                        </div>
                </div>

                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Serial Number</label>
                    <div class="col-sm-9">
                            {{$device->serial_number}}
                        </div>
                </div>

                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Buying Date</label>
                    <div class="col-sm-9">
                            {{$device->buying_date }}
                        </div>
                </div>                            
            
                <div class="row mb-1 mt-4">
                                <label for="edit_status" class="col-sm-3">Status</label>
                                <div class="col-sm-9">
                                @if($device->status == 0)
                                    <span class="badge rounded-pill bg-success">Free</span>
                                    @else
                                    <span class="badge rounded-pill bg-primary">In Use</span>
                                    @endif
                                </div>
                            </div>
                <div class="text-center">
                    <!-- <a href="{{route('projects.index')}}" class="btn btn-primary">Back</a> -->
                </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')

@endsection