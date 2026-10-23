@extends('layouts.employee')

@section('title', 'Dashboard · '.config('app.name'))

@php
    $quickLinks = [
        ['icon' => 'user', 'label' => 'Profile', 'about' => 'Your personal and employment details', 'route' => 'employee.profile'],
        ['icon' => 'folder', 'label' => 'My Files', 'about' => 'Your documents and records', 'route' => 'employee.files'],
        ['icon' => 'gift', 'label' => 'Benefits', 'about' => 'Your benefits and contributions', 'route' => 'employee.benefits'],
        ['icon' => 'employees', 'label' => 'Directory', 'about' => 'Find your colleagues', 'route' => 'employee.directory'],
        ['icon' => 'calendar', 'label' => 'Calendar', 'about' => 'Your leave and schedule', 'route' => 'employee.calendar'],
    ];
@endphp

{{-- Everything is filled in by resources/js/employee-dashboard.js from the signed-in employee's mock data. --}}
@section('content')
    <div class="d-grid gap-4" data-page="employee-dashboard">
        <div>
            <h1 class="page-title" data-portal-greeting>Welcome</h1>
            <p class="text-body-secondary mt-1 mb-0">Here is your information at a glance.</p>
        </div>

        <div class="card d-none" data-portal-missing>
            <div class="employees-empty">
                <strong>Employee record not found</strong>
                Your record may have been removed. Contact HR for help.
            </div>
        </div>

        <div class="d-grid gap-4" data-portal-content>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3" data-portal-kpis></div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="card dashboard-panel">
                        <h2>Upcoming approved leave</h2>
                        <p class="dashboard-panel-subtitle">Leave that is approved and still ahead</p>

                        <ul class="list-group list-group-flush dashboard-list" data-portal-leave></ul>
                        <p class="text-body-secondary mb-0 d-none" data-portal-leave-empty>No approved leave coming up.</p>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="card dashboard-panel">
                        <h2>My information</h2>
                        <p class="dashboard-panel-subtitle">From your employee record</p>

                        <dl class="row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Employee ID</dt><dd class="mb-0 tabular-nums" data-portal-field="id"></dd></div>
                            <div class="col"><dt>Status</dt><dd class="mb-0" data-portal-field="status"></dd></div>
                            <div class="col"><dt>College</dt><dd class="mb-0" data-portal-field="department"></dd></div>
                            <div class="col"><dt>Job Title</dt><dd class="mb-0" data-portal-field="jobTitle"></dd></div>
                            <div class="col"><dt>Date Hired</dt><dd class="mb-0" data-portal-field="hiredOn"></dd></div>
                            <div class="col"><dt>Phone</dt><dd class="mb-0" data-portal-field="phone"></dd></div>
                            <div class="col-12"><dt>Email</dt><dd class="mb-0 text-break" data-portal-field="email"></dd></div>
                        </dl>
                    </section>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="card dashboard-panel">
                        <h2>Quick links</h2>
                        <p class="dashboard-panel-subtitle">Jump to a section</p>

                        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
                            @foreach ($quickLinks as $link)
                                <div class="col">
                                    <a class="quick-link" href="{{ route($link['route']) }}">
                                        <x-icon :name="$link['icon']" />
                                        <span class="quick-link-label">{{ $link['label'] }}</span>
                                        <small>{{ $link['about'] }}</small>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="card dashboard-panel">
                        <h2>My benefits</h2>
                        <p class="dashboard-panel-subtitle">What your job title includes</p>

                        <div class="d-flex flex-wrap gap-2" data-portal-benefits></div>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
