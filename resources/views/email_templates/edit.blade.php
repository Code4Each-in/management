@extends('layout')
@section('title', 'Edit Template')
@section('subtitle', 'Edit Template')
@section('content')

<div class="pagetitle">
    <h1>Edit Template</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">Email Templates</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body pt-4">

                    <form action="{{ route('templates.update', $template->id) }}"
                          method="POST" enctype="multipart/form-data" id="template-form">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $template->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category" class="form-select">
                                    @foreach(['festival','business','followup','other'] as $cat)
                                    <option value="{{ $cat }}"
                                        {{ old('category', $template->category) == $cat ? 'selected' : '' }}>
                                        {{ ucfirst($cat) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subject Line</label>
                            <input type="text" name="subject" class="form-control"
                                   value="{{ old('subject', $template->subject) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Banner Image</label>

                            @if($template->banner_image)
                            <div class="mb-2 position-relative" id="existing-banner">
                                <img src="{{ asset('storage/' . $template->banner_image) }}"
                                     class="img-fluid rounded w-100" style="max-height:120px;object-fit:cover">
                                <span class="badge bg-secondary position-absolute top-0 start-0 m-1">Current banner</span>
                            </div>
                            <p class="text-muted small">Upload a new image below to replace it.</p>
                            @endif

                            <div id="upload-zone"
                                 onclick="document.getElementById('banner_image').click()"
                                 style="border:2px dashed #7F77DD;border-radius:10px;padding:20px;
                                        text-align:center;cursor:pointer;background:#EEEDFE">
                                <i class="bi bi-cloud-upload" style="color:#7F77DD;font-size:24px"></i>
                                <p class="mb-0 mt-1 small fw-semibold" style="color:#3C3489">
                                    {{ $template->banner_image ? 'Click to replace banner' : 'Click to upload banner' }}
                                </p>
                            </div>
                            <input type="file" id="banner_image" name="banner_image"
                                   accept="image/*" class="d-none" onchange="previewBanner(this)">

                            <div id="banner-preview-wrap" class="mt-2 d-none position-relative">
                                <img id="banner-preview-img" class="img-fluid rounded w-100"
                                     style="max-height:120px;object-fit:cover">
                                <button type="button" onclick="removeBanner()"
                                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Body</label>

                            <div class="mb-2">
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
                                <button type="button" onclick="insertPlaceholder('banner_image')"
                                        class="btn btn-sm me-1 mb-1"
                                        style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC;font-size:11px">
                                    + banner_image
                                </button>
                            </div>

                            <div class="col-sm-12">
                                <div id="toolbar-container">
                                    <span class="ql-formats">
                                        <select class="ql-font"></select>
                                        <select class="ql-size"></select>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-bold"></button>
                                        <button class="ql-italic"></button>
                                        <button class="ql-underline"></button>
                                        <button class="ql-strike"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <select class="ql-color"></select>
                                        <select class="ql-background"></select>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-script" value="sub"></button>
                                        <button class="ql-script" value="super"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-header" value="1"></button>
                                        <button class="ql-header" value="2"></button>
                                        <button class="ql-blockquote"></button>
                                        <button class="ql-code-block"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-list" value="ordered"></button>
                                        <button class="ql-list" value="bullet"></button>
                                        <button class="ql-indent" value="-1"></button>
                                        <button class="ql-indent" value="+1"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-direction" value="rtl"></button>
                                        <select class="ql-align"></select>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-link"></button>
                                        <button class="ql-image"></button>
                                        <button class="ql-video"></button>
                                        <button class="ql-formula"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-clean"></button>
                                    </span>
                                </div>
                                <div id="editor" style="height: 300px;">{!! old('body', $template->body) !!}</div>
                                <input type="hidden" name="body" id="body-input" value="{{ old('body', $template->body) }}">

                                @error('body')
                                <span style="font-size: 12px;" class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Live preview --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Live Preview</label>
                            <div class="border rounded overflow-hidden">
                                @if($template->banner_image)
                                <!-- <img id="preview-banner"
                                     src="{{ asset('storage/' . $template->banner_image) }}"
                                     class="w-100" style="max-height:80px;object-fit:cover"> -->
                                @else
                                <div id="preview-banner-placeholder"
                                     style="height:50px;background:linear-gradient(90deg,#EEEDFE,#E6F1FB);
                                            display:flex;align-items:center;justify-content:center">
                                    <small class="text-muted">[ Banner will appear here ]</small>
                                </div>
                                @endif

                                <div class="p-3" style="font-size:13px;color:#555;line-height:1.7" id="live-preview"></div>

                                <div class="px-3 py-2 border-top" style="background:#f8f9fa;font-size:11px;color:#999">
                                    Sent via {{ config('app.name') }} &middot; Unsubscribe
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-between">
                            <div class="d-flex gap-2">
                                <a href="{{ route('templates.index') }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Save Changes
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- Placeholder guide --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body pt-4">
                    <h6 class="fw-semibold mb-3">
                        <i class="bi bi-info-circle text-primary"></i> Placeholder Guide
                    </h6>
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr><td><code>@{{client_name}}</code></td><td class="text-muted small">Client's name</td></tr>
                            <tr><td><code>@{{company_name}}</code></td><td class="text-muted small">App name</td></tr>
                            <tr><td><code>@{{project_name}}</code></td><td class="text-muted small">Project name</td></tr>
                            <tr><td><code>@{{banner_image}}</code></td><td class="text-muted small">Banner image (inline)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- Quill CSS + JS (skip these two lines if Quill is already loaded in your layout) --}}
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editorEl = document.getElementById('editor');
        const bodyInput = document.getElementById('body-input');
        const form = document.getElementById('template-form');

        if (!editorEl || !bodyInput || !form) {
            console.error('Init failed: missing #editor, #body-input, or #template-form');
            return;
        }

        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: '#toolbar-container'
            }
        });

        // Track whichever banner is currently "active" — either the
        // originally saved one, or a newly chosen file (base64 preview)
        let currentBannerSrc = @json($template->banner_image ? asset('storage/' . $template->banner_image) : null);
        const originalBannerSrc = currentBannerSrc; // remembered so removeBanner() can restore it

        function syncBody() {
            bodyInput.value = quill.root.innerHTML;
        }

   
        // or a muted placeholder box if no banner is set yet
        function renderBodyForPreview(html) {
            const placeholderRegex = /\{\{\s*banner_image\s*\}\}/gi;

            if (currentBannerSrc) {
                const imgTag = '<img src="' + currentBannerSrc + '" '
                    + 'style="max-width:100%;border-radius:6px;margin:6px 0;display:block;" '
                    + 'alt="Banner image">';
                return html.replace(placeholderRegex, imgTag);
            }

            const emptyBox = '<span style="display:inline-block;padding:10px 14px;'
                + 'background:#f1f1f1;color:#999;border-radius:4px;font-size:12px;">'
                + '[ Banner image will appear here ]</span>';
            return html.replace(placeholderRegex, emptyBox);
        }

        function updateLivePreview() {
            const preview = document.getElementById('live-preview');
            if (preview) preview.innerHTML = renderBodyForPreview(quill.root.innerHTML);
        }

        syncBody();
        updateLivePreview();

        quill.on('text-change', function () {
            syncBody();
            updateLivePreview();
        });

        form.addEventListener('submit', function (e) {
            syncBody();
            if (quill.getText().trim().length === 0) {
                e.preventDefault();
                alert('Email body cannot be empty.');
            }
        });

        window.previewBanner = function (input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('banner-preview-img').src = e.target.result;
                    document.getElementById('banner-preview-wrap').classList.remove('d-none');
                    document.getElementById('upload-zone').style.display = 'none';

                    const pb = document.getElementById('preview-banner');
                    if (pb) pb.src = e.target.result;

                  
                    currentBannerSrc = e.target.result;
                    updateLivePreview();
                };
                reader.readAsDataURL(input.files[0]);
            }
        };

        window.removeBanner = function () {
            document.getElementById('banner_image').value = '';
            document.getElementById('banner-preview-wrap').classList.add('d-none');
            document.getElementById('upload-zone').style.display = 'block';

            // Revert inline preview back to the originally saved banner (if any)
            currentBannerSrc = originalBannerSrc;
            updateLivePreview();
        };

        window.insertPlaceholder = function (name) {
            const open = '{' + '{';
            const close = '}' + '}';
            const ph = open + ' ' + name + ' ' + close;

            let range = quill.getSelection();
            if (!range) {
                quill.focus();
                const length = quill.getLength();
                range = { index: length > 0 ? length - 1 : 0 };
            }

            quill.insertText(range.index, ph, 'user');
            quill.setSelection(range.index + ph.length);
        };
    });
</script>

@endsection