@if (session('conflicts') && (session('conflicts')['form'] ?? null) === ($suffix ?? 'form'))
    <div class="alert alert-warning mb-0" role="alert">
        <strong>That time overlaps something, so nothing was changed.</strong>
        <ul class="mb-2">
            @foreach (session('conflicts')['messages'] as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        @if (! empty(session('conflicts')['free']))
            <p class="mb-0">
                Free at that time: {{ collect(session('conflicts')['free'])->pluck('name')->implode(', ') }}.
                @if (($suffix ?? 'form') === 'reschedule')
                    Choose another time.
                @else
                    Pick one of them, or choose another time.
                @endif
            </p>
        @else
            <p class="mb-0">Nobody else in HR is free at that time. Choose another time.</p>
        @endif
    </div>
@endif
