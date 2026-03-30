@extends('layout')
@section('title', 'Applicants')
@section('subtitle', 'Applicants')
@section('content')

<div class="pagetitle">
    <h1>Email Templates</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item active">Email Templates</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-3">

                    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                        <div>
                            {{-- Category filter tabs --}}
                            <a href="{{ route('templates.index') }}"
                               class="btn btn-sm {{ !request('category') ? 'btn-primary' : 'btn-outline-secondary' }}">
                                All
                            </a>
                            @foreach(['festival','business','followup','other'] as $cat)
                            <a href="{{ route('templates.index', ['category' => $cat]) }}"
                               class="btn btn-sm {{ request('category') == $cat ? 'btn-primary' : 'btn-outline-secondary' }}">
                                {{ ucfirst($cat) }}
                            </a>
                            @endforeach
                        </div>
                        <a href="{{ route('templates.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> New Template
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @forelse($templates as $template)
                    <div class="card border mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">{{ $template->name }}</h5>
                                    <small class="text-muted">
                                        {{ ucfirst($template->category) }} &middot;
                                        Updated {{ $template->updated_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    {{-- Category badge --}}
                                    @php
                                        $badgeColors = [
                                            'festival' => 'warning',
                                            'business' => 'success',
                                            'followup' => 'primary',
                                            'other'    => 'secondary',
                                        ];
                                        $color = $badgeColors[$template->category] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst($template->category) }}</span>

                                    <a href="{{ route('templates.edit', $template->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="{{ route('scheduled.create', ['template_id' => $template->id]) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-clock"></i> Schedule
                                    </a>
                                </div>
                            </div>

                            {{-- Banner image preview --}}
                            @if($template->banner_image)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $template->banner_image) }}"
                                     alt="Banner" class="img-fluid rounded"
                                     style="max-height:80px;object-fit:cover;width:100%">
                            </div>
                            @endif

                            {{-- Body preview --}}
                            <div class="mt-2 p-3 rounded" style="background:#f8f9fa;font-size:13px;color:#555;line-height:1.7">
                                {!! \Illuminate\Support\Str::limit(
                                    preg_replace('/\{\{(\w+)\}\}/', '<span class="badge bg-light text-primary border">{{$1}}</span>', e($template->body)),
                                    300
                                ) !!}
                            </div>

                            {{-- Placeholder chips --}}
                            <div class="mt-2">
                                @foreach(getPlaceholders($template->body) as $ph)
                                    <span class="badge me-1" style="background:#EEEDFE;color:#3C3489">{{ $ph }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-envelope-x fs-1"></i>
                        <p class="mt-2">No templates yet. <a href="{{ route('templates.create') }}">Create one</a></p>
                    </div>
                    @endforelse

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
