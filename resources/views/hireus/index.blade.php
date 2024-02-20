@extends('layout')
@section('title', 'Hire Us')
@section('subtitle', 'Hire Us')
@section('content')
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading..">
</div>
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">

            <!-- filter -->
            <div class="box-header with-border" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%;margin-top:5%">
                    <table class="table table-borderless dashboard" id="hireus_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Hiring for</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                use Carbon\Carbon;
                            @endphp

                            @forelse($hire as $index => $data)
                            <tr>
                                <td> {{ $index + 1 }}</td>
                                <td>{{$data->name}}</td>
                                <td>{{$data->email}}</td>
                                <td>{{$data->phone}}</td>
                                <td>
                                {{$data->skill}}
                                </td>
                                <td>{{$data->message}}</td>
                               <td>{{$data->status}}</td>
                                <!-- <td> {{$data->status}}</td> -->
                                <td>{{date("d M Y", strtotime($data->created_at));}}</td>
                                @if (auth()->user()->role->name == "Super Admin" ||auth()->user()->role->name == "HR Manager")
                                <td>
                                    <i style="color:#4154f1;" onClick="editHireus('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-edit fa-fw pointer"></i>
                                    <i style="color:#4154f1;" onClick="deleteHireus('{{ $data->id }}')"
                                        href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                </td>
                                @endif
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

<!--start: Edit Module Modal -->
<div class="modal fade" id="editHireus" tabindex="-1" aria-labelledby="editHireusLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editHireusLabel">Edit Hire Us</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editHireusForm" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>

                    <div class="row mb-3">
                        <label for="edit_name" class="col-sm-3 col-form-label required">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_name" id="edit_name">
                        </div>
                        @if ($errors->has('edit_name'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_name') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <label for="edit_email" class="col-sm-3 col-form-label required">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" name="edit_email" id="edit_email">
                        </div>
                        @if ($errors->has('edit_email'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_email') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="edit_phone" class="col-sm-3 col-form-label required">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_phone" id="edit_phone">
                        </div>
                        @if ($errors->has('edit_phone'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_phone') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="edit_phone" class="col-sm-3 col-form-label required">Skill</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="edit_skill" id="edit_skill">
                        </div>
                        @if ($errors->has('edit_skill'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_skill') }}</span>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <label for="edit_phone" class="col-sm-3 col-form-label required">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control form-select" id="status" name="status">
                               <option value="active">Active</option>
                               <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        @if ($errors->has('edit_phone'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_phone') }}</span>
                        @endif
                    </div>


                    <input type="hidden" class="form-control" id="hidde_hireus_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onClick="updateHireus()"
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
        $('#hireus_table').DataTable({
            "order": []
            //"columnDefs": [ { "orderable": false, "targets": 7 }]
        });
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function editHireus(id){
        $('#hidde_hireus_id').val(id)

    $.ajax({
        type: "POST",
        url: "{{ url('/edit/hireus') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.data != null) {
                $('#editHireus').modal('show');
                $('#edit_name').val(res.data.name);
                $('#edit_email').val(res.data.email);
                $('#edit_phone').val(res.data.phone);
                $('#status').val(res.data.status);
                $('#edit_skill').val(res.data.skill);
            }
        }
    });
  }

  function deleteHireus(id){
 if(confirm("Are you sure You Want To Delete Hire Us From List?") == true){
    $.ajax({
        type: "DELETE",
        url: "{{ url('/delete/hireus') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
        if(res.status==200){
            location.reload();
        }
        }
    });
 }

  }


  function updateHireus() {
    var id = $('#hidde_hireus_id').val();
            var name  =   $('#edit_name').val();
            var email  =  $('#edit_email').val();
            var phone  =  $('#edit_phone').val();
            var status =  $('#status').val();
            var skill  =  $('#edit_skill').val();

    $.ajax({
        type: "POST",
        url: "{{ url('/update/hireus') }}",
        data: {
            id: id,
            edit_name:name,
            edit_email:email,
            edit_phone:phone,
            edit_skill:skill,
            status:status,
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
                $("#editHoliday").modal('hide');
                location.reload();
            }
        }
    });
}
    </script>
@endsection
