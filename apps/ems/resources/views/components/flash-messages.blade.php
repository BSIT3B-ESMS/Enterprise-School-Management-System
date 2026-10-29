@php
    $flashes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];
@endphp

@foreach ($flashes as $key => $variant)
    @if (session()->has($key))
        <div class="alert {{ $variant }} alert-dismissible fade show" role="alert">
            {{ session()->get($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach
