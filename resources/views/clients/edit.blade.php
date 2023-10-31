
@extends('layout')
@section('title', 'Clients')
@section('subtitle', 'Edit')
@section('content')
<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
            <form method="post" id="edit-client-form" enctype="multipart/form-data">
                @csrf
                @method('PUT') <!-- Use 'PUT' HTTP method -->
                <div class="form-group">
                    <label for="edit_clientname">Client Name</label>
                    <input type="text" class="form-control" name="name" id="edit_clientname" value="{{ $client->name }}">
                    @error('name')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="text" class="form-control" name="email" id="edit_email" value="{{ $client->email }}">
                    @error('email')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="edit_email">Phone</label>
                    <input type="text" class="form-control" name="phone" id="edit_phone" value="{{ $client->phone }}">
                    @error('phone')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="edit_birth_date">birth date</label>
                    <input type="text" class="form-control" name="birth_date" id="edit_birth_date" value="{{ $client->birth_date }}">
                    @error('birth_date')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>


                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <input type="text" class="form-control" name="address" id="edit_address" value="{{ $client->address }}">
                    @error('address')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="edit_birth_date">City</label>
                    <input type="text" class="form-control" name="city" id="edit_city" value="{{ $client->city }}">
                    @error('city')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="edit_state">State</label>
                    <input type="text" class="form-control" name="edit_state" id="edit_state" value="{{ $client->state }}">
                    @error('state')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="edit_zip">Zip</label>
                    <input type="text" class="form-control" name="edit_zip" id="edit_zip" value="{{ $client->zip }}">
                    @error('zip')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="edit_project">Select projects</label>
                    <input type="select" class="form-control" name="edit_project" id="edit_project" value="{{ $client->project }}">
                    @error('project')
                    <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

    <button type="submit">Save</button>
</form>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#edit-client-form').submit(function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                type: 'PUT', // Use 'PUT' or 'PATCH' HTTP method as appropriate
                url: "{{ url('/clients/edit')}}",
                data: formData,
                success: function(data) {
                    // Handle success, e.g., show a success message
                    console.log(data);
                },
                error: function(err) {
                    // Handle errors, e.g., display error messages
                    console.error(err);
                }
            });
        });
    });

    $(function(){
  $('#edit-client-form').submit(function() {
    $('#loader').show(); 
    return true;
  });
});

function editClient(id) {
    $('#edit-client-form').val(id);

    $.ajax({
        type: "POST",
        url: "{{ url('/edit/client') }}",
        data: {
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.client != null) {
                $('#edit-client-form').modal('show');
        
            }
        }
    });
}

function updateClient() {
    var id = $('#edit-client-form').val();
   
    $.ajax({
        type: "POST",
        url: "{{ url('/update/client') }}",
        data: {
            id: id,
            edit_client_name: edit_client_name
            
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
