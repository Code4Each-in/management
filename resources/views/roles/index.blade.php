@extends('layout')
@section('title', 'Departments')
@section('subtitle', 'Departments')
@section('content')
<center>  <h4>Roles</h4></center>
<button class="btn btn-primary" onClick="openroleModal()" href="javascript:void(0)">ADD ROLES</button>
<br><hr>

<div class="box-header with-border" id="filter-box">
@if(session()->has('message'))
    <div class="alert alert-success message">
        {{ session()->get('message') }}
    </div>
	 
@endif
<br>

	<div class="box-body table-responsive" style="margin-bottom: 5%">
		<table class="table table-hover" id="role_table">
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
						<i style ="color:#4154f1;" onClick="editRole('{{ $data->id }}')" href="javascript:void(0)"  class="fa fa-edit fa-fw"></i>
						
						<i style ="color:#4154f1;" onClick="deleteRole('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw"></i>
					</td>
				</tr>
					@empty
					@endforelse
			
			</tbody>
		</table>
	</div>
</div>
<!--start: Add role Modal -->
<div class="modal fade" id="addRole" tabindex="-1" aria-labelledby="role" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="width: 520px;">
      <div class="modal-header">
        <h5 class="modal-title" id="role">Add Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
		<form method="post" id="addRoleForm" action="">
		@csrf
			<div class="modal-body">
			    <div class="alert alert-danger" style="display:none"></div>
				<div class="mb-3">
				<div class="form-group">
				
					<label for="role_name" class="control-label required">Name</label>
					
				  </div>
					<!--<label for="role_name" class="form-label required">Name</label>-->
					<input type="text" class="form-control" id="role_name">
				  </div>
				  
				  	<label class="mb-2" for="permission">Permissions:</label>
					@forelse($pages as $page)
				<div class="row">
					<div class="col-md-4">
						<label class="form-check-label permissionLabel" for=""> {{$page->name}}</label>
					</div>
					@forelse($page->module as $val)
					<div class="form-check col-md-2 mb-3">
						<label class="form-check-label" for="listing_page"> {{$val->module_name}}</label>
						<input class="form-check-input" type="checkbox" id="listing_page" value="{{$val->id}}">			  
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
				<button type="button" class="btn btn-primary" onClick="addRole()" href="javascript:void(0)">Save</button>
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
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRoleLabel">Edit role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
		<form method="post" id="editRoleForm" action="">
		@csrf
			<div class="modal-body">
						    <div class="alert alert-danger" style="display:none"></div>

				<div class="mb-3">
					<label for="role_name" class="form-label">Name</label>
					<input type="text" class="form-control" id="edit_role_name">
				</div>
				<input type="hidden" class="form-control" id="hidden_role_id" value="">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary"  onClick="updateRole()"href="javascript:void(0)">Update</button>
			</div>
		</form>
    </div>
  </div>
</div>
<!--end: Edit role Modal -->
@endsection
@section('js_scripts')
    <script>
        $(document).ready(function(){
			
			setTimeout(function() {
		$('.message').fadeOut("slow");
		}, 2000 );
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
		
			function openroleModal(){
			$('#role_name').val('');
			$('#addRole').modal('show');
		}
			function addRole(){
			var roleName = $('#role_name').val();
			var userPage =0;
			if($("#user_page").prop('checked') == true){
					userPage = 1;
				}
			var departmentPage =0;
			if($("#department_page").prop('checked') == true){
					departmentPage = 1;
				}
			$.ajax({
				type:'POST',
				url: "{{ url('/add/role')}}",
				data: { roleName: roleName,userPage:userPage,
				departmentPage:departmentPage},
				cache:false,
				success: (data) => {
					if(data.errors){
					 $('.alert-danger').html('');

                            $.each(data.errors, function(key, value){
                                $('.alert-danger').show();
                                $('.alert-danger').append('<li>'+value+'</li>');
                            })
					}
					else
					{
						$('.alert-danger').html('');
						$("#addRole").modal('hide');
						location.reload();
					}
				},
				error: function(data){
				console.log(data);
				}
				});
			}
			function editRole(id){
				$('#hidden_role_id').val(id);
				$.ajax({
				type:"POST",
				url: "{{ url('/edit/role') }}",
				data: { id: id },
				dataType: 'json',
				success: function(res){
					if(res.role !=null){
						$('#editRole').modal('show');
						$('#edit_role_name').val(res.role.name);
					}
				}
				});
			} 
				function updateRole(){
				var id = $('#hidden_role_id').val();
				var name = $('#edit_role_name').val();
				$.ajax({
				type:"POST",
				url: "{{ url('/update/role') }}",
				data: { id: id, name:name },
				dataType: 'json',
				success: (res)=>
				{
					
					if(res.errors){
					 $('.alert-danger').html('');

                            $.each(res.errors, function(key, value){
                                $('.alert-danger').show();
                                $('.alert-danger').append('<li>'+value+'</li>');
                            })
					}
					else
					{
						$('.alert-danger').html('');
						$("#editRole").modal('hide');
						location.reload();
					}
					//if(res.status ==200){
					//	$("#editRole").modal('hide');
					//	location.reload();
					//}
				}
				});
			}
				function deleteRole(id){
					
				if (confirm("Are you sure ?") == true) {
					// ajax
					$.ajax({
					type:"DELETE",
					url: "{{ url('/delete/role') }}",
					data: { id: id },
					dataType: 'json',
					success: function(res){
						location.reload();
					}
					});
				}
			}			
	  </script>
@endsection  