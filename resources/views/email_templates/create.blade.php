@extends('layout')
@section('title', 'Applicants')
@section('subtitle', 'Applicants')
@section('content')

<div class="pagetitle">
    <h1>Create Email Template</h1>
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
                            <label class="form-label fw-semibold">
                                Banner / Header Image
                                <small class="text-muted fw-normal">(optional, shown at top of email)</small>
                            </label>

                            {{-- Upload zone --}}
                            <div id="upload-zone"
                                onclick="document.getElementById('banner_image').click()"
                                style="border:2px dashed #7F77DD;border-radius:10px;padding:30px;
                                        text-align:center;cursor:pointer;background:#EEEDFE;transition:background 0.2s">
                                <i class="bi bi-cloud-upload fs-2" style="color:#7F77DD"></i>
                                <p class="mb-0 mt-1 fw-semibold" style="color:#3C3489">Click to upload banner image</p>
                                <small style="color:#7F77DD">PNG, JPG, GIF · max 5MB</small>
                            </div>
                            <input type="file" id="banner_image" name="banner_image"
                                accept="image/*" class="d-none @error('banner_image') is-invalid @enderror"
                                onchange="previewBanner(this)">
                            @error('banner_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                            {{-- Preview --}}
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
                            <label class="form-label fw-semibold">Email Body <span class="text-danger">*</span></label>

                            {{-- Placeholder buttons --}}
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
                                <div id="mail_editor" style="height: 300px;">{!! old('body') !!}</div>
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

<script>


let email_quill;
document.addEventListener('DOMContentLoaded', function () {
    const editor = document.getElementById('mail_editor');

    if (editor) {
        email_quill = new Quill('#mail_editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'image'],
                    ['code-block'],
                    ['clean']
                ]
            }
        });
    }
});


    // Sync Quill's HTML content into the hidden input right before submit
    document.getElementById('template-form').addEventListener('submit', function (e) {
        const bodyInput = document.getElementById('body-input');
        bodyInput.value = email_quill.root.innerHTML;

        if (email_quill.getText().trim().length === 0) {
            e.preventDefault();
            alert('Email body cannot be empty.');
        }
    });

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


    function insertPlaceholder(name) {
        const open = '{' + '{';
        const close = '}' + '}'; 
        const ph = open + ' ' + name + ' ' + close; 

        const range = email_quill.getSelection(true); 
        email_quill.insertText(range.index, ph, 'user');
        email_quill.setSelection(range.index + ph.length);
    }
</script>

@endsection