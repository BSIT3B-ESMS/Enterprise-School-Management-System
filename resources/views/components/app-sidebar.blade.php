@php
    $pageItems = [
        ['route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'employees.index', 'pattern' => 'employees.*', 'icon' => 'employees', 'label' => 'Employees'],
        ['route' => 'departments.index', 'pattern' => 'departments.*', 'icon' => 'departments', 'label' => 'Departments'],
        ['route' => 'job-titles.index', 'pattern' => 'job-titles.*', 'icon' => 'job-titles', 'label' => 'Job Titles'],
        ['route' => 'recruitment.index', 'pattern' => 'recruitment.*', 'icon' => 'recruitment', 'label' => 'Recruitment'],
        ['route' => 'job-positions.index', 'pattern' => 'job-positions.*', 'icon' => 'positions', 'label' => 'Open Job Positions'],
        ['route' => 'calendar.index', 'pattern' => 'calendar.*', 'icon' => 'calendar', 'label' => 'Calendar'],
        ['route' => 'audit.index', 'pattern' => 'audit.*', 'icon' => 'audit', 'label' => 'Audit Logs'],
    ];
    $comingSoonItems = [];
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
                    @foreach ($pageItems as $item)
                        @php($isCurrent = request()->routeIs($item['pattern']))
                        <li class="nav-item">
                            <a class="nav-link{{ $isCurrent ? ' active' : '' }}"
                                aria-current="{{ $isCurrent ? 'page' : 'false' }}" href="{{ route($item['route']) }}">
                                <x-icon :name="$item['icon']" />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach

                    @foreach ($comingSoonItems as $icon => $label)
                        <li class="nav-item">
                            <button type="button" class="nav-link" data-coming-soon="{{ $label }}">
                                <x-icon :name="$icon" />
                                {{ $label }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="app-sidebar-footer">
                <a class="app-sidebar-logout" href="{{ route('login') }}" data-logout>
                    <x-icon name="logout" />
                    Logout
                </a>
            </div>
        </div>
    </div>
</aside>
