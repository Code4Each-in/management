@extends('layout')
@section('title', 'Modules')
@section('subtitle', 'Modules')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3 mb-4" onClick="openModuleModel()" href="javascript:void(0)">Add
                Module</button>
            <!-- filter -->
            <div class="box-header with-border" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="module_table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Page Name</th>
                                <th>Route Name</th>
                                <th>Action</th>

                            </tr>
                        </thead>

                        <tbody>
                            @forelse($modulesData as $data)
                            <tr>
                                <td>{{ $data->module_name }}</td>
                                <td>{{ $data->page->name}}</td>
                                <td>{{ $data->route_name ?? '---'}}</td>
                                <td>
                                    <i style="color:#4154f1;" onClick="editModule('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>
                                    <i style="color:#4154f1;" onClick="deleteModule('{{ $data->id }}')"
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

<!--start: Add Module Modal -->
<div class="modal fade" id="addModule" tabindex="-1" aria-labelledby="addModuleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModuleLabel">Add Module</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addDeaprtmentForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    
                    <div class="row mb-3">
                        <label for="page_id" class="col-sm-3 col-form-label required">Page</label>
                        <div class="col-sm-9">
                            <!-- <input type="text" class="form-control" name="page_name" id="page_name"> -->
                            <select class="form-select form-control" id="page_id" name="page_id" data-placeholder="Select Page">
                                <option value="" >Select Page</option>
                                         @foreach ($pages as $data)
                                        <option value="{{$data->id}}">
                                         {{$data->name}}
                                        </option>
                                        @endforeach
                                </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="module_name" class="col-sm-3 col-form-label required">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="module_name" id="module_name">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="route_name" class="col-sm-3 col-form-label required">Route Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="route_name" id="route_name">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="addModule()"
                        href="javascript:void(0)">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Add Module Modal -->

<!--start: Edit Module Modal -->
<div class="modal fade" id="editModule" tabindex="-1" aria-labelledby="editModuleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModuleLabel">Edit Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editModuleForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="edit_page_id" class="col-sm-3 col-form-label required">Page</label>
                        <div class="col-sm-9">
                            <!-- <input type="text" class="form-control" name="page_name" id="page_name"> -->
                            <select class="form-select form-control" id="edit_page_id" name="page_id" data-placeholder="Select Page">
                                <option value="" disabled>Select Page</option>
                                         @foreach ($pages as $data)
                                        <option value="{{$data->id}}">
                                         {{$data->name}}
                                        </option>
                                        @endforeach
                                </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="edit_module_name" class="col-sm-3 col-form-label required">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="module_name"
                                id="edit_module_name">
                        </div>
                    </div>


                    <div class="row mb-3">
                        <label for="edit_route_name" class="col-sm-3 col-form-label required">Route Name</label>
                        <div class="col-sm-9">
                            <!-- <input type="text" class="form-control" name="route_name" id="edit_route_name"> -->
                            <select class="form-select form-control" id="edit_route_name" name="route_name" data-placeholder="Select ">
                                <option value="" disabled>Select Route</option>
                                         @foreach ($routeNames as $route)
                                        <option value="{{$route}}">
                                        {{$route}}
                                        </option>
                                        @endforeach
                                </select>
                        </div>
                    </div>

                    <input type="hidden" class="form-control" id="hidde_module_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="updateModule()"
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

function openModuleModel() {
    $('#page_name').val('');
    $('#addModule').modal('show');
}

function addModule() {
    var pageId = $('#page_id').val();
    var moduleName = $('#module_name').val();
    var routeName = $('#route_name').val();
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/module')}}",
        data: {
            pageId: pageId,
            moduleName: moduleName,
            routeName: routeName     
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
                $("#addModule").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}

function editModule(id) {
    $('#hidde_module_id').val(id);

    $.ajax({
        type: "POST",
        url: "{{ url('/edit/module') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.module != null) {
                $('#editModule').modal('show');
                $('#edit_module_name').val(res.module.module_name);
                $('#edit_route_name').val(res.module.route_name);
                $('#edit_page_id').val(res.module.page_id);
            }
        }
    });
}

function updateModule() {
    var id = $('#hidde_module_id').val();
    var edit_module_name = $('#edit_module_name').val();
    var edit_route_name = $('#edit_route_name').val();
    var edit_page_id = $('#edit_page_id').val();

    $.ajax({
        type: "POST",
        url: "{{ url('/update/module') }}",
        data: {
            id: id,
            edit_module_name: edit_module_name,
            edit_route_name: edit_route_name,
            edit_page_id: edit_page_id,
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
                $("#editModule").modal('hide');
                location.reload();
            }
        }
    });
}

    function deleteModule(id) {
        if (confirm("Are you sure?") == true) {
            $.ajax({
                type: "DELETE",
                url: "{{ url('/delete/module') }}",
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