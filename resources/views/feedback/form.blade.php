@extends('blank')

@section('title', 'Submit Feedback')
@section('subtitle', 'Submit Feedback')

@section('content')

<style>
body {
    background-color: #f3f4f6;
    font-family: 'Inter', sans-serif;
}
.card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

/* STAR RATING */
.star-rating-wrap { display: flex; gap: 6px; }
.star-rating-wrap .star {
    font-size: 32px;
    cursor: pointer;
    color: #d1d5db;
    transition: all 0.2s;
}
.star-rating-wrap .star.active { color: #f59e0b; }
.rating-label {
    font-size: 13px;
    color: #6b7280;
    margin-top: 5px;
    min-height: 20px;
}

/* TICKET INFO GRID */
.ticket-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 20px 16px;
}
.info-item label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
    display: block;
    margin-bottom: 3px;
}
.info-item .value {
    font-size: 13px;
    color: #111827;
    font-weight: 500;
}

/* BADGES */
.badge-base {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
}
.badge-priority-high   { background:#fef3c7; color:#92400e; }
.badge-priority-medium { background:#dbeafe; color:#1e40af; }
.badge-priority-low    { background:#f3f4f6; color:#374151; }
.badge-status          { background:#d1fae5; color:#065f46; }

/* AVATARS */
.dev-avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #4F46E5;
    color: #fff;
    font-size: 12px; font-weight: 600;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.client-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    background: #4F46E5;
    color: #fff;
    font-size: 12px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.dev-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f3f4f6;
    border-radius: 20px;
    padding: 4px 10px 4px 4px;
    font-size: 12px;
    font-weight: 500;
}
.client-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    border-radius: 20px;
    padding: 4px 12px 4px 4px;
    font-size: 12px;
    font-weight: 600;
    color: #3730a3;
}

/* FORM */
.form-control {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 13px;
    padding: 10px;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: #4F46E5;
    box-shadow: 0 0 0 2px rgba(79,70,229,0.15);
}
.btn-primary {
    background-color: #4F46E5;
    border-color: #4F46E5;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-primary:hover {
    background-color: #4338ca;
    border-color: #4338ca;
}
    .success-card {
        width: 100%;
        border-radius: 20px;
        background: #ffffff;
        overflow: hidden;
        position: relative;
        animation: fadeIn 1s ease;
    }

    /* Floating Glow */
    .success-card::before {
        content: '';
        position: absolute;
        width: 250px;
        height: 250px;
        background: rgba(25, 135, 84, 0.08);
        border-radius: 50%;
        top: -80px;
        right: -80px;
    }

    /* Success Icon Animation */
    .success-icon {
        font-size: 90px;
        animation: pop 0.8s ease forwards,
                   pulse 2s infinite;
        transform: scale(0);
    }

    /* Pop Animation */
    @keyframes pop {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        70% {
            transform: scale(1.2);
            opacity: 1;
        }
        100% {
            transform: scale(1);
        }
    }

    /* Pulse Animation */
    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.08);
        }
        100% {
            transform: scale(1);
        }
    }

    /* Card Fade */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media(max-width:768px){
        .success-card{
            padding: 35px 20px !important;
        }

        .success-icon{
            font-size: 70px;
        }
    }
</style>

<div style="max-width:640px; margin:0 auto; padding:20px;">

@if(session('success'))
<div class="container-fluid px-3">
    <div class="card text-center p-5 mt-3 border-0 shadow-lg success-card">

        <!-- Animated Success Icon -->
        <div class="success-animation">
            <i class="bi bi-check-circle-fill text-success success-icon"></i>
        </div>

        <!-- Heading -->
        <h2 class="text-success fw-bold mt-4">
            Thank You! 🎉
        </h2>

        <!-- Message -->
        <p class="text-muted mt-3 mb-2" style="font-size:18px; line-height:1.8;">
            {{ session('success') }}
        </p>

        <!-- Extra Text -->
        <p class="text-secondary mb-4" style="font-size:16px;">
            We truly appreciate you taking the time to share your valuable experience with us.
            Your feedback helps us improve and serve you better every day.
        </p>

        <!-- Optional Button -->
        <div>
            <a href="/" class="btn btn-success px-4 py-2 rounded-pill">
                Back to Home
            </a>
        </div>

    </div>
