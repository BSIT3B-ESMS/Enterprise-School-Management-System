@extends('layouts.public')

@section('title', 'Careers · '.config('app.name'))

@php
    $steps = [
        ['title' => 'Create an account', 'about' => 'Accept the Terms and Conditions, then set up your applicant account.'],
        ['title' => 'Apply for a position', 'about' => 'Fill in the application form once; your details are kept for your next application.'],
        ['title' => 'Follow your application', 'about' => 'See your status and the screening and interview dates HR sets for you.'],
    ];
@endphp

{{--
    The public Careers website: anyone can browse without an account. The open positions and the benefits come from the
    same job titles HR manages (resources/js/applicant-positions.js). The contact details are PLP's own, as published on
    plpasig.edu.ph; nothing here is made up.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-positions" data-positions-context="public"
        data-details-url="{{ route('careers.index') }}"
        data-register-url="{{ route('applicant.register') }}" data-apply-url="{{ route('applicant.apply') }}">
        <section class="card employee-form-card">
            <p class="text-body-secondary small text-uppercase fw-semibold mb-1">Pamantasan ng Lungsod ng Pasig</p>
            <h1 class="page-title">Careers at PLP</h1>
            <p class="text-body-secondary mt-2 mb-3">
                Join our faculty and staff. Browse the open positions, read what each involves, and apply online.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="#positions-open-title">View Open Positions</a>
                <a class="btn btn-outline-primary" href="{{ route('applicant.register') }}" data-terms-continue>Create Account</a>
            </div>
        </section>

        <x-applicant-position-list />

        <section class="card dashboard-panel" aria-labelledby="careers-benefits-title">
            <h2 id="careers-benefits-title">Benefits of working at PLP</h2>
            <p class="dashboard-panel-subtitle">Offered with the open positions; each position lists the benefits it includes</p>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-careers-benefits></div>
        </section>

        <section class="card dashboard-panel" aria-labelledby="careers-steps-title">
            <h2 id="careers-steps-title">How applying works</h2>
            <p class="dashboard-panel-subtitle">Apply online and follow your application in one place</p>

            <ol class="row row-cols-1 row-cols-md-3 g-3 list-unstyled mb-0">
                @foreach ($steps as $step)
                    <li class="col">
                        <div class="quick-link">
                            <span class="quick-link-label">{{ $loop->iteration }}. {{ $step['title'] }}</span>
                            <small>{{ $step['about'] }}</small>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="card dashboard-panel" aria-labelledby="careers-contact-title">
            <h2 id="careers-contact-title">Contact us</h2>
            <p class="dashboard-panel-subtitle">Pamantasan ng Lungsod ng Pasig</p>

            <dl class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3 employee-details">
                <div class="col"><dt>Address</dt><dd class="mb-0">12-B Alcalde Jose, Pasig, 1600 Metro Manila</dd></div>
                <div class="col"><dt>Phone</dt><dd class="mb-0"><a href="tel:+63286431014">2-8643-1014</a></dd></div>
                <div class="col"><dt>Email</dt><dd class="mb-0"><a href="mailto:inquiry@plpasig.edu.ph">inquiry@plpasig.edu.ph</a></dd></div>
                <div class="col"><dt>Website</dt><dd class="mb-0"><a href="https://plpasig.edu.ph" target="_blank" rel="noopener">plpasig.edu.ph</a></dd></div>
            </dl>

            <p class="small text-body-secondary mb-0">Contact details as published on the official PLP website.</p>
        </section>
    </div>
@endsection
