@extends('layout')
@section('title', 'Pages')
@section('subtitle', 'Pages')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3 mb-4" onClick="openPageModel()" href="javascript:void(0)"  style="background: rgb(65, 84, 241);">Add
                Page</button>
            <!-- filter -->
            <div class="box-header with-border" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="department_table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Parent Page</th>
                                <th>Action</th>

                            </tr>
                        </thead>

                        <tbody>
                            @forelse($pageData as $data)
                            <tr>
                                <td>{{ $data->name }}</td>
                                <td>{{ $name = $data->parentpage->name ?? '---'; }}</td>
                                <td>
                                    <i style="color:#4154f1;" onClick="editPage('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>
                                    <i style="color:#4154f1;" onClick="deleteDepartment('{{ $data->id }}')"
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

<!--start: Add Page Modal -->
<div class="modal fade" id="addPage" tabindex="-1" aria-labelledby="addPageLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPageLabel">Add Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addDeaprtmentForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>


                    <div class="row mb-3">
                        <label for="page_name" class="col-sm-3 col-form-label required">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="page_name" id="page_name">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="page_name" class="col-sm-3 col-form-label">Parent Page</label>
                        <div class="col-sm-9">
                        <select class="form-select form-control" id="parent_id" name="parent_id" data-placeholder="Select Page">
                                <option value="0" >Select Page</option>
                                         @foreach ($parentPage as $data)
                                        <option value="{{$data->id}}">
                                         {{$data->name}}
                                        </option>
                                        @endforeach
                                </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="addPage()"
                        href="javascript:void(0)">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Add Page Modal -->

<!--start: Edit Page Modal -->
<div class="modal fade" id="editPage" tabindex="-1" aria-labelledby="editPageLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPageLabel">Edit Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editPageForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="edit_page_name" class="col-sm-3 col-form-label required">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_page_name"
                                id="edit_page_name">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="edit_parent_id" class="col-sm-3 col-form-label">Parent Page</label>
                        <div class="col-sm-9">
                        <select class="form-select form-control" id="edit_parent_id" name="edit_parent_id" data-placeholder="Select Page">
                                <option value="0" >Select Page</option>
                                         @foreach ($parentPage as $data)
                                        <option value="{{$data->id}}">
                                         {{$data->name}}
                                        </option>
                                        @endforeach
                                </select>
                        </div>
                    </div>

                    <input type="hidden" class="form-control" id="hidden_page_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="updatePage()"
                        href="javascript:void(0)">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Edit Page Modal -->
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('.message').fadeOut("slow");
    }, 2000);
    $('#department_table').DataTable({
        "order": []
        //"columnDefs": [ { "orderable": false, "targets": 7 }]
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

function openPageModel() {
    $('#page_name').val('');
    $('#addPage').modal('show');
}

function addPage() {
    var pageName = $('#page_name').val();
    var parentId = $('#parent_id').val();
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/page')}}",
        data: {
            pageName: pageName,
            parentId:parentId,
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
                $("#addPage").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}

function editPage(id) {
    $('#hidden_page_id').val(id);

    $.ajax({
        type: "POST",
        url: "{{ url('/edit/page') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.page != null) {
                $('#editPage').modal('show');
                $('#edit_page_name').val(res.page.name);
                $('#edit_parent_id').val(res.page.parent_id);
            }
        }
    });
}

function updatePage() {
    var id = $('#hidden_page_id').val();
    var name = $('#edit_page_name').val();
    var parent_id = $('#edit_parent_id').val();
    $.ajax({
        type: "POST",
        url: "{{ url('/update/page') }}",
        data: {
            id: id,
            name: name,
            parent_id: parent_id,
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
                $("#editPage").modal('hide');
                location.reload();
            }
        }
    });
}

    function deleteDepartment(id) {
        if (confirm("Are you sure?") == true) {
            $.ajax({
                type: "DELETE",
                url: "{{ url('/delete/page') }}",
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