</div>
@else

    @if(session('error'))
        <div class="alert alert-danger mt-2 py-2" style="font-size:13px;">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mt-2">
            <ul class="mb-0" style="font-size:13px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- TICKET CARD --}}
    <div class="card mb-4">

        {{-- WHITE HEADER: Logo left | Client identity center-left | actions right --}}
        <div class="px-4 py-3 d-flex justify-content-between align-items-center"
            style="background:#fff; border-bottom:0.5px solid #e5e7eb;">

            {{-- Left: Client identity --}}
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('assets/img/code4each_logo.png') }}" alt="Code4Each" style="height:28px;">
            
            </div>

            {{-- Right: ticket link --}}
            <a href="{{ url('/view/ticket/'.$ticket->id) }}" target="_blank"
            class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:12px; white-space:nowrap;">
                #{{ $ticket->id }} <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>

        </div>
                {{-- Client Info --}}
                    
                    <div style="background:#f8fafc; border:1px solid #e5e9f0; border-radius:8px; padding:12px 16px; margin-top:16px; text-align:center;">
                        <p style="margin:0; font-size:15px; font-weight:600; color:#111827;">Hello, {{ $clientName }} 👋</p>
                        <p style="margin:0; font-size:12px; color:#6b7280; margin-top:2px;">We'd love to hear how we did on this ticket.</p>
                    </div>
        {{-- Ticket Details --}}
        <div class="px-4 py-3 bg-white">
            <div class="ticket-info-grid">

            <div class="info-item d-flex align-items-center justify-content-between flex-wrap" style="grid-column:1/-1; gap:1rem;">
                
                {{-- Ticket Title --}}
                <div>
                    <label class="text-muted" style="font-size:12px;">Ticket</label>
                    <div class="value fw-semibold" style="font-size:14px;">{{ $ticket->title }}</div>
                </div>

            </div>

                @if($ticket->project)
                <div class="info-item">
                    <label>Project</label>
                    <div class="value">{{ $ticket->project->project_name ?? '—' }}</div>
                </div>
                @endif

                @if($ticket->ticket_category)
                <div class="info-item">
                    <label>Category</label>
                    <div class="value">{{ ucfirst($ticket->ticket_category) }}</div>
                </div>
                @endif

                <div class="info-item">
                    <label>Status</label>
                    <span class="badge-base badge-status">Completed</span>
                </div>

                @if($ticket->eta)
                <div class="info-item">
                    <label>ETA</label>
                    <div class="value">{{ \Carbon\Carbon::parse($ticket->eta)->format('M d, Y') }}</div>
                </div>
                @endif

                @if($ticket->time_estimation)
                <div class="info-item">
                    <label>Time Estimation</label>
                    <div class="value">{{ $ticket->time_estimation }} hrs</div>
                </div>
                @endif

            </div>
        </div>

        {{-- Assigned Developers --}}
        @if($ticket->ticketAssigns && $ticket->ticketAssigns->isNotEmpty())
        <div class="px-4 py-3 bg-white" style="border-top:1px solid #e5e7eb;">
            <label style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#9ca3af;">Assigned Developers</label>
            <div class="d-flex flex-wrap gap-2 mt-1">
                @foreach($ticket->ticketAssigns as $assign)
                    @if($assign->user)
                    <div class="dev-chip">
                        <div class="dev-avatar">
                            {{ strtoupper(substr($assign->user->first_name,0,1)) }}{{ strtoupper(substr($assign->user->last_name ?? '',0,1)) }}
                        </div>
                        {{ $assign->user->first_name }} {{ $assign->user->last_name }}
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- FEEDBACK FORM --}}
    <div class="card mb-4">
        <div class="px-4 py-3" style="border-bottom:1px solid #e5e7eb;">
            <h6 class="mb-0 fw-bold" style="color:#111827;">Share Your Experience</h6>
            <p class="mb-0 text-muted" style="font-size:13px;">Your feedback helps us improve.</p>
        </div>

        <div class="px-4 py-4">
            <form id="feedbackForm" action="{{ route('ticketfeedback.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="encoded_ticket_id" value="{{ $encodedId }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1" style="color:#374151; font-size:13px;">
                        How would you rate this experience?
                    </label>
                    <div class="star-rating-wrap">
                        <input type="hidden" name="rating" id="rating-value">
                        <span class="star" data-value="1">&#9733;</span>
                        <span class="star" data-value="2">&#9733;</span>
                        <span class="star" data-value="3">&#9733;</span>
                        <span class="star" data-value="4">&#9733;</span>
                        <span class="star" data-value="5">&#9733;</span>
                    </div>
                    <div class="rating-label" id="rating-label">Click a star to rate</div>
                    @error('rating')<span class="text-danger small">{{ $message }}</span>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold mb-1" style="color:#374151; font-size:13px;">Comments</label>
                    <textarea name="comments" class="form-control" rows="4"
                              placeholder="Tell us about your experience with this ticket...">{{ old('comments') }}</textarea>
                    @error('comments')<span class="text-danger small">{{ $message }}</span>@enderror
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary px-5" style="border-radius:8px;">
                        Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>

@endif
</div>

<script>
const stars       = document.querySelectorAll('.star-rating-wrap .star');
const ratingInput = document.getElementById('rating-value');
const ratingLabel = document.getElementById('rating-label');
const labels      = ['', 'Terrible 😞', 'Poor 😕', 'Average 😐', 'Good 😊', 'Excellent 🤩'];
let current = 0;

stars.forEach((star, i) => {
    star.addEventListener('click', () => {
        current = i + 1;
        ratingInput.value = current;
        ratingLabel.textContent = labels[current];
        ratingLabel.style.color = '#f59e0b';
        paint(current);
    });
    star.addEventListener('mouseover', () => paint(i + 1));
    star.addEventListener('mouseout',  () => paint(current));
});

function paint(n) {
    stars.forEach((s, i) => s.classList.toggle('active', i < n));
}

document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    if (!ratingInput.value) {
        e.preventDefault();
        ratingLabel.textContent = 'Please select a rating ⭐';
        ratingLabel.style.color = '#ef4444';
    }
});
</script>

@endsection