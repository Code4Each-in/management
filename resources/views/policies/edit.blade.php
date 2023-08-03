@extends('layout')
@section('title', 'Edit Policy')
@section('subtitle', 'Edit')
@section('content')

<div class="col-lg-12 mx-auto">
    <div class="card">
        <div class="card-body">
            <form method="post" id="editProjectsForm" action="{{route('policies.update',$policies->id)}}" enctype="multipart/form-data">
            @csrf    
            <div class="row mb-5 mt-4">
                    <label for="edit_name" class="col-sm-3 col-form-label required">Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="edit_name" id="edit_name" value="{{$policies->name}}">
                        @if ($errors->has('edit_name'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('edit_name') }}</span>
                        @endif
                    </div>
                </div>
                @if ($policies->policy_text != null)
                <div class="row mb-5">
                    <label for="tinymce_textarea" class="col-sm-3 col-form-label required">Description</label>
                    <div class=" col-sm-9">
                        <textarea name="edit_policy_text" class="form-control" id="tinymce_textarea" >{{$policies->policy_text}}</textarea>
                        @if ($errors->has('description'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                        @endif
                    </div>
                </div>
                @endif
               
                 <div class="row mb-5">
                    <label for="edit_document" class="col-sm-3 col-form-label">Document Formats</label>
                    <div class="col-sm-9" id="Projectsdata" style="margin:auto;">
                    @if (count($policies->PolicyDocuments) < 1) 
                            NO Policy In Document Format 
                        @else 
                        @foreach ($policies->PolicyDocuments as $document)
                            @php
                            $filePath =  ($document->document_link ?? '#');
                            $fileUrl = asset('storage/' . $filePath);

                            $documentType = strtolower($document->document_type);
                            $iconClass = '';

                            if ($documentType === 'pdf') {
                                $iconClass = 'bi-file-earmark-pdf';
                            } elseif ($documentType === 'doc') {
                                $iconClass = 'bi-file-earmark-word';
                            } elseif ($documentType === 'txt') {
                                $iconClass = 'bi-file-earmark-text';
                            } else {
                                $iconClass = 'bi-file-earmark';
                            }
                            @endphp

                            <a href="{{ $fileUrl }}" target="_blank" > 
                                <i class="{{ $iconClass }}" aria-hidden="true" style="font-size: 35px;"></i>
                            </a>
                        @endforeach    
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                                <label for="" class="col-sm-3 col-form-label required">Save File In Format</label>
                                <div class="col-sm-9">
                                <input type="checkbox" class="form-check-input" name="pdf" id="pdf">
                                <label for="pdf" class="">PDF</label>
                                <input type="checkbox" class="form-check-input" name="word" id="word">
                                <label for="word" class="">WORD</label>
                                <input type="checkbox" class="form-check-input" name="text" id="text">
                                <label for="text" class="">TEXT</label>
                                </div>
                </div>
                {{--
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

                </div> --}}
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