<?php
use App\Models\Client;?>

@extends('layout')
@section('title', 'Clients')
@section('subtitle', 'Show')
@section('content')


<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
            <div class="row mb-1 mt-4">
                
                <label for="" class="col-sm-3">ID</label>
                   <div class="col-sm-9">{{ $client->id }}</div>
                   <label for="" class="col-sm-3">Client Name</label>
                   <div class="col-sm-9">{{ $client->name }}</div>
                   <label for="" class="col-sm-3">Email</label>
                   <div class="col-sm-9">{{ $client->email}}</div>
                   <label for="" class="col-sm-3">Phone number</label>
                   <div class="col-sm-9">{{  $client->phone }}</div>
                   <label for="" class="col-sm-3">Birth date</label>
                   <div class="col-sm-9">{{  $client->birth_date }}</div>
                   <label for="" class="col-sm-3">Address</label>
                   <div class="col-sm-9">{{ $client->address}}</div>
                   <label for="" class="col-sm-3">City</label>
                   <div class="col-sm-9">{{ $client->city }}</div>
                   <label for="" class="col-sm-3">Status</label>
                   <div class="col-sm-9">{{ Client::getStatus($client->status) }}</></div>
                   <label for="" class="col-sm-3">Zip</label>
                   <div class="col-sm-9">{{ $client->zip }}</div>
                   <label for="" class="col-sm-3">State</label>
                   <div class="col-sm-9">{{ $client->state }}</div>
                   <label for="" class="col-sm-3">Projects</label>
                   <div class="col-sm-9">{{ Client::getProjectName($client->projects)}}</div>
                
                   <div class="text-center">
                    <a href="{{ route('clients.index') }}" class="btn btn-primary">Back</a>
                </div>
            </div>
            @endsection
@section('js_scripts')

@endsection