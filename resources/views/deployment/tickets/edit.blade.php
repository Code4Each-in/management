@extends('layout')

@section('title', 'Edit Deployment Ticket')

@section('content')

    <div class="col-12">

        <p class="text-muted">{{ $ticket->deployment_code }} - {{ $ticket->deployment_name }}</p>

        <form method="POST" action="{{ route('deployment.tickets.update', $ticket) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Basic Information</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Deployment Name *</label>
                        <input type="text" name="deployment_name" class="form-control" required value="{{ old('deployment_name', $ticket->deployment_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Project *</label>
                        <select name="project_id" class="form-select" required>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}"  <?php if($ticket->project_id == $project->id) echo 'selected="selected"'; ?>>{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Related Ticket / Task IDs</label>
                        <input type="text" name="related_ticket_ids" class="form-control" value="{{ old('related_ticket_ids', $ticket->related_ticket_ids) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority *</label>
                        <select name="priority" class="form-select" required>
                            @foreach (['Low', 'Medium', 'High', 'Critical'] as $p)
                                <option value="{{ $p }}" <?php if($ticket->priority === $p) echo 'selected="selected"'; ?>>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Assigned Developer</label>
                        <select name="assigned_developer_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" <?php if($ticket->assigned_developer_id == $user->id) echo 'selected="selected"'; ?>>{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reviewer</label>
                        <select name="reviewer_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" <?php if($ticket->reviewer_id == $user->id) echo 'selected="selected"'; ?>>{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">QA Tester</label>
                        <select name="qa_tester_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"  <?php if($ticket->qa_tester_id == $user->id) echo 'selected="selected"'; ?>>{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div> 
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Deployment Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Changes Done</label>
                        <textarea name="changes_done" class="form-control" rows="3">{{ old('changes_done', $ticket->changes_done) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Files Modified</label>
                        <textarea name="files_modified" class="form-control" rows="3">{{ old('files_modified', $ticket->files_modified) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modules Affected</label>
                        <textarea name="modules_affected" class="form-control" rows="2">{{ old('modules_affected', $ticket->modules_affected) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Testing Done</label>
                        <textarea name="testing_done" class="form-control" rows="2">{{ old('testing_done', $ticket->testing_done) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Deployment Notes</label>
                        <textarea name="deployment_notes" class="form-control" rows="2">{{ old('deployment_notes', $ticket->deployment_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Database Changes</div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label d-block">Database Changes Required?</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="db_changes_required" value="1" id="dbChangesSwitch" <?php echo ($ticket->db_changes_required == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="dbChangesSwitch">Yes</label>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Migration Details</label>
                        <textarea name="migration_details" class="form-control" rows="2">{{ old('migration_details', $ticket->migration_details) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Add More Attachments</div>
                <div class="card-body">
                    <div id="attachment-rows">
                        <div class="row g-2 mb-2 attachment-row">
                            <div class="col-md-3">
                                <select name="attachment_types[]" class="form-select">
                                    <option value="Screenshot">Screenshot</option>
                                    <option value="Document">Document</option>
                                    <option value="SQL">SQL</option>
                                    <option value="Other" selected>Other</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <input type="file" name="attachments[]" class="form-control">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addAttachmentRow">
                        <i class="bi bi-plus"></i> Add Another File
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('deployment.tickets.show', $ticket) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

    </div>

@endsection

@section('js_scripts')
<script>
document.getElementById('addAttachmentRow').addEventListener('click', function () {
    var container = document.getElementById('attachment-rows');
    var row = container.querySelector('.attachment-row').cloneNode(true);
    row.querySelectorAll('input[type=file]').forEach(function (el) { el.value = ''; });
    container.appendChild(row);
});
</script>
@endsection
