@extends('layout')
@section('title', 'Developer Details')
@section('subtitle', 'Show')

@section('content')
<div class="col-lg-12 mx-auto">
    <div class="card shadow">
        <div class="card-body mt-3">

            <!-- Developer Details -->
            <div class="row justify-content-center">
                <div class="col-md-4 text-center mb-4">
                    <div class="card shadow-sm rounded-lg">
                        <div class="card-body">
                            <img src="{{ asset($developer->profile_picture) }}" alt="Profile Picture" class="img-fluid rounded-circle shadow mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <h5 class="card-title font-weight-bold">{{ $developer->first_name }} {{ $developer->last_name }}</h5>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="card shadow-sm rounded-lg">
                        <div class="card-body">
                            <p><strong>Birthday:</strong> {{ \Carbon\Carbon::parse($developer->birth_date)->format('d M, Y') }}</p>
                            <div class="mb-3">
                                <strong>Skills:</strong>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach(explode(',', $developer->skills) as $skill)
                                        <span class="badge rounded-pill bg-primary px-3 py-2 text-white">{{ ucfirst(trim($skill)) }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

            <hr>
            <h5 class="mt-4">Leave Feedback</h5>
            <form id="feedback-form" method="POST">
                @csrf
                    <input type="hidden" class="form-control" name="developer_id" value="{{ $developer->id }}">

                <div class="mb-3">
                    <textarea name="feedback" rows="4" class="form-control" placeholder="Write your feedback here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Submit Feedback</button>
            </form>
            

            <div id="feedbackSuccess" class="alert alert-success mt-3 d-none">Feedback submitted successfully!</div>
            

        </div>
    </div>
</div>
@endsection

@section('js_scripts')
<script>
    $(document).ready(function() {
    
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    
        $('#feedback-form').submit(function (e) {
            e.preventDefault();
    
            let formData = new FormData(this);
    
            $.ajax({
                url: "{{ route('feedback.submit') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    alert(response.message);
                },
                error: function (xhr, status, error) {
                    console.log('ERROR:', xhr.responseText);
                    alert('Something went wrong!');
                }
            });
        });
    
    });
    </script>    
@endsection
