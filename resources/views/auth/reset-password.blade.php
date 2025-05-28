<!DOCTYPE html>
<html lang="en">
@section('title', 'Reset Password')
@include('includes.css')

<body>
    <main>
        <div class="container">
            <section
                class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                            <div class="d-flex justify-content-center py-4">
                                <a href="{{ url('/') }}" class="logo d-flex align-items-center w-auto" >
                                    <img src="{{ asset('assets/img/code4each_logo.png') }}" alt="Code4Each" style="margin:auto;" >
                                </a>
                            </div><!-- End Logo -->

                            <div class="card mb-3">

                                <div class="card-body">

                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">Reset Password</h5>
                                        <p class="text-center small">Enter your new password below</p>
                                    </div>

                                    @if (session('status'))
                                        <div class="alert alert-success">{{ session('status') }}</div>
                                    @endif

                                    <form method="POST" action="{{ route('password.update') }}" class="row g-3 needs-validation" novalidate>
                                        @csrf
                                        <input type="hidden" name="token" value="{{ $token }}">

                                        <div class="col-12">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input 
                                                type="email" 
                                                class="form-control @error('email') is-invalid @enderror" 
                                                id="email" 
                                                name="email" 
                                                value="{{ old('email') }}" 
                                                required 
                                                autofocus
                                            >
                                            @error('email')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="password" class="form-label">New Password</label>
                                            <input 
                                                type="password" 
                                                class="form-control @error('password') is-invalid @enderror" 
                                                id="password" 
                                                name="password" 
                                                required
                                            >
                                            @error('password')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                                            <input 
                                                type="password" 
                                                class="form-control @error('password_confirmation') is-invalid @enderror" 
                                                id="password_confirmation" 
                                                name="password_confirmation" 
                                                required
                                            >
                                            @error('password_confirmation')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit">Reset Password</button>
                                        </div>
                                    </form>

                                    <div class="col-12 text-center mt-3">
                                        <a href="{{ route('login') }}" class="small text-primary">Back to Login</a>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main><!-- End #main -->

    @include('includes.jss')
</body>

</html>
