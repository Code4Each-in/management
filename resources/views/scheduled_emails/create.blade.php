@extends('layout')
@section('title', 'Applicants')
@section('subtitle', 'Applicants')
@section('content')

<div class="pagetitle">
    <h1>Schedule Email</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('scheduled.index') }}">Scheduled Emails</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body pt-4">

                    <form action="{{ route('scheduled.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Template <span class="text-danger">*</span></label>
                            <select name="template_id" id="template-select"
                                    class="form-select @error('template_id') is-invalid @enderror"
                                    onchange="loadPreview(this)">
                                <option value="">-- Choose a template --</option>
                                @foreach($templates as $t)
                                <option value="{{ $t->id }}"
                                    data-body="{{ e($t->body) }}"
                                    data-subject="{{ e($t->subject) }}"
                                    data-banner="{{ $t->banner_image ? asset('storage/'.$t->banner_image) : '' }}"
                                    {{ old('template_id', request('template_id')) == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} ({{ ucfirst($t->category) }})
                                </option>
                                @endforeach
                            </select>
                            @error('template_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Clients <span class="text-danger">*</span></label>
                            <div class="mb-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="select-all"
                                           onchange="toggleAll(this)">
                                    <label class="form-check-label small" for="select-all">Select all</label>
                                </div>
                            </div>
                            <div class="border rounded p-2" style="max-height:180px;overflow-y:auto">
                                @foreach($clients as $client)
                                <div class="form-check">
                                    <input class="form-check-input client-check" type="checkbox"
                                           name="client_ids[]" value="{{ $client->id }}"
                                           id="c{{ $client->id }}"
                                           {{ in_array($client->id, old('client_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="c{{ $client->id }}">
                                        {{ $client->name }}
                                        @if($client->email)
                                            <small class="text-muted">— {{ $client->email }}</small>
                                        @endif
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @error('client_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Project <small class="text-muted fw-normal">(optional)</small></label>
                            <select name="project_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                <input type="date" name="send_date"
                                       class="form-control @error('send_at') is-invalid @enderror"
                                       value="{{ old('send_date') }}"
                                       min="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Time <span class="text-danger">*</span></label>
                                <input type="time" name="send_time" class="form-control"
                                       value="{{ old('send_time', '09:00') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Timezone</label>
                                <input type="text" class="form-control" value="IST" disabled>
                            </div>
                        </div>
                        @error('send_at')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('scheduled.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-clock"></i> Confirm & Schedule
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- Preview panel --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body pt-4">
                    <h6 class="fw-semibold mb-3">
                        <i class="bi bi-eye text-primary"></i> Email Preview
                    </h6>
                    <div id="preview-empty" class="text-muted small text-center py-4">
                        Select a template to see preview
                    </div>
                    <div id="preview-box" class="d-none border rounded overflow-hidden">
                        <img id="preview-banner" class="w-100 d-none" style="max-height:80px;object-fit:cover">
                        <div id="preview-banner-placeholder"
                             style="height:40px;background:linear-gradient(90deg,#EEEDFE,#E6F1FB);
                                    display:flex;align-items:center;justify-content:center">
                            <small class="text-muted">[ Banner ]</small>
                        </div>
                        <div class="p-3">
                            <p class="mb-1"><strong style="font-size:12px;color:#999">Subject:</strong>
                                <span id="preview-subject" class="small"></span></p>
                            <hr class="my-2">
                            <div id="preview-body"
                                 style="font-size:13px;color:#555;line-height:1.7;white-space:pre-line"></div>
                        </div>
                        <div class="px-3 py-2 border-top"
                             style="background:#f8f9fa;font-size:11px;color:#999">
                            Sent via {{ config('app.name') }} · Unsubscribe
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

@push('scripts')
<script>
function loadPreview(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) {
        document.getElementById('preview-empty').classList.remove('d-none');
        document.getElementById('preview-box').classList.add('d-none');
        return;
    }
    document.getElementById('preview-empty').classList.add('d-none');
    document.getElementById('preview-box').classList.remove('d-none');
    document.getElementById('preview-subject').textContent = opt.dataset.subject;
    document.getElementById('preview-body').textContent = opt.dataset.body;
    const banner = opt.dataset.banner;
    const img = document.getElementById('preview-banner');
    const ph  = document.getElementById('preview-banner-placeholder');
    if (banner) {
        img.src = banner; img.classList.remove('d-none');
        ph.style.display = 'none';
    } else {
        img.classList.add('d-none');
        ph.style.display = 'flex';
    }
}
function toggleAll(cb) {
    document.querySelectorAll('.client-check').forEach(c => c.checked = cb.checked);
}
// Auto-load preview if template pre-selected (from "Schedule" button on templates page)
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('template-select');
    if (sel.value) loadPreview(sel);
});
</script>
@endpush

@endsection
