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
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body pt-4">

                    <form action="{{ route('templates.store') }}" method="POST" enctype="multipart/form-data">
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
                                    <option value="festival"  {{ old('category') == 'festival'  ? 'selected' : '' }}>Festival</option>
                                    <option value="business"  {{ old('category') == 'business'  ? 'selected' : '' }}>Business</option>
                                    <option value="followup"  {{ old('category') == 'followup'  ? 'selected' : '' }}>Follow-up</option>
                                    <option value="other"     {{ old('category') == 'other'     ? 'selected' : '' }}>Other</option>
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
                                <button type="button" onclick="insertPlaceholder('sender_name')"
                                        class="btn btn-sm me-1 mb-1"
                                        style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC;font-size:11px">
                                    + sender_name
                                </button>
                                <button type="button" onclick="insertPlaceholder('meeting_date')"
                                        class="btn btn-sm me-1 mb-1"
                                        style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC;font-size:11px">
                                    + meeting_date
                                </button>
                            </div>

                            <textarea name="body" id="body-textarea" rows="7"
                                      class="form-control @error('body') is-invalid @enderror"
                                      placeholder="Hi [[client_name]], wishing you a Happy Diwali from [[company_name]]!">{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
        </div>
    </div>
</section>

<script>
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
    const ph = '{' + '{' + name + '}' + '}';
    const ta = document.getElementById('body-textarea');
    const start = ta.selectionStart;
    const end   = ta.selectionEnd;
    ta.value = ta.value.substring(0, start) + ph + ta.value.substring(end);
    ta.focus();
    ta.selectionStart = ta.selectionEnd = start + ph.length;
}
</script>



@endsection
