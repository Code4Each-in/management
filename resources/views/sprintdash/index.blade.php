@extends('layout')
@section('title', 'Sprint Dashboard')
@section('subtitle', 'Sprint')
@section('content')
{{-- <div class="col-md-2">
    <button class="btn btn-primary m-3" onClick="openticketModal()" href="javascript:void(0)">Add Sprint</button>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card recent-sales overflow-auto">
        <div class="card-body">
            <h5 class="card-title">All Sprints</h5>
            <table class="table table-borderless datatable" id="totaljobs">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Assigned To</th>                   
                        <th scope="col">ETA(d/m/y)</th> 
                        <th scope="col">Status</th> 
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>    
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#totaljobs').DataTable({
            "order": []
        });
    });

    $(document).ready(function () {
    // Initialize your DataTable(s)
    var table1 = $('#totaljobs').DataTable();
    // Get the maximum height of both tables
    var table1Height = $('#totaljobs').height();
    var maxHeight = Math.max(table1Height);

    // Set the height of both tables to the maximum height
    $('#totaljobs').height(maxHeight);
});
</script> --}}
<h1 style="font-size: 3rem; color: #1a1313; font-family: 'Arial', sans-serif; text-align: center;">We're Launching Soon!</h1>
<p style="font-size: 1.2rem; color: #3a0909; text-align: center; font-family: 'Arial', sans-serif;">Our new feature is in the works. Stay tuned for something exciting!</p>

@endsection
@section('js_scripts')