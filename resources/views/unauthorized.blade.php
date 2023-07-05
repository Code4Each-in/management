@extends('layout')
@section('title', 'Unauthorized')
@section('subtitle', '')

@section('content')
    <div class="row justify-content-center">
        <div class="col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h1 class="card-title text-warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Access Unauthorized <i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1>
                    <p class="card-text text-danger">You do not have permission to access this page.Please Contact To Admin To Ask For Permission.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js_scripts')
