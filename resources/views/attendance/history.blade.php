@extends('layout')
@section('title', 'Attendance History')
@section('subtitle', 'Attendance')
@section('content')
<div class="col-lg-12">
    <div class="card">
        <div class="card-body mt-5">
            <div class="table-responsive mb-5">
                <table class="table table-borderless dashboard" id="attendance">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>In Time</th>
                                        <th>Out Time</th>
                                        <th>Total Working Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                            @if(!empty($attendances) && count($attendances) > 0)
                                @forelse($attendances as $data)
                            <tr>
                                <td>{{ $data->user->first_name ?? '' }}</td>
                                @php
                                    $newdate = $data->date . ' ' . $data->in_time;
                                @endphp
                                <td>{{ date('d-m-Y', strtotime($newdate)) }}</td>
                                <td>{{ date('h:i A', strtotime($data->in_time)) }}</td>
                                <td>
                {{ $data->out_time_date ? date('h:i A', strtotime($data->out_time_date)) : '-' }}
            </td>
            <td data-in-time="{{ $data->in_time }}" class="working-hours"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No attendance records found.</td>
                            </tr>
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
    document.addEventListener('DOMContentLoaded', function () {
        const readMoreLinks = document.querySelectorAll('.readMoreLink');
        const readLessLinks = document.querySelectorAll('.readLessLink');

        readMoreLinks.forEach((readMore, index) => {
            readMore.addEventListener('click', function (e) {
                e.preventDefault();
                readMore.classList.add('d-none');
                readLessLinks[index].classList.remove('d-none');
                readMore.previousElementSibling.classList.add('d-none'); 
                readMore.previousElementSibling.previousElementSibling.classList.remove('d-none');
            });
        });

        readLessLinks.forEach((readLess, index) => {
            readLess.addEventListener('click', function (e) {
                e.preventDefault();
                readLess.classList.add('d-none');
                readMoreLinks[index].classList.remove('d-none');
                readLess.previousElementSibling.classList.add('d-none'); 
                readLess.previousElementSibling.previousElementSibling.classList.remove('d-none'); 
            });
        });
    });
    function updateWorkingHours() {
        const now = new Date();
        document.querySelectorAll('.working-hours').forEach(td => {
            const inTime = td.dataset.inTime;
            const [hours, minutes, seconds] = inTime.split(':');
            const inDate = new Date();
            inDate.setHours(hours, minutes, seconds);

            const diffMs = now - inDate;
            const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
            const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

            td.textContent = `${diffHrs}h ${diffMins}m`;
        });
    }
    updateWorkingHours();
    setInterval(updateWorkingHours, 60000); // every minute
</script>
<script>
    $(document).ready(function () {
        $('#attendance').DataTable({
            "order": [[1, "desc"]], 
            "pageLength": 10,       
            "responsive": true
        });
    });
</script>
@endsection
