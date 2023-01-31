@extends('layout')
@section('title', 'Departments')
@section('subtitle', 'Departments')
@section('content')
<center>  <h4>Users</h4></center>
<button class="btn btn-primary" onClick="openusersModal()" href="javascript:void(0)">ADD USERS</button>
<br><hr>

<div class="box-header with-border" id="filter-box">
<br>
@if(session()->has('message'))
	<div class="alert alert-success message">
		{{ session()->get('message') }}
	</div>	 
@endif


	<div class="box-body table-responsive" style="margin-bottom: 5%">
		<table class="table table-hover" id="users_table">
			<thead>
				<tr>
					<th>FirstName</th>
					<th>LastName</th>
					<th>Email</th>
					<th>Password</th>
					<th>Salary</th>
					<th>Department</th>
					<th>Role</th>
					<th>Address</th>
					<th>Phone</th>
					<th>Active</th>
					<th>Action</th>

				</tr>
			</thead>

			<tbody>
			@forelse($usersData as $data)
				<tr>
					<td>{{ $data->first_name }}</td>
					<td>{{ $data->last_name }}</td>
					<td>{{ $data->email }}</td>
					<td>{{ $data->password }}</td>
					<td>{{ $data->salary }}</td>
					<td>{{$data->role_id}}</td>
					<td>{{$data->department_id}}</td>
					<td>{{ $data->address }}</td>					
					<td>{{ $data->phone }}</td>
					<td>
						<div class="form-group form-check active_user">
							<input type="checkbox" onClick="Showdata(this)" data-user-id = "{{ $data->id}}" class="form-check-input" id="{{'active_user_'.$data->id}}" {{$data->status == 1 ? 'checked' : ''}}>
							<label class="form-check-label" for="active_user"></label>
						</div>
					</td>		
						<td>
							<i style ="color:#4154f1;"  onClick="editUsers('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-edit fa-fw"></i>
							
							<i style ="color:#4154f1;" onClick="deleteUsers('{{ $data->id }}')" href="javascript:void(0)"class="fa fa-trash fa-fw"></i>
						</td>
				</tr>
					
					@empty
					@endforelse
			
			</tbody>
		</table>
	</div>
</div>


<!--start: Add users Modal -->
<div class="modal fade" id="addUsers" tabindex="-1" aria-labelledby="role" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="role">Add Users</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>	
      </div>
		<form method="post" id="addUsersForm" action="">
		@csrf
		<div class="modal-body">
			<div class="alert alert-danger" style="display:none"></div>
				<div class="row">
				<div class="col-lg-6">
					<label for="user_name" class="form-label">FirstName</label>
					<input type="text" class="form-control" name="user_name" id="user_name">
				</div>
				<div class="col-lg-6">
					<label for="last_name" class="form-label">LastName</label>
					<input type="text" class="form-control" name="last_name" id="last_name">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<label for="email" class="form-label">Email</label>
					<input type="text" class="form-control" name="email" id="email">
				</div>
			</div>
			<div class="row">
				<div class="col-sm-5">
					<label for="phone" class="form-label">Phone</label>
					<input type="text" class="form-control" name="phone" id="phone">
				</div>
				<div class="col-sm-3 mt-4">
					<div class="form-group form-check">
						<input type="checkbox" class="form-check-input" name="salaried" id="salaried">
						<label class="form-check-label"  for="salaried">If salaried</label>
					</div>
				</div>
				<div class="col-sm-4 mt-4">
					<input style="display:none;" name="addsalary" type="number" class="form-control" id="addsalary">
				</div>
			</div>			
			<div class="row">
				<div class="col-md-6 mt-3">
					<div class="form-group">
						<label for="">Select Role</label>
						<select name="role_select" class="form-control" id="role_select">
						<option value="">-- Select Role --</option>
                         @foreach ($roleData as $data)
                         <option value="{{$data->id}}">
                         {{$data->name}}
                         </option>
                         @endforeach
						</select>
					</div>
				</div>
				<div class="col-md-6 mt-3">
					<div class="form-group">
						<label for="">Select Department</label>
						<select name="department_select" class="form-control" id="department_select">
						<option value="">-- Select Department --</option>
                         @foreach ($departmentData as $data)
                         <option value="{{$data->id}}">
                         {{$data->name}}
                         </option>
                         @endforeach
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12 mt-3">
					<div class="form-group">
						<label for="">Select Manager</label>
						<select name="manager_select[]" class="form-control select" id="manager_select" multiple>
						<option value="" disabled>-- Select Manager --</option>
                         @foreach ($usersData as $data)
                         <option value="{{$data->id}}">
                         {{$data->first_name.' '.$data->last_name}}
                         </option>
                         @endforeach
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 mt-3">
					<label for="password" class="form-label">Password</label>
					<input type="text" name="password" class="form-control" id="password">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 mt-3 ">Address
					<textarea name="address" class="form-control"  id="address"></textarea>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="addusers(this)" href="javascript:void(0)">Save</button>
			</div>
		</form>
    </div>
  </div>
</div>
</div>
<!--end: Add department Modal -->

