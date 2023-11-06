<?php
use App\Models\Projects;?>
use App\Models\Client;

@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Edit')
@section('content')

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
            <form method="post" id="editProjectsForm" action="{{route('projects.update',$projects->id)}}" enctype="multipart/form-data">
            @csrf    
            <div class="row mb-5 mt-4">
                    <label for="edit_projectname" class="col-sm-3 col-form-label required">Project Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="edit_projectname" id="edit_projectname" value="{{$projects->project_name}}">
                        @if ($errors->has('edit_projectname'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_projectname') }}</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-5 mt-4">
                    <label for="client_id" class="col-sm-3 col-form-label required">Client Name</label>
                    <div class="col-sm-9">
                        <select name="edit_client_id" class="form-select form-control" id="client_id">
                            <option value="" disabled>Select Clients</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ $client->id == $projects->client_id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>  
                    </div>
                </div>

                <div class="row mb-5">
                    <label for="edit_assign" class="col-sm-3 col-form-label required"> Project Assigned</label>
                    <div class="col-sm-9" id="Projectsdata">
                        @foreach ($projectAssign as $data)
                        <button type="button" class="btn btn-outline-primary btn-sm mb-2">
                            {{$data->user->first_name}}<i class="bi bi-x pointer ticketassign" onClick="deleteProjectAssign('{{ $data->id }}')"></i></button>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="row mb-5 mt-4">
                    <label for="edit_liveurl" class="col-sm-3 col-form-label ">Live Url</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="edit_liveurl" id="edit_liveurl" value="{{$projects->live_url}}">
                        @if ($errors->has('edit_liveurl'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_liveurl') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-5 mt-4">
                    <label for="dev_liveurl" class="col-sm-3 col-form-label ">Dev Url</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="edit_devurl" id="edit_devurl" value="{{$projects->dev_url}}">
                        @if ($errors->has('edit_devurl'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('dev_liveurl') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-5 mt-4">
                    <label for="edit_gitrepo" class="col-sm-3 col-form-label ">Git Repository</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="edit_gitrepo" id="edit_gitrepo" value="{{$projects->git_repo}}">
                        @if ($errors->has('edit_gitrepo'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_gitrepo') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-5 mt-4">
                    <label for="edit_techstacks" class="col-sm-3 col-form-label ">Tech Stacks</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="edit_techstacks" id="edit_techstacks" value="{{$projects->tech_stacks}}" data-role="taginput">
                        @if ($errors->has('edit_techstacks'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_techstacks') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-5">
                    <label for="tinymce_textarea" class="col-sm-3 col-form-label">Description</label>
                    <div class=" col-sm-9">
                        <textarea name="description" class="form-control" id="tinymce_textarea">{{$projects->description}}</textarea>
                        @if ($errors->has('description'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                        @endif
                    </div>
                </div>
               
                <div class="row mb-5">
                    <label for="edit_assign" class="col-sm-3 col-form-label required ">Add More Assign</label>
                    <div class="col-sm-9">
                        <select name="edit_assign[]" class="form-select" id="edit_assign" multiple size="1">
                            <option>Select User</option>
                            @foreach ($users as $data)
                            <option value="{{$data['id']}}">
                                {{$data['first_name']}}-{{$data['role_name']}} 
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @if ($errors->has('edit_assign'))
                    <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_assign') }}</span>
                    @endif
                </div>
                <div class="row mb-3">
                                <label for="edit_startdate" class="col-sm-3 col-form-label required">Start Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="edit_startdate" name="edit_startdate" value="{{$projects->start_date}}">
                                </div>
                                 @if ($errors->has('edit_startdate'))
                    <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_startdate') }}</span>
                    @endif
                </div>
                <div class="row mb-3">
                                <label for="edit_enddate" class="col-sm-3 col-form-label">End Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="edit_enddate" name="edit_enddate" value="{{$projects->end_date}}">
                                </div>
                                @if ($errors->has('edit_enddate'))
                    <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_enddate') }}</span>
                    @endif
                </div>
                <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label">Credentials</label>
                                <div class="col-sm-9">
                                    <textarea name="credentials" class="form-control" id="tinymce_textarea">{{$projects->credentials}}</textarea>
                                </div>
                                @if ($errors->has('credentials'))
                    <span style="font-size: 12px;" class="text-danger">{{ $errors->first('credentials') }}</span>
                    @endif
                </div>
                            <div class="row mb-3">
                                <label for="edit_status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="edit_status" class="form-select" id="edit_status">
                                        <option value="not_started" {{$projects->status == 'not-started' ? 'selected' : ' ' }}>Not Started</option>
                                        <option value="active" {{$projects->status == 'active' ? 'selected' : ' ' }}>Active</option>
                                        <option value="deactivated" {{$projects->status == 'deactivated' ? 'selected' : ' ' }}>Deactivated</option>
                                        <option value="completed" {{$projects->status == 'completed' ? 'selected' : ' ' }}>Completed</option>
                                    </select>
                                </div>
                                @if ($errors->has('edit_status'))
                    <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_status') }}</span>
                    @endif
                            </div>
                <div class="row mb-5">
                    <label for="edit_document" class="col-sm-3 col-form-label">Uploaded Documents</label>
                    <div class="col-sm-9" id="Projectsdata" style="margin:auto;">
                        @if (count($ProjectDocuments) < 1) 
                        No Uploaded Document Found 
                        @else 
                        @foreach ($ProjectDocuments as $data)
                         <button type="button" class="btn btn-outline-primary btn-sm mb-2">
                            @php
                            $extension = pathinfo($data->document, PATHINFO_EXTENSION);
                            $iconClass = '';

                            switch ($extension) {
                            case 'pdf':
                            $iconClass = 'bi-file-earmark-pdf';
                            break;
                            case 'doc':
                            case 'docx':
                            $iconClass = 'bi-file-earmark-word';
                            break;
                            case 'xls':
                            case 'xlsx':
                            $iconClass = 'bi-file-earmark-excel';
                            break;
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            $iconClass = 'bi-file-earmark-image';
                            break;
                            // Add more cases for other file extensions as needed
                            default:
                            $iconClass = 'bi-file-earmark';
                            break;
                            }
                            @endphp
                            <i class="bi {{ $iconClass }} mr-1" onclick="window.open('{{asset('assets/img/').'/'.$data->document}}', '_blank')"></i>
                            <i class="bi bi-x pointer ticketfile text-danger" onClick="deleteUploadedFile('{{ $data->id }}')"></i>
                            </button>
                            @endforeach
                            @endif
                    </div>
                </div>
                <div class="row mb-5">
                    <label for="edit_document" class="col-sm-3 col-form-label">Document</label>
                    <div class="col-sm-9">
                        <input type="file" class="form-control" name="edit_document[]" id="edit_document" multiple>
                    </div>
                    @if ($errors->has('edit_document.*'))
                    @foreach($errors->get('edit_document.*') as $key => $errorMessages)
                    @foreach($errorMessages as $error)
                    <span style="font-size: 12px; padding: 10px 100px;" class="text-danger">
                    @if ($error == 'The document failed to upload.')
                        {{$error}} The document may not be greater than 5 mb.
                        @else
                            {{$error}} 
                    @endif 
                    </span>
                    @endforeach
                    @endforeach
                    @endif

                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
    $(document).ready(function() {
        setTimeout(function() {
            $('.message').fadeOut("slow");
        }, 2000);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#commentsData").submit(function() {
            event.preventDefault();
            var comment = $('#comment').val();
            var id = $('#hidden_id').val();

            $.ajax({
                type: 'POST',
                url: "{{ url('/add/comments')}}",
                data: {
                    comment: comment,
                    id: id,
                },
                success: (data) => {
                    if (data.errors) {
                        $('.alert-danger').html('');
                        $.each(data.errors, function(key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<li>' + value +
                                '</li>');
                        });
                    } else {
                        $('.alert-danger').html('');
                        $('.alert-danger').hide();
                        $('#comment').val("");
                        var html = "";
                        $.each(data.CommentsData, function(key, data) {
                            var picture = 'blankImage';
                            if (data.user.profile_picture != "") {
                                picture = data.user.profile_picture;
                            }
                            html +=
                                '<div class="row post-item clearfix mb-3 "><div class="col-md-2"><img src="{{asset("assets/img")}}/' +
                                picture +
                                '" class="rounded-circle" alt = "" ></div><div class="col-md-3"><p>' +
                                data.user.first_name +
                                '</p><p>' + moment(data.created_at).format(
                                    'MMM DD LT') +
                                '</p></div><div class="col-md-7 text-left mt-3 ml-3">' +
                                data.comments + '</div></div>';
                        });

                        $('.comments').append(html);
                        $('.Commentmessage').html(data.Commentmessage);
                        $('.Commentmessage').show();
                        $('#NoComments').hide();
                        setTimeout(function() {
                            $('.Commentmessage').fadeOut("slow");
                        }, 2000);
                    }
                }
            });
        });

        $( '#edit_assign' ).select2( {
                    // closeOnSelect: false,
                } );
    });

    function deleteProjectAssign(id) {
        var ProjectId = $('#hidden_id').val();
        if (confirm("Are you sure ?") == true) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/delete/project/assign')}}",
                data: {
                    id: id,
                    ProjectId: ProjectId,
                },
                success: (data) => {
                    location.reload();

                    // if (data.user != null) {
                    //     $('#edit_assign').find('option').remove().end();
                    //     $.each(data.user, function(key, value) {
                    //         $('#edit_assign').append('<option value="' + value.id + '">' + value
                    //             .first_name + '</option>');
                    //     });
                    // }
                    // if (data.AssignData.length == 0) {

                    //     $('#Ticketsdata').hide();
                    // }
                }

            });
        }
    }

    function deleteUploadedFile(id) {
        var ProjectId = $('#hidden_id').val();
        if (confirm("Are you sure ?") == true) {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('/delete/project/file')}}",
                data: {
                    id: id,
                    ProjectId: ProjectId,
                },
                success: (data) => {
                    location.reload();
                }

            });
        }

    }

</script>
<script>

            //TAGS KEY JS
            $('#edit_techstacks').tagsinput({
            confirmKeys: [13, 188]
            });

            $('#edit_techstacks').on('keypress', function(e){
            if (e.keyCode == 13){
                e.preventDefault();
            };
            });

</script>

@endsection