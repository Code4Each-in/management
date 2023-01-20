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
					<th>Name</th>
					<th>Lastname</th>

				</tr>
			</thead>

			<tbody>
		
				<tr>
					<td></td>
					<td>
						
					</td>
				</tr>
					
			
			
			</tbody>
		</table>
	</div>
</div>


<!--end: Add department Modal -->
<!--start: Add department Modal -->
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
					<label for="first_name" class="form-label">FirstName</label>
					<input type="text" class="form-control" id="users_name">
				</div>
				<div class="col-lg-6">
					<label for="last_name" class="form-label">LastName</label>
					<input type="text" class="form-control" id="users_name">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<label for="email" class="form-label">Email</label>
					<input type="text" class="form-control" id="users_name">
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
				<div class="col-lg-12">
					<label for="password" class="form-label">Password</label>
					<input type="text" class="form-control" id="password">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">Address
					<textarea name="address" class="form-control"  id="textarea"></textarea>
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
<!--end: Add department Modal -->
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
		
		
	  </script>
@endsection  