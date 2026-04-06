@extends('layout')
@section('title', 'Settings')
@section('subtitle', 'Settings')

@section('content')
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">

            <h5 class="card-title mb-4">Company Availability Settings</h5>

            <form method="POST" action="{{ route('settings.update') }}">
                @csrf

                <!-- Toggle -->
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active"
                        {{ $setting && $setting->is_active ? 'checked' : '' }}>
                    <label class="form-check-label">Enable Company Status</label>
                </div>

                <!-- Working Hours -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control"
                            value="{{ $setting->start_time ?? '09:00' }}">
                    </div>

                    <div class="col-md-6">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control"
                            value="{{ $setting->end_time ?? '19:00' }}">
                    </div>
                </div>

                <!-- Weekend -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="skip_weekends"
                        {{ $setting && $setting->skip_weekends ? 'checked' : '' }} id="skip_weekends">
                    <label for="skip_weekends" class="form-check-label">Disable on Weekends (Sat & Sun)</label>
                </div>

                <button class="btn btn-primary">Save Settings</button>

            </form>

        </div>
    </div>
</div>
@endsection