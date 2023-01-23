@extends('layout')
@section('title', 'Departments')
@section('subtitle', 'Departments')
@section('content')
<center>  <h4>Users</h4></center>
<button class="btn btn-primary" onClick="openusersModal()" href="javascript:void(0)">ADD USERS</button>
<br><hr>

<div class="box-header with-border" id="filter-box">
<br>

	<div class="box-body table-responsive" style="margin-bottom: 5%">
		<table class="table table-hover" id="user_table">
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
					<td></td>
					<td></td>
					<td>{{ $data->address }}</td>
					<td>{{ $data->phone }}</td>

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
			<div class="row">
				<div class="col-lg-6">
					<label for="user_name" class="form-label">FirstName</label>
					<input type="text" class="form-control" id="user_name">
				</div>
				<input type="hidden" class="form-control" id="hidden_users_id" value="">
				<div class="col-lg-6">
					<label for="last_name" class="form-label">LastName</label>
					<input type="text" class="form-control" id="last_name">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<label for="email" class="form-label">Email</label>
					<input type="text" class="form-control" id="email">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6">
					<label for="phone" class="form-label">Phone</label>
					<input type="text" class="form-control" id="phone">
				</div>
				<div class="col-lg-6">
					<label for="salary" class="form-label">Salary</label>
					<input type="text" class="form-control" id="salary">
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
				<div class="col-lg-12 mt-3">
					<label for="password" class="form-label">Password</label>
					<input type="text" class="form-control" id="password">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 mt-3 ">Address
					<textarea name="address" class="form-control"  id="address"></textarea>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="addusers()" href="javascript:void(0)">Save</button>
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
			<div class="row">
				<div class="col-lg-6">
					<label for="user_name" class="form-label">FirstName</label>
					<input type="text" class="form-control" id="edit_username">
				</div>
				<div class="col-lg-6">
					<label for="last_name" class="form-label">LastName</label>
					<input type="text" class="form-control" id="edit_lastname">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<label for="email" class="form-label">Email</label>
					<input type="text" class="form-control" id="edit_email">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6">
					<label for="phone" class="form-label">Phone</label>
					<input type="text" class="form-control" id="edit_phone">
				</div>
				<div class="col-lg-6">
					<label for="salary" class="form-label">Salary</label>
					<input type="text" class="form-control" id="edit_salary">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6">
					<label for="department" class="form-label">Department</label>
					<input type="text" class="form-control" id="edit_department">
				</div>
				<div class="col-lg-6">
					<label for="salary" class="form-label">Salary</label>
					<input type="text" class="form-control" id="edit_salary">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<label for="password" class="form-label">Password</label>
					<input type="text" class="form-control" id="edit_password">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">Address
					<textarea name="address" class="form-control"  id="edit_address"></textarea>
				</div>
			</div>
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
			
            $('#users_table').DataTable({
                "order": []
                //"columnDefs": [ { "orderable": false, "targets": 7 }]
            });
		$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});
		});
		
			function openusersModal(){
			$('#first_name').val('');
			$('#addUsers').modal('show');
		}
		
			function addusers(){
				var userName = $('#user_name').val();	
				var lastname = $('#last_name').val();	
				var email = $('#email').val();
				var phone = $('#phone').val();
				var password = $('#password').val();
				var salary = $('#salary').val();
				var address = $('#address').val();
				var address = $('#address').val();
				var role_id =$('#role_select').val();
				var department_id =$('#department_select').val();

			$.ajax({
				type:'POST',
				url: "{{ url('/add/users')}}",
				data: { userName:userName ,
				lastname:lastname,
				email:email,
				password:password,
				salary:salary,
				address:address,
				phone:phone,role_id:role_id,department_id:department_id},
				cache:false,
				success: (data) => {
					if(data.status ==200){
						$("#addUsers").modal('hide');
						location.reload();
					}
				},
				error: function(data){
				console.log(data);
				}
				});
			}
			
			
				function editUsers(id){
				$('#hidden_users_id').val(id);
				$.ajax({
				type:"POST",
				url: "{{ url('/edit/users') }}",
				data: { id: id},
				dataType: 'json',
				success: function(res){
					if(res.users !=null){
						$('#editUsers').modal('show');
						$('#edit_username').val(res.users.first_name);	
						$('#edit_lastname').val(res.users.last_name);											
						$('#edit_email').val(res.users.email);
						$('#edit_phone').val(res.users.phone);
						$('#edit_salary').val(res.users.salary);
						$('#edit_address').val(res.users.address);
						$('#edit_password').val(res.users.password);

					}
				}
				});
			} 
			
				function updateUsers(){
				var id = $('#hidden_users_id').val();
				var first_name = $('#edit_username').val();
				var last_name = $('#edit_lastname').val();
				var email = $('#edit_email').val();
				var phone = $('#edit_phone').val();
				var salary = $('#edit_salary').val();
				var address = $('#edit_address').val();
				var password = $('#edit_password').val();


				$.ajax({
				type:"POST",
				url: "{{ url('/update/users') }}",
				data: { id: id,first_name:first_name,
				last_name:last_name,email:email,phone:phone,salary:salary,address:address,
				password:password},
				dataType: 'json',
				success: function(res){
					if(res.status ==200){
						$("#editRole").modal('hide');
						location.reload();
					}
				}
				});
				
			}
				function deleteUsers(id){
				if (confirm("Are you sure ?") == true) {
					// ajax
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