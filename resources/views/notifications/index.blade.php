@extends($layout)

@section('title', 'Notifications · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Notifications</h1>
                <p class="text-body-secondary mt-1 mb-0">{{ $unreadCount }} unread. Notifications stay in the system; nothing is sent to your email.</p>
            </div>

            @if ($unreadCount > 0)
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">Mark All as Read</button>
                </form>
            @endif
        </div>

        <div class="card overflow-hidden">
            <ul class="list-group list-group-flush">
                @forelse ($notifications as $notification)
                    <li class="list-group-item">
                        <form method="post" action="{{ route('notifications.open', $notification->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-link text-start text-reset p-0 w-100 d-flex justify-content-between gap-3 text-decoration-none">
                                <span>
                                    <span class="fw-medium d-block">
                                        {{ $notification->data['title'] }}
                                        @if ($notification->unread()) <span class="badge text-bg-primary ms-1">New</span> @endif
                                    </span>
                                    <small class="text-body-secondary d-block">{{ $notification->data['body'] }}</small>
                                </span>
                                <small class="text-body-secondary text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-body-secondary">You have no notifications yet.</li>
                @endforelse
            </ul>
        </div>

        {{ $notifications->links() }}
    </div>
@endsection
