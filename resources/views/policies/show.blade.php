@extends('layout')
@section('title', 'Show Policy')
@section('subtitle', 'Show')
@section('content')

<div class="col-lg-12 mx-auto">
    <div class="card">
        <div class="card-body">
            <div class="row mb-1 mt-4">

                <label for="" class="col-sm-3">Name</label>
                   <div class="col-sm-9">
                        {{$policies->name}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="tinymce_textarea" class="col-sm-3">Policy Text</label>
                    <div class=" col-sm-9">
                             @if ($policies->policy_text)
                             <textarea class="form-control" id="tinymce_textarea" disabled>{{$policies->policy_text}}</textarea>
                                    @else
                                    {{ '---'}}
                                    @endif
                    </div>
                </div>  
                <div class="row mb-1 mt-4">
                    <label for="edit_document" class="col-sm-3">Document Formats</label>
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

                            <a href="{{ $fileUrl }}" target="_blank"> 
                                <i class="{{ $iconClass }}" aria-hidden="true" style="font-size: 35px;"></i>
                            </a>
                        @endforeach    
                        @endif
                    </div>
                </div> 
                {{--
                <div class="row mb-1 mt-4">
                                <label for="edit_status" class="col-sm-3">Status</label>
                                <div class="col-sm-9">
                                @if($projects->status == 'not_started')
                                    <span class="badge rounded-pill bg-primary">Not Started</span>
                                    @elseif($projects->status == 'active')
                                    <span class="badge rounded-pill bg-info text-mute">Active</span>
                                    @elseif($projects->status == 'deactivated')
                                    <span class="badge rounded-pill bg-danger text-mute">Deactivated</span>
                                    @else
                                    <span class="badge rounded-pill  bg-success">Completed</span>
                                    @endif
                                </div>
                            </div> --}}
                <div class="text-center">
                    <!-- <a href="{{route('projects.index')}}" class="btn btn-primary">Back</a> -->
                </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')

@endsection