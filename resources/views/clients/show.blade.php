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
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Client Name</label>
                   <div class="col-sm-9">{{ $client->name }}</div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Email</label>
                   <div class="col-sm-9">{{ $client->email}}</div>
            </div>

            <div class="row mb-1 mt-4">
                <label for="" class="col-sm-3">Phone number</label>
                <div class="col-sm-9">{{  $client->phone }}</div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Birth date</label>
                   <div class="col-sm-9">
                      @if (!empty($client->birth_date ))
                         {{  $client->birth_date }}
                      @else
                        ---
                      @endif
                   </div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Address</label>
                   <div class="col-sm-9">
                      @if (!empty($client->address))
                        {{ $client->address}}
                      @else
                        ---
                      @endif
                  </div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">City</label>
                   <div class="col-sm-9">
                   @if (!empty($client->city ))
                     {{ $client->city }}
                   @else
                     ---
                   @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Status</label>
                   <div class="col-sm-9">
                     @if (!empty(Client::getStatus($client->status) ))
                     {{ Client::getStatus($client->status)}}
                   @else
                     ---
                   @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Zip</label>
                   <div class="col-sm-9">
                     @if (!empty($client->zip  ))
                     {{$client->zip}}
                   @else
                     ---
                   @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Country</label>
                   <div class="col-sm-9">
                     @if (!empty($client->country))
                     {{$client->country}}
                   @else
                     ---
                   @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
                   <label for="" class="col-sm-3">Projects</label>
                   <div class="col-sm-9">
                     @if(!empty(Client::getProjectName($client->projects)))
                     {{ Client::getProjectName($client->projects)}}
                     @else
                     ---
                   @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
              <label for="" class="col-sm-3">Company</label>
              <div class="col-sm-9">
                     @if(!empty($client->company))
                     {{ $client->company}}
                     @else
                     ---
                   @endif
              </div>
           </div>

            <div class="row mb-1 mt-4">
                   <div class="text-center">
                    <a href="{{ route('clients.index') }}" class="btn btn-primary">Back</a>
                </div>
            </div>
            @endsection
@section('js_scripts')

@endsection