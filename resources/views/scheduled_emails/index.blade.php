@extends('layout')
@section('title', 'Applicants')
@section('subtitle', 'Applicants')
@section('content')

<div class="pagetitle">
    <h1>Scheduled Emails</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item active">Scheduled Emails</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">

        {{-- Stats --}}
        <div class="col-md-4">
            <div class="card" style="background:#EEEDFE;border:none">
                <div class="card-body py-3">
                    <div class="small fw-semibold" style="color:#7F77DD;text-transform:uppercase;letter-spacing:.05em">Scheduled</div>
                    <div class="fs-3 fw-semibold" style="color:#3C3489">
                        {{ $emails->where('status','scheduled')->count() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="background:#E1F5EE;border:none">
                <div class="card-body py-3">
                    <div class="small fw-semibold" style="color:#1D9E75;text-transform:uppercase;letter-spacing:.05em">Sent This Month</div>
                    <div class="fs-3 fw-semibold" style="color:#085041">
{{ $emails->where('status','sent')->count() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="background:#FCEBEB;border:none">
                <div class="card-body py-3">
                    <div class="small fw-semibold" style="color:#E24B4A;text-transform:uppercase;letter-spacing:.05em">Failed</div>
                    <div class="fs-3 fw-semibold" style="color:#791F1F">
                        {{ $emails->where('status','failed')->count() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                        <h5 class="card-title mb-0">All Scheduled Emails</h5>
                        <a href="{{ route('scheduled.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Schedule Email
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Template</th>
                                    <th>Client(s)</th>
                                    <th>Project</th>
                                    <th>Scheduled For</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($emails as $email)
                                <tr>
                                    <td>
                                    <strong>{{ $email->template->name ?? 'Deleted Template' }}</strong><br>
                                    <small class="text-muted">{{ $email->template->subject ?? '—' }}</small>
                                    </td>
                                    <td>
                                        @foreach($email->recipients->take(2) as $r)
                                            <span class="badge bg-light text-dark border">
                                                {{ $r->client->name ?? 'N/A' }}
                                            </span>
                                        @endforeach
                                        @if($email->recipients->count() > 2)
                                            <span class="badge bg-secondary">
                                                +{{ $email->recipients->count() - 2 }} more
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $email->project->name ?? '—' }}</td>
                                    <td>
                                    {{ $email->send_at ? $email->send_at->format('d M Y') : '—' }}<br>
                                    <small class="text-muted">{{ $email->send_at ? $email->send_at->format('h:i A') : '' }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'scheduled'  => 'primary',
                                                'sent'       => 'success',
                                                'failed'     => 'danger',
                                                'cancelled'  => 'secondary',
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$email->status] ?? 'secondary' }}">
                                            {{ ucfirst($email->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($email->status === 'scheduled')
                                        <form action="{{ route('scheduled.destroy', $email->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Cancel this scheduled email?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </form>
                                        @elseif($email->status === 'failed')
                                        <span class="text-muted small">Check tracking</span>
                                        @else
                                        <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No scheduled emails yet.
                                        <a href="{{ route('scheduled.create') }}">Schedule one now</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
