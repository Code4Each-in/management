@extends('layout')
@section('title', 'Create Email Template')
@section('subtitle', 'Create Email Template')
@section('content')

<div class="pagetitle">
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">Email Templates</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body pt-4">
                    <form action="{{ route('templates.store') }}" method="POST" enctype="multipart/form-data" id="template-form">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" placeholder="e.g. Happy Diwali Wishes">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror">
                                    <option value="">-- Select --</option>
                                    <option value="festival" {{ old('category') == 'festival'  ? 'selected' : '' }}>Festival</option>
                                    <option value="business" {{ old('category') == 'business'  ? 'selected' : '' }}>Business</option>
                                    <option value="followup" {{ old('category') == 'followup'  ? 'selected' : '' }}>Follow-up</option>
                                    <option value="other" {{ old('category') == 'other'     ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subject Line <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject') }}"
                                placeholder="e.g. Happy Diwali !">
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
 
                    

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Body <span class="text-danger">*</span></label>

                            {{-- Placeholder buttons --}}
                            <div class="mb-2">
                                <small class="text-muted me-2">Insert placeholder:</small>
                                <button type="button" onclick="insertPlaceholder('email_body')"
                                    class="btn btn-sm me-1 mb-1"
                                    style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC;font-size:11px">
                                    + email_body
                                </button>
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
                            </div>

                            <div class="col-sm-12">
                                <!-- <div id="mail_editor" style="height: 300px;">{!! old('body') !!}</div> -->
                                <textarea id="mail_editor">{!! old('body') !!}</textarea>
                                <input type="hidden" name="body" id="body-input">

                                @error('body')
                                <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('templates.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Template
                            </button>
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

document.addEventListener('DOMContentLoaded', function () {

    // tinymce.init({
    //     selector: '#mail_editor',
    //     height: 500,
    //     menubar: false,
    //     plugins: 'lists link image code',
    //     toolbar: 'undo redo | formatselect | bold italic underline | bullist numlist | link image | code | removeformat',

    //     setup: function (editor) {
    //         email_editor = editor;

    //         editor.on('init', function () {
    //             // ✅ Load old content
    //             let oldHtml = `{!! old('body') !!}`;
    //             if (oldHtml) {
    //                 editor.setContent(oldHtml);
    //             }
    //         });
    //     }
    // });
    tinymce.init({
    selector: '#mail_editor',
    height: 500,
    menubar: false,
    plugins: 'lists link image code',
    toolbar: 'undo redo | formatselect | bold italic underline | bullist numlist | link image | code | removeformat',

    // ✅ IMPORTANT FIXES
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,

    setup: function (editor) {
        email_editor = editor;

        editor.on('init', function () {
            let oldHtml = `{!! old('body') !!}`;
            if (oldHtml) {
                editor.setContent(oldHtml);
            }
        });
    }
});

});


// ✅ Sync TinyMCE content before submit
document.getElementById('template-form').addEventListener('submit', function (e) {
    const content = tinymce.get('mail_editor').getContent();
    document.getElementById('body-input').value = content;

    if (tinymce.get('mail_editor').getContent({ format: 'text' }).trim().length === 0) {
        e.preventDefault();
        alert('Email body cannot be empty.');
    }
});


// ✅ Placeholder insert (TinyMCE version)
function insertPlaceholder(name) {
    const open = '{' + '{';
    const close = '}' + '}';
    const ph = open + ' ' + name + ' ' + close;

    tinymce.get('mail_editor').execCommand('mceInsertContent', false, ph);
}


// ✅ Banner preview (same as before)
function previewBanner(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('banner-preview-img').src = e.target.result;
            document.getElementById('banner-preview-wrap').classList.remove('d-none');
            document.getElementById('upload-zone').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeBanner() {
    document.getElementById('banner_image').value = '';
    document.getElementById('banner-preview-wrap').classList.add('d-none');
    document.getElementById('upload-zone').style.display = 'block';
}

</script>

@endsection
