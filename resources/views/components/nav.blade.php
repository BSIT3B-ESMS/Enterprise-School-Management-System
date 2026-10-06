<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">{{ config('app.name') }}</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav"
            aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="main-nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link{{ request()->routeIs('home') ? ' active' : '' }}"
                        aria-current="{{ request()->routeIs('home') ? 'page' : 'false' }}"
                        href="{{ route('home') }}">Home</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
