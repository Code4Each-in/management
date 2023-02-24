@extends('layout')
@section('title', 'Tickets')
@section('subtitle', 'Tickets')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-primary mt-3" onClick="openticketModal()" href="javascript:void(0)">ADD
                Tickets</button>
            <!-- filter -->
            <div class="box-header with-border mt-4" id="filter-box">
                <div class="box-body table-responsive" style="margin-bottom: 5%">
                    <table class="table table-borderless dashboard" id="tickets">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Assign</th>
                                <th>Upload</th>
                                <th>Status</th>
                                <th>Comment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td> <i style="color:#4154f1;" class="fa fa-edit fa-fw pointer"></i>
                                    <i style="color:#4154f1;" class="fa fa-trash fa-fw pointer"></i>
                                </td>
                            </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!----Add Tickets--->
<div class="modal fade" id="addTickets" tabindex="-1" aria-labelledby="role" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 630px;">
            <div class="modal-header">
                <h5 class="modal-title" id="role">Add Tickets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addTicketsForm" action="">
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    <div class="row mb-3">
                        <label for="title" class="col-sm-3 col-form-label">Title</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="title" id="title">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="description" class="col-sm-3 col-form-label">Description</label>
                        <div class="col-sm-9">
                            <textarea name="description" class="form-control" id="description"></textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label ">Assign</label>
                        <div class="col-sm-9">
                            <select name="assign" class="form-select" id="assign">
                                <option value="">Select User</option>
                                @foreach ($user as $data)
                                <option value="{{$data->id}}">
                                    {{$data->first_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @csrf
                    <div class="row mb-3">
                        <label for="status" class="col-sm-3 col-form-label ">Status</label>
                        <div class="col-sm-9">
                            <select name="status" class="form-select" id="status">
                                <option value="">To do</option>
                                <option value="In_progress">In Progress</option>
                                <option value="ready">Ready</option>
                                <option value="complete">
                                    Complete </option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="comment" class="col-sm-3 col-form-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea name="comment" class="form-control" id="comment"></textarea>
                        </div>
                    </div>
                    <!-- <div class="row mb-3">
                        <label for="document" class="col-sm-3 col-form-label">Document</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" name="document" id="document">
                        </div>
                    </div> -->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" href="javascript:void(0)">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {

    $('#tickets').DataTable({
        "order": []

    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#addTicketsForm").submit(function(event) {
        event.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: "{{ url('/add/tickets')}}",
            data: formData,
            cache: false,
            processData: false,
            contentType: false,
            success: (data) => {
                if (data.errors) {
                    $('.alert-danger').html('');
                } else {
                    $('.alert-danger').show();
                    $("#addTickets").modal('hide');
                    location.reload();
                }
            },
            error: function(data) {}
        });
    });
});

function openticketModal() {
    $('#addTickets').modal('show');
}
</script>
@endsection