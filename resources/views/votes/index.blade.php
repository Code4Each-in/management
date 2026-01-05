@if(isset($winners) && isset($allVotes))
    @php
        // Collect **all votes of winners**
        $winnerVotes = collect($winners)->flatMap(function ($winner) {
            return $winner->uservotes->map(function ($vote) {
                return (object) [
                    'notes' => $vote->notes,
                    'first_name' => $vote->first_name ?? 'Unknown',
                    'last_name' => $vote->last_name ?? '',
                    'profile_picture' => $vote->profile_picture ?? 'blankImage.jpg',
                ];
            });
        });

        // Merge **winner votes and other votes**
        $mergedVotes = $winnerVotes->merge($allVotes);
    @endphp

    @if($mergedVotes->isNotEmpty())
    <section class="testimonial">
        <div class="container">
            <h5 class="card-title">Appreciations</h5>
            <div class="testimonial__inner">
                <div class="testimonial-slider">
                    @foreach($mergedVotes as $vote)
                        <div class="testimonial-slide" style="width: 100%; display: inline-block;">
                            <div class="testimonial_box">
                                <div class="testimonial_box-inner">
                                    <div class="testimonial_box-top">
                                        <div class="text-wrapper">
                                            <div class="testimonial_box-text">
                                                <p>{{ $vote->notes }}</p>
                                            </div>
                                            <div class="image-design">
                                                <div class="text-img">
                                                    <div class="testimonial_box-name1">
                                                        <h4>Appreciation For</h4>
                                                    </div>
                                                    <div class="testimonial_box-img">
                                                        <img src="{{ asset('assets/img/' . $vote->profile_picture) }}" alt="profile">
                                                    </div>
                                                    <div class="testimonial_box-name">
                                                        <h4>{{ $vote->first_name }} {{ $vote->last_name }}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif

@endif
