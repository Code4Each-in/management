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
            <form method="GET">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="searchTerm" class="form-label">Search Term</label>
                    <input type="text" name="searchTerm" id="searchTerm" class="form-control text-dark" placeholder="Enter keyword...">
                </div>
                <div class="col-md-6">
                    <label for="searchPage" class="form-label">Search In</label>
                    <select name="searchPage[]" id="searchPage" class="form-select searchList" style="height: 47px;" multiple="multiple">
                        <option value="ticket">Ticket Page</option>
                        <option value="message">Message Page</option>
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
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    $('.searchList').select2();
});
</script>
@endsection
