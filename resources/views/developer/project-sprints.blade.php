@extends('layout')
@section('subtitle', 'Show')
@section('content')
@section('content')
<div class="container">
    <h1>Sprints for Project: {{ $project->project_name }}</h1>
    
    @if($sprints->count() > 0)
        <div class="col-lg-12 mx-auto">
            <div class="card">
                <div class="card-body mt-3">
                    <table class="table table-borderless dashboard " id="sprint-listing">
                        <thead>
                            <tr style="color: #2c2c2e;">
                                <th>Sr No</th>
                                <th>Sprint Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sprints as $sprint)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $sprint->name }}</td>
                                    <td>
                                        <a href="{{ url('/view/sprint/'.$sprint->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye fa-fw pointer"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No records to show</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>                    
                </div>
            </div>
        </div>
    @else
        <p>No sprints found for this project.</p>
    @endif
</div>
@endsection
@endsection
@section('js_scripts')
@endsection