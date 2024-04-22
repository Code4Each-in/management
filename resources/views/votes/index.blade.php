 <!-- ------------voting profile start ---------- -->
 @php 
$count=count($winners); 
@endphp 
<div class="row mt-4">
    @foreach ($winners as $winner)
    <h4 class="font-weight-bolder">Employee Of The Month</h4>
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
                        <div class="winner-slogan"><h2>Employee Of the Month ({{ date('F', mktime(0, 0, 0, $winner->month, 1)) }})</h2></div>
                       
                    </div>

                    <div class="">
                    <div class="winner-profile text-center">
                        <img src="{{ asset('assets/img/'.$winner->user->profile_picture) }}" alt="Profile Picture" class="profile-pic">
                    </div>
                    <div class="profile-info">
                        <h5 class="mb-1">{{ $winner->user->first_name }} {{ $winner->user->last_name }}</h5>
                        <p>Total Votes: {{ $winner->totalvotes }}</p> 
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
                    <div class="bubble">
                        <blockquote>
                            <p>{{ $vote->notes }}</p>
                        </blockquote>
                        <div></div>
                    </div>
                    @endforeach
                </section>
                
            </div>
        </div>
    </div>
    @endforeach
</div>
    <!-- ------------voting profile end ---------- -->