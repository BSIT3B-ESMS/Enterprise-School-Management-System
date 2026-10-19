@php
    // The Applicant interface's five pages. A position's details and the application form belong to Open Positions;
    // an application's details belong to Applications.
    $items = [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'route' => 'applicant.dashboard'],
        ['icon' => 'user', 'label' => 'Profile', 'route' => 'applicant.profile'],
        ['icon' => 'calendar', 'label' => 'Calendar', 'route' => 'applicant.calendar'],
        ['icon' => 'job-titles', 'label' => 'Open Positions', 'route' => 'applicant.positions', 'patterns' => ['applicant.positions*', 'applicant.apply']],
        ['icon' => 'recruitment', 'label' => 'Applications', 'route' => 'applicant.applications', 'patterns' => ['applicant.applications*']],
    ];
@endphp

<aside class="app-sidebar">
    <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="app-sidebar" aria-labelledby="app-sidebar-title">
        <div class="offcanvas-header">
            <span class="app-brand" id="app-sidebar-title">
                <span class="app-brand-logo"><x-brand-logo /></span>
                Menu
            </span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#app-sidebar" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <nav aria-label="Main">
                <ul class="nav nav-pills flex-column gap-1 app-nav">
                    @foreach ($items as $item)
                        @php($isCurrent = request()->routeIs(...($item['patterns'] ?? [$item['route']])))
                        <li class="nav-item">
                            <a class="nav-link{{ $isCurrent ? ' active' : '' }}"
                                aria-current="{{ $isCurrent ? 'page' : 'false' }}" href="{{ route($item['route']) }}">
                                <x-icon :name="$item['icon']" />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="app-sidebar-footer">
                <a class="app-sidebar-logout" href="{{ route('login') }}" data-applicant-logout>
                    <x-icon name="logout" />
                    Log Out
                </a>
            </div>
        </div>
    </div>
</aside>
