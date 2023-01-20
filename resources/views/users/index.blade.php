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

<!--start: Add department Modal -->
<div class="modal fade" id="addRole" tabindex="-1" aria-labelledby="role" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="role">Add Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
		<form method="post" id="addDeaprtmentForm" action="">
		@csrf
			<div class="modal-body">
				<div class="mb-3">
					<label for="role_name" class="form-label">Name</label>
					<input type="text" class="form-control" id="role_name">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="addRole()" href="javascript:void(0)">Save</button>
			</div>
		</form>
    </div>
  </div>
</div>
<!--end: Add department Modal -->
@endsection
@section('js_scripts')
    <script>
       
		
		
	  </script>
@endsection  