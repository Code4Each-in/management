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
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body pt-4">
                <form action="{{ route('templates.update', $template->id) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- NAME + CATEGORY --}}
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Template Name *</label>
                            <input type="text" name="name"
                                   class="form-control"
                                   value="{{ old('name', $template->name) }}">
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

                    {{-- SUBJECT --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Line</label>
                        <input type="text" name="subject" class="form-control"
                               value="{{ old('subject', $template->subject) }}">
                    </div>  

                    {{-- BODY --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Body</label>

                        {{-- PLACEHOLDERS --}}
                        <div class="mb-2">
                            @foreach(['email_body','client_name','company_name'] as $ph)
                                <button type="button"
                                        onclick="insertPlaceholder('{{ $ph }}')"
                                        class="btn btn-sm me-1 mb-1 btn-light">
                                    + {{ $ph }}
                                </button>
                            @endforeach
                        </div>

                        <textarea id="html-input" class="form-control">
                           @php echo old('body', $template->body); @endphp </textarea>
                        <input type="hidden" name="body" id="body-input"
                               value="{{ old('body', $template->body ?? '') }}">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
</section>
@endsection
@section('js_scripts')
<script src="https://cdn.tiny.cloud/1/zcnv3wpknfdm4lkpqq5gopif0az219stkskraxdyyb3cfb44/tinymce/6/tinymce.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    let currentBanner = @json($template->banner_image ? asset('storage/'.$template->banner_image) : null);

    const input = document.getElementById('html-input');
    const preview = document.getElementById('preview');

    tinymce.init({
        selector: '#html-input',
        height: 700,
        plugins: 'code preview',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | code preview',
        menubar: false,

        setup: function (editor) {
            editor.on('keyup change', function () {
                renderPreview();
            });
        }
    });

    function renderPreview() {

        let html = tinymce.get('html-input')?.getContent() || input.value;

        const bannerRegex = new RegExp('\\{\\{\\s*banner_image\\s*\\}\\}', 'gi');

        if (currentBanner) {
            html = html.replace(
                bannerRegex,
                '<img src="' + currentBanner + '" style="max-width:100%;margin:10px 0;">'
            );
        } else {
            html = html.replace(
                bannerRegex,
                '<span style="background:#eee;padding:10px;">[ Banner here ]</span>'
            );
        }

        preview.innerHTML = html;

        document.getElementById('body-input').value = html;
    }

    // ✅ FIXED PLACEHOLDER INSERTION
    window.insertPlaceholder = function(name) {

        const editor = tinymce.get('html-input');

        const placeholder = '@{{ ' + name + ' }}';

        if (editor) {
            editor.insertContent(placeholder);
        }

        renderPreview();
    };

    window.previewBanner = function(inputFile) {
        const file = inputFile.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            currentBanner = e.target.result;
            renderPreview();
        };
        reader.readAsDataURL(file);
    };

    renderPreview();
});
</script>

@endsection
