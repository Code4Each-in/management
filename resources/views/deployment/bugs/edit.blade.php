@extends('layout')

@section('title', 'Edit Bug - ' . $bug->bug_code)

@section('content')

    <div class="col-12">

        <form method="POST" action="{{ route('deployment.bugs.update', $bug) }}">
            @csrf
            @method('PUT')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Bug Title *</label>
                        <input type="text" name="title" class="form-control" required value="{{ old('title', $bug->title) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $bug->description) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Severity *</label>
                        <select name="severity" class="form-select" required>
                            @foreach (['Low', 'Medium', 'High', 'Critical'] as $s)
                                <option value="{{ $s }}" @selected(old('severity', $bug->severity) === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned Developer</label>
                        <select name="assigned_developer_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('assigned_developer_id', $bug->assigned_developer_id) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Steps To Reproduce</label>
                        <textarea name="steps_to_reproduce" class="form-control" rows="3">{{ old('steps_to_reproduce', $bug->steps_to_reproduce) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('deployment.bugs.show', $bug) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

    </div>

@endsection
