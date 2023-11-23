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
              <div class="col-sm-9">
                @if (!empty($client->email))
                  {{$client->email}}
                @else
                  ---
                @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
              <label for="" class="col-sm-3">Secondary Email</label>
              <div class="col-sm-9">
                @if (!empty($client->secondary_email))
                  {{$client->secondary_email}}
                @else
                  ---
                @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
              <label for="" class="col-sm-3">Additional Email</label>
              <div class="col-sm-9">
                @if (!empty($client->additional_email))
                  {{$client->additional_email}}
                @else
                  ---
                @endif
              </div>
            </div>

            <div class="row mb-1 mt-4">
              <label for="" class="col-sm-3">Phone number</label>
              <div class="col-sm-9">
                @if (!empty($client->phone))
                  {{$client->phone}}
                @else
                  ---
                @endif
              </div>
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
                    {{ Client::getCountry($client->country) }}
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
            <label for="" class="col-sm-3">Source</label>
            <div class="col-sm-9">
                   @if(!empty($client->source))
                   {{ $client->source}}
                   @else
                   ---
                 @endif
            </div>
         </div>
           
         <div class="row mb-1 mt-4">
          <label for="" class="col-sm-3">Skype</label>
          <div class="col-sm-9">
                 @if(!empty($client->skype))
                 {{ $client->skype}}
                 @else
                 ---
               @endif
          </div>
       </div>

       <div class="row mb-1 mt-4">
        <label for="" class="col-sm-3">last worked</label>
        <div class="col-sm-9">
               @if(!empty($client->last_worked))
               {{ $client->last_worked}}
               @else
               ---
             @endif
        </div>
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