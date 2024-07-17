 <!-- ------------voting profile start ---------- -->
 @php
 $count=count($winners);
 @endphp
 @if($count > 0)
 <div class="row mt-4">
        <h4 class="font-weight-bolder">Employee Of The Month</h4>
        @foreach ($winners as $winner)
        @php
            $previousMonth = (int)date('m', strtotime('-1 month'));
        @endphp
        @if ($winner->month === $previousMonth)
        <div class="col-lg-5 mb-lg-0 mb-4">
            <div class="card mb-3 winner-card">
                <div class="header-image-right">
                    <img src="{{ asset('assets/img/design1.png') }}" class="design1">
                </div>
                <div class="footer-image-left">
                    <img src="{{ asset('assets/img/design1.png') }}" class="design1">
                </div>
                <div class="share-image">
                    <img src="{{ asset('assets/img/shareicon.png') }}" class="share">
                </div>

                <div class="trophy"> <img src="{{ asset('assets/img/winner.png') }}" class="winner"></div>
                <div class="card-body p-3">
                    <div class="display-user h-100 with-background">
                        @if ($winner->user)

                        <div class="winner-card-header text-center">
                            <div class="company-name"><span>Code4Each</span></div>
                            <div class="winner-slogan">
                                <h2>Employee Of the Month ({{ date('F', mktime(0, 0, 0, $winner->month, 1)) }})</h2>
                            </div>

                        </div>

                        <div class="">
                            <div class="winner-profile text-center">
                                <img src="{{ asset('assets/img/'.$winner->user->profile_picture) }}" alt="Profile Picture" class="profile-pic">
                            </div>
                            <div class="profile-info">
                                <h5 class="mb-1">{{ $winner->user->first_name }} {{ $winner->user->last_name }}</h5>
                                <p>Total Votes: {{ $winner->winner_votes }}/{{$winner->totalvotes}}</p>
                            </div>

                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Notes -->
        <div class="col-lg-7">
            <div class="card p-4">
                <div class="card-body position-relative z-index-1 d-flex flex-column h-100 p-3">
                    <section class="quotes">
                    @foreach ($winner->uservotes as $vote)
                    @php
                    $notesDate = $vote->month; 
                    $previousMonth = (int)date('m', strtotime('-1 month')); 
                    @endphp

                    @if ($notesDate === $previousMonth)
                        <div class="bubble">
                            <blockquote>
                                <p>{{ $vote->notes }}</p>
                            </blockquote>
                            <div></div>
                        </div>
                    @endif
                @endforeach
                    </section>

                </div>
            </div>
        </div>
        <!----------End of Notes---------------->
        @endif
        @endforeach
 </div>
 @endif
 @if(isset($allVotes))
 
    <section class="testimonial">
        <div class="container">
            <div class="testimonial__inner">
                <div class="testimonial-slider">
                @foreach($allVotes as $vote)

                                        <div class="testimonial-slide" style="width: 100%;display: inline-block;">
                                            <div class="testimonial_box">
                                                <div class="testimonial_box-inner">
                                                    <div class="testimonial_box-top">
                                                    
                                                        <div class="text-wrapper">
                                                            <div class="testimonial_box-text">
                                                                <p>{{ $vote->notes }}</p>
                                                            </div>
                                                            <div class="image-design">
                                                                <div class="testimonial_box-img">
                                                                    @if($vote->profile_picture)
                                                                        <img src="{{ asset('assets/img/' . $vote->profile_picture) }}" alt="profile">
                                                                    @else
                                                                        <img src="{{asset('assets/img/blankImage.jpg')}}" alt="dummy profile">
                                                                    @endif
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
                                  
                    @endforeach

                </div>
            </div>
        </div>
    </section>
        <!----------End of Notes---------------->
@endif
 <!-- ------------voting profile end ---------- -->