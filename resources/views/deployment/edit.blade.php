@extends('layout')

@section('title', 'Edit ' . $ticket->deployment_code)

@section('content')
<div style="margin: 3rem auto; font-family: 'Nunito', sans-serif;">

  {{-- Page header --}}
  <div class="mb-4">
    <a href="{{ route('deployment.tickets.show', $ticket) }}" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-2" style="font-weight: 600; font-size: 13px;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Back to ticket
    </a>
    <p class="text-muted mb-0" style="font-size: 13px; font-weight: 600;">Editing {{ $ticket->deployment_code }} — update the details below.</p>
  </div>

  <form method="POST" action="{{ route('deployment.tickets.update', $ticket) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Basic Information --}}
    <div class="card border rounded-3 overflow-hidden mb-3 shadow-none">
      <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background: var(--bs-light);">
        <div class="d-flex align-items-center justify-content-center rounded-2 border bg-white" style="width:28px;height:28px;color:#6b7280;">
          <i class="bi bi-info-circle" style="font-size:13px;"></i>
        </div>
        <span class="fw-bold small">Basic information</span>
      </div>
      <div class="card-body p-3">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Deployment name <span class="text-danger">*</span></label>
            <input type="text" name="deployment_name" class="form-control form-control-sm" required
              value="{{ old('deployment_name', $ticket->deployment_name) }}" placeholder="e.g. Auth token refresh flow" style="font-family: 'Nunito', sans-serif;">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Project <span class="text-danger">*</span></label>
            <select name="project_id" id="projectSelect" class="form-select form-select-sm" required style="font-family: 'Nunito', sans-serif;">
              <option value="">Select project</option>
              @foreach ($projects as $project)
                <option value="{{ $project->id }}"
                    {{ old('project_id', $ticket->project_id) == $project->id ? 'selected' : '' }}>
                    {{ $project->project_name }}
                </option>
                            @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Related ticket / task</label>
            <select name="related_ticket_ids" id="ticketSelect" class="form-select form-select-sm" style="font-family: 'Nunito', sans-serif;">
              @if($ticket->relatedTicket)
                <option value="{{ $ticket->relatedTicket->id }}" selected>{{ $ticket->relatedTicket->title }}</option>
              @else
                <option value="">Select project first</option>
              @endif
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Priority <span class="text-danger">*</span></label>
            <select name="priority" class="form-select form-select-sm" required style="font-family: 'Nunito', sans-serif;">
              @foreach (['Low', 'Medium', 'High', 'Critical'] as $p)
                <option value="{{ $p }}"
                    {{ old('priority', $ticket->priority) === $p ? 'selected' : '' }}>
                    {{ $p }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">
                Assigned Developers
            </label>
            @php
              $selectedDeveloperIds = array_map('intval', old('assigned_developer_ids', $ticket->developers->pluck('id')->toArray()));
            @endphp
            <select name="assigned_developer_ids[]"
                    class="form-select select2"
                    multiple>
                @foreach($users as $user)
                <option value="{{ $user->id }}"
                    {{ in_array($user->id, $selectedDeveloperIds) ? 'selected' : '' }}>
                    {{ $user->first_name }} {{ $user->last_name }}
                </option>
                @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">QA</label>
            <select name="qa_id" class="form-select form-select-sm" style="font-family: 'Nunito', sans-serif;">
              <option value="">Reviewer</option>
              @foreach ($users as $user)
                <option value="{{ $user->id }}"
                    {{ old('qa_id', $ticket->qa_id) == $user->id ? 'selected' : '' }}>
                    {{ $user->first_name }} {{ $user->last_name }}
                </option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
    </div>

    {{-- Deployment Details --}}
    <div class="card border rounded-3 overflow-hidden mb-3 shadow-none">
      <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background: var(--bs-light);">
        <div class="d-flex align-items-center justify-content-center rounded-2 border bg-white" style="width:28px;height:28px;color:#6b7280;">
          <i class="bi bi-file-earmark-text" style="font-size:13px;"></i>
        </div>
        <span class="fw-bold small">Deployment details</span>
      </div>
      <div class="card-body p-3">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Changes done</label>
            <textarea name="changes_done" class="form-control form-control-sm" rows="3" placeholder="Summarize what was changed" style="font-family: 'Nunito', sans-serif;">{{ old('changes_done', $ticket->changes_done) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Files modified</label>
            <textarea name="files_modified" class="form-control form-control-sm" rows="3" placeholder="List changed files or paths" style="font-family: 'Nunito', sans-serif;">{{ old('files_modified', $ticket->files_modified) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Modules affected</label>
            <textarea name="modules_affected" class="form-control form-control-sm" rows="2" placeholder="e.g. Auth, Notifications" style="font-family: 'Nunito', sans-serif;">{{ old('modules_affected', $ticket->modules_affected) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-secondary mb-1">Testing done</label>
            @php $testingDoneVal = old('testing_done', $ticket->testing_done); @endphp
            <div class="d-flex align-items-center gap-3">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="testing_done" id="testingDoneYes" value="1"
                  @checked((string) $testingDoneVal === '1')>
                <label class="form-check-label small" for="testingDoneYes" style="font-weight: 600;">Yes</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="testing_done" id="testingDoneNo" value="0"
                  @checked((string) $testingDoneVal === '0')>
                <label class="form-check-label small" for="testingDoneNo" style="font-weight: 600;">No</label>
              </div>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label small fw-bold text-secondary mb-1">Deployment notes</label>
            <textarea name="deployment_notes" class="form-control form-control-sm" rows="2" placeholder="Any instructions or caveats for the deployment step" style="font-family: 'Nunito', sans-serif;">{{ old('deployment_notes', $ticket->deployment_notes) }}</textarea>
          </div>
        </div>
      </div>
    </div>

    {{-- Database Changes --}}
    <div class="card border rounded-3 overflow-hidden mb-3 shadow-none">
      <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background: var(--bs-light);">
        <div class="d-flex align-items-center justify-content-center rounded-2 border bg-white" style="width:28px;height:28px;color:#6b7280;">
          <i class="bi bi-database" style="font-size:13px;"></i>
        </div>
        <span class="fw-bold small">Database changes</span>
      </div>
      <div class="card-body p-3">
        <div class="row g-3">
          <div class="col-md-3 d-flex flex-column justify-content-center">
            <label class="form-label small fw-bold text-secondary mb-2">Changes required?</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="db_changes_required" value="1"
                id="dbChangesSwitch" @checked(old('db_changes_required', $ticket->db_changes_required))>
              <label class="form-check-label small" for="dbChangesSwitch" id="dbLabel" style="font-weight: 700;">
                {{ old('db_changes_required', $ticket->db_changes_required) ? 'Yes' : 'No' }}
              </label>
            </div>
          </div>
          <div class="col-md-9">
            <label class="form-label small fw-bold text-secondary mb-1">Migration details</label>
            <textarea name="migration_details" class="form-control form-control-sm" rows="3"
              placeholder="Describe migrations or SQL script details. Attach the .sql file below if applicable." style="font-family: 'Nunito', sans-serif;">{{ old('migration_details', $ticket->migration_details) }}</textarea>
          </div>
        </div>
      </div>
    </div>

    {{-- Attachments --}}
    <div class="card border rounded-3 overflow-hidden mb-3 shadow-none">
      <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background: var(--bs-light);">
        <div class="d-flex align-items-center justify-content-center rounded-2 border bg-white" style="width:28px;height:28px;color:#6b7280;">
          <i class="bi bi-paperclip" style="font-size:13px;"></i>
        </div>
        <span class="fw-bold small">Attachments</span>
      </div>
      <div class="card-body p-3">

        @if($ticket->attachments->count())
        <p class="text-muted mb-2" style="font-size: 12px; font-weight: 600;">Existing files. Check "Remove" to delete on save.</p>
        <div class="mb-3">
          @foreach($ticket->attachments as $attachment)
          @php
            $typeIcon = match($attachment->type ?? 'Other') {
              'Screenshot' => 'bi-image',
              'Document'   => 'bi-file-earmark-text',
              'SQL'        => 'bi-filetype-sql',
              default      => 'bi-file-earmark',
            };
          @endphp
          <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="font-size:13.5px;">
            <i class="bi {{ $typeIcon }} text-muted" style="font-size:15px;"></i>
            <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" class="text-decoration-none flex-grow-1 min-w-0 text-truncate" style="font-weight:600;">
              {{ $attachment->original_name ?? basename($attachment->file_path) }}
            </a>
            <span class="badge bg-light text-secondary border" style="font-size:10.5px; font-weight:700;">{{ $attachment->type ?? 'Other' }}</span>
            <a href="{{ route('deployment.attachments.destroy', $attachment->id) }}"
                class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Delete this attachment?')">
                    <i class="bi bi-trash"></i> Delete
            </a>
          </div>
          @endforeach
        </div>
        @endif

        <p class="text-muted mb-3" style="font-size: 12px; font-weight: 600;">Add new screenshots, documents, SQL files, or other supporting files.</p>
        <div id="attachment-rows">
          <div class="row g-2 mb-2 attachment-row">
            <div class="col-md-3">
              <select name="attachment_types[]" class="form-select form-select-sm" style="font-family: 'Nunito', sans-serif;">
                <option>Screenshot</option>
                <option>Document</option>
                <option>SQL</option>
                <option selected>Other</option>
              </select>
            </div>
            <div class="col-md-9">
              <input type="file" name="attachments[]" class="form-control form-control-sm">
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-sm d-inline-flex align-items-center gap-1 mt-1" id="addAttachmentRow"
          style="font-size: 12px; font-weight: 700; color: var(--bs-primary); background: rgba(var(--bs-primary-rgb),.08); border: 1px solid rgba(var(--bs-primary-rgb),.2); border-radius: 6px; padding: 5px 12px;">
          <i class="bi bi-plus"></i> Add another file
        </button>
      </div>
    </div>

    <div class="d-flex justify-content-end align-items-center gap-2 pb-4">
        <a href="{{ route('deployment.tickets.show', $ticket) }}"
        class="btn btn-outline-secondary btn-sm"
        style="min-width:120px; font-weight:700;">
            Cancel
        </a>
        <button type="submit"
                class="btn btn-primary btn-sm d-inline-flex align-items-center"
                style="width:auto; display:inline-flex; font-weight:600;">
            <i class="bi bi-save me-1"></i> Save Changes
        </button>
    </div>

  </form>
</div>

<script>
document.getElementById('addAttachmentRow').addEventListener('click', function () {
  const row = document.querySelector('.attachment-row').cloneNode(true);
  row.querySelectorAll('input,select').forEach(el => el.value = '');
  document.getElementById('attachment-rows').appendChild(row);
});

document.getElementById('dbChangesSwitch').addEventListener('change', function () {
  document.getElementById('dbLabel').textContent = this.checked ? 'Yes' : 'No';
});

document.getElementById('projectSelect').addEventListener('change', function () {
  const projectId = this.value;
  const ticketSelect = document.getElementById('ticketSelect');
  if (!projectId) {
    ticketSelect.innerHTML = '<option value="">Select project first</option>';
    return;
  }
  ticketSelect.innerHTML = '<option value="">Loading…</option>';
  fetch(`/projects/${projectId}/tickets-json`)
    .then(res => res.json())
    .then(data => {
      ticketSelect.innerHTML = '<option value="">None</option>';
      data.forEach(t => {
        ticketSelect.innerHTML += `<option value="${t.id}">${t.title}</option>`;
      });
    });
});
$(function () {
    $('.select2').select2({
        placeholder: 'Select Developers',
        width: '100%'
    });

    // Explicitly set pre-selected developers (fixes select2 not showing existing values on edit)
    var preselectedDevelopers = @json($selectedDeveloperIds ?? []);
    $('.select2').val(preselectedDevelopers.map(String)).trigger('change');
});
</script>
@endsection
