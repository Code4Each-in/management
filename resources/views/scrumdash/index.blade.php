@extends('layout')
@section('title', 'Scrum Dashboard')
@section('subtitle', 'Scrum')
@section('content')

<div class="row">
<div class="col-md-6">
    <div class="card recent-sales overflow-auto">
    <div class="card-body">
        <h5 class="card-title">Total Active Jobs</h5>
        <table class="table table-borderless datatable" id="totaljobs">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Assigned To</th>                   
                    <th scope="col">ETA</th> 
                    <th scope="col">Status</th> 
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                <tr>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->assigned_user_names }}</td>
                    <td>{{ $task->eta }}</td>
                    <td>{{ $task->status }}</td>
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
        <h5 class="card-title">Jobs Inprogress</h5>
        <table class="table table-borderless datatable" id="runningjobs">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Assigned To</th>
                    <th scope="col">ETA</th>  
                </tr>
            </thead>
            <tbody>
                @foreach($activetasks as $tasks)
                    @php
                        $eta = \Carbon\Carbon::parse($tasks->eta);
                        $isCloseToDeadline = $eta->diffInDays(\Carbon\Carbon::now()) <= 2 && $eta > \Carbon\Carbon::now();
                    @endphp
                    <tr style="{{ $isCloseToDeadline ? 'background-color: red;' : '' }}">
                        <td>{{ $tasks->title }}</td>
                        <td>{{ $tasks->assigned_user_names }}</td>
                        <td>{{ $tasks->eta }}</td>
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
                    <th scope="col">Title</th>
                    <th scope="col">Assigned To</th>                 
                </tr>
            </thead>
            <tbody>
                @foreach($taskss as $tasks)
                                <tr>
                                    <td>{{ $tasks->title }}</td>
                                    <td>{{ $tasks->assigned_user_name }}</td>      
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
                                    <td>{{ $notask->assigned_user_name }}</td>      
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
    // Initialize your DataTable(s)
    var table1 = $('#totaljobs').DataTable();
    var table2 = $('#runningjobs').DataTable();
    var table2 = $('#assignedjobs').DataTable();
    var table2 = $('#notask').DataTable();
    // Get the maximum height of both tables
    var table1Height = $('#totaljobs').height();
    var table2Height = $('#runningjobs').height();
    var table3Height = $('#assignedjobs').height();
    var table4Height = $('#notask').height();
    var maxHeight = Math.max(table1Height, table2Height, table3Height, table4Height);

    // Set the height of both tables to the maximum height
    $('#totaljobs, #runningjobs, #assignedjobs, #notask').height(maxHeight);
});
</script>
@endsection