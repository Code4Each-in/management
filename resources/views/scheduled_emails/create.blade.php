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
                                 {{ old('template_id', request('template_id')) == $t->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ ucfirst($t->category) }})
                            </option>
                            @endforeach
                        </select>

                        @error('template_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Email Subject <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                            name="subject"
                            id="subject-input"
                            class="form-control @error('subject') is-invalid @enderror"
                            value="{{ old('subject') }}">

                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- FROM EMAIL / NAME --}}
                    <div class="mb-3">
                            <label class="form-label fw-semibold">From Email</label>
                            <input type="email"
                                name="from_email"
                                class="form-control @error('from_email') is-invalid @enderror"
                                value="{{ old('from_email', config('mail.from.address')) }}"
                                placeholder="e.g. noreply@yourcompany.com">
                            @error('from_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        <!-- <div class="col-md-6">
                            <label class="form-label fw-semibold">From Name</label>
                            <input type="text"
                                name="from_name"
                                class="form-control @error('from_name') is-invalid @enderror"
                                value="{{ old('from_name', config('mail.from.name')) }}"
                                placeholder="e.g. Your Company">
                            @error('from_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->
                    </div>

                    {{-- REPLY TO --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reply-To Email</label>
                        <input type="email"
                            name="reply_to"
                            class="form-control @error('reply_to') is-invalid @enderror"
                            value="{{ old('reply_to') }}"
                            placeholder="e.g. support@yourcompany.com">
                        @error('reply_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- CC / BCC --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CC Email(s)</label>
                            <input type="text"
                                name="cc_email"
                                class="form-control @error('cc_email') is-invalid @enderror"
                                value="{{ old('cc_email') }}"
                                placeholder="comma separated, e.g. a@x.com, b@x.com">
                            <small class="text-muted">Separate multiple emails with commas</small>
                            @error('cc_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">BCC Email(s)</label>
                            <input type="text"
                                name="bcc_email"
                                class="form-control @error('bcc_email') is-invalid @enderror"
                                value="{{ old('bcc_email') }}"
                                placeholder="comma separated, e.g. a@x.com, b@x.com">
                            <small class="text-muted">Separate multiple emails with commas</small>
                            @error('bcc_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
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
                    <!-- <div class="mb-3">
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
                                     id="client-{{ $client->id }}"
                                    value="{{ $client->id }}"
                                    {{ in_array($client->id, $oldClients) ? 'checked' : '' }}>

                                <label class="form-check-label" for="client-{{ $client->id }}">
                                    {{ $client->name }} <br>
                                    <small>{{ $client->email }}</small>
                                </label>

                            </div>
                            @endforeach
                        </div>

                        @error('client_ids')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div> -->
                    {{-- SEND TO --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Send To <span class="text-danger">*</span>
                        </label>

                        <ul class="nav nav-pills mb-3" id="recipient-tabs">
                            <li class="nav-item">
                                <button type="button" class="nav-link recipient-tab-btn active" data-type="client">Client</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link recipient-tab-btn" data-type="user">User</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link recipient-tab-btn" data-type="manual">Manual</button>
                            </li>
                        </ul>

                        <input type="hidden" name="recipient_type" id="recipient_type" value="{{ old('recipient_type', 'client') }}">

                        @error('recipient_type')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror

                        {{-- CLIENT PANEL --}}
                        <div id="panel-client" class="recipient-panel">
                            <div class="mb-2 d-flex justify-content-between">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all-client">
                                    <label class="form-check-label small">Select all</label>
                                </div>
                                <small class="text-muted"><span id="selected-count-client">0</span> selected</small>
                            </div>

                            @php $oldClients = old('client_ids') ? (array) old('client_ids') : []; @endphp

                            <div class="border rounded p-2" style="max-height:220px;overflow-y:auto;">
                                @foreach($clients as $client)
                                <div class="form-check py-1">
                                    <input class="form-check-input client-checkbox"
                                        type="checkbox" name="client_ids[]" id="client-{{ $client->id }}"
                                        value="{{ $client->id }}"
                                        {{ in_array($client->id, $oldClients) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="client-{{ $client->id }}">
                                        {{ $client->name }}<br><small>{{ $client->email }}</small>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            @error('client_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- USER PANEL --}}
                        <div id="panel-user" class="recipient-panel" style="display:none;">
                            <div class="mb-2 d-flex justify-content-between">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all-user">
                                    <label class="form-check-label small">Select all</label>
                                </div>
                                <small class="text-muted"><span id="selected-count-user">0</span> selected</small>
                            </div>

                            @php $oldUsers = old('user_ids') ? (array) old('user_ids') : []; @endphp

                            <div class="border rounded p-2" style="max-height:220px;overflow-y:auto;">
                                @foreach($users as $user)
                                <div class="form-check py-1">
                                    <input class="form-check-input user-checkbox"
                                        type="checkbox" name="user_ids[]" id="user-{{ $user->id }}"
                                        value="{{ $user->id }}"
                                        {{ in_array($user->id, $oldUsers) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="user-{{ $user->id }}">
                                        {{ $user->first_name }}<br><small>{{ $user->email }}</small>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            @error('user_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- MANUAL PANEL --}}
                        <div id="panel-manual" class="recipient-panel" style="display:none;">
                            <div class="border rounded p-2">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="email" id="manual-email-input" class="form-control"
                                        placeholder="Type an email and press Enter or comma">
                                    <button type="button" class="btn btn-outline-primary" id="manual-email-add">Add</button>
                                </div>
                                <div id="manual-email-list" class="d-flex flex-wrap gap-2"></div>
                                <div id="manual-email-hidden-inputs"></div>
                            </div>
                            <small class="text-muted d-block mt-1"><span id="manual-count">0</span> email(s) added</small>

                            @error('manual_emails') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            @error('manual_emails.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Send Option</label>

                        <div class="form-check">
                            <input class="form-check-input"
                                type="radio"
                                name="send_type"
                                id="send_now"
                                value="now"
                                {{ old('send_type', 'later') == 'now' ? 'checked' : '' }}>

                            <label class="form-check-label" for="send_now">
                                Send Instantly
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                type="radio"
                                name="send_type"
                                id="send_later"
                                value="later"
                                {{ old('send_type', 'later') == 'later' ? 'checked' : '' }}>

                            <label class="form-check-label" for="send_later">
                                Later On
                            </label>
                        </div>
                    </div>
                   <div id="schedule-datetime" class="row mb-3">
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
                            <label class="form-label fw-semibold"> Time (IST)</label>
                            <input type="time" name="send_time"
                                class="form-control"
                                value="{{ old('send_time', '09:00') }}">
                            @error('send_time')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
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
            let oldSubject = @json(old('subject'));

            if (oldSubject) {
                document.getElementById('subject-input').value = oldSubject;
            }
            else if (templateSelect.value) {
                const opt = templateSelect.options[templateSelect.selectedIndex];
                document.getElementById('subject-input').value = opt.dataset.subject || '';
            }

            if (oldHtml) {
                editor.setContent(oldHtml);
            }
            else if (templateSelect.value) {
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

function onTemplateChange(sel) { console.log(sel);
    loadTemplateIntoEditor(sel);
        const opt = sel.options[sel.selectedIndex];
        if (!opt.value) return;

        // ✅ Set subject
        document.getElementById('subject-input').value = opt.dataset.subject || '';
}

document.getElementById('schedule-form').addEventListener('submit', function () {
    document.getElementById('body-input').value = email_editor.getContent();
});

document.addEventListener('DOMContentLoaded', function () {

    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.client-checkbox');
    const selectedCount = document.getElementById('selected-count');
    const footerCount = document.getElementById('footer-count');

    // ✅ Update count function
    function updateCount() {
        let count = document.querySelectorAll('.client-checkbox:checked').length;
        selectedCount.textContent = count;
        footerCount.textContent = count;
    }

    // ✅ Select All click
    selectAll.addEventListener('change', function () {
        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
        updateCount();
    });

    // ✅ Individual checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {

            // If any unchecked → uncheck select-all
            if (!this.checked) {
                selectAll.checked = false;
            }
            // If all checked → check select-all
            else if (document.querySelectorAll('.client-checkbox:checked').length === checkboxes.length) {
                selectAll.checked = true;
            }

            updateCount();
        });
    });

    // ✅ Initial state (important for old values)
    function initState() {
        let checkedCount = document.querySelectorAll('.client-checkbox:checked').length;

        if (checkedCount === checkboxes.length && checkboxes.length > 0) {
            selectAll.checked = true;
        }

        updateCount();
    }

    initState();

        const sendNow = document.getElementById('send_now');
        const sendLater = document.getElementById('send_later');
        const scheduleBox = document.getElementById('schedule-datetime');
        const scheduleBtn = document.getElementById('schedule-btn');

    function toggleSchedule() {
        if (sendNow.checked) {
            scheduleBox.style.display = 'none';
            scheduleBtn.textContent = 'Send Now';
        } else {
            scheduleBox.style.display = 'flex';
            scheduleBtn.textContent = 'Confirm & Schedule';
        }
    }

    sendNow.addEventListener('change', toggleSchedule);
    sendLater.addEventListener('change', toggleSchedule);

    // Initial state (important)
    toggleSchedule();
});
document.addEventListener('DOMContentLoaded', function () {

    // ---------- TAB SWITCHING ----------
    const tabButtons = document.querySelectorAll('.recipient-tab-btn');
    const recipientTypeInput = document.getElementById('recipient_type');
    const panels = {
        client: document.getElementById('panel-client'),
        user:   document.getElementById('panel-user'),
        manual: document.getElementById('panel-manual'),
    };
    const footerCount = document.getElementById('footer-count');

    function activateTab(type) {
        tabButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
        Object.keys(panels).forEach(key => {
            panels[key].style.display = (key === type) ? 'block' : 'none';
        });
        recipientTypeInput.value = type;
        updateFooterCount();
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            activateTab(this.dataset.type);
        });
    });

    // Respect old() on validation-failed reload
    activateTab(recipientTypeInput.value || 'client');

    // ---------- SELECT-ALL HELPER (client + user) ----------
    function setupSelectAll(selectAllId, checkboxClass, countId) {
        const selectAll = document.getElementById(selectAllId);
        const checkboxes = document.querySelectorAll('.' + checkboxClass);
        const countEl = document.getElementById(countId);
        if (!selectAll) return;

        function update() {
            const count = document.querySelectorAll('.' + checkboxClass + ':checked').length;
            countEl.textContent = count;
            updateFooterCount();
        }

        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            update();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (!this.checked) {
                    selectAll.checked = false;
                } else if (document.querySelectorAll('.' + checkboxClass + ':checked').length === checkboxes.length) {
                    selectAll.checked = true;
                }
                update();
            });
        });

        if (checkboxes.length && document.querySelectorAll('.' + checkboxClass + ':checked').length === checkboxes.length) {
            selectAll.checked = true;
        }
        update();
    }

    setupSelectAll('select-all-client', 'client-checkbox', 'selected-count-client');
    setupSelectAll('select-all-user', 'user-checkbox', 'selected-count-user');

    // ---------- MANUAL EMAIL TAG INPUT ----------
    let manualEmails = @json(old('manual_emails', []));

    const manualInput  = document.getElementById('manual-email-input');
    const manualAddBtn = document.getElementById('manual-email-add');
    const manualList   = document.getElementById('manual-email-list');
    const manualHidden = document.getElementById('manual-email-hidden-inputs');
    const manualCount  = document.getElementById('manual-count');

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function renderManualEmails() {
        manualList.innerHTML = '';
        manualHidden.innerHTML = '';

        manualEmails.forEach((email, idx) => {
            const chip = document.createElement('span');
            chip.className = 'badge bg-light text-dark border d-flex align-items-center gap-1 p-2';
            chip.innerHTML = `${email} <span data-idx="${idx}" class="remove-manual-email ms-1" style="cursor:pointer;font-weight:bold;">&times;</span>`;
            manualList.appendChild(chip);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'manual_emails[]';
            hidden.value = email;
            manualHidden.appendChild(hidden);
        });

        manualCount.textContent = manualEmails.length;

        document.querySelectorAll('.remove-manual-email').forEach(el => {
            el.addEventListener('click', function () {
                manualEmails.splice(parseInt(this.dataset.idx), 1);
                renderManualEmails();
                updateFooterCount();
            });
        });

        updateFooterCount();
    }

    function addManualEmail() {
        const raw = manualInput.value.trim().replace(/,$/, '');
        if (!raw) return;

        if (!isValidEmail(raw)) {
            alert('Please enter a valid email address.');
            return;
        }
        if (manualEmails.includes(raw)) {
            manualInput.value = '';
            return;
        }

        manualEmails.push(raw);
        manualInput.value = '';
        renderManualEmails();
    }

    manualAddBtn.addEventListener('click', addManualEmail);
    manualInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addManualEmail();
        }
    });

    renderManualEmails();

    // ---------- FOOTER COUNT ----------
    function updateFooterCount() {
        const activeType = recipientTypeInput.value;
        let count = 0;

        if (activeType === 'client') {
            count = document.querySelectorAll('.client-checkbox:checked').length;
        } else if (activeType === 'user') {
            count = document.querySelectorAll('.user-checkbox:checked').length;
        } else if (activeType === 'manual') {
            count = manualEmails.length;
        }

        footerCount.textContent = count;
    }
});
</script>

@endsection
