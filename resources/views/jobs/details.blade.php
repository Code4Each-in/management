@extends('layout')
@section('title', 'Job Details')
@section('subtitle', 'Show')
@section('content')

<div class="col-lg-12 mx-auto">
    <div class="card">
        <div class="card-body">
            <div class="row mb-1 mt-4">

                <label for="" class="col-sm-3">Title</label>
                   <div class="col-sm-9">
                     {{$jobs->jobTitle}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">

                <label for="" class="col-sm-3">Category</label>
                   <div class="col-sm-9">
                     {{$jobs->category}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="tinymce_textarea" class="col-sm-3">Description</label>
                    <div class=" col-sm-9">
                    {!! $jobs->description !!}
                    </div>
                </div>

                <div class="row mb-1 mt-4">
                    <label for="edit_document" class="col-sm-3">Experience</label>
                    <div class="col-sm-9">
                    {{$jobs->experience}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="edit_document" class="col-sm-3">Location</label>
                    <div class="col-sm-9">
                    {{$jobs->location}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="edit_document" class="col-sm-3">Salary</label>
                    <div class="col-sm-9">
                    {{$jobs->salary}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="edit_document" class="col-sm-3">Skills</label>
                    <div class="col-sm-9">
                    {{$jobs->skills}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="edit_document" class="col-sm-3">Status</label>
                    <div class="col-sm-9">
                    {{$jobs->status==1?'Active':'Inactive'}}
                    </div>
                </div>

                <div class="text-center">
                    <a href="{{route('jobs.index')}}" class="btn btn-primary">Back</a>
                </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')

@endsection
