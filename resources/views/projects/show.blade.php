@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Show')
@section('content')

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
            <div class="row mb-1 mt-4">

                <label for="" class="col-sm-3">Project Name</label>
                   <div class="col-sm-9">
                        {{$projects->project_name}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3">Client Name</label>
                       <div class="col-sm-9">
                        {{$projects->client->name ?? '--'}}
                        </div>
                    </div>
                 <div class="row mb-1 mt-4">
                    <label for="" class="col-sm-3"> Project Assigned</label>
                    <div class="col-sm-9" id="Projectsdata">
                        @foreach ($projectAssigns as $data)
                        <button type="button" class="btn btn-outline-primary btn-sm ">
                            {{$data->user->first_name}}</button>
                        </button>
                        @endforeach
                    </div>
                </div> 
                <div class="row mb-1 mt-4">
                    <label for="edit_liveurl" class="col-sm-3 ">Live Url</label>
                    <div class="col-sm-9">
                        @if ($projects->live_url)
                            <a href="{{$projects->live_url}}" target="_blank">{{$projects->live_url}}</a>
                        @else
                            {{'---'}}
                        @endif
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="dev_liveurl" class="col-sm-3 ">Dev Url</label>
                    <div class="col-sm-9">
                        @if ($projects->dev_url)
                            <a href="{{$projects->dev_url}}" target="_blank">{{$projects->dev_url}}</a>
                        @else
                            {{'---'}}
                        @endif
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="edit_gitrepo" class="col-sm-3 ">Git Repository</label>
                    <div class="col-sm-9">
                        @if ($projects->git_repo)
                            <a href="{{$projects->git_repo}}" target="_blank">{{$projects->git_repo}}</a>
                        @else
                            {{'---'}}
                        @endif
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="edit_techstacks" class="col-sm-3 ">Tech Stacks</label>
                    <div class="col-sm-9">
                            {{$projects->tech_stacks ?? '---'}}
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                    <label for="tinymce_textarea" class="col-sm-3">Description</label>
                    <div class=" col-sm-9">
                             @if ($projects->description)
                                    {!! $projects->description!!}
                                    @else
                                    {{ '---'}}
                                    @endif
                    </div>
                </div>
                <div class="row mb-1 mt-4">
                                <label for="edit_startdate" class="col-sm-3">Start Date</label>
                                <div class="col-sm-9">
                                {{date('d-m-Y', strtotime($projects->start_date));}}

                                </div>
                </div>
                <div class="row mb-1 mt-4">
                                <label for="edit_enddate" class="col-sm-3">End Date</label>
                                <div class="col-sm-9">
                               @if ($projects->end_date != Null)
                               {{\Carbon\Carbon::parse($projects->end_date)->format('d-m-Y') }}
                               @else
                               {{ '---' }}
                               @endif
                               
                                
                                </div>
                </div>
                <div class="row mb-1 mt-4">
                                <label for="tinymce_textarea" class="col-sm-3">Credentials</label>
                                <div class="col-sm-9">
                                    @if ($projects->credentials)
                                    {!! $projects->credentials!!}
                                    @else
                                    {{ '---'}}
                                    @endif
                               
                                </div>
                </div>
                            
                 <div class="row mb-1 mt-4">
                    <label for="edit_document" class="col-sm-3">Uploaded Documents</label>
                    <div class="col-sm-9" id="Projectsdata" style="margin:auto;">
                        @if (count($ProjectDocuments) < 1) 
                        No Uploaded Document Found 
                        @else 
                        @foreach ($ProjectDocuments as $data)
                         <button type="button" class="btn btn-outline-primary btn-sm mb-1">
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
                            </button>
                            @endforeach
                            @endif
                    </div>
                </div> 
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
                            </div>
                <div class="text-center">
                    <!-- <a href="{{route('projects.index')}}" class="btn btn-primary">Back</a> -->
                </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')

@endsection