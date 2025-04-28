@extends('layout')
@section('title', 'Developer Listing')
@section('subtitle', 'Show')
@section('content')
<div class="col-lg-12 mx-auto">
    <div class="card">
        <div class="card-body mt-3">
            <table class="table table-borderless dashboard" id="dev-listing">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Developer</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($developers as $developer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $developer->first_name }}</td>
                        <td>
                            <a href="{{ route('developer.detail', ['id' => $developer->id]) }}">&nbsp;&nbsp;<i class="fa fa-eye fa-fw pointer"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody> 
            </table>
        </div>
    </div>
</div>

@endsection
@section('js_scripts')
<script>
    $(document).ready(function() {
        $('#dev-listing').DataTable();
    });
</script>
@endsection