@php
    $items = [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'route' => 'employee.dashboard'],
        ['icon' => 'folder', 'label' => 'My Files', 'route' => 'employee.files'],
        ['icon' => 'gift', 'label' => 'Benefits', 'route' => 'employee.benefits'],
        ['icon' => 'employees', 'label' => 'Directory', 'route' => 'employee.directory'],
        ['icon' => 'calendar', 'label' => 'Calendar', 'route' => 'employee.calendar'],
        // Its own page: the Calendar route with ?view=leave-requests (the Calendar item is current only without it).
        ['icon' => 'calendar', 'label' => 'Leave Request', 'route' => 'employee.calendar', 'query' => ['view' => 'leave-requests']],
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
                        @php($isCurrent = request()->routeIs($item['route'])
                            && (request()->query('view') === 'leave-requests') === isset($item['query']))
                        <li class="nav-item">
                            <a class="nav-link{{ $isCurrent ? ' active' : '' }}"
                                aria-current="{{ $isCurrent ? 'page' : 'false' }}" href="{{ route($item['route'], $item['query'] ?? []) }}">
                                <x-icon :name="$item['icon']" />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="app-sidebar-footer">
                <a class="app-sidebar-logout" href="{{ route('home') }}">
                    <x-icon name="logout" />
                    Logout
                </a>
            </div>
        </div>
    </div>
</aside>
