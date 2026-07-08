@extends('layout')
@section('title', 'Send Mail')
@section('subtitle', 'Send Mail')
@section('content')

<div class="pagetitle">
    <h1>Send Mail to Clients</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">Email Templates</a></li>
            <li class="breadcrumb-item active">Send Mail</li>
        </ol>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<section class="section">
    <form action="{{ route('mail.send') }}" method="POST" id="send-mail-form">
        @csrf
        <div class="row">
            {{-- STEP 1: SELECT CLIENTS --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body pt-4">
                        <h5 class="card-title fw-semibold mb-3" style="color:#3C3489">
                            Select Clients
                        </h5>

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <input type="text" id="client-search" class="form-control form-control-sm"
                                   placeholder="Search clients by name or email...">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="select-all-clients">
                                <label class="form-check-label small" for="select-all-clients">Select all</label>
                            </div>
                            <small class="text-muted"><span id="selected-count">0</span> selected</small>
                        </div>

                        <div id="client-list" class="border rounded p-2"
                             style="max-height:320px;overflow-y:auto;background:#fbfbff">
                            @forelse($clients as $client)
                                <div class="form-check py-1 client-row"
                                     data-search="{{ strtolower($client->name.' '.$client->email) }}">
                                    <input type="checkbox" class="form-check-input client-checkbox"
                                           name="client_ids[]" value="{{ $client->id }}"
                                           id="client-{{ $client->id }}">
                                    <label class="form-check-label" for="client-{{ $client->id }}">
                                        <span class="fw-semibold">{{ $client->name }}</span>
                                        <br><small class="text-muted">{{ $client->email }}</small>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No clients found.</p>
                            @endforelse
                        </div>
                        @error('client_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- STEP 2: SELECT TEMPLATE --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body pt-4">
                        <h5 class="card-title fw-semibold mb-3" style="color:#3C3489">
                            Select Template
                        </h5>

                        <select name="template_id" id="template-select"
                                class="form-select @error('template_id') is-invalid @enderror">
                            <option value="">-- Choose a template --</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}"
                                        data-subject="{{ $template->subject }}"
                                        data-body="{{ base64_encode($template->body) }}"
                                        data-banner="{{ $template->banner_image ? asset('storage/'.$template->banner_image) : '' }}"
                                        {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }} ({{ ucfirst($template->category) }})
                                </option>
                            @endforeach
                        </select>
                        @error('template_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        {{-- Preview --}}
                        <div id="template-preview" class="mt-3 d-none">
                            <label class="form-label fw-semibold small text-muted">Preview</label>
                            <div class="border rounded overflow-hidden" style="background:#fbfbff">
                                <img id="preview-banner" class="w-100 d-none" style="max-height:120px;object-fit:cover">
                                <div id="preview-banner-placeholder"
                                     style="height:50px;background:linear-gradient(90deg,#EEEDFE,#E6F1FB);
                                            display:flex;align-items:center;justify-content:center">
                                   
                                </div>

                                <div class="p-3">
                                    <div class="fw-semibold mb-2" id="preview-subject" style="color:#3C3489"></div>
                                    <div id="preview-body" style="font-size:14px;color:#555;line-height:1.7"></div>
                                </div>

                               
                            </div>
                            <small class="text-muted d-block mt-1">
                                Placeholders like <code></code> will be auto-filled per client when sent.
                            </small>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: SEND --}}
                <div class="card mt-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Sending to <span id="footer-count" class="fw-semibold">0</span> client(s)
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('templates.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="send-btn" disabled>
                                <i class="bi bi-send"></i> Send Mail
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</section>

<script>
const clientCheckboxes = document.querySelectorAll('.client-checkbox');
const selectAll         = document.getElementById('select-all-clients');
const selectedCountEl   = document.getElementById('selected-count');
const footerCountEl     = document.getElementById('footer-count');
const sendBtn           = document.getElementById('send-btn');
const templateSelect    = document.getElementById('template-select');

function updateCounts() {
    const count = document.querySelectorAll('.client-checkbox:checked').length;
    selectedCountEl.textContent = count;
    footerCountEl.textContent   = count;
    toggleSendButton();
}

function toggleSendButton() {
    const hasClients  = document.querySelectorAll('.client-checkbox:checked').length > 0;
    const hasTemplate = templateSelect.value !== '';
    sendBtn.disabled = !(hasClients && hasTemplate);
}

clientCheckboxes.forEach(cb => cb.addEventListener('change', updateCounts));

selectAll.addEventListener('change', function () {
    document.querySelectorAll('.client-row:not(.d-none) .client-checkbox').forEach(cb => {
        cb.checked = selectAll.checked;
    });
    updateCounts();
});

document.getElementById('client-search').addEventListener('input', function () {
    const term = this.value.toLowerCase();
    document.querySelectorAll('.client-row').forEach(row => {
        row.classList.toggle('d-none', !row.dataset.search.includes(term));
    });
});

// using the given banner URL. If no banner exists, show a muted box instead.
function renderBodyForPreview(html, bannerSrc) {
    const placeholderRegex = /\{\{\s*banner_image\s*\}\}/gi;

    if (bannerSrc) {
        const imgTag = '<img src="' + bannerSrc + '" '
            + 'style="max-width:100%;border-radius:6px;margin:6px 0;display:block;" '
            + 'alt="Banner image">';
        return html.replace(placeholderRegex, imgTag);
    }

    const emptyBox = '<span style="display:inline-block;padding:10px 14px;'
        + 'background:#f1f1f1;color:#999;border-radius:4px;font-size:12px;">'
        + '[ Banner image not set ]</span>';
    return html.replace(placeholderRegex, emptyBox);
}

templateSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const preview = document.getElementById('template-preview');

    if (!this.value) {
        preview.classList.add('d-none');
        toggleSendButton();
        return;
    }

    document.getElementById('preview-subject').textContent = opt.dataset.subject || '';

    // Body is stored HTML (from Quill), base64-encoded in the data attribute
    // to avoid Blade/HTML-escaping issues — decode it here.
    const rawBodyHtml = opt.dataset.body ? atob(opt.dataset.body) : '';
    const bannerSrc = opt.dataset.banner || '';

    document.getElementById('preview-body').innerHTML = renderBodyForPreview(rawBodyHtml, bannerSrc);

    const banner = document.getElementById('preview-banner');
    const bannerPlaceholder = document.getElementById('preview-banner-placeholder');


    preview.classList.remove('d-none');
    toggleSendButton();
});

document.getElementById('send-mail-form').addEventListener('submit', function (e) {
    const count = document.querySelectorAll('.client-checkbox:checked').length;
    if (count === 0 || !templateSelect.value) {
        e.preventDefault();
        alert('Please select at least one client and a template.');
        return;
    }
    if (!confirm('Send this email to ' + count + ' client(s)?')) {
        e.preventDefault();
    }
});
</script>

@endsection