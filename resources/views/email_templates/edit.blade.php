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
                          method="POST" enctype="multipart/form-data">
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

                            {{-- NO placeholder= attribute here to avoid Blade parsing issues --}}
                            <textarea name="body" id="body-textarea" rows="7"
                                      class="form-control @error('body') is-invalid @enderror">{{ old('body', $template->body) }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Live preview --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Live Preview</label>
                            <div class="border rounded overflow-hidden">
                                @if($template->banner_image)
                                <img id="preview-banner"
                                     src="{{ asset('storage/' . $template->banner_image) }}"
                                     class="w-100" style="max-height:80px;object-fit:cover">
                                @else
                                <div id="preview-banner-placeholder"
                                     style="height:50px;background:linear-gradient(90deg,#EEEDFE,#E6F1FB);
                                            display:flex;align-items:center;justify-content:center">
                                    <small class="text-muted">[ Banner will appear here ]</small>
                                </div>
                                @endif

                                {{-- Use {!! !!} so innerText updates work, body is set via JS not Blade echo --}}
                                <div class="p-3" style="font-size:13px;color:#555;line-height:1.7" id="live-preview"></div>

                                <div class="px-3 py-2 border-top" style="background:#f8f9fa;font-size:11px;color:#999">
                                    Sent via {{ config('app.name') }} &middot; Unsubscribe
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-between">
                            <form action="{{ route('templates.destroy', $template->id) }}" method="POST"
                                  onsubmit="return confirm('Delete this template?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
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
                            {{-- Use @{{ to escape curly braces from Blade --}}
                            <tr><td><code>@{{client_name}}</code></td><td class="text-muted small">Client's name</td></tr>
                            <tr><td><code>@{{company_name}}</code></td><td class="text-muted small">App name</td></tr>
                            <tr><td><code>@{{project_name}}</code></td><td class="text-muted small">Project name</td></tr>
                            <tr><td><code>@{{sender_name}}</code></td><td class="text-muted small">Logged-in user</td></tr>
                            <tr><td><code>@{{meeting_date}}</code></td><td class="text-muted small">Custom date</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

@push('scripts')
<script>
    // Set live preview content from PHP on page load
    var templateBody = @json(old('body', $template->body));
    document.getElementById('live-preview').innerText = templateBody;

    function previewBanner(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('banner-preview-img').src = e.target.result;
                document.getElementById('banner-preview-wrap').classList.remove('d-none');
                document.getElementById('upload-zone').style.display = 'none';
                const pb = document.getElementById('preview-banner');
                if (pb) pb.src = e.target.result;
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
        document.getElementById('live-preview').innerText = ta.value;
    }

    document.getElementById('body-textarea').addEventListener('input', function () {
        document.getElementById('live-preview').innerText = this.value;
    });
</script>
@endpush

@endsection
