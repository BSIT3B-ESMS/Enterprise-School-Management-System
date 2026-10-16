{{-- User and notification content is filled in by resources/js/app-shell.js. --}}
<header class="app-header">
    <div class="d-flex align-items-center gap-2 gap-lg-3 min-w-0">
        <button type="button" class="app-icon-btn" aria-label="Toggle navigation" aria-controls="app-sidebar"
            aria-expanded="false" data-sidebar-toggle>
            <x-icon name="menu" />
        </button>

        <a class="app-brand" href="{{ route('dashboard') }}">
            <span class="app-brand-logo"><x-brand-logo /></span>
            <span class="app-brand-name">Employee Management System</span>
        </a>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="button" class="app-icon-btn" data-theme-toggle>
            <x-icon name="sun" class="theme-when-dark" />
            <x-icon name="moon" class="theme-when-light" />
            <span class="visually-hidden theme-when-dark">Switch to light mode</span>
            <span class="visually-hidden theme-when-light">Switch to dark mode</span>
        </button>

        <div class="dropdown">
            <button type="button" class="app-icon-btn" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="Notifications">
                <x-icon name="bell" />
                <span class="app-notification-dot d-none" data-notification-dot></span>
            </button>

            <div class="dropdown-menu dropdown-menu-end app-menu app-menu-wide">
                <div class="app-menu-header">Notifications</div>

                <a class="dropdown-item d-flex align-items-center gap-2 py-2 border-bottom d-none"
                    href="{{ route('recruitment.index', ['status' => 'Applied']) }}" data-pending-candidates>
                    <span class="badge rounded-pill text-bg-warning" data-pending-count></span>
                    <span data-pending-label></span>
                </a>

                <div data-notifications></div>

                <a class="dropdown-item link-primary border-top py-2" href="{{ route('audit.index') }}">
                    View all activity
                </a>
            </div>
        </div>

        <div class="dropdown">
            <button type="button" class="app-user" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="app-avatar" data-user-initials></span>
                <span class="d-none d-lg-block">
                    <span class="app-user-name" data-user-name></span>
                    <span class="app-user-role" data-user-role></span>
                </span>
                <span class="visually-hidden d-lg-none">Account menu</span>
            </button>

            <div class="dropdown-menu dropdown-menu-end app-menu">
                <div class="app-menu-note border-0">
                    <strong data-user-name></strong>
                    <small data-user-email></small>
                </div>

                <hr class="dropdown-divider my-0">

                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" data-theme-toggle>
                    <x-icon name="sun" class="theme-when-dark" />
                    <x-icon name="moon" class="theme-when-light" />
                    <span class="theme-when-dark">Light mode</span>
                    <span class="theme-when-light">Dark mode</span>
                </button>

                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" data-bs-toggle="modal"
                    data-bs-target="#reset-demo-modal">
                    <x-icon name="audit" />
                    Reset demo data
                </button>

                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('login') }}" data-logout>
                    <x-icon name="logout" />
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>
