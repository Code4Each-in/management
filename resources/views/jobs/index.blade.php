@extends('layout')
@section('title', 'Jobs')
@section('subtitle', 'Jobs')
@section('content')
<style>
    .addjobs .modal-dialog, .editJob .modal-dialog{
        max-width: 800px;
    }
</style>
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openAddJobModal()" href="javascript:void(0)">Add
                Job</button>
            <div class="box-header with-border" id="filter-box">
                <br>
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless datatable" id="jobstable">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Experience</th>
                                <th>Job Category</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Salary</th>
                                <th>Skills</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobsData as $data)
                            <tr>
                                <td><a href="/details/{{$data->id }}">{{$data->id }}</a></td>
                                <td>{{$data->title }}</td>
                                <td>
                                    @if(strlen($data->description) >= 100)
                                    <span class="description">
                                        @php
                                        $plainTextDescription = strip_tags(htmlspecialchars_decode($data->description));
                                        $limitedDescription = substr($plainTextDescription, 0, 100) . '...';
                                        echo $limitedDescription;
                                        @endphp
                                    </span>
                                    <span class="fullDescription" style="display: none;">
                                        @php
                                        echo $data->description;
                                        @endphp
                                    </span>
                                    <a href="#" class="readMoreLink">Read More</a>
                                    <a href="#" class="readLessLink" style="display: none;">Read Less</a>
                                    @else
                                    {!! $data->description !!}
                                        @endif
                                </td>
                                <td>{{$data->experience }}</td>
                                <td>{{$data->jobcategory->title }}</td>
                                <td>{{ $data->status == 1 ? 'Active' : 'Deactive' }}</td>
                                <td>{{$data->location }}</td>
                                <td>{{$data->salary }}</td>
                                <td>{{ $data->skills }}</td>
                                <td>
                                    <i style="color:#4154f1;" onClick="editJob('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>
                                    <i style="color:#4154f1;" onClick="deleteJob('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
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

<!--start: Add users Modal -->
<div class="modal fade addjobs" id="addjobs" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addjobsForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    <div class="row mb-3">
                        <label for="title" class="col-sm-3 col-form-label required">Title</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="title" id="title">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                        <div class="col-sm-9">
                            <textarea name="desc" class="form-control" id="tinymce_textarea"></textarea>
                        </div>
                        <input name="description" type="hidden" value="" class="description">
                    </div>
                    <div class="row mb-3">
                        <label for="experience" class="col-sm-3 col-form-label required">Experience</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="experience" id="experience">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="job_category_id" class="col-sm-3 col-form-label required ">Job Category</label>

                        <div class="col-sm-9">
                            <select name="job_category_id" class="form-select form-control" id="job_category_id">
                                @foreach ($jobCategories as $data)
                                    <option value="{{$data->id}}">
                                        {{$data->title}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="location" class="col-sm-3 col-form-label required ">Location</label>
                        <div class="col-sm-9">
                            <select name="location" class="form-select form-control" id="location">
                                <option value="Onsite">Onsite</option>
                                <option value="remote">Remote</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="status" class="col-sm-3 col-form-label required">Status</label>
                        <div class="col-sm-9">
                            <select name="status" class="form-select" id="status">
                                <option value="1">Active</option>
                                <option value="0">Deactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="skills" class="col-md-4 col-lg-3 col-form-label required">Skills</label>
                        <div class="col-md-8 col-lg-9">
                            <input name="skills" type="text" class="form-control" id="skills"
                                value='' data-role="taginput">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="salary" class="col-sm-3 col-form-label">Salary</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="salary" id="salary">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onClick="addJobs(this)" href="javascript:void(0)">Add Job</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Add Module Modal -->

<!--start: Edit Module Modal -->
<div class="modal fade editJob" id="editJob" tabindex="-1" aria-labelledby="editJobLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editJobLabel">Edit Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editJobForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    <div class="row mb-3">
                        <label for="edit_title" class="col-sm-3 col-form-label required">Title</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control edit_title" name="edit_title" id="edit_title">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                        <div class="col-sm-9">
                            <textarea name="edit_desc" class="form-control edit_desc" id="tinymce_textarea"></textarea>
                        </div>
                        <input name="edit_description" type="hidden" value="" class="edit_description">
                    </div>
                    <div class="row mb-3">
                        <label for="edit_experience" class="col-sm-3 col-form-label required">Experience</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control edit_experience" name="edit_experience" id="edit_experience">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="edit_job_category_id" class="col-sm-3 col-form-label required ">Job Category</label>

                        <div class="col-sm-9">
                            <select name="edit_job_category_id" class="form-select form-control edit_job_category_id" id="edit_job_category_id">
                                @foreach ($jobCategories as $data)
                                    <option value="{{$data->id}}">
                                        {{$data->title}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="edit_location" class="col-sm-3 col-form-label required ">Location</label>
                        <div class="col-sm-9">
                            <select name="edit_location" class="form-select form-control edit_location" id="edit_location">
                                <option value="Onsite">Onsite</option>
                                <option value="remote">Remote</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="edit_status" class="col-sm-3 col-form-label required">Status</label>
                        <div class="col-sm-9">
                            <select name="edit_status" class="form-select edit_status" id="edit_status">
                                <option value="1">Active</option>
                                <option value="0">Deactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="edit_skills" class="col-md-4 col-lg-3 col-form-label required">Skills</label>
                        <div class="col-md-8 col-lg-9">
                            <input name="edit_skills" type="text" class="form-control edit_skills" id="edit_skills"
                                value='' data-role="taginput">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="edit_salary" class="col-sm-3 col-form-label">Salary</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control edit_salary" name="edit_salary" id="edit_salary">
                        </div>
                    </div>
                    <input type="hidden" class="hidden_job_id" value="" name="id">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="updateJob()"
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
    $('#jobstable').DataTable({
        "order": []
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#skills').tagsinput({
        confirmKeys: [13, 188]
        });

        $('#skills').on('keypress', function(e){
        if (e.keyCode == 13){
            e.preventDefault();
        };
    });

});

