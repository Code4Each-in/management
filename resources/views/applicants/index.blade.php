@extends('layout')
@section('title', 'Applicants')
@section('subtitle', 'Applicants')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3 mb-4" onClick="openHolidayModel()" href="javascript:void(0)">Applicants</button>
            <!-- filter -->
            <div class="box-header with-border" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%">
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
                                <!-- <th>Status</th> -->
                                <th>Action</th>

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
                                    @if($data->job_id == 1)
                                    PHP
                                    @elseif($data->job_id == 2)
                                    Web Designer
                                    @elseif($data->job_id == 3)
                                    BDE
                                    @elseif($data->job_id == 5)
                                    Others
                                    @endif
                                </td>

                                <td><button onclick="window.open('/assets/docs/{{$data->resume}}', '_blank')" class="resume_button"><i class="fa fa-eye"></i></button></td>
                                <!-- <td> {{$data->status}}</td> -->
                                <td>{{date("d M Y", strtotime($data->created_at));}}</td>

                                <td>
                                    Action
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

@endsection
