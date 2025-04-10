@extends('layout')
@section('title', 'Sprint Dashboard')
@section('subtitle', 'Sprint')
@section('content')
<div class="col-md-2">
    <button class="btn btn-primary m-3" onClick="opensprintModal()" href="javascript:void(0)">Add Sprint</button>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card recent-sales overflow-auto">
        <div class="card-body">
            <h5 class="card-title">All Sprints</h5>
            <table class="table table-borderless datatable" id="allsprint">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th> 
                        <th scope="col">Project</th>  
                        <th scope="col">Started At</th>                
                        <th scope="col">End Date(d/m/y)</th> 
                        <th scope="col">Time Left</th> 
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sprints as $sprint)
                        <tr>
                            <td>{{ $sprint->name }}</td>
                            <td>{{ strip_tags(htmlspecialchars_decode($sprint->description ?? '---'));}}</td>
                            <td>{{ $sprint->project_name ?? '---' }}</td>
                            <td>{{ $sprint->created_at ?? '---' }}</td>
                            <td>{{ \Carbon\Carbon::parse($sprint->eta)->format('d/m/Y') }}</td>
                            <td>
                                @php
                                    $eta = \Carbon\Carbon::parse($sprint->eta);
                                    $now = \Carbon\Carbon::now('Asia/Kolkata');
                                    $timeLeft = $eta->diff($now);
                                    $daysLeft = $eta->diffInDays($now);
                                    $timeLeftString = $eta->isPast() ? '-' . $timeLeft->format('%H:%I:%S') : $timeLeft->format('%H:%I:%S');
                                @endphp
                                <p>
                                    @if($daysLeft <= 2 && $daysLeft >= 0)
                                        <i class="fas fa-exclamation-circle" style="color: red;" title="Task is approaching!"></i>
                                    @endif
                                    Days Left: {{ $daysLeft >= 0 ? $daysLeft : '0' }}
                                </p>                                
                            </td>                            
                            <td>
                                <a href="{{ url('/view/sprint/'.$sprint->id) }}">
                                    <i style="color:#4154f1;" class="fa fa-eye fa-fw pointer"></i>
                                </a>
                                <a href="{{ url('/edit/sprint/'.$sprint->id) }}">
                                    <i style="color:#4154f1;" class="fa fa-edit fa-fw pointer"></i>
                                </a>
                                <i style="color:#4154f1;" onClick="deleteSprint('{{ $sprint->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                            </td>                            
                        </tr>    
                    @endforeach
                </tbody>
            </table>            
        </div>
    </div>
    </div>
</div>
<div class="col-lg-12">
    <div class="card">
        <div class="modal fade" id="addSprints" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Sprint</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addTicketsForm" enctype="multipart/form-data">
                     @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label required">Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name" id="title">
                                </div>
                            </div> 
                            
                            <div class="row mb-3">
                                <label for="etaDateTime" class="col-sm-3 col-form-label required">Eta</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control" id="eta" name="eta">
                                    <input type="hidden" class="form-control" name="sprint_id" id="sprint_id" value="{{ $sprint->id ?? '' }}">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <label for="project" class="col-sm-3 col-form-label required">Projects</label>
                                <div class="col-sm-9">
                                    <select name="project" class="form-select form-control" id="project">
                                        <option value="" disabled selected>Select your project</option>
                                        @foreach ($projects as $data)
                                            <option value="{{$data->id}}">
                                                {{$data->project_name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>  
                            
                            <div class="row mb-3">
                                <label for="client" class="col-sm-3 col-form-label required">Client Name</label>
                                <div class="col-sm-9">
                                    <select name="client" class="form-select form-control" id="client">
                                        <option value="" disabled selected>Select Client</option>
                                        @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>  
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select form-control" id="status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>                            
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                                <div class="col-sm-9">
                                    <textarea name="description" class="form-control" id="tinymce_textarea"></textarea>
                                </div>
                            </div>
                                                     
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" href="javascript:void(0)">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!---end Add modal-->
    </div>
</div>
<div id="loader">
    <img class="loader-image" src="{{ asset('assets/img/loading.gif') }}" alt="Loading.......">
</div>
<script>
function opensprintModal() {
                document.getElementById("addTicketsForm").reset();
                $('#addSprints').modal('show');
            }

            $(document).ready(function() {
        $('#allsprint').DataTable({
            "order": []
            });
        });
        $(document).ready(function () {
        var table1 = $('#allsprint').DataTable();
        var table1Height = $('#allsprint').height();
        var maxHeight = Math.max(table1Height);
        $('#allsprint').height(maxHeight);
        });
        

        $(document).ready(function() {
    $("#addTicketsForm").on("submit", function(e) {
        e.preventDefault(); 
        var formData = new FormData(this); 

        $.ajax({
            url: "{{ url('/add/sprint') }}",
            type: "POST",
            data: formData,
            contentType: false,  
            processData: false,  
            success: function(response) {
                if (response.status == 'success') {
                    $('#addSprints').modal('hide'); 
                    $('#loader').show(); 
                    setTimeout(function() {
                        location.reload();  
                    }, 1000);  
                } else {
                    $(".alert-danger").text(response.message).show();  
                }
            },
            error: function(xhr, status, error) {
                $(".alert-danger").text("An error occurred. Please try again.").show();  
            }
        });
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

    function deleteSprint(id) {
                $('#sprint_id').val(id);
                if (confirm("Are you sure ?") == true) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('/delete/sprint') }}",
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(res) {
                            location.reload();
                        }
                    });
                }
            }
</script>
@endsection
@section('js_scripts')