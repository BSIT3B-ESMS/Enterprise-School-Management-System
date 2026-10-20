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

        <x-notification-bell />

        <div class="dropdown">
            <button type="button" class="app-user" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                <span class="app-avatar" data-user-initials>{{ auth()->user()->initials() }}</span>
                <span class="d-none d-lg-block">
                    <span class="app-user-name" data-user-name>{{ auth()->user()->name }}</span>
                    <span class="app-user-role" data-user-role>{{ auth()->user()->roleLabel() }}</span>
                </span>
                <span class="visually-hidden d-lg-none">Account menu</span>
            </button>

            <div class="dropdown-menu dropdown-menu-end app-menu app-menu-account">
                <div class="app-menu-profile">
                    <span class="app-avatar app-avatar-lg" data-user-initials>{{ auth()->user()->initials() }}</span>
                    <div class="min-w-0">
                        <strong class="d-block text-truncate" data-user-name>{{ auth()->user()->name }}</strong>
                        <small class="d-block text-truncate" data-user-email>{{ auth()->user()->email }}</small>
                        <span class="ems-chip d-inline-block mt-1" data-user-role>{{ auth()->user()->roleLabel() }}</span>
                    </div>
                </div>

                <hr class="dropdown-divider my-0">

                @php($isProfilePage = request()->routeIs('profile.show'))
                <a class="dropdown-item d-flex align-items-center gap-2 py-2{{ $isProfilePage ? ' active' : '' }}"
                    aria-current="{{ $isProfilePage ? 'page' : 'false' }}" href="{{ route('profile.show') }}">
                    <x-icon name="user" />
                    My Profile
                </a>

                @php($isSettingsPage = request()->routeIs('account.settings'))
                <a class="dropdown-item d-flex align-items-center gap-2 py-2{{ $isSettingsPage ? ' active' : '' }}"
                    aria-current="{{ $isSettingsPage ? 'page' : 'false' }}" href="{{ route('account.settings') }}">
                    <x-icon name="settings" />
                    Account Settings
                </a>
                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('profile.show') }}#security">
                    <x-icon name="lock" />
                    Security
                </a>

                <hr class="dropdown-divider my-0">

                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" data-theme-toggle>
                    <x-icon name="sun" class="theme-when-dark" />
                    <x-icon name="moon" class="theme-when-light" />
                    <span class="theme-when-dark">Light mode</span>
                    <span class="theme-when-light">Dark mode</span>
                </button>

                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2" data-logout>
                        <x-icon name="logout" />
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
