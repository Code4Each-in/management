@extends('layout')
@section('title', 'Search')
@section('subtitle', 'Search')
@section('content')
<style>
.searchbtn {
    display: flex;
    justify-content: center;
}
.select2-container--default .select2-selection--multiple {
    min-height: 47px !important;
    height: 47px !important;
    padding: 6px 8px;
    border: 1px solid #ced4da;
}
</style>
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form id="searchForm" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="searchTerm" class="form-label">Search Term <span style="color: red;">*</span></label>
                        <input type="text" name="searchTerm" id="searchTerm" class="form-control text-dark" placeholder="Enter keyword...">
                        <small class="text-danger" id="searchTermError"></small>
                    </div>
                    <div class="col-md-6">
                        <label for="searchPage" class="form-label">Search In</label>
                        <select name="searchPage[]" id="searchPage" class="form-select searchList" style="height: 47px;" multiple="multiple">
                            <option value="ticket">Ticket Page</option>
                            @if(in_array(auth()->user()->role_id, [1, 6]))
                                <option value="message">Message Page</option>
                            @endif
                            <option value="sprint">Sprint Page</option>
                        </select>
                    </div>
                </div>
                <div class="searchbtn">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- This div will hold AJAX results -->
<div id="searchResults" class="mt-4"></div>

@endsection

@section('js_scripts')
<script>
    $(document).ready(function () {
        // Initialize select2
        $('.searchList').select2();

        // AJAX submit
        $('#searchForm').on('submit', function (e) {
            e.preventDefault();

            var url = "{{ route('search.index') }}";

            $.ajax({
                type: "POST",
                url: url,
                data: $(this).serialize(),
                success: function (response) {
                    $('#searchTermError').text('');
                    $('#searchResults').html(response);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $('#searchTermError').text(errors.searchTerm ? errors.searchTerm[0] : '');
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });

        // Delegated click handler for dynamic rows
        $(document).on('click', '.clickable-row', function () {
            const url = $(this).data('url');
            if (url) {
                 window.open(url, '_blank'); 
            }
        });
    });
</script>
@endsection
