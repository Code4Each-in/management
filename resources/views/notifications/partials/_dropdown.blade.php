<li class="nav-item dropdown notifyicon">
    <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" style="display: flex; gap: 10px;">
        <i class="bi bi-bell"></i>
        @if($unreadCount > 0)
            <span class="badge bg-primary badge-number">{{ $unreadCount }}</span>
        @endif
    </a><!-- End Notification Icon -->

    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
        <li class="dropdown-header">
            You have {{ $unreadCount }} new notification{{ $unreadCount > 1 ? 's' : '' }}
            <a href="{{ route('notifications.all') }}" target="_blank"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
        </li>

        <li><hr class="dropdown-divider"></li>

        @forelse($notifications as $notif)
            <li class="notification-item">
                @php
                    $icon = match($notif->type) {
                        'comment' => 'bi-chat-dots text-primary',
                        'status_change' => 'bi-arrow-repeat text-warning',
                        'assigned' => 'bi-person-plus text-success',
                        'mention' => 'bi-at text-info',
                        default => 'bi-info-circle text-secondary'
                    };
                @endphp

                <i class="bi {{ $icon }}"></i>
                <a href="{{ url('/view/ticket/'.$notif->ticket_id) }}" class="text-decoration-none text-dark">
                    <div>
                        <h4>{{ ucfirst(str_replace('_', ' ', $notif->type)) }}</h4>
                        <p>{{ $notif->message }}</p>
                        <p><small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small></p>
                    </div>
                </a>
            </li>

            <li><hr class="dropdown-divider"></li>
        @empty
            <li class="notification-item text-center text-muted p-3">
                No new notifications
            </li>
        @endforelse

        <li class="dropdown-footer">
            <a href="{{ route('notifications.all') }}" target="_blank">Show all notifications</a>
        </li>
    </ul><!-- End Notification Dropdown Items -->
</li>
