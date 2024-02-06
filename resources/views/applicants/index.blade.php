@extends('layout')
@section('title', 'Applicants')
@section('subtitle', 'Applicants')
@section('content')
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">

            <!-- filter -->
            <div class="box-header with-border" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%;margin-top:5%">
                    <table class="table table-borderless dashboard" id="applicant_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Apply for</th>
                                <th>Resume</th>
                                <th>Date</th>
                                <th>Status</th>


                            </tr>
                        </thead>

                        <tbody>
                            @php
                                use Carbon\Carbon;
                            @endphp

                            @forelse($applicants as $index => $data)
                            <tr>
                                <td> {{ $index + 1 }}</td>
                                <td>{{$data->name}}</td>
                                <td>{{$data->email}}</td>
                                <td>{{$data->phone}}</td>
                                <td>
                                {{$data->title}}
                                </td>

                                <td><button onclick="window.open('/assets/docs/{{$data->resume}}', '_blank')" class="resume_button"><i class="fa fa-eye"></i></button></td>
                                <!-- <td> {{$data->status}}</td> -->
                                <td>{{date("d M Y", strtotime($data->created_at));}}</td>
                                <td> <select style="width:150px;" applicant-id="{{$data->id}}" name="application_status"
                                        class="form-select application_status" id="application_status_{{$data->id}}">
                                        <option value="pending" {{$data->application_status == "pending" || $data->application_status == ""  ? 'selected' : ''}}>
                                            Pending</option>
                                        <option value="in_process" {{$data->application_status == "in_process"  ? 'selected' : ''}}>
                                        In Process</option>
                                        <option value="rejected" {{$data->application_status == "rejected"  ? 'selected' : ''}}>
                                            Rejected</option>
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
@endsection

@section('js_scripts')
<script>
    $(document).ready(function() {
        setTimeout(function() {
            $('.message').fadeOut("slow");
        }, 2000);
        $('#applicant_table').DataTable({
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

<script>
    $(document).ready(function () {
        var prevValue = '';
        $('.application_status').mousedown(function () {
            prevValue = $(this).val();

        });

        // Attach a change event listener to the dropdown
        $('.application_status').change(function () {

            var selectedValue = $(this).val();
            if (selectedValue !== "") {
                // Show a confirmation dialog
                var isConfirmed = confirm('Are you sure you want to choose ' + selectedValue );
                if (isConfirmed) {
                        var applicantId = $(this).attr('applicant-id');
                        $('#loader').show();
                    $.ajax({
                        type: 'POST',
                        url: '/applicants/status',
                        data: {
                            applicantId: applicantId,
                            application_status: selectedValue
                        },
                        success: function (response) {
                            $('#loader').hide();
                          window.location.reload();
                        },
                        error: function (error) {
                            $('#loader').hide();
                        }
                    });


                } else {
                    $(this).val(prevValue);
                }
            }
        });
    });
</script>
@endsection
