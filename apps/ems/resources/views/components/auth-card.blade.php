@props(['title', 'subtitle' => null])

{{-- A centered card for the sign-in steps that follow the Login page (two-factor code, password change, setup). --}}
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="col-12 col-md-8 col-lg-6 col-xl-4">
        <div class="card employee-form-card d-grid gap-3">
            <div>
                <h1 class="login-form-title mb-1">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-body-secondary mb-0">{{ $subtitle }}</p>
                @endif
            </div>

            <x-flash-messages />

            @if ($errors->any())
                <div class="alert alert-danger mb-0" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
