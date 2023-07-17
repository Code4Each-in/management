@extends('layout')
@section('title', 'User Documents')
@section('subtitle', 'User Documents')
@section('content')

<div class="col-lg-10 m-auto">
    <div class="card">
        <div class="card-body">
        <div class="row mb-3"> 
            <div class="col-md-12">
                <div class="documents-grid mt-4">
                 @foreach ($userDocuments as $document )
                                    <div class="card doc-card">
                                        <div class="card-body" style="padding: 0;">
                                            <div class="imagePreview">
                                                <a href="#" onclick="window.open('{{asset('assets/img/').'/'.$document->document_link}}', '_blank')">
                                                @php
                                                    $extension = pathinfo($document->document_link, PATHINFO_EXTENSION);
                                                @endphp

                                                @if (in_array($extension, ['pdf', 'doc', 'docx']))
                                                    <!-- Show icons for PDF, DOC, and DOCX file formats -->
                                                    <div class="file-icon">
                                                        @if ($extension === 'pdf')
                                                            <i class="bi bi-file-earmark-pdf" style="padding-left: 70px;font-size: 100px;"></i>
                                                        @elseif (in_array($extension, ['doc', 'docx']))
                                                            <i class="bi bi-file-earmark-word" style="padding-left: 70px;font-size: 100px;"></i>
                                                        @endif
                                                    </div>
                                                @else
                                                    <!-- Show image for other file formats -->
                                                    <img src="{{ asset('assets/img/') . '/' . $document->document_link }}" alt="{{ $document->document_title }}" id="document">
                                                @endif
                                                </a>
                                     
                                            </div>
                                            <div class="form-input">
                                                <p>{{ $document->document_title }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                </div>
            </div>
        </div> 
               
        </div>
    </div>
</div>
@endsection
@section('js_scripts')

@endsection