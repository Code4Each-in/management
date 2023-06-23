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
                    <div class="display-document" id="1">
                        <div class="document-head">
                            <!-- <a href=""><span class="cross-icon">&times;</span></a> -->
                            <!-- <a href="#" title="delete Document" onclick="deleteDocument({{ $document->id }})"><span class="cross-icon text-danger">&times;</span></a> -->
                            <!-- <a href=""><span class="edit-icon"><i class="fa fa-edit"></i></span></a> -->
                        </div>
                        <img src="{{asset('assets/img/').'/'.$document->document_link}}" alt="{{ $document->document_title }}" id="document">
                        <div class="text-center" id="uploaded_document_name">{{ $document->document_title }}</div>
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