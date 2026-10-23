@extends('layouts.admin')

@section('title', 'Dashboard · '.config('app.name'))

{{-- Figures, charts, and lists are filled in by resources/js/dashboard.js from resources/js/mock-data.js. --}}
@section('content')
    <div class="d-grid gap-4" data-page="dashboard" data-employees-url="{{ url('/employees') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title" data-greeting>Welcome</h1>
                <p class="text-body-secondary mt-1 mb-0">Here is where the workforce stands today.</p>
            </div>

            <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="{{ route('employees.create') }}">
                <x-icon name="plus" />
                Add Employee
            </a>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3" data-dashboard-kpis></div>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card dashboard-panel">
                    <h2>Headcount by college</h2>
                    <p class="dashboard-panel-subtitle">Employees per academic unit</p>

                    <div class="d-grid gap-2" data-dashboard-headcounts></div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="card dashboard-panel">
                    <h2>Recruitment pipeline</h2>
                    <p class="dashboard-panel-subtitle" data-dashboard-candidate-total></p>

                    <div class="progress-stacked dashboard-pipeline" data-dashboard-pipeline></div>

                    <ul class="list-unstyled d-grid gap-2 mb-0" data-dashboard-pipeline-legend></ul>
                </section>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card dashboard-panel">
                    <h2>Hires per year</h2>
                    <p class="dashboard-panel-subtitle">Based on date hired</p>

                    <div data-dashboard-hires-chart></div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="card dashboard-panel">
                    <h2>Recent hires</h2>
                    <p class="dashboard-panel-subtitle">Latest additions to the roster</p>

                    <ul class="list-group list-group-flush dashboard-list" data-dashboard-recent-hires></ul>
                </section>
            </div>
        </div>

        <section class="card dashboard-panel">
            <h2>Recent activity</h2>
            <p class="dashboard-panel-subtitle">Latest changes made in the system</p>

            <ul class="list-group list-group-flush dashboard-list" data-dashboard-activity></ul>

            <div class="mt-2">
                <a class="btn btn-link p-0" href="{{ route('audit.index') }}">View all audit logs</a>
            </div>
        </section>
    </div>
@endsection
