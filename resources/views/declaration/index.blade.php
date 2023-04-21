@extends('layout')
@section('title', 'Self Declarations')
@section('subtitle', 'Self Declarations')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <div class="box-header with-border" id="filter-box">
                @if(session()->has('message'))
                <div class="alert alert-success message">
                    {{ session()->get('message') }}
                </div>

                @endif
                <br>
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="role_table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Father Name</th>
                                <th>Adharcard No</th>
                                <th>Block</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($selfDeclaration as $data)
                            <tr>
                                <td>{{ $data->full_name }}</td>
                                <td>{{ $data->father_name }}</td>
                                <td>{{ $data->adharcard_no }}</td>
                                <td>{{ $data->block }}</td>
                                <td>{{ $data->address }}</td>
                               
                            </tr>
                            @empty
                            @endforelse
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('.message').fadeOut("slow");
    }, 2000);
    $('#role_table').DataTable({
        "order": []
        //"columnDefs": [ { "orderable": false, "targets": 7 }]
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});
</script>
@endsection