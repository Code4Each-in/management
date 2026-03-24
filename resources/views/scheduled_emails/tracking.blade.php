@extends('layout')
@section('title', 'Applicants')
@section('subtitle', 'Applicants')
@section('content')

<div class="pagetitle">
    <h1>Email Tracking</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item active">Email Tracking</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">

        {{-- Stats --}}
        <div class="col-md-4">
            <div class="card" style="background:#EEEDFE;border:none">
                <div class="card-body py-3">
                    <div class="small fw-semibold" style="color:#7F77DD;text-transform:uppercase">Total Sent</div>
                    <div class="fs-3 fw-semibold" style="color:#3C3489">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="background:#E1F5EE;border:none">
                <div class="card-body py-3">
                    <div class="small fw-semibold" style="color:#1D9E75;text-transform:uppercase">Delivered</div>
                    <div class="fs-3 fw-semibold" style="color:#085041">{{ $stats['sent'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="background:#FCEBEB;border:none">
                <div class="card-body py-3">
                    <div class="small fw-semibold" style="color:#E24B4A;text-transform:uppercase">Failed</div>
                    <div class="fs-3 fw-semibold" style="color:#791F1F">{{ $stats['failed'] }}</div>
                </div>
            </div>
        </div>

        {{-- Timeline log --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                        <h5 class="card-title mb-0">Email Activity Log</h5>
                        <div>
                            <a href="{{ route('scheduled.tracking') }}"
                               class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
                            <a href="{{ route('scheduled.tracking', ['status'=>'sent']) }}"
                               class="btn btn-sm {{ request('status')=='sent' ? 'btn-success' : 'btn-outline-secondary' }}">Sent</a>
                            <a href="{{ route('scheduled.tracking', ['status'=>'failed']) }}"
                               class="btn btn-sm {{ request('status')=='failed' ? 'btn-danger' : 'btn-outline-secondary' }}">Failed</a>
                            <a href="{{ route('scheduled.tracking', ['status'=>'pending']) }}"
                               class="btn btn-sm {{ request('status')=='pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Pending</a>
                        </div>
                    </div>

                    @forelse($recipients as $r)
                    @php
                        $dotColor = match($r->status) {
                            'sent'    => '#1D9E75',
                            'failed'  => '#E24B4A',
                            'pending' => '#7F77DD',
                            default   => '#888',
                        };
                        $badgeColor = match($r->status) {
                            'sent'    => 'success',
                            'failed'  => 'danger',
                            'pending' => 'primary',
                            default   => 'secondary',
                        };
                    @endphp
                    <div class="d-flex gap-3 py-3 border-bottom">
                        <div style="width:10px;height:10px;border-radius:50%;
                                    background:{{ $dotColor }};margin-top:4px;flex-shrink:0"></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong style="font-size:13px">
                                        {{ $r->scheduledEmail->template->name ?? 'N/A' }}
                                        → {{ $r->client->name ?? 'N/A' }}
                                    </strong>
                                    <div class="text-muted small mt-1">
                                        {{ $r->client->email ?? '' }}
                                        @if($r->scheduledEmail->project)
                                            &middot; Project: {{ $r->scheduledEmail->project->name }}
                                        @endif
                                    </div>
                                    @if($r->error)
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $r->error }}
                                    </div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $badgeColor }}">{{ ucfirst($r->status) }}</span>
                                    <div class="text-muted small mt-1">
                                        @if($r->sent_at)
                                            {{ $r->sent_at->format('d M Y · h:i A') }}
                                        @else
                                            Scheduled: {{ $r->scheduledEmail->send_at->format('d M Y · h:i A') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No email activity yet.</p>
                    </div>
                    @endforelse

                    <div class="mt-3">{{ $recipients->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