function openAddJobModal() {
    $('.alert-danger').html('');
    $('#title').val('');
    $('#status').val(1);
    $('#addjobs').modal('show');
}

function addJobs() {
    var spinner = $('#loader');
    spinner.show();

    //THIS WILL ADD THE TINYMCE DATA INTO HIDDEN FIELD SO THAT WE CAN USE THE SERIALIZE DATA
    $(".description").val(tinyMCE.activeEditor.getContent());

  $.ajax({
    url: "{{ url('/add/job')}}",
    data: $('#addjobsForm').serialize(), // Send the data object
    method: 'POST',
    dataType: 'JSON',
    cache: false,
    success: function(data) {
      // Introduce a delay before hiding the spinner
      setTimeout(function() {
        spinner.hide();

        if (data.errors) {
          $('.alert-danger').html('');
          $.each(data.errors, function(key, value) {
            $('.alert-danger').show();
            $('.alert-danger').append('<li>' + value + '</li>');
          });
        } else {
          $('.alert-danger').html('');
          $("#addjobs").modal('hide');
          location.reload();
        }
      }, 3000); // Adjust the duration (in milliseconds) as needed
    },
    error: function(data) {
      spinner.hide();
      console.log(data);
    }
  });
}

function deleteJob(id) {
    if (confirm("Are you sure You Want To Delete This Job ?") == true) {

        $.ajax({
            type: "DELETE",
            url: "{{ url('/delete/job') }}",
            data: {
                id: id
            },
            dataType: 'json',
            success: function(res) {
                console.log(res);
                location.reload();
            }
        });
    }
}

function editJob(id) {
    $('.hidden_job_id').val(id);

    $.ajax({
        type: "POST",
        url: "{{ url('/edit/job') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.jobs != null) {
                $('#editJob').modal('show');
                $('.edit_title').val(res.jobs.title);
                $('.edit_experience').val(res.jobs.experience);
                $('.edit_job_category_id').val(res.jobs.job_category_id);
                $('.edit_location').val(res.jobs.location);
                $('.edit_status').val(res.jobs.status);
                $('.edit_salary').val(res.jobs.salary);
                $('.edit_skills').tagsinput('removeAll');

                var skillsString = res.jobs.skills;
                if( skillsString != 'undefined'){
                    var tagsArray = skillsString.split(',');
                    $.each(tagsArray, function(key, value) {
                        $('.edit_skills').tagsinput('add', value);
                    });
                }
                tinyMCE.activeEditor.setContent(res.jobs.description);

            }
        }
    });
}

function updateJob() {
    // var id = $('#hidden_job_id').val();
    // var edit_title = $('#edit_device_name').val();
    // var edit_description = $('#edit_device_model').val();
    // var edit_experience = $('#edit_brand').val();
    // var edit_job_cat_id = $('#edit_serial_number').val();
    // var edit_buying_date = $('#edit_buying_date').val();
    // var edit_location = $('#edit_buying_date').val();
    // var edit_status = $('#edit_buying_date').val();
    // var edit_skills = $('#edit_buying_date').val();
    // var edit_salary = $('#edit_buying_date').val();


    // var formData = new FormData();
    // formData.append('id', id);
    // formData.append('edit_device_name', edit_device_name);
    // formData.append('edit_device_model', edit_device_model);
    // formData.append('edit_brand', edit_brand);
    // formData.append('edit_serial_number', edit_serial_number);
    // formData.append('edit_buying_date', edit_buying_date);
    var spinner = $('#loader');
    spinner.show();
    $(".edit_description").val(tinyMCE.activeEditor.getContent());

        $.ajax({
        url: "{{ url('/update/job')}}",
        data: $('#editJobForm').serialize(), // Send the data object
        method: 'POST',
        dataType: 'JSON',
        cache: false,
        success: function(res) {
            spinner.hide();
            if (res.errors) {
                $('.alert-danger').html('');

                $.each(res.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                });
            } else {
                $('.alert-danger').html('');
                $("#editJob").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            spinner.hide();
            console.log(data);
        }
    });
}

</script>
<script>
    $(document).ready(function(){
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

                var truncatedDescription = fullDescription.text().substring(0, 100) + '...';
                description.text(truncatedDescription);
                $(this).hide();
                $(this).siblings('.readMoreLink').show();
            });
    });
</script>

@endsection
