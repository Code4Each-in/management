@extends('layout')

@section('title', 'Users Details')
@section('subtitle', 'Users Details')

@section('content')
<div class="card shadow-sm bg-white p-4">
    <div class="card-body">
        <!-- <h5 class="card-title">User Details</h5> -->

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Full Name</div>
            <div class="col-lg-9 col-md-8 detail_full_name">
                {{$usersProfiled->first_name." ".$usersProfiled->last_name}}
            </div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Email</div>
            <div class="col-lg-9 col-md-8 detail_full_email">{{$usersProfiled->email}}</div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Address</div>
            <div class="col-lg-9 col-md-8 detail_full_address">
                @if ($usersProfiled->address)
                {{$usersProfiled->address}} {{$usersProfiled->city}} , {{$usersProfiled->state}} , {{$usersProfiled->zip}}
                @endif
            </div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Phone</div>
            <div class="col-lg-9 col-md-8 detail_full_phone">{{$usersProfiled->phone}}</div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Gender</div>
            <div class="col-lg-9 col-md-8 detail_full_phone">{{$usersProfiled->gender}}</div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Joining Date</div>
            <div class="col-lg-9 col-md-8 detail_full_joining_date">
                {{date("d-m-Y", strtotime($usersProfiled->joining_date))}}
            </div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Birthdate</div>
            <div class="col-lg-9 col-md-8 detail_full_birth_date">
                {{date("d-m-Y", strtotime($usersProfiled->birth_date))}}
            </div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Skills</div>
            <div class="col-lg-9 col-md-8 detail_skills">
                @if ($usersProfiled->skills)
                {{$usersProfiled->skills}}
                @else
                {{'---'}}
                @endif
            </div>
        </div>

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">T-Shirt Size</div>
            <div class="col-lg-9 col-md-8 detail_tshirt_size tshirt-text">
                @if ($usersProfiled->tshirt_size)
                {{$usersProfiled->tshirt_size}}
                @else
                {{'---'}}
                @endif
            </div>
        </div>

        @if(isset($usersProfiled->department->name))
        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Department</div>
            <div class="col-lg-9 col-md-8">{{$usersProfiled->department->name}}</div>
        </div>
        @endif

        <div class="row line-space">
            <div class="col-lg-3 col-md-4 label">Emergency Contact</div>
            <div class="col-lg-9 col-md-8">
                @if ($usersProfiled->emergency_name)
                <strong>Name:</strong> {{ $usersProfiled->emergency_name }} <br>
                <strong>Relation:</strong> {{ $usersProfiled->emergency_relation }} <br>
                <strong>Phone:</strong> {{ $usersProfiled->emergency_phone }}
                @else
                {{ '---' }}
                @endif
            </div>
        </div>

        <div class="row line-space mt-3">
            <div class="col-lg-3 col-md-4 label">Secondary Emergency Contact</div>
            <div class="col-lg-9 col-md-8">
                @if ($usersProfiled->emergency_name_secondary)
                <strong>Name:</strong> {{ $usersProfiled->emergency_name_secondary }} <br>
                <strong>Relation:</strong> {{ $usersProfiled->emergency_relation_secondary }} <br>
                <strong>Phone:</strong> {{ $usersProfiled->emergency_phone_secondary }}
                @else
                {{ '---' }}
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
