@extends('layout')
@section('title', 'Attendance')
@section('subtitle', 'Attendance')
@section('content')

<div class="col-lg-12">
    <div class="card">
        <div class="card-body mt-5">
            {{-- <form method="post" action="{{ route('attendance.add')}}">
                @csrf
                <div class="row mb-3 mt-4">
                    <div class="col-sm-3 col-md-3">
                        <div class="mb-4">
                            <label for="intime" class="required">In Time</label>
                            <input type="time" id="intime" class="form-control" name="intime">
                            @if ($errors->has('intime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('intime') }}</span>
                            @endif
                        </div>
                        <div class="mb-4">
                            <label for="outtime" class="required">Out Time</label>
                            <input type="time" id="outtime" class="form-control" name="outtime">
                            @if ($errors->has('outtime'))
                            <span style="font-size: 12px;" class="text-danger">{{ $errors->first('outtime') }}</span>
                            @endif
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary " style="margin-top:10px;width:100%;" href="javascript:void(0)">Add</button>

                        </div>

                    </div>

                    <div class="col-sm-9 col-md-9">
                        <label for="tinymce_textarea">Notes</label>
                        <textarea name="notes" rows="1" class="form-control" id="tinymce_textarea"></textarea>
                    </div>
                    <!-- <div class="col-sm-2">
                    </div> -->
                </div>
                <!-- <div class="row mb-3 mt-4">
                <div class="col-sm-2">

                    </div>
                </div> -->
            </form>
            --}}

            <!-- Form For Filter The Range Between Two Dates In Attendance -->
            <form action="" id="intervalsFilterForm" method="get">
                <div class="row my-4">
                <div class="col-md-3 form-group">
                        <label for="intervalsFilterselectBox">Date Range</label>
                        <select class="form-control" id="intervalsFilterselectBox" name="intervals_filter">
                            <option value="" selected disabled>Select Filter</option>
                            <option value="yesterday" {{ request()->input('intervals_filter') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="last_week" {{ request()->input('intervals_filter') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="last_month" {{ request()->input('intervals_filter') == 'last_month' ? 'selected' : '' }} >Last Month</option>
                            <option value="custom_intervals" {{ request()->input('intervals_filter') == 'custom_intervals' ? 'selected' : '' }}>Custom Date Range</option>
                        </select>
                    </div>
                    <div class="col-md"></div>
                    <div class="col-md-2 form-group custom-intervals" style = "{{ request()->input('intervals_filter') !== 'custom_intervals' ? 'display: none;' : '' }}">
                        <label for="">Date From</label>
                        <input type="date" name="date_from" class="form-control custom-date" value="{{ request()->input('intervals_filter') === 'custom_intervals' ? (request()->has('date_from') ? request()->input('date_from') : '') : '' }}" required >
                        @if ($errors->has('date_from'))
                            <span style="font-size: 10px;" class="text-danger">{{ $errors->first('date_from') }}</span>
                            @endif
                    </div>
                    <div class="col-md-2 form-group custom-intervals" style="{{ request()->input('intervals_filter') !== 'custom_intervals' ? 'display: none;' : '' }}">
                        <label for="">Date To</label>
                        <input type="date" name="date_to" class="form-control custom-date" value="{{ request()->input('intervals_filter') === 'custom_intervals' ? (request()->has('date_to') ? request()->input('date_to') : '') : '' }}" required>
                        @if ($errors->has('date_to'))
                            <span style="font-size: 10px;" class="text-danger">{{ $errors->first('date_to') }}</span>
                            @endif
                    </div>
                    <div class="col-md-2 form-group custom-intervals" style="{{ request()->input('intervals_filter') !== 'custom_intervals' ? 'display: none;' : '' }}">
                        <input type="submit" class="btn btn-primary custom-search" value="Search" style="margin-top: 19px;">
                    </div>
                </div>
            </form> 

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
                            @php
                                $newdate=$data->date.''.$data->in_time;
                            @endphp
                            <td>{{date("d-m-Y H:i a", strtotime($newdate));}} </td>
                            <td>{{ date("h:i A", strtotime($data->in_time));}}
                            </td>
                            <td>{{date("h:i A", strtotime($data->out_time));}}</td>
                            <td>
                                @php
                                    $inTime = new DateTime($data->in_time);
                                    $outTime = new DateTime($data->out_time);

                                    if ($data->out_time_date) {
                                        $inDate = new DateTime($data->date);
                                        $combinedInDateTime = new DateTime($inDate->format('Y-m-d') . ' ' . $inTime->format('H:i:s'));
                                        $outTimeDate = new DateTime($data->out_time_date);

                                        $durationT = $combinedInDateTime->diff($outTimeDate);
                                        $hours = $durationT->h + ($durationT->d * 24); // total hours
                                        $formattedDuration = $durationT->format('%d days %h hours %i minutes');
                                        if ($hours < 9) {
                                            echo '<span class="text-danger" title="Less than 9 hours"><i class="fas fa-exclamation-triangle"></i></span> ';
                                        }

                                        echo $formattedDuration;
                                    } else {
                                        $duration = $inTime->diff($outTime);
                                        $hours = $duration->h + ($duration->d * 24); // total hours
                                        $formattedDuration = $duration->format('%h:%i');
                                        if ($hours < 9) {
                                            echo '<span class="text-danger" title="Less than 9 hours"><i class="fas fa-exclamation-triangle"></i></span> ';
                                        }

                                        echo $formattedDuration;
                                    }
                                @endphp
                            </td>
                            <td style="width:200px;">
                                @if(strlen($data->notes) >= 100)
                                <span class="description">
                                    @php
                                    $plainTextDescription = strip_tags(htmlspecialchars_decode($data->notes));
                                    $limitedDescription = substr($plainTextDescription, 0, 100) . '...';
                                    echo $limitedDescription;
                                    @endphp
                                </span>
                                <span class="fullDescription" style="display: none;">
                                 @php
                                    echo $data->notes;
                                    @endphp
                                </span>
                                <a href="#" class="readMoreLink">Read More</a>
                                <a href="#" class="readLessLink" style="display: none;">Read Less</a>
                                @else
                                {!! $data->notes !!}                                       
                                 @endif
                            </td> 
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

    var intervalsFilterselectBox = document.getElementById('intervalsFilterselectBox');
    var customIntervalsOption = document.querySelector('option[value="custom_intervals"]');
    var customIntervalsSection = document.querySelectorAll('.custom-intervals');

    intervalsFilterselectBox.addEventListener('change', function() {
        if (this.value === customIntervalsOption.value) {
            for (var i = 0; i < customIntervalsSection.length; i++) {
                customIntervalsSection[i].style.display = 'block';
            }
        } else {
            for (var i = 0; i < customIntervalsSection.length; i++) {
                customIntervalsSection[i].style.display = 'none';
            }
        }
        // Submit the form when any select option is changed
        document.getElementById('intervalsFilterForm').submit();

    });

    $('.readMoreLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                description.text(fullDescription.text());
                $(this).hide();
                $(this).siblings('.readLessLink').show();
            });

            $('.readLessLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                var truncatedDescription = fullDescription.text().substring(0, 100) + '...';
                description.text(truncatedDescription);
                $(this).hide();
                $(this).siblings('.readMoreLink').show();
            });


</script>
@endsection
