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
                                <th>Date</th>
                                <th>Resume</th>
                                <th>Notes</th>
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
                                <td>{{date("d M Y", strtotime($data->created_at));}}</td>
                                <td><button onclick="window.open('/assets/docs/{{$data->resume}}', '_blank')" class="resume_button"><i class="fa fa-eye"></i></button></td>
                                <!-- <td> {{$data->status}}</td> -->

                                <td>
                                    <button data-bs-toggle="modal" data-bs-target="#view_note_modal" onclick="view_note('{{ htmlspecialchars($data->note) }}')" class="resume_button"><i class="fa fa-sticky-note-o"></i></button>
                                </td>

                                <td>
                                    <select style="width:150px;" applicant-id="{{$data->id}}" applicant-name="{{$data->name}}" name="application_status"
                                        class="form-select application_status" id="application_status_{{$data->id}}">
                                        <option value="pending" {{$data->application_status == "pending" || $data->application_status == ""  ? 'selected' : ''}}>
                                            Pending</option>

                                        <option value="in_process" {{$data->application_status == "in_process"  ? 'selected' : ''}}>
                                        In Process</option>
                                        <option value="selected" {{$data->application_status == "selected"  ? 'selected' : ''}}>
                                            Selected</option>
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



<!--start: Add Team's Attendance Modal -->
<div class="modal fade" id="status_note_modal" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" id="status_change_note">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    <div class="row mb-3">
                    <input type="hidden" class="form-control" name="applicant_id" id="applicant_id">
                        <div class="col-sm-12">
                            <!-- <div class="col-sm-4 mb-2"> -->
                            <label for="tinymce_textarea">Name</label>
                           <input type="text" class="form-control" name="name" id="name" readonly>
                            <!-- / </div> -->
                        </div>
                    </div>
                     <div class="row mb-3">

                        <div class="col-sm-12">
                            <!-- <div class="col-sm-4 mb-2"> -->
                            <label for="tinymce_textarea">Status:</label>
                           <input type="text" class="form-control" name="status" id="status" readonly>
                            <!-- / </div> -->
                        </div>
                    </div>
                    <div class="row mb-3">

                        <div class="col-sm-12">
                            <!-- <div class="col-sm-4 mb-2"> -->
                            <label for="tinymce_textarea">Notes:</label>
                            <textarea name="notes" rows="4" col="3" class="form-control" id="tinymce_textarea"></textarea>
                            <!-- / </div> -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="close_button">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                 </div>
            </form>
    </div>
</div>
</div>

<!--View note---->
<div class="modal fade" id="view_note_modal" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
                <div class="modal-body">
                    <div class="note_section">

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="close_button">Close</button>

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
        var name="";
        var id="";
        var prevValue = '';
        $('.application_status').mousedown(function () {
            prevValue = $(this).val();

        });

        // Attach a change event listener to the dropdown
        $('.application_status').change(function () {
            name=$(this).attr('applicant-name')
            id=$(this).attr('applicant-id')
            $('#status').val($(this).val());
            $('#name').val(name);
            $('#status_note_modal').modal('show');
            $(this).val(prevValue);
            $('#applicant_id').val(id);
        });


        $("#status_change_note").submit(function(event){
        event.preventDefault();
        var formData = $(this).serialize();
                        $('#loader').show();
                    $.ajax({
                        type: 'POST',
                        url: '/applicants/status',
                        data: formData,
                        success: function (response) {
                            $('#loader').hide();
                          window.location.reload();
                        },
                        error: function (error) {
                            $('#loader').hide();
                        }
                    });

    });

    $('.readMoreLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                description.text(fullDescription.text());
                $(this).hide();
                $(this).siblings('.readLessLink').show();
            });

            $('.readLessLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                var truncatedDescription = fullDescription.text().substring(0, 80) + '...';
                description.text(truncatedDescription);
                $(this).hide();
                $(this).siblings('.readMoreLink').show();
            });

});
 function view_note(data){
    console.log(data);
    if(data){
        var textContent = $('<div>').html(data).text();
        $('.note_section').html(textContent);
    }
    else{
        $('.note_section').html('No Notes');
    }

 }


</script>
@endsection
