@extends('layout')
@section('title', 'Scheduled Email Recipients')
@section('subtitle', 'Scheduled Email Recipients')

@section('content')

<div class="pagetitle">
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
                <div class="small fw-semibold text-uppercase">Pending</div>
                <div class="fs-3 fw-semibold">
                    {{ $recipients->where('status','pending')->count() }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card" style="background:#E1F5EE;border:none">
            <div class="card-body py-3">
                <div class="small fw-semibold text-uppercase">Sent</div>
                <div class="fs-3 fw-semibold">
                    {{ $recipients->where('status','sent')->count() }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card" style="background:#FCEBEB;border:none">
            <div class="card-body py-3">
                <div class="small fw-semibold text-uppercase">Failed</div>
                <div class="fs-3 fw-semibold">
                    {{ $recipients->where('status','failed')->count() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body pt-3">

                <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                    <h5 class="card-title mb-0">All Email Recipients</h5>
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
                                <th>Client</th>
                                <th>Project</th>
                                <th>Scheduled For</th>
                                <th>Status</th>
                                <th>Action</th>
                                <th>Preview</th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse($recipients as $recipient)
                            <tr>
                                {{-- Template --}}
                                <td>
                                    <strong>
                                        {{ $recipient->scheduledEmail->template->name ?? 'Deleted Template' }}
                                    </strong><br>
                                    <small class="text-muted">
                                        {{ $recipient->scheduledEmail->template->subject ?? '—' }}
                                    </small>
                                </td>

                                {{-- Client --}}
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $recipient->client->name ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Project --}}
                                <td>
                                    {{ $recipient->scheduledEmail->project->name ?? '—' }}
                                </td>

                                {{-- Date --}}
                                <td>
                                    {{ $recipient->scheduledEmail->send_at ? $recipient->scheduledEmail->send_at->format('d M Y') : '—' }}<br>
                                    <small class="text-muted">
                                        {{ $recipient->scheduledEmail->send_at ? $recipient->scheduledEmail->send_at->format('h:i A') : '' }}
                                    </small>
                                </td>

                                {{-- Status --}}
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'primary',
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                        ];
                                    @endphp

                                    <span class="badge bg-{{ $statusColors[$recipient->status] ?? 'secondary' }}">
                                        {{ ucfirst($recipient->status) }}
                                    </span>
                                </td>

                                {{-- Action --}}
                                <td>
                                    @if($recipient->status === 'pending')
                                      <form action="{{ url('scheduled/cancel/' . $recipient->id) }}" method="POST">                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">
                                                Cancel
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                                {{-- Preview --}}
                                <td>
                                    <button class="btn btn-sm btn-primary preview-btn"
                                            data-id="{{ $recipient->scheduled_email_id }}">
                                        Preview
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No records found.
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

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Email Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <iframe id="previewFrame" style="width:100%; height:500px; border:none;"></iframe>
            </div>

        </div>
    </div>
</div>
```

</section>

<script>
document.querySelectorAll('.preview-btn').forEach(btn => {
    btn.addEventListener('click', function () {

        let id = this.getAttribute('data-id');
        let url = `/scheduled/${id}/preview`;

        let iframe = document.getElementById('previewFrame');

        fetch(url)
            .then(res => res.text())
            .then(html => {
                iframe.srcdoc = html;
                let modal = new bootstrap.Modal(document.getElementById('previewModal'));
                modal.show();
            });
    });
});
</script>

@endsection
