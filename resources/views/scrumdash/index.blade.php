@extends('layout')
@section('subtitle', 'Scrum')
@section('content')

    <div class="pagetitle scrumquotes">
            <div class="row">
                <div class="col">
                    <h1>Scrum Dashboard</h1>
                </div>
                <h3>{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</h3>
            </div>
    
            <div class="blockquote-wrapper">
                <div class="blockquote">
                    @if($quotes->isNotEmpty())
                        <h1>
                            {{ $quotes->first()->quote_text }} 
                        </h1>
                        <h4>—One Thought, One Change</h4>
                    @else
                        <h1>No quote available for today.</h1>
                    @endif
                </div>
            </div>            
    </div>

<div class="row">
   <div class="col-md-6">
        <div class="card recent-sales overflow-auto">
            <div class="card-body">
            <h5 class="card-title">Total Active Sprints</h5>
            <table class="table table-borderless datatable" id="totaljobs">
            <thead>
                <tr>
                    <th scope="col">Name</th> 
                    <th scope="col">Project</th>                  
                    <th scope="col">ETA (d/m/y)</th> 
                    <th scope="col">Time Left</th> 
                </tr>
            </thead>
            <tbody>
                @foreach ($sprints as $sprint)
                    <tr>
                        <td><a href="{{ url('/view/sprint/'.$sprint->id) }}" target="_blank">
                            {{ $sprint->name }}</a></td>
                        <td>{{ $sprint->project_name ?? '---' }}</td>
                        <td>{{ \Carbon\Carbon::parse($sprint->eta)->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $eta = \Carbon\Carbon::parse($sprint->eta);
                                $now = \Carbon\Carbon::now('Asia/Kolkata');
                                $timeLeft = $eta->diff($now);
                                $daysLeft = $eta->diffInDays($now);
                            @endphp
                            <p>{!! ($daysLeft <= 2 && $daysLeft >= 0) ? '<i class="fas fa-exclamation-circle" style="color: red;" title="Task is approaching!"></i>' : '' !!} Days Left: {{ $daysLeft >= 0 ? $daysLeft : '0' }}</p>
                        </td>                                                      
                    </tr>    
                @endforeach
            </tbody>
            </table>
        </div>
    </div>
</div>
<div class="col-md-6">
    <div class="card recent-sales overflow-auto">
     <div class="card-body">
        <h5 class="card-title">Jobs In Progress</h5>
        <table class="table table-borderless datatable" id="runningjobs">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Assigned To</th>
                    <th scope="col">Sprint</th>
                    <th scope="col">ETA (d/m/y)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activetasks as $tasks)
                @php
                    $eta = \Carbon\Carbon::parse($tasks->eta); 
                    $currentTime = \Carbon\Carbon::now('Asia/Kolkata');  
                    $isOverdue = $currentTime->toDateTimeString() > $eta->toDateTimeString();
                @endphp
                <tr>
                    <td>
                        @if($isOverdue)
                            <i class="fas fa-exclamation-circle" style="color: red;" title="Task is overdue!"></i>
                        @endif
                        <a href="{{ url('/view/ticket/'.$tasks->ticket_id) }}" target="_blank">
                            <i style="color:#4154f1;" class="pointer"></i>
                            {{ $tasks->title }}
                        </a>
                    </td>
                    <td>{{ $tasks->assigned_user_names }}</td>
                    <td>{{ $tasks->sprint_name ?? '---' }}</td>
                    <td>{{ $tasks->eta ? \Carbon\Carbon::parse($tasks->eta)->format('d/m/Y') : '---' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>                       
     </div>
   </div>
</div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card recent-sales overflow-auto">
         <div class="card-body">
          <h5 class="card-title">Assigned Jobs</h5>
          <table class="table table-borderless datatable" id="assignedjobs">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Assigned Jobs</th>                 
                </tr>
            </thead>
            <tbody>
                @foreach($taskss as $tasks)
                    <tr>
                        <td>{{ $tasks->assigned_user_name }}&nbsp;-&nbsp;{{ $tasks->designation }}</td>
                        <td>{{ $tasks->assigned_titles }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>        
    </div>
  </div>
</div>
    <div class="col-md-6">
        <div class="card recent-sales overflow-auto">
            <div class="card-body">
        <h5 class="card-title">No Job Assigned</h5>
        <table class="table table-borderless datatable" id="notask">
            <thead>
                <tr>
                    <th scope="col">Name</th>                
                </tr>
            </thead>
            <tbody>
                @foreach($notasks as $notask)
                                <tr>
                                    <td>{{ $notask->assigned_user_name }}&nbsp;-&nbsp;{{ $notask->designation }}</td>      
                                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</div>
    </div>
@endsection
@section('js_scripts')

<script>
    $(document).ready(function() {
        $('#totaljobs').DataTable({
            "order": []
        });
    });
    $(document).ready(function() {
        $('#runningjobs').DataTable({
            "order": []
        });
    });
    $(document).ready(function() {
        $('#assignedjobs').DataTable({
            "order": []
        });
    });
    $(document).ready(function() {
        $('#notask').DataTable({
            "order": []
        });
    });

    $(document).ready(function () {
    var table1 = $('#totaljobs').DataTable();
    var table2 = $('#runningjobs').DataTable();
    var table2 = $('#assignedjobs').DataTable();
    var table2 = $('#notask').DataTable();
    var table1Height = $('#totaljobs').height();
    var table2Height = $('#runningjobs').height();
    var table3Height = $('#assignedjobs').height();
    var table4Height = $('#notask').height();
    var maxHeight = Math.max(table1Height, table2Height, table3Height, table4Height);
    $('#totaljobs, #runningjobs, #assignedjobs, #notask').height(maxHeight);
});
</script>
@endsection