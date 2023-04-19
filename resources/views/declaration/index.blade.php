@extends('layout')
@section('title', 'Self Declarations')
@section('subtitle', 'Self Declarations')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openSelfDeclarationModal()" href="javascript:void(0)">ADD SELF DEACLARATION</button>
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
                                <th>Full Name</th>
                                <th>Father Name</th>
                                <th>Adharcard No</th>
                                <th>Block</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($selfDeclaration as $data)
                            <tr>
                                <td>{{ $data->full_name }}</td>
                                <td>{{ $data->father_name }}</td>
                                <td>{{ $data->adharcard_no }}</td>
                                <td>{{ $data->block }}</td>
                                <td>{{ $data->address }}</td>
                               
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

<!--start: Add users Modal -->
<div class="modal fade" id="declarationModal" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width:570px;">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Self Declaration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="declarationForm">
                @csrf
                <div class=" modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    
                    <div class="row mb-3 mt-4">
                        <label for="full_name" class="col-sm-3 col-form-label required">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="full_name" id="full_name">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="father_name" class="col-sm-3 col-form-label required">Father Name </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="father_name" id="father_name">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="adharcard_no" class="col-sm-3 col-form-label required">Adharcard no</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="adharcard_no" id="adharcard_no">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="address" class="col-sm-3 col-form-label required">Address</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="address" id="address">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="block" class="col-sm-3 col-form-label required">Block</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="block" id="block">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="district" class="col-sm-3 col-form-label required">District</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="district" id="district">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="state" class="col-sm-3 col-form-label required">State</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="state" id="state">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="zip" class="col-sm-3 col-form-label required">Zip</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="zip" id="zip">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary"  onClick="addSelfDeclaration()" href="javascript:void(0)">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<!--end: Add department Modal -->
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

function openSelfDeclarationModal() {
    // $('#role_name').val('');
    $('#declarationModal').modal('show');
}

function addSelfDeclaration() {
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/declaration')}}",
        data: $('#declarationForm').serialize(),
        success: (data) => {
            if (data.errors) {
                $('.alert-danger').html('');
                $.each(data.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                })
            } else {
                $('.alert-danger').html('');
                $("#declarationModal").modal('hide');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}
</script>
@endsection