@extends('layout')
@section('title', 'All Bidder Sprints')
@section('subtitle', 'Show')
@section('content')

<div class="row row-design mb-4 mt-2">
    <div class="col-md-2">
        <button class="btn btn-primary m-3" onclick="addBidSprintModal()">Add Bid Sprint</button>
    </div>
</div>

<div class="card">
    <div class="card-body pb-4">
        <table class="styled-sprint-table sprint-table">
            <thead>
                <tr style="color: #297bab;">
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Started At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bidSprint as $index => $sprint)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $sprint->name }}</td>
                        <td>{{ $sprint->start_date ? \Carbon\Carbon::parse($sprint->start_date)->format('d/m/Y H:i') : '---' }}</td>
                        <td>
                            <span class="badge {{ $sprint->status == 1 ? 'active' : ($sprint->status == 2 ? 'completed' : 'inactive') }}">
                                {{ $sprint->status == 1 ? 'Active' : ($sprint->status == 2 ? 'Completed' : 'Inactive') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ url('/view/bid-sprint/' . $sprint->id) }}" target="_blank">
                                <i class="fa fa-eye fa-fw pointer" title="View"></i>
                            </a>
                              <a href="{{ url('/edit/bid-sprint/' . $sprint->id) }}">
                            <i class="fa fa-edit fa-fw pointer" title="Edit"></i>
                            </a>
                            <i class="fa fa-trash fa-fw pointer" title="Delete" onclick="deleteBidSprint('{{ $sprint->id }}')"></i>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No Bid Sprints found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Add Bid Sprint Modal -->
        <div class="modal fade" id="addBidSprintModal" tabindex="-1" aria-labelledby="addBidSprintLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBidSprintLabel">Add Bid Sprint</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addBidSprintForm" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label required">Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control text-dark" name="name" id="bid_sprint_name">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label required">Start Date</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control text-dark" name="start_date" id="bid_start_date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">End Date</label>
                                <div class="col-sm-9">
                                    <input type="datetime-local" class="form-control text-dark" name="end_date" id="bid_end_date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Status</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="status" id="bid_status">
                                        <option value="1" selected>Active</option>
                                        <option value="0">Inactive</option>
                                        <option value="2">Completed</option>
                                    </select>
                                </div>
                            </div>

                           <div class="row mb-3">
                              <label class="col-sm-3 col-form-label">Description</label>
                              <div class="col-sm-9">
                                  <!-- Quill Toolbar -->
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
                          
                                  <div id="editor" name="description" style="height: 300px;"></div>
                                  <input type="hidden" name="description" id="description_input">
                                  
                                  @if ($errors->has('description'))
                                      <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                                  @endif
                              </div>
                          </div>   

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_scripts')
<script>
    function addBidSprintModal() {
        $('#addBidSprintModal').modal('show');
    }

    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#addBidSprintForm').submit(function (e) {
            e.preventDefault();

            const description = quill.root.innerHTML;
            $('#description_input').val(description);

            
            const formData = new FormData(this);
            $('.alert-danger').hide().html('');

            $.ajax({
                type: 'POST',
                url: "{{ route('bdeSprint.add') }}",
                data: formData,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        $('.alert-danger').show();
                        $.each(data.errors, function (key, value) {
                            $('.alert-danger').append('<li>' + value + '</li>');
                        });
                    } else {
                        $('#addBidSprintModal').modal('hide');
                        location.reload();
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    $('.alert-danger').show().html("Something went wrong.");
                }
            });
        });
    });
    function deleteBidSprint(id) {
        if (confirm('Are you sure you want to delete this Bid Sprint?')) {
            $.ajax({
                url: '/bde-sprint/' + id,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    window.location.href = "{{ route('bdeSprint.index') }}";
                },
                error: function(xhr) {
                    alert('Something went wrong while deleting. Please try again.');
                }
            });
        }
    }
</script>
<script>
    $(document).ready(function() {
        $('.styled-sprint-table').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true
        });
    });
</script>
@endsection
