@extends('layout')
@section('title', 'Schedule Email')
@section('subtitle', 'Schedule Email')

@section('content')

<div class="pagetitle">
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
                                {{ old('template_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ ucfirst($t->category) }})
                            </option>
                            @endforeach
                        </select>

                        @error('template_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- EMAIL BODY --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Email Body <span class="text-danger">*</span>
                        </label>

                        <textarea id="email_body_editor">{{ old('body') }}</textarea>
                        <input type="hidden" name="body" id="body-input">

                        @error('body')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- CLIENTS --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Target Clients <span class="text-danger">*</span>
                        </label>

                        <div class="mb-2 d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all">
                                <label class="form-check-label small">Select all</label>
                            </div>
                            <small class="text-muted">
                                <span id="selected-count">0</span> selected
                            </small>
                        </div>
                        @php
                            $oldClients = old('client_ids') ? (array) old('client_ids') : [];
                        @endphp

                       <div class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                            @foreach($clients as $client)
                            <div class="form-check py-1">

                                <input class="form-check-input client-checkbox"
                                    type="checkbox"
                                    name="client_ids[]"
                                    value="{{ $client->id }}"
                                    {{ in_array($client->id, $oldClients) ? 'checked' : '' }}>

                                <label class="form-check-label">
                                    {{ $client->name }} <br>
                                    <small>{{ $client->email }}</small>
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
                                value="{{ old('send_date') }}"
                                min="{{ now()->format('Y-m-d') }}">
                            @error('send_date')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Time</label>
                            <input type="time" name="send_time"
                                class="form-control"
                                value="{{ old('send_time', '09:00') }}">
                            @error('send_time')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Timezone</label>
                            <input type="text" class="form-control" value="IST" disabled>
                        </div>
                    </div>

                    {{-- ACTION --}}
                    <div class="d-flex justify-content-between">
                        <div class="small text-muted">
                            Scheduling for <span id="footer-count">0</span> client(s)
                        </div>

                        <div>
                            <a href="{{ route('scheduled.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="schedule-btn">
                                Confirm & Schedule
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

<script src="https://cdn.tiny.cloud/1/mfhlch1z0ky97217fc0jx6wktt3uh1uo7euvbtx415h9jyhb/tinymce/6/tinymce.min.js"></script>

<script>
let email_editor;

const templateSelect = document.getElementById('template-select');

tinymce.init({
    selector: '#email_body_editor',
    height: 450,
    menubar: false,
    plugins: 'lists link image code',
    toolbar: 'undo redo | bold italic | bullist numlist | link image | code',

    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,

    setup: function (editor) {
        email_editor = editor;

        editor.on('init', function () {

            let oldHtml = @json(old('body'));

            if (oldHtml) {
                editor.setContent(oldHtml);
            } else if (templateSelect.value) {
                loadTemplateIntoEditor(templateSelect);
            }
        });
    }
});

function loadTemplateIntoEditor(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value || !email_editor) return;

    let rawHtml = atob(opt.dataset.body || '');
    const banner = opt.dataset.banner || '';

    const baseUrl = "{{ url('/') }}";
    rawHtml = rawHtml.replace(/src="\.\.\//g, 'src="' + baseUrl + '/');

    email_editor.setContent(rawHtml);
}

function onTemplateChange(sel) {
    loadTemplateIntoEditor(sel);
}

document.getElementById('schedule-form').addEventListener('submit', function () {
    document.getElementById('body-input').value = email_editor.getContent();
});
</script>

@endsection
