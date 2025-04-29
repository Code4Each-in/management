@extends('layout')
@section('title', 'Developer Listing')
@section('subtitle', 'Show')
@section('content')
<div class="col-lg-12 mx-auto">
    <div class="card">
        <div class="card-body mt-3">
            <table class="table table-borderless dashboard" id="dev-listing">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Sr No</th>
                        <th style="width: 200px;">Developer</th>
                        <th style="width: 400px;">Skills</th> <!-- Increased width here -->
                        <th style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($developers as $developer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ trim($developer->first_name . ' ' . ($developer->last_name ?? '')) }}</td>
                        <td>
                            @if(!empty($developer->skills))
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach(explode(',', $developer->skills) as $skill)
                                        <div class="badge bg-primary text-capitalize" style="font-size: 0.8rem; padding: 0.4em 0.7em; border-radius: 1rem;">
                                            {{ trim($skill) }}
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">---</span>
                            @endif
                        </td>                        
                        <td>
                            <a href="javascript:void(0);" class="open-feedback-modal" data-developer-id="{{ $developer->id }}">
                                <i class="fa fa-eye fa-fw pointer"></i>
                            </a>                          
                        </td>
                    </tr>
                    @endforeach
                </tbody> 
            </table>            
        </div>
    </div>
    <!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="feedback-form" method="POST" action="{{ route('feedback.submit') }}">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="feedbackModalLabel">Submit Feedback</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
  
            <div class="modal-body">
                <input type="hidden" id="developerIdInput" name="developer_id" value="">
                <div class="mb-3">
                    <textarea name="feedback" rows="4" class="form-control" placeholder="Write your feedback here..." required></textarea>
                </div>
            </div>
  
            <div class="modal-footer">
              <button type="submit" class="btn btn-success">Submit Feedback</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
      </div>
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

        $(document).on('click', '.open-feedback-modal', function(e) {
            e.preventDefault();  
            var developerId = $(this).data('developer-id');
            $('#feedback-form input[name="developer_id"]').val(developerId);
            $('#feedbackModal').modal('show');
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
            $('#feedbackModal .modal-body').html(`
            <div class="text-center p-4">
                <i class="fa fa-check-circle" style="font-size: 50px; color: #28a745;"></i>
                <h4 class="mt-3">Thank you for your feedback!</h4>
            </div>
        `);
            setTimeout(function() {
                $('#feedbackModal').modal('hide');
                $('#feedback-form')[0].reset(); 
                $('#feedbackModal .modal-body').html(`
                    <form id="feedback-form" method="POST">
                        @csrf
                        <input type="hidden" class="form-control" name="developer_id">

                        <div class="mb-3">
                            <textarea name="feedback" rows="4" class="form-control" placeholder="Write your feedback here..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Submit Feedback</button>
                    </form>
                `);
            }, 3000);
        },

        error: function (xhr, status, error) {
            console.log('ERROR:', xhr.responseText);
            alert('Something went wrong!');
        }
    });
});
});
    $(document).ready(function() {
        $('#dev-listing').DataTable();
    });
</script>
@endsection