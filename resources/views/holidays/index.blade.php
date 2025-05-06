@extends('layout')
@section('title', 'Holidays')
@section('subtitle', 'Holidays')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3 mb-4" onClick="openHolidayModel()" href="javascript:void(0)" style="background-color: #4154f1;">Add
                Holiday</button>

            <form id="filter-data" method="GET" action="{{ route('holidays.index') }}">
                <div class="row mt-3 mx-auto">
                    <div class="col-md-6 filtersContainer d-flex p-0">
                        <div style="margin-right:20px;">
                            
                            <input type="checkbox" class="form-check-input" name="all_holidays" id="all_holidays"
                                {{ $allHolidaysFilter == 'on' ? 'checked' : '' }}>  
                                <label for="all_holiday">All</label>
                        </div>
                        
                    </div>
                </div>
            </form>
            <!-- filter -->
            <div class="box-header with-border" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="module_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Holiday</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Day</th>
                                <!-- <th>Status</th> -->
                                @if (auth()->user()->role->name == "Super Admin" ||auth()->user()->role->name == "HR Manager")
                                <th>Action</th>
                                @endif
                              
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                use Carbon\Carbon;
                            @endphp
                            
                            @forelse($holidays as $index => $data)
                            <tr>
                                <td> {{ $index + 1 }}</td>
                                <td>{{$data->name}}</td>
                                <!-- <td>
                                    @if ($data->from === $data->to)
                                    {{$data->from}}
                                    @else
                                        {{$data->from}}, {{$data->to}}                                    
                                    @endif
                                </td> -->
                                <td>{{date("d-m-Y", strtotime($data->from));}}</td>
                                <td>{{date("d-m-Y", strtotime($data->to));}}</td>
                                <td>
                                @if ($data->from === $data->to)
                                    {{ \Carbon\Carbon::parse($data->from)->format('l') }}
                                @else
                                    {{ \Carbon\Carbon::parse($data->from)->format('l') }} To {{ \Carbon\Carbon::parse($data->to)->format('l') }}
                                @endif
                                </td>
                                <!-- <td> {{$data->status}}</td> -->
                                @if (auth()->user()->role->name == "Super Admin" ||auth()->user()->role->name == "HR Manager")
                                <td>
                                    <i style="color:#4154f1;" onClick="editHoliday('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>
                                    <i style="color:#4154f1;" onClick="deleteHoliday('{{ $data->id }}')"
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
<div class="modal fade" id="addHoliday" tabindex="-1" aria-labelledby="addHolidayLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addHolidayLabel">Add Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addHolidayForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="holiday_name" class="col-sm-3 col-form-label required">Holiday Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="holiday_name" id="holiday_name">
                        </div>
                        @if ($errors->has('holiday_name'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('holiday_name') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="user_name" class="col-sm-3 col-form-label required">From</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="from" id="from">
                        </div>
                        @if ($errors->has('from'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('from') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="last_name" class="col-sm-3 col-form-label required">To</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="to" id="to">
                        </div>
                        @if ($errors->has('to'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('to') }}</span>
                        @endif
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="addHoliday()"
                        href="javascript:void(0)">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Add Module Modal -->

<!--start: Edit Module Modal -->
<div class="modal fade" id="editHoliday" tabindex="-1" aria-labelledby="editHolidayLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editHolidayLabel">Edit Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editHolidayForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="edit_holiday_name" class="col-sm-3 col-form-label required">Holiday Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_holiday_name" id="edit_holiday_name">
                        </div>
                        @if ($errors->has('edit_holiday_name'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_holiday_name') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="edit_from" class="col-sm-3 col-form-label required">From</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="edit_from" id="edit_from">
                        </div>
                        @if ($errors->has('edit_from'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_from') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="last_name" class="col-sm-3 col-form-label required">To</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="edit_to" id="edit_to">
                        </div>
                        @if ($errors->has('edit_to'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_to') }}</span>
                        @endif
                    </div>


                    <input type="hidden" class="form-control" id="hidde_holiday_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="updateHoliday()"
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

function openHolidayModel() {
    $('#page_name').val('');
    $('#addHoliday').modal('show');
}

function addHoliday() {
    var holidayName = $('#holiday_name').val();
    var from = $('#from').val();
    var to = $('#to').val();
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/holiday')}}",
        data: {
            holidayName: holidayName,
            from: from,
            to: to     
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
                $("#addHoliday").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}

function editHoliday(id) {
    $('#hidde_holiday_id').val(id);

    $.ajax({
        type: "POST",
        url: "{{ url('/edit/holiday') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.holiday != null) {
                $('#editHoliday').modal('show');
                $('#edit_holiday_name').val(res.holiday.name);
                $('#edit_from').val(res.holiday.from);
                $('#edit_to').val(res.holiday.to);
            }
        }
    });
}

function updateHoliday() {
    var id = $('#hidde_holiday_id').val();
    var edit_holiday_name = $('#edit_holiday_name').val();
    var edit_from = $('#edit_from').val();
    var edit_to = $('#edit_to').val();

    $.ajax({
        type: "POST",
        url: "{{ url('/update/holiday') }}",
        data: {
            id: id,
            edit_holiday_name: edit_holiday_name,
            edit_from: edit_from,
            edit_to: edit_to,
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
                $("#editHoliday").modal('hide');
                location.reload();
            }
        }
    });
}

    function deleteHoliday(id) {
        if (confirm("Are you sure You Want To Delete Holiday From List?") == true) {
            $.ajax({
                type: "DELETE",
                url: "{{ url('/delete/holiday') }}",
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

   // Event listener for checkbox changes
   $("#filter-data input:checkbox").change(function() {
                // Submit the form
                $("#filter-data").submit();
            });

    
</script>
@endsection