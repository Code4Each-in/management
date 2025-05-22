@php use Illuminate\Support\Str; @endphp

@if(!empty($results))
    @foreach($results as $type => $items)
        @if($type === 'message' && !in_array(auth()->user()->role_id, [1, 6]))
            @continue
        @endif

        <div class="search-table search-{{ $loop->iteration }}">
            <div class="search-header">
                <div class="avatar">{{ strtoupper(substr($type, 0, 1)) }}</div>
                <h5>Matches found in {{ ucfirst($type) }}: {{ $items->count() }}</h5>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        @if($type === 'ticket')
                            <tr>
                                <th>Ticket Id</th>
                                <th>Title</th>
                                <th>Description</th>
                            </tr>
                        @elseif($type === 'sprint')
                            <tr>
                                <th>Sprint Id</th>
                                <th>Name</th>
                                <th>Description</th>
                            </tr>
                        @elseif($type === 'message')
                            <tr>
                                <th>Project Id</th>
                                <th>Message</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="clickable-row" data-url="{{ 
                                $type === 'ticket' ? url('/view/ticket/' . $item->id) :
                                ($type === 'sprint' ? url('/view/sprint/' . $item->id) :
                                url('/messages?project_id=' . $item->project_id)) 
                            }}">
                                @if($type === 'ticket')
                                    <td data-label="ID">{{ $item->id }}</td>
                                    <td data-label="Title">{{ $item->title }}</td>
                                    <td data-label="Description">
                                        {{ Str::limit(strip_tags($item->description), 100, '...') }}
                                    </td>
                                @elseif($type === 'sprint')
                                    <td data-label="ID">{{ $item->id }}</td>
                                    <td data-label="Name">{{ $item->name }}</td>
                                    <td data-label="Description">
                                        {{ Str::limit(strip_tags($item->description), 100, '...') }}
                                    </td>
                                @elseif($type === 'message')
                                    <td data-label="ID">{{ $item->project_id }}</td>
                                    <td data-label="Message">
                                        {{ Str::limit(strip_tags($item->message), 100, '...') }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center;">No {{ $type }}s found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@else
    <div class="alert alert-info">No results found.</div>
@endif
