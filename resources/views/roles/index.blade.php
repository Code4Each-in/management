@extends('layout')
@section('title', 'Departments')
@section('subtitle', 'Departments')
@section('content')
<center>  <h4>Roles</h4></center>
<button class="btn btn-primary" onClick="openroleModal()" href="javascript:void(0)">ADD ROLES</button>
<br><hr>

<div class="box-header with-border" id="filter-box">
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
						
					</td>
				</tr>
					@empty
					@endforelse
			
			</tbody>
		</table>
	</div>
</div>
<!--start: Add department Modal -->
<div class="modal fade" id="addRole" tabindex="-1" aria-labelledby="roledepartment" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="roledepartment">Add Department</h5>
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
        $(document).ready(function(){
			
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
			$.ajax({
				type:'POST',
				url: "{{ url('/add/role')}}",
				data: { roleName: roleName },
				cache:false,
				success: (data) => {
					if(data.status ==200){
						$("#addRole").modal('hide');
						location.reload();
					}
				},
				error: function(data){
				console.log(data);
				}
				});
			}
	  </script>
@endsection  