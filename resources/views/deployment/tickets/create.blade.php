@extends('layout')

@section('title', 'New Deployment Ticket')

@section('content')

    <div class="col-12">
        <form method="POST" action="{{ route('deployment.tickets.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #0d6efd !important;">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fs-6 fw-bold text-dark text-uppercase tracking-wide" style="font-size: 0.85rem !important; letter-spacing: 0.5px;">Basic Information</h5>
                </div>
                <div class="card-body pt-0 row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Deployment Name <span class="text-danger">*</span></label>
                        <input type="text" name="deployment_name" class="form-control" required value="{{ old('deployment_name') }}" placeholder="e.g., Q3 Payment Gateway Integration">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Project <span class="text-danger">*</span></label>
                        <select name="project_id" id="projectSelect" class="form-select" required>
                            <option value="">Select Project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label text-secondary small fw-semibold">Related Ticket / Task</label>
                        <select name="related_ticket_ids" id="ticketSelect" class="form-select">
                            <option value="">-- Select Project First --</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            @foreach (['Low', 'Medium', 'High', 'Critical'] as $p)
                                <option value="{{ $p }}" @selected(old('priority', 'Medium') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Assigned Developer</label>
                        <select name="assigned_developer_id" class="form-select">
                            <option value="">-- Select Developer --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Reviewer</label>
                        <select name="reviewer_id" class="form-select">
                            <option value="">-- Select Reviewer --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">QA Tester</label>
                        <select name="qa_tester_id" class="form-select">
                            <option value="">-- Select Tester --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #6c757d !important;">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fs-6 fw-bold text-dark text-uppercase tracking-wide" style="font-size: 0.85rem !important; letter-spacing: 0.5px;">Deployment Details</h5>
                </div>
                <div class="card-body pt-0 row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Changes Done</label>
                        <textarea name="changes_done" class="form-control" rows="3" placeholder="Briefly detail high-level business logic adjustments..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Files Modified</label>
                        <textarea name="files_modified" class="form-control" rows="3" placeholder="e.g., app/Http/Controllers/PaymentController.php"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Modules Affected</label>
                        <textarea name="modules_affected" class="form-control" rows="2" placeholder="e.g., Checkout, Authentication, User Profile"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Testing Done</label>
                        <textarea name="testing_done" class="form-control" rows="2" placeholder="Mention features tested manually or test suites executed..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-semibold">Deployment Notes</label>
                        <textarea name="deployment_notes" class="form-control" rows="2" placeholder="Any special env keys, config changes or queue resets required?"></textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #ffc107 !important;">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fs-6 fw-bold text-dark text-uppercase tracking-wide" style="font-size: 0.85rem !important; letter-spacing: 0.5px;">Database Changes</h5>
                </div>
                <div class="card-body pt-0 row g-3">
                    <div class="col-md-3">
                        <label class="form-label d-block text-secondary small fw-semibold">Changes Required?</label>
                        <div class="form-check form-switch pt-1">
                            <input class="form-check-input" type="checkbox" name="db_changes_required" value="1" id="dbChangesSwitch" @checked(old('db_changes_required'))>
                            <label class="form-check-label text-muted small" for="dbChangesSwitch">Toggle if yes</label>
                        </div>
                    </div>
                    <div class="col-md-9 d-none" id="migrationDetailsContainer">
                        <label class="form-label text-secondary small fw-semibold">Migration Details</label>
                        <textarea name="migration_details" class="form-control" rows="2" placeholder="Describe migrations / SQL script details. Attach the actual .sql file below if applicable.">{{ old('migration_details') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #17a2b8 !important;">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fs-6 fw-bold text-dark text-uppercase tracking-wide" style="font-size: 0.85rem !important; letter-spacing: 0.5px;">Attachments</h5>
                </div>
                <div class="card-body pt-0">
                    <p class="text-muted small mb-3">Add screenshots, documents, SQL files, or other supporting assets.</p>

                    <div id="attachment-rows">
                        <div class="row g-2 mb-2 attachment-row align-items-center">
                            <div class="col-md-3">
                                <select name="attachment_types[]" class="form-select form-select-sm">
                                    <option value="Screenshot">Screenshot</option>
                                    <option value="Document">Document</option>
                                    <option value="SQL">SQL</option>
                                    <option value="Other" selected>Other</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="file" name="attachments[]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-1 text-end action-container">
                                </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-secondary p-0 mt-2" id="addAttachmentRow">
                        <i class="bi bi-plus-circle-fill text-primary me-1"></i> Add Another File
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="{{ route('deployment.tickets.index') }}" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">Save as Draft</button>
            </div>
        </form>
    </div>

@endsection

@section('js_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Dynamic Attachment Addition and Removal
    const addAttachmentBtn = document.getElementById('addAttachmentRow');
    const container = document.getElementById('attachment-rows');

    addAttachmentBtn.addEventListener('click', function () {
        const row = container.querySelector('.attachment-row').cloneNode(true);
        row.querySelector('input[type=file]').value = '';

        // Populate the delete button to dynamically added rows
        const actionCol = row.querySelector('.action-container');
        actionCol.innerHTML = `<button type="button" class="btn btn-sm text-danger remove-row-btn p-0"><i class="bi bi-trash"></i></button>`;

        container.appendChild(row);
    });

    // Event delegation handling row removals
    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row-btn')) {
            e.target.closest('.attachment-row').remove();
        }
    });

    // 2. Hide/Show DB details based on switch status
    const dbSwitch = document.getElementById('dbChangesSwitch');
    const migrationContainer = document.getElementById('migrationDetailsContainer');

    function toggleDbField() {
        if(dbSwitch.checked) {
            migrationContainer.classList.remove('d-none');
        } else {
            migrationContainer.classList.add('d-none');
        }
    }
    dbSwitch.addEventListener('change', toggleDbField);
    toggleDbField(); // Initial layout run checking validation state on fallback old values

    // 3. Existing cascade logic for project tickets
    const projectSelect = document.getElementById('projectSelect');
    const ticketSelect = document.getElementById('ticketSelect');

    projectSelect.addEventListener('change', function () {
        const projectId = this.value;
        ticketSelect.innerHTML = '<option value="">-- Select Ticket --</option>';

        if (!projectId) {
            ticketSelect.innerHTML = '<option value="">-- Select Project First --</option>';
            return;
        }

        ticketSelect.disabled = true;
        ticketSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`{{ url('deployment/tickets/by-project') }}/${projectId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(tickets => {
                ticketSelect.innerHTML = '<option value="">-- Select Ticket --</option>';
                tickets.forEach(ticket => {
                    const option = document.createElement('option');
                    option.value = ticket.id;
                    option.textContent = `#${ticket.id} - ${ticket.title}`;
                    ticketSelect.appendChild(option);
                });
                if (tickets.length === 0) {
                    ticketSelect.innerHTML = '<option value="">No in-progress tickets for this project</option>';
                }
            })
            .catch(() => {
                ticketSelect.innerHTML = '<option value="">Error loading tickets</option>';
            })
            .finally(() => {
                ticketSelect.disabled = false;
            });
    });
});
</script>
@endsection
