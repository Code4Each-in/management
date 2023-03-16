@extends('layout')
@section('title', 'Roles')
@section('subtitle', 'Roles')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openroleModal()" href="javascript:void(0)">ADD ROLE</button>
            <div class="box-header with-border" id="filter-box">
                @if(session()->has('message'))
                <div class="alert alert-success message">
                    {{ session()->get('message') }}
                </div>

                @endif
                <br>
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="role_table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roleData as $data)
                            <tr>
                                <td>{{ $data->name }}</td>
                                <td>
                                    <i style="color:#4154f1;" onClick="editRole('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>

                                    <i style="color:#4154f1;" onClick="deleteRole('{{ $data->id }}')"
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

<!--start: Add role Modal -->
<div class="modal fade" id="addRole" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 630px;">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addRoleForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="role_name" class="col-sm-3 col-form-label required">First Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="role_name" id="role_name">
                        </div>
                    </div>

                    <label class="mb-2" for="permission">Permissions:</label>
                    @forelse($pages as $page)
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-check-label permissionLabel" for=""> {{$page->name}}</label>
                        </div>
                        @forelse($page->module as $val)
                        <div class="form-check col-md-2 mb-4">
                            <label class="form-check-label" for="{{'listing_page_'.$val->id}}">
                                {{$val->module_name}}</label>
                            <input class="form-check-input" name="role_permissions[]" type="checkbox"
                                id="{{'listing_page_'.$val->id}}" value="{{$val->id}}">
                        </div>
                        @empty
                        @endforelse
                        <!--<div class="form-check col-md-4">
						<label class="form-check-label" for="add_page">Add</label>
						<input class="form-check-input" type="checkbox" id="add_page" >					  
					</div>
				</div>
				<div class="row mb-3">
					<div class="form-check col-md-4">
					</div>
					<div class="form-check col-md-4">
						<label class="form-check-label" for="edit_page">Edit</label>
						<input class="form-check-input" type="checkbox" id="edit_page" >					  
					</div>
					<div class="form-check col-md-4">
						<label class="form-check-label" for="delete_page">Delete</label>
						<input class="form-check-input" type="checkbox" id="delete_page" >					  
					</div>
						-->
                    </div>
                    @empty
                    @endforelse
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onClick="addRole()"
                            href="javascript:void(0)">Save</button>
                    </div>
            </form>
        </div>
    </div>
</div>
</div>
<!--end: Add department Modal -->
<!--start: Edit department Modal -->
<div class="modal fade" id="editRole" tabindex="-1" aria-labelledby="editRoleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 630px;">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoleLabel">Edit role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editRoleForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="edit_role_name" class="col-sm-3 col-form-label required">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_role_name" id="edit_role_name">
                        </div>
                    </div>
                    <input type="hidden" class="form-control" name="role_id" id="hidden_role_id" value="">
                    <label class="mb-2" for="permission">Permissions:</label>
                    @forelse($pages as $page)
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-check-label permissionLabel" for=""> {{$page->name}}</label>
                        </div>
                        @forelse($page->module as $val)
                        <div class="form-check col-md-2 mb-4">
                            <label class="form-check-label" for="{{'role_permissions_'.$val->id}}">
                                {{$val->module_name}}</label>
                            <input class="form-check-input" name="role_permissions[]" type="checkbox"
                                id="{{'role_permissions_'.$val->id}}" value="{{$val->id}}">
                        </div>
                        @empty
                        @endforelse
                    </div>
                    @empty
                    @endforelse
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onClick="updateRole()"
                            href="javascript:void(0)">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end: Edit role Modal -->
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('.message').fadeOut("slow");
    }, 2000);
    $('#role_table').DataTable({
        "order": []
        //"columnDefs": [ { "orderable": false, "targets": 7 }]
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

function openroleModal() {
    $('#role_name').val('');
    $('#addRole').modal('show');
}

function addRole() {
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/role')}}",
        data: $('#addRoleForm').serialize(),
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
                $("#addRole").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}

function editRole(id) {
    $('#hidden_role_id').val(id);
    $.ajax({
        type: "POST",
        url: "{{ url('/edit/role') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.role != null) {
                $('#editRole').modal('show');
                $('#edit_role_name').val(res.role.name);
            }
            if (res.RolePermission != null) {
                $.each(res.RolePermission, function(key, value) {
                    $('#role_permissions_' + value.module_id).prop('checked', true);
                });
            }
        }
    });
}

function updateRole() {

    $.ajax({
        type: "POST",
        url: "{{ url('/update/role') }}",
        data: $('#editRoleForm').serialize(),
        dataType: 'json',
        success: (res) => {
            if (res.errors) {
                $('.alert-danger').html('');

                $.each(res.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                })
            } else {
                $('.alert-danger').html('');
                $("#editRole").modal('hide');
                location.reload();
            }

        }
    });
}

function deleteRole(id) {

    if (confirm("Are you sure ?") == true) {
        // ajax
        $.ajax({
            type: "DELETE",
            url: "{{ url('/delete/role') }}",
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