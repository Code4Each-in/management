@extends('layout')
@section('title', 'Departments')
@section('subtitle', 'Departments')
@section('content')
<center>  <h4>Departments</h4></center>

<button class="btn btn-primary" onClick="openDepartmentModal()" href="javascript:void(0)">ADD NEW DEPARTMENT</button>
<br><hr>

<!-- filter -->
<div class="box-header with-border" id="filter-box">
<br>

	<div class="box-body table-responsive" style="margin-bottom: 5%">
		<table class="table table-hover" id="department_table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Action</th>

				</tr>
			</thead>

			<tbody>
			@forelse($departmentData as $data)
				<tr>
					<td>{{ $data->name }}</td>
					<td>
						<i style ="color:#4154f1;" 
						onClick="editDepartment('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-edit fa-fw"></i>
						<i style ="color:#4154f1;" onClick="deleteDepartment('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw"></i>	
					</td>
				</tr>
				
			@empty
			@endforelse
			</tbody>
		</table>
	</div>
</div>

<!--start: Add department Modal -->
<div class="modal fade" id="addDepartment" tabindex="-1" aria-labelledby="addDepartmentLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addDepartmentLabel">Add Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
		<form method="post" id="addDeaprtmentForm" action="">
		@csrf
			<div class="modal-body">
				<div class="mb-3">
					<label for="department_name" class="form-label">Name</label>
					<input type="text" class="form-control" id="department_name">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="addDepartment()" href="javascript:void(0)">Save</button>
			</div>
		</form>
    </div>
  </div>
</div>
<!--end: Add department Modal -->

<!--start: Edit department Modal -->
<div class="modal fade" id="editDepartment" tabindex="-1" aria-labelledby="editDepartmentLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editDepartmentLabel">Edit Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
		<form method="post" id="editDeaprtmentForm" action="">
		@csrf
			<div class="modal-body">
				<div class="mb-3">
					<label for="department_name" class="form-label">Name</label>
					<input type="text" class="form-control" id="edit_department_name">
				</div>
				<input type="hidden" class="form-control" id="hidden_department_id" value="">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="updateDepartment()" href="javascript:void(0)">Update</button>
			</div>
		</form>
    </div>
  </div>
</div>
<!--end: Edit department Modal -->
@endsection
@section('js_scripts')
    <script>
        $(document).ready(function(){
			
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
		function openDepartmentModal(){
			$('#department_name').val('');
			$('#addDepartment').modal('show');
		}
		function addDepartment(){
			var departmentName = $('#department_name').val();
			$.ajax({
				type:'POST',
				url: "{{ url('/add/department')}}",
				data: { departmentName: departmentName },
				cache:false,
				success: (data) => {
					if(data.status ==200){
						$("#addDepartment").modal('hide');
						location.reload();
					}
				},
				error: function(data){
				console.log(data);
				}
				});
			}
			function editDepartment(id){
				$('#hidden_department_id').val(id);
				$.ajax({
				type:"POST",
				url: "{{ url('/edit/department') }}",
				data: { id: id },
				dataType: 'json',
				success: function(res){
					if(res.department !=null){
						$('#editDepartment').modal('show');
						$('#edit_department_name').val(res.department.name);
					}
				}
				});
			} 
			function updateDepartment(){
				var id = $('#hidden_department_id').val();
				var name = $('#edit_department_name').val();
				$.ajax({
				type:"POST",
				url: "{{ url('/update/department') }}",
				data: { id: id, name:name },
				dataType: 'json',
				success: function(res){
					if(res.status ==200){
						$("#editDepartment").modal('hide');
						location.reload();
					}
				}
				});
			} 
			function deleteDepartment(id){
				if (confirm("Are you sure?") == true) {
					// ajax
					$.ajax({
					type:"DELETE",
					url: "{{ url('/delete/department') }}",
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