<div class="modal fade" id="editUsers" tabindex="-1" aria-labelledby="editUsersLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUsersLabel">Add Users</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
		<form method="post" id="editUsersForm" action="">
		@csrf
		<div class="modal-body">
			<div class="alert alert-danger" style="display:none"></div>
			<div class="row">
				<div class="col-lg-6">
					<label for="user_name" class="form-label">FirstName</label>
					<input type="text"  class="form-control" name="edit_username" id="edit_username">
				</div>
				<div class="col-lg-6">
					<label for="last_name" class="form-label">LastName</label>
					<input type="text" class="form-control" name="edit_lastname"  id="edit_lastname">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<label for="email" class="form-label">Email</label>
					<input type="text" class="form-control" name="edit_email"  id="edit_email">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-5">
					<label for="phone" class="form-label">Phone</label>
					<input type="text"  class="form-control" name="edit_phone" id="edit_phone">
				</div>
				<div class="col-sm-3 mt-4">
					<div class="form-group form-check">
						<input type="checkbox" class="form-check-input"  name="edit_salaried" id="edit_salaried">
						<label class="form-check-label" for="salaried">If salaried</label>
					</div>
				</div>
					<div class="col-sm-4 mt-4">
						<input style="display:none;" name="edit_salary" type="number" class="form-control" id="edit_salary">
					</div>
			</div>
			<div class="row">
				<div class="col-md-6 mt-3">
					<div class="form-group">
						<label for="">Select Role</label>
						<select name="role_select" class="form-control" id="role_select">
						<option value="">-- Select Role --</option>
                         @foreach ($roleData as $data)
                         <option value="{{$data->id}}">
                         {{$data->name}}
                         </option>
                         @endforeach
						</select>
					</div>
				</div>
				<div class="col-md-6 mt-3">
					<div class="form-group">
						<label for="">Select Department</label>
						<select name="department_select" class="form-control" id="department_select">
						<option value="">-- Select Department --</option>
                         @foreach ($departmentData as $data)
                         <option value="{{$data->id}}">
                         {{$data->name}}
                         </option>
                         @endforeach
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<label for="password" class="form-label">Password</label>
					<input type="text" name="edit_password"  class="form-control" id="edit_password">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">Address
					<textarea name="address" class="form-control" name="edit_address"  id="edit_address"></textarea>
				</div>
			</div>
			<input type="hidden" class="form-control" name="users_id" id="users_id" value="">
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="updateUsers()" href="javascript:void(0)">Save</button>
			</div>
		</form>
    </div>
  </div>
</div>
@endsection
@section('js_scripts')
    <script>
       $(document).ready(function(){
		setTimeout(function()
			{
				$('.message').fadeOut("slow");
			}, 2000 );
		
			
            $('#users_table').DataTable({
                "order": []
                
            });
			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});
			
			$('#salaried').on('click',function(e)
			{
				if(e.target.checked == true){
				
					$('#addsalary').show();
				}
				else
				{
					$('#addsalary').hide();
					$('#addsalary').val('');
				}
			});
			$('#edit_salaried').on('click',function(e)
			{
				if(e.target.checked == true){
					
					$('#editsalary').show();
				}
				else
				{
					$('#editsalary').hide();
					$('#editsalary').val('');
				}
			});
				
		});
		
			function Showdata(ele)
			{				
				var dataId = $(ele).attr("data-user-id");
								
				var status =0;
				if($("#active_user_"+dataId).prop('checked') == true){
					status = 1;
				}
				$.ajax({
				type:'POST',
				url: "{{ url('/update/users/status')}}",
				data: { dataId:dataId ,
				status:status,
				},
				cache:false,
				success: (data) => {
					if(data.status ==200){
						location.reload();
					}
				},
				error: function(data){
				console.log(data);
				}
				});
												
			}
			function openusersModal()
			{
				$('.alert-danger').html('');
				$('#first_name').val('');
				$('#addUsers').modal('show');
			}		
			function addusers()
			{
	
				$.ajax({
				type:'POST',
				url: "{{ url('/add/users')}}",
				data: $('#addUsersForm').serialize(),
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

						$("#addUsers").modal('hide');
						location.reload();
					}
				},
				error: function(data){
				console.log(data);
				}
				});
			}						
				function editUsers(id)
				{
					$('.alert-danger').html('');
					$('#users_id').val(id);
					$.ajax({
					type:"POST",
					url: "{{ url('/edit/users') }}",
					data: { id: id},
					dataType: 'json',
					success: function (res)
					{						
						if(res.users !=null)
						{
							$('#editUsers').modal('show');
							$('#edit_username').val(res.users.first_name);	
							$('#edit_lastname').val(res.users.last_name);											
							$('#edit_email').val(res.users.email);
							$('#edit_phone').val(res.users.phone);
							if(res.users.salary !=null)
							{
								$("#edit_salaried").prop('checked', true);
								$('#edit_salary').show();
								$('#edit_salary').val(res.users.salary);
							}						
								$('#edit_address').val(res.users.address);
								$('#edit_password').val(res.users.password);
								$('#role_select option[value="'+ res.users.role_id +'"]').attr('selected','selected');	
								$('#department_select option[value="'+ res.users.department_id +'"]').attr('selected','selected');								
						}
					}
					});
				} 			
				function updateUsers(){
				
			
				$.ajax({
				type:"POST",
				url: "{{ url('/update/users') }}",
				data: $('#editUsersForm').serialize(),
				dataType: 'json',
				success: function(res){
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
						$("#editUsers").modal('hide');
						location.reload();
					}
				}
				});				
			}
				function deleteUsers(id){
				if (confirm("Are you sure ?") == true)
				{					
					$.ajax({
					type:"DELETE",
					url: "{{ url('/delete/users') }}",
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