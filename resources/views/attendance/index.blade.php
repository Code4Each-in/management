@extends('layout')
@section('title', 'Attendance')
@section('subtitle', 'Attendance')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('attendance.store')}}">
                @csrf
                <div class="row mb-3 mt-4">
                    <div class="col-sm-2">
                        <label for="intime">In Time</label>
                        <input type="time" id="intime" class="form-control" name="intime">
                        @if ($errors->has('intime'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('intime') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-2">
                        <label for="outtime">Out Time</label>
                        <input type="time" id="outtime" class="form-control" name="outtime">
                        @if ($errors->has('outtime'))
                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('outtime') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-4">
                        <label for="notes">Notes</label>
                        <textarea name="notes" rows="1" class="form-control" id="notes"></textarea>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-primary " style="margin-top:23px;" href="javascript:void(0)">Add</button>
                    </div>
                </div>
            </form>
            @if(session()->has('message'))
            <div class="alert alert-success message">
                {{ session()->get('message') }}
            </div>
            @endif
            <div class="box-body table-responsive" style="margin-bottom: 5%">
                <table class="table table-borderless dashboard" id="attendance">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Date</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Worked Hours</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($attendanceData) !=0)
                        @forelse($attendanceData as $data)
                        <tr>
                            <td>{{ auth()->user()->first_name ?? " " }}</td>
                            <!-- <td>{{$data->created_at}}</td> -->
                            <td>{{date("d-m-Y H:s a", strtotime($data->created_at));}}</td>
                            <td>{{ date("h:i A", strtotime($data->in_time));}}
                            </td>
                            <td>{{date("h:i A", strtotime($data->out_time));}}</td>
                            <td>
                                @php
                                $inTime = new DateTime($data->in_time);
                                $outTime = new DateTime($data->out_time);

                                $duration = $inTime->diff($outTime)->format('%h:%i');

                                echo $duration;
                                @endphp
                            </td>

                            <td>{{ strip_tags($data->notes) }}</td>
                        </tr>
                        @empty
                        @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
@section('js_scripts')
<script>
    $(document).ready(function() {
        setTimeout(function() {
            $('.message').fadeOut("slow");
        }, 2000);
        $('#attendance').DataTable({
            "order": []
            //"columnDefs": [ { "orderable": false, "targets": 7 }]
        });
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    tinymce.init({
      selector: '#notes',
      plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed linkchecker a11ychecker tinymcespellchecker permanentpen powerpaste advtable advcode editimage tinycomments tableofcontents footnotes mergetags autocorrect typography inlinecss',
      toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
      tinycomments_mode: 'embedded',
      tinycomments_author: 'Author name',
      mergetags_list: [
        { value: 'First.Name', title: 'First Name' },
        { value: 'Email', title: 'Email' },
      ]
    });


    
</script>
@endsection