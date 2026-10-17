{{-- The applicant's name and details are filled in by resources/js/applicant-portal.js. --}}
<header class="app-header">
    <div class="d-flex align-items-center gap-2 gap-lg-3 min-w-0">
        <button type="button" class="app-icon-btn" aria-label="Toggle navigation" aria-controls="app-sidebar"
            aria-expanded="false" data-sidebar-toggle>
            <x-icon name="menu" />
        </button>

        <a class="app-brand" href="{{ route('applicant.dashboard') }}" data-applicant-link>
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
            <button type="button" class="app-user" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                <span class="app-avatar" data-applicant-initials></span>
                <span class="d-none d-lg-block">
                    <span class="app-user-name" data-applicant-name></span>
                    <span class="app-user-role">Applicant</span>
                </span>
                <span class="visually-hidden d-lg-none">Account menu</span>
            </button>

            <div class="dropdown-menu dropdown-menu-end app-menu app-menu-account">
                <div class="app-menu-profile">
                    <span class="app-avatar app-avatar-lg" data-applicant-initials></span>
                    <div class="min-w-0">
                        <strong class="d-block text-truncate" data-applicant-name></strong>
                        <small class="d-block text-truncate" data-applicant-email></small>
                        <span class="ems-chip d-inline-block mt-1">Applicant</span>
                    </div>
                </div>

                <hr class="dropdown-divider my-0">

                @php($isProfilePage = request()->routeIs('applicant.profile'))
                <a class="dropdown-item d-flex align-items-center gap-2 py-2{{ $isProfilePage ? ' active' : '' }}"
                    aria-current="{{ $isProfilePage ? 'page' : 'false' }}" href="{{ route('applicant.profile') }}"
                    data-applicant-link>
                    <x-icon name="user" />
                    My Profile
                </a>

                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" data-theme-toggle>
                    <x-icon name="sun" class="theme-when-dark" />
                    <x-icon name="moon" class="theme-when-light" />
                    <span class="theme-when-dark">Light mode</span>
                    <span class="theme-when-light">Dark mode</span>
                </button>

                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('login') }}">
                    <x-icon name="logout" />
                    Exit to Sign In
                </a>
            </div>
        </div>
    </div>
</header>
