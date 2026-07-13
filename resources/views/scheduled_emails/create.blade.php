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
                                    onchange="onTemplateChange(this)">
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

                        {{-- EMAIL BODY (loads the full template, editable directly here) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Body <span class="text-danger">*</span></label>
                            <small class="text-muted d-block mb-2">
                                Selecting a template loads it here fully rendered (banner included) — edit anything before scheduling.
                            </small>

                            <!-- <div class="mb-2">
                                <small class="text-muted me-2">Insert placeholder:</small>
                                <button type="button" onclick="insertPlaceholder('client_name')"
                                    class="btn btn-sm me-1 mb-1"
                                    style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC;font-size:11px">
                                    + client_name
                                </button>
                                <button type="button" onclick="insertPlaceholder('company_name')"
                                    class="btn btn-sm me-1 mb-1"
                                    style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC;font-size:11px">
                                    + company_name
                                </button>
                                <button type="button" onclick="insertPlaceholder('project_name')"
                                    class="btn btn-sm me-1 mb-1"
                                    style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC;font-size:11px">
                                    + project_name
                                </button>
                            </div> -->

                            <textarea id="email_body_editor">{!! old('body') !!}</textarea>
                            <input type="hidden" name="body" id="body-input">

                            @error('body')
                            <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                            @enderror
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

@endsection

@section('js_scripts')

{{-- TinyMCE CDN --}}
<script src="https://cdn.tiny.cloud/1/mfhlch1z0ky97217fc0jx6wktt3uh1uo7euvbtx415h9jyhb/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>

let email_editor;

const clientCheckboxes = document.querySelectorAll('.client-checkbox');
const selectAll = document.getElementById('select-all');
const selectedCountEl = document.getElementById('selected-count');
const footerCountEl = document.getElementById('footer-count');
const scheduleBtn = document.getElementById('schedule-btn');
const templateSelect = document.getElementById('template-select');

document.addEventListener('DOMContentLoaded', function () {

    // tinymce.init({
    //     selector: '#email_body_editor',
    //     height: 450,
    //     menubar: false,
    //     plugins: 'lists link image code',
    //     toolbar: 'undo redo | formatselect | bold italic underline | bullist numlist | link image | code | removeformat',
    //     setup: function (editor) {
    //         email_editor = editor;

    //         editor.on('init', function () {
    //             let oldHtml = `{!! old('body') !!}`;
    //             if (oldHtml) {
    //                 editor.setContent(oldHtml);
    //             } else if (templateSelect.value) {
    //                 loadTemplateIntoEditor(templateSelect);
    //             }
    //         });

    //         editor.on('keyup change', toggleButton);
    //     }
    // });
    tinymce.init({
    selector: '#email_body_editor',
    height: 450,
    menubar: false,
    plugins: 'lists link image code',
    toolbar: 'undo redo | formatselect | bold italic underline | bullist numlist | link image | code | removeformat',

    // 🔥 FIX IMAGE PATH ISSUE
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,

    setup: function (editor) {
        email_editor = editor;

        editor.on('init', function () {
            let oldHtml = `{!! old('body') !!}`;
            if (oldHtml) {
                editor.setContent(oldHtml);
            } else if (templateSelect.value) {
                loadTemplateIntoEditor(templateSelect);
            }
        });

        editor.on('keyup change', toggleButton);
    }
});
});

function updateCounts() {
    const count = document.querySelectorAll('.client-checkbox:checked').length;
    selectedCountEl.textContent = count;
    footerCountEl.textContent = count;
    toggleButton();
}

function toggleButton() {
    const hasClients = document.querySelectorAll('.client-checkbox:checked').length > 0;
    const hasTemplate = templateSelect.value !== '';
    const hasBody = email_editor ? email_editor.getContent({ format: 'text' }).trim().length > 0 : false;
    scheduleBtn.disabled = !(hasClients && hasTemplate && hasBody);
}

clientCheckboxes.forEach(cb => cb.addEventListener('change', updateCounts));

selectAll.addEventListener('change', function () {
    clientCheckboxes.forEach(cb => cb.checked = selectAll.checked);
    updateCounts();
});

// ✅ Merge banner_image into the raw template so it shows inline in the editor
function mergeBanner(templateHtml, bannerSrc) {
    const bannerRegex = /\{\{\s*banner_image\s*\}\}/gi;

    return bannerSrc
        ? templateHtml.replace(bannerRegex, '<img src="' + bannerSrc + '" style="max-width:100%;margin:6px 0;border-radius:6px;">')
        : templateHtml.replace(bannerRegex, '<span style="background:#eee;padding:6px 10px;border-radius:4px;">No Banner</span>');
}

// ✅ Loads the selected template's full body (banner merged in) straight into the editor
// function loadTemplateIntoEditor(sel) {
//     const opt = sel.options[sel.selectedIndex];

//     if (!opt.value || !email_editor) return;

//     const rawHtml = atob(opt.dataset.body || '');
//     const banner = opt.dataset.banner || '';

//     email_editor.setContent(mergeBanner(rawHtml, banner));
//     toggleButton();
// }
function loadTemplateIntoEditor(sel) {
    const opt = sel.options[sel.selectedIndex];

    if (!opt.value || !email_editor) return;

    let rawHtml = atob(opt.dataset.body || '');
    const banner = opt.dataset.banner || '';

    // ✅ FIX: convert relative paths to absolute
    const baseUrl = "{{ url('/') }}";
    rawHtml = rawHtml.replace(/src="\.\.\//g, 'src="' + baseUrl + '/');

    email_editor.setContent(mergeBanner(rawHtml, banner));
    toggleButton();
}

function onTemplateChange(sel) {
    if (!sel.value) {
        toggleButton();
        return;
    }
    loadTemplateIntoEditor(sel);
}

function insertPlaceholder(name) {
    const open = '{' + '{';
    const close = '}' + '}';
    const ph = open + ' ' + name + ' ' + close;

    email_editor.execCommand('mceInsertContent', false, ph);
}

document.getElementById('schedule-form').addEventListener('submit', function (e) {
    document.getElementById('body-input').value = email_editor ? email_editor.getContent() : '';

    const count = document.querySelectorAll('.client-checkbox:checked').length;
    const hasBody = email_editor ? email_editor.getContent({ format: 'text' }).trim().length > 0 : false;

    if (count === 0 || !templateSelect.value || !hasBody) {
        e.preventDefault();
        alert('Select a template, write the email body, and pick at least one client.');
    }
});
</script>

@endsection