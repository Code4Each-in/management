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
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body pt-4">

                    <form action="{{ route('scheduled.store') }}" method="POST" id="schedule-form">
                        @csrf

                        {{-- TEMPLATE --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Select Template <span class="text-danger">*</span>
                            </label>

                            <select name="template_id" id="template-select"
                                    class="form-select @error('template_id') is-invalid @enderror"
                                    onchange="loadPreview(this)">
                                <option value="">-- Choose a template --</option>

                                @foreach($templates as $t)
                                <option value="{{ $t->id }}"
                                    data-subject="{{ $t->subject }}"
                                    data-body="{{ base64_encode($t->body) }}"
                                    data-banner="{{ $t->banner_image ? asset('storage/'.$t->banner_image) : '' }}"
                                    {{ old('template_id', request('template_id')) == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} ({{ ucfirst($t->category) }})
                                </option>
                                @endforeach
                            </select>

                            @error('template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- PREVIEW --}}
                        <div id="template-preview" class="mb-3 d-none">
                            <label class="form-label fw-semibold small text-muted">Preview</label>
                            <div class="border rounded overflow-hidden" style="background:#fbfbff">
                                <div class="p-3">
                                    <div class="fw-semibold mb-2"
                                         id="preview-subject"
                                         style="color:#3C3489"></div>

                                    <div id="preview-body"
                                         style="font-size:14px;color:#555;line-height:1.7"></div>
                                </div>
                            </div>
                        </div>
                        {{-- CLIENTS --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Target Clients <span class="text-danger">*</span>
                            </label>

                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                    <label class="form-check-label small" for="select-all">
                                        Select all
                                    </label>
                                </div>
                                <small class="text-muted">
                                    <span id="selected-count">0</span> selected
                                </small>
                            </div>

                            <div class="border rounded p-2"
                                 style="max-height:200px;overflow-y:auto;background:#fbfbff">

                                @foreach($clients as $client)
                                <div class="form-check py-1 client-row">
                                    <input class="form-check-input client-checkbox"
                                           type="checkbox"
                                           name="client_ids[]"
                                           value="{{ $client->id }}"
                                           id="c{{ $client->id }}">

                                    <label class="form-check-label" for="c{{ $client->id }}">
                                        <span class="fw-semibold">{{ $client->name }}</span><br>
                                        <small class="text-muted">{{ $client->email }}</small>
                                    </label>
                                </div>
                                @endforeach

                            </div>

                            @error('client_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        

                        {{-- DATE TIME --}}
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Date</label>
                                <input type="date" name="send_date"
                                       class="form-control"
                                       min="{{ now()->format('Y-m-d') }}">
                            @error('send_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            </div>
                         

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Time</label>
                                <input type="time" name="send_time"
                                       class="form-control"
                                       value="09:00">
                                           @error('send_time')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            </div>
                        

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Timezone</label>
                                <input type="text" class="form-control" value="IST" disabled>
                            </div>
                        </div>

                        {{-- ACTION --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                Scheduling for <span id="footer-count">0</span> client(s)
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('scheduled.index') }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary" id="schedule-btn" disabled>
                                    <i class="bi bi-clock"></i> Confirm & Schedule
                                </button>
                            </div>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</section>

<script>
const clientCheckboxes = document.querySelectorAll('.client-checkbox');
const selectAll = document.getElementById('select-all');
const selectedCountEl = document.getElementById('selected-count');
const footerCountEl = document.getElementById('footer-count');
const scheduleBtn = document.getElementById('schedule-btn');
const templateSelect = document.getElementById('template-select');

function updateCounts() {
    const count = document.querySelectorAll('.client-checkbox:checked').length;
    selectedCountEl.textContent = count;
    footerCountEl.textContent = count;
    toggleButton();
}

function toggleButton() {
    const hasClients = document.querySelectorAll('.client-checkbox:checked').length > 0;
    const hasTemplate = templateSelect.value !== '';
    scheduleBtn.disabled = !(hasClients && hasTemplate);
}

clientCheckboxes.forEach(cb => cb.addEventListener('change', updateCounts));

selectAll.addEventListener('change', function () {
    clientCheckboxes.forEach(cb => cb.checked = selectAll.checked);
    updateCounts();
});

function renderBodyForPreview(html, bannerSrc) {
    const regex = /\{\{\s*banner_image\s*\}\}/gi;

    if (bannerSrc) {
        return html.replace(regex,
            '<img src="'+bannerSrc+'" style="max-width:100%;margin:6px 0;border-radius:6px;">');
    }

    return html.replace(regex,
        '<span style="background:#eee;padding:6px 10px;border-radius:4px;">No Banner</span>');
}

function loadPreview(sel) {
    const opt = sel.options[sel.selectedIndex];
    const preview = document.getElementById('template-preview');

    if (!opt.value) {
        preview.classList.add('d-none');
        toggleButton();
        return;
    }

    document.getElementById('preview-subject').textContent = opt.dataset.subject;

    const rawHtml = atob(opt.dataset.body || '');
    const banner = opt.dataset.banner || '';

    document.getElementById('preview-body').innerHTML =
        renderBodyForPreview(rawHtml, banner);

    // const img = document.getElementById('preview-banner');
    // const ph = document.getElementById('preview-banner-placeholder');

    // if (banner) {
    //     img.src = banner;
    //     img.classList.remove('d-none');
    //     ph.style.display = 'none';
    // } else {
    //     img.classList.add('d-none');
    //     ph.style.display = 'flex';
    // }

    preview.classList.remove('d-none');
    toggleButton();
}

window.addEventListener('DOMContentLoaded', () => {
    if (templateSelect.value) loadPreview(templateSelect);
});

document.getElementById('schedule-form').addEventListener('submit', function(e){
    const count = document.querySelectorAll('.client-checkbox:checked').length;

    if (count === 0 || !templateSelect.value) {
        e.preventDefault();
        alert('Select at least one client and template');
    }
});
</script>

@endsection