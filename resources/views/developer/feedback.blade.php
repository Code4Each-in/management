@extends('layout')
@section('title', 'All Feedbacks')
@section('subtitle', 'Show')
@section('content')
<div class="card">
    <div class="card-body pb-4">
        <table class="table table-striped  table-bordered" id="feedbacks">
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Feedback</th>
                <th>Developer</th>
                <th>Given  By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($feedbacks as $index => $feedback)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    @if(strlen(preg_replace('/&nbsp;/', ' ', strip_tags(htmlspecialchars_decode($feedback->feedback)))) >= 100)
                        <span class="feedback-description">
                            @php
                                $plainTextFeedback = preg_replace('/&nbsp;/', ' ', strip_tags(htmlspecialchars_decode($feedback->feedback)));
                                $limitedFeedback = substr($plainTextFeedback, 0, 100) . '...';
                                echo $limitedFeedback;
                            @endphp
                        </span>
                        <span class="fullFeedback" style="display: none;">
                            @php
                                echo $feedback->feedback;
                            @endphp
                        </span>
                        <a href="#" class="readMoreLink">Read More</a>
                        <a href="#" class="readLessLink" style="display: none;">Read Less</a>
                    @else
                        {!! preg_replace('/&nbsp;/', ' ', strip_tags(htmlspecialchars_decode($feedback->feedback))) !!}
                    @endif
                </td>                              
                <td>{{ $feedback->developer ? $feedback->developer->first_name . ' ' . $feedback->developer->last_name : 'N/A' }}</td>
                <td>{{ $feedback->client->first_name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>
@endsection
@section('js_scripts')
<script>
    $(document).ready(function() {
        $('#feedbacks').DataTable(); 
    });
    $('.readMoreLink').click(function(event) {
    event.preventDefault();

    var description = $(this).siblings('.feedback-description');
    var fullDescription = $(this).siblings('.fullFeedback');

    description.text(fullDescription.text());
    $(this).hide();
    $(this).siblings('.readLessLink').show();
});

$('.readLessLink').click(function(event) {
    event.preventDefault();

    var description = $(this).siblings('.feedback-description');
    var fullDescription = $(this).siblings('.fullFeedback');

    var truncatedDescription = fullDescription.text().substring(0, 100) + '...';
    description.text(truncatedDescription);
    $(this).hide();
    $(this).siblings('.readMoreLink').show();
});

</script>
@endsection
