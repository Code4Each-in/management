@extends('layout')
@section('title', 'Devices')
@section('subtitle', 'Show')
@section('content')

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Device Name</label>
                    <div class="col-sm-9">
                            {{$device->name}}
                        </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Device Model</label>
                    <div class="col-sm-9">
                            {{$device->device_model}}
                        </div>
                </div>

                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Brand</label>
                    <div class="col-sm-9">
                            {{$device->brand }}
                        </div>
                </div>

                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Serial Number</label>
                    <div class="col-sm-9">
                            {{$device->serial_number}}
                        </div>
                </div>

                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Buying Date</label>
                    <div class="col-sm-9">
                            {{$device->buying_date ?? '---'}}
                        </div>
                </div>                            
                
            @if (count($deviceDocuments) > 0)
                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Documents</label>
                    <div class="col-sm-9">
                        <div class="documents-grid mt-4">
                        @foreach ($deviceDocuments as $document )
                                    <div class="card doc-card">
                                        <div class="card-body" style="padding: 0;">
                                            <div class="imagePreview">
                                                <a href="#" onclick="window.open('{{asset('assets/img/').'/'.$document->document}}', '_blank')">
                                                @php
                                                    $extension = pathinfo($document->document, PATHINFO_EXTENSION);
                                                @endphp

                                                @if (in_array($extension, ['pdf', 'doc', 'docx']))
                                                    <!-- Show icons for PDF, DOC, and DOCX file formats -->
                                                    <div class="file-icon">
                                                        @if ($extension === 'pdf')
                                                            <i class="bi bi-file-earmark-pdf" style="padding-left: 20px;font-size: 100px;"></i>
                                                        @elseif (in_array($extension, ['doc', 'docx']))
                                                            <i class="bi bi-file-earmark-word" style="padding-left: 20px;font-size: 100px;"></i>
                                                        @endif
                                                    </div>
                                                @else
                                                    <!-- Show image for other file formats -->
                                                    <img src="{{ asset('assets/img/') . '/' . $document->document }}" alt="{{ $document->id }}" id="document">
                                                @endif
                                                </a>
                                            <a href="#" title="delete Document" onclick="deleteDocument({{ $document->id }})"> <i class="fa fa-times del"></i></a>
                                            </div>
                                        </div>
                                    </div>
                        @endforeach
                        </div>
                    </div>
                </div>    
            @endif


                <div class="row mb-1 mt-4">
                                <label for="edit_status" class="col-sm-3">Status</label>
                                <div class="col-sm-9">
                                @if($device->status == 0)
                                    <span class="badge rounded-pill bg-success">Free</span>
                                    @else
                                    <span class="badge rounded-pill bg-primary">In Use</span>
                                    @endif
                                </div>
                            </div>
                <div class="text-center">
                    <!-- <a href="{{route('projects.index')}}" class="btn btn-primary">Back</a> -->
                </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
     $(document).ready(function() {
        // loadDocuments(); 
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });

      function deleteDocument(documentId) {
        if (confirm("Are you sure You Want To Delete Document?") == true) {
            $.ajax({
                url: "{{ url('/delete/device/document')}}",
                data: {
                    documentId: documentId,
                },
                type: 'DELETE',
                success: function(response) {
                    // Handle success response
                    // For example, remove the document item from the DOM
                    $('#document-' + documentId).remove();
                    location.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.log(xhr.responseText);
                }
            });
        }
    }
</script>
@endsection