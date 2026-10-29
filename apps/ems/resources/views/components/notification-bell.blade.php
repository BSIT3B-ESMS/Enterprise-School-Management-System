@php
    $recent = auth()->user()->notifications()->limit(6)->get();
    $unread = auth()->user()->unreadNotifications()->count();
@endphp

{{-- The bell in every header: the latest notifications from the database, with a dot while any are unread. --}}
<div class="dropdown">
    <button type="button" class="app-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}">
        <x-icon name="bell" />
        @if ($unread > 0)
            <span class="app-notification-dot"></span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end app-menu app-menu-wide">
        <div class="app-menu-header">Notifications</div>

        @forelse ($recent as $notification)
            <form method="post" action="{{ route('notifications.open', $notification->id) }}">
                @csrf
                <button type="submit" class="dropdown-item app-menu-note text-start">
                    <span>{{ $notification->data['title'] }} @if ($notification->unread()) <span class="badge text-bg-primary">New</span> @endif</span>
                    <small>{{ $notification->data['body'] }} · {{ $notification->created_at->diffForHumans() }}</small>
                </button>
            </form>
        @empty
            <div class="app-menu-note text-body-secondary">No notifications yet.</div>
        @endforelse

        <a class="dropdown-item link-primary border-top py-2" href="{{ route('notifications.index') }}">View all notifications</a>
    </div>
</div>
