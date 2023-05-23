@extends('layout')
@section('title', 'Attendance')
@section('subtitle', 'Attendance')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('attendance.store')}}">
                @csrf
                <div class="row mb-3 mt-4">
                    <div class="col-sm-3 col-md-3">
                        <div class="mb-4">
                            <label for="intime" class="required">In Time</label>
                            <input type="time" id="intime" class="form-control" name="intime">
                            @if ($errors->has('intime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('intime') }}</span>
                            @endif
                        </div>
                        <div class="mb-4">
                            <label for="outtime" class="required">Out Time</label>
                            <input type="time" id="outtime" class="form-control" name="outtime">
                            @if ($errors->has('outtime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('outtime') }}</span>
                            @endif
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary " style="margin-top:10px;width:100%;" href="javascript:void(0)">Add</button>

                        </div>

                    </div>

                    <div class="col-sm-9 col-md-9">
                        <label for="tinymce_textarea">Notes</label>
                        <textarea name="notes" rows="1" class="form-control" id="tinymce_textarea"></textarea>
                    </div>
                    <!-- <div class="col-sm-2">
                    </div> -->
                </div>
                <!-- <div class="row mb-3 mt-4">
                <div class="col-sm-2">
                        
                    </div>
                </div> -->
            </form>
            @if(session()->has('message'))
            <div class="alert alert-success message">
                {{ session()->get('message') }}
            </div>
            @endif
            <div class="box-body table-responsive" style="margin-bottom: 5%">
                <table class="table table-borderless dashboard" id="attendance">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Date</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Worked Hours</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($attendanceData) !=0)
                        @forelse($attendanceData as $data)
                        <tr>
                            <td>{{ auth()->user()->first_name ?? " " }}</td>
                            <!-- <td>{{$data->created_at}}</td> -->
                            <td>{{date("d-m-Y H:s a", strtotime($data->created_at));}}</td>
                            <td>{{ date("h:i A", strtotime($data->in_time));}}
                            </td>
                            <td>{{date("h:i A", strtotime($data->out_time));}}</td>
                            <td>
                                @php
                                $inTime = new DateTime($data->in_time);
                                $outTime = new DateTime($data->out_time);

                                $duration = $inTime->diff($outTime)->format('%h:%i');

                                echo $duration;
                                @endphp
                            </td>

                            <td>{{ html_entity_decode(strip_tags($data->notes)) }}</td>
                        </tr>
                        @empty
                        @endforelse
                        @endif
                    </tbody>
                </table>
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
</script>
@endsection