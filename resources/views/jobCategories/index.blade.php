@extends('layout')
@section('title', 'Job Categories')
@section('subtitle', 'Job Categories')
@section('content')
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openJobCategoryModal()" href="javascript:void(0)">Add
                Job Category</button>
            <div class="box-header with-border" id="filter-box">
                <br>
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless datatable" id="jobcategoriestable">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobCategoriesData as $data)
                            <tr>
                                <td>{{$data->id }}</td>
                                <td>{{$data->title }}</td>
                                <td>{{ $data->status == 1 ? 'Active' : 'Deactive' }}</td>
                                <td>
                                    <!-- <i style="color:#4154f1;" onClick="editDevice('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i> -->
                                    <i style="color:#4154f1;" onClick="deleteJobCategory('{{ $data->id }}')"
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
<div class="modal fade" id="addjobcategories" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Job Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addjobcategoriesForm" action="" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="title" class="col-sm-3 col-form-label required">Title</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="title" id="title" value="">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="category_img" class="col-sm-3 col-form-label">Image</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" name="category_img" id="category_img" >
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="status" class="col-sm-3 col-form-label ">Status</label>
                        <div class="col-sm-9">
                            <select name="status" class="form-select" id="status">
                                <!-- <option value="">-- Select type --</option> -->
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onClick="addJobCategory(this)"
                            href="javascript:void(0)">Add Job Category</button>
                    </div>
            </form>
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
    $('#jobcategoriestable').DataTable({
        "order": []
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

});

function openJobCategoryModal() {
    $('.alert-danger').html('');
    $('#title').val('');
    $('#status').val(1);
    $('#addjobcategories').modal('show');
}

function addJobCategory() {
  var spinner = $('#loader');
  spinner.show();

    // Prepare the data object manually
    // const data = {
    //     title: $('#title').val(),
    //     status : $('#status').val(),
    // };
    var data = new FormData($('#addjobcategoriesForm')[0]);

  $.ajax({
    url: "{{ url('/add/job-category')}}",
    data: data, // Send the data object
    method: 'POST',
    // dataType: 'JSON',
    contentType: false,
    processData: false,
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
          $("#addjobcategories").modal('hide');
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
function deleteJobCategory(id) {
    if (confirm("Are you sure You Want To Delete Job Category?") == true) {
        $.ajax({
            type: "DELETE",
            url: "{{ url('/delete/job-category') }}",
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
