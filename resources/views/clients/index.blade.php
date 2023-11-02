<?php
use App\Models\Client;?>

@extends('layout')
    @section('title', 'Clients')
    @section('subtitle', 'Clients')
    @section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <button class="btn btn-primary mt-3 mb-4" onClick="openclientsModal()">Add
                    client
                </button>
                <table class="table table-borderless dashboard" id="clients">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Client Name</th>
                            <th>Email</th>
                            <th>Phone number</th>
                            <th>Birth date</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Zip</th>
                            <th>State</th>
                            @if (auth()->user()->role['name'] == 'Super Admin' || auth()->user()->role['name'] == 'HR Manager') 
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td>{{ $client->id }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->birth_date }}</td>
                            <td>{{ $client->address }}</td>
                            <td>{{ $client->city }}</td>
                            <td>{{ Client::getStatus($client->status) }}</td>
                            <td>{{ $client->zip }}</td>
                            <td>{{ $client->state }}</td>
                            @if (auth()->user()->role['name'] == 'Super Admin' || auth()->user()->role['name'] == 'HR Manager') 
                                <td>  
                                    <a href="{{ route('clients.show', ['id' => $client->id]) }}">
                                        <i class="fas fa-eye" style="color: #007bff;"></i>
                                    </a>

                                    <a href="javascript:void(0)" onClick="editClientData('{{ $client->id }}')">                                       
                                         <i class="fas fa-edit" style="color: #007bff;"></i>                                       
                                    </a>
                                    <a href="{{ route('clients.delete', ['id' => $client->id]) }}">
                                        <i class="fas fa-trash" style="color: #007bff;"></i>

                                    </a>
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
        <!---Add Client-->
        <div class="modal fade" id="addClient" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 550px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role" id="openaddClient">Add Client</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addClientForm" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="name" class="col-sm-3 col-form-label required">Client Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter client name">
                                </div>
                                
                            </div>
                            <div class="row mb-3">
                                <label for="email" class="col-sm-3 col-form-label required">Email</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="email" id="email" placeholder="Enter email">
                                </div>
                                
                            </div>
                            <div class="row mb-3">
                                <label for="phone" class="col-sm-3 col-form-label required">Phone number</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter phone number">
                                </div>
                                
                            </div>
                            <div class="row mb-3">
                                <label for="birth_date" class="col-sm-3 col-form-label">Birth date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" name="birth_date" id="birth_date" placeholder="Enter birth date">
                                </div>
                                
                            </div>
                        
                            <div class="row mb-3">
                                <label for="address" class="col-sm-3 col-form-label">Address</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="address" id="address" placeholder="Enter address">
                                </div>
                                
                            </div>
                            <div class="row mb-3">
                                <label for="city" class="col-sm-3 col-form-label">City</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="city" id="city" placeholder="Enter city">
                                </div>
                                
                            </div>
                            <div class="row mb-3">
                                <label for="state" class="col-sm-3 col-form-label">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-control">
                                        <option value="0">Active</option>
                                        <option value="1">Inactive</option>
                                        <option value="2">Talked</option>
                                    </select>
                                </div>
                            
                            </div>
                            <div class="row mb-3">
                                <label for="zip" class="col-sm-3 col-form-label">Zip</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="zip" id="zip" placeholder="Enter zip">
                                </div>
                                
                            </div>

                            <div class="row mb-3">
                                <label for="address" class="col-sm-3 col-form-label">State</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="state" id="state" placeholder="Enter state">
                                </div>
                            </div>
                        
                            <div class="row mb-3">
                            <label for="project" class="col-sm-3 col-form-label">Projects</label>
                                <div class="col-sm-9">
                                    <select name="projects" class="form-select form-control" id="project_id">
                                        <option value="" disabled selected>Select Project</option>
                                        @foreach ($projects as $data)
                                        <option value="{{$data->id}}">
                                            {{$data->project_name}} 
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="company" class="col-sm-3 col-form-label">Company</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="company" id="company" placeholder="Enter company name">
                                </div>
                            </div>
                        </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!---end Add modal-->
    @endsection
  <!---edit Client-->
  <div class="modal fade" id="editClient" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 630px;">
            <div class="modal-header">
                <h5 class="modal-title" id="role" id="openeditClient">Edit Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editClientForm">
                @csrf
                <input type="hidden" name="id" value="">
                <div class="modal-body">
                    <div class="alert alert-danger" id="update-issue" style="display:none"></div>
                    <div class="row mb-3">
                        <label for="name" class="col-sm-3 col-form-label required">Client Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="name" id="name" placeholder="Enter client name">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="email" class="col-sm-3 col-form-label required">Email</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="email" id="email" placeholder="Enter email">
                        </div>    
                    </div>

                    <div class="row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label required">Phone number</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="birth_date" class="col-sm-3 col-form-label">Birth date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="birth_date" id="birth_date" placeholder="Enter birth date">
                        </div>
                    </div>
                
                    <div class="row mb-3">
                        <label for="address" class="col-sm-3 col-form-label">Address</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="address" id="address" placeholder="Enter address">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="city" class="col-sm-3 col-form-label">City</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="city" id="city" placeholder="Enter city">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="status" class="col-sm-3 col-form-label">Status</label>
                        <div class="col-sm-9">
                        <select name="status" class="form-control">
                                <option value="0">Active</option>
                                <option value="1">Inactive</option>
                                <option value="2">Talked</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="zip" class="col-sm-3 col-form-label">Zip</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="zip" id="zip" placeholder="Enter zip">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="state" class="col-sm-3 col-form-label">State</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="state" id="state" placeholder="Enter state">
                        </div>  
                    </div>

                
                    <div class="row mb-3">
                    <label for="project" class="col-sm-3 col-form-label">Projects</label>
                        <div class="col-sm-9">
                            <select name="projects" class="form-select form-control" id="project_id">
                                <option value="" disabled selected>Select Project</option>
                                @foreach ($projects as $data)
                                <option value="{{$data->id}}">
                                    {{$data->project_name}}  
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="company" class="col-sm-3 col-form-label">Company</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="company" id="company" placeholder="Enter company name">
                        </div>
                    </div>
                </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
        </form>
        </div>
    </div>
</div>
<!---end edit modal-->

  @section('js_scripts')
  <script>

    $(document).ready(function() {
        setTimeout(function() {
            $('.message').fadeOut("slow");
        }, 2000);
        $('#clients').DataTable({
            "order": []
        });

    $("#addClientForm").submit(function(event) {
        event.preventDefault();
        var formData = new FormData(this);
        var csrfToken = $('input[name="_token"]').val();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });

        $.ajax({
            type: 'POST',
            url: "{{ url('/clients/store')}}",
            data: formData,
            processData: false,
            contentType: false,
            success: (data) => {
                // Comment out this line to prevent the modal from closing on success
                $("#addClient").modal('hide');
                location.reload();
            },
            error: function(data) {
                $('.alert-danger').html('');
                $('.alert-danger').css('display','block');
                console.log(data.responseJSON.message);
                $.each(data.responseJSON.errors, function(key, value) {    
                    $('.alert-danger').append('<li>' + value + '</li>');
                });
            }
        });
    });

        $("#editClientForm").submit(function(event) {
            event.preventDefault();
            var formData = new FormData(this);
            var csrfToken = $('input[name="_token"]').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            $.ajax({
                type: 'POST',
                url: "{{ url('/clients/update')}}",
                data: formData,
                processData: false,
                contentType: false,
                success: (data) => {
                    // Comment out this line to prevent the modal from closing on success
                    $("#editClient").modal('hide');
                    location.reload();
                },
                error: function(data) {
                    $('#update-issue').html('');
                    $('#update-issue').css('display','block');
                    $.each(data.responseJSON.errors, function(key, value) {    
                        $('#update-issue').append('<li>' + value + '</li>');
                    });
                }
            });
        });
    });

        $('#user').select2({
        dropdownParent: $('#addClient')
    });

function editClientData(clientId) {
          
            $('#editClient').modal('show');
            let clientID = clientId;

            $.ajax({
                type: 'get',
                url: "{{ url('/clients/edit') }}/"+clientID,
                processData: false,
                contentType: false,
                success: (data) => {
                    $('#editClientForm')[0].reset();

                    // append data in form fields
                    $("#editClientForm input[name='id']").val(data.id);
                    $("#editClientForm input[name='name']").val(data.name);
                    $("#editClientForm input[name='email']").val(data.email);
                    $("#editClientForm input[name='phone']").val(data.phone);
                    $("#editClientForm input[name='birth_date']").val(data.birth_date);
                    $("#editClientForm input[name='address']").val(data.address);
                    $("#editClientForm input[name='city']").val(data.city);
                    $("#editClientForm select[name='status']").val(data.status);
                    $("#editClientForm input[name='zip']").val(data.zip);
                    $("#editClientForm input[name='state']").val(data.state);
                    $("#editClientForm select[name='projects']").val(data.projects);
                    $("#editClientForm input[name='company']").val(data.company);

                    // display the form
                    $('#editClient').modal('show');
                },
                error: function(data) {
                    console.log('error');
                }
            });
        }
function openclientsModal() {
    // Clear any previous error messages and reset the form
    $('#addClientForm')[0].reset();
    $('#addClient').modal('show');
}
    </script>
    <!-- <script src="{{ asset('assets/js/bootstrap-tags.js') }}"></script> -->
@endsection

        </div>
            </div>
                </div>
                    </div>