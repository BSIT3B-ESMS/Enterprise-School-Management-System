@extends('layouts.public')

@section('title', 'PLP Careers · '.config('app.name'))

@php
    $steps = [
        ['title' => 'Create an account', 'about' => 'Accept the Terms and Conditions, then set up your applicant account.'],
        ['title' => 'Apply for a position', 'about' => 'Fill in the application form once; your details are kept for your next application.'],
        ['title' => 'Follow your application', 'about' => 'See your status and the demo interview and interview dates HR sets for you.'],
    ];
@endphp

{{--
    PLP Careers, the EMS's landing page (the root address opens it): anyone can browse without an account. In order: the
    hero, Featured Job Openings (the open positions with the most openings), every open position with a search and an
    Employment Type filter, and About PLP Careers; the navigation bar and footer (with PLP's contact details, as published
    on plpasig.edu.ph) come from layouts/public.blade.php. The positions and benefits come from the same job titles HR
    manages (resources/js/applicant-positions.js), so only positions HR has open are listed, and View Details and Apply
    follow the existing flows (Apply asks for the Terms and Conditions, then opens Create Account with the position kept).
--}}
@section('content')
    <div class="d-grid gap-5" data-page="applicant-positions" data-positions-context="public"
        data-details-url="{{ route('careers.index') }}"
        data-register-url="{{ route('applicant.register') }}" data-apply-url="{{ route('applicant.apply') }}">

        {{-- Hero --}}
        <section class="careers-hero" aria-labelledby="careers-hero-title">
            <p class="careers-hero-eyebrow">Pamantasan ng Lungsod ng Pasig</p>
            <h1 class="careers-hero-title" id="careers-hero-title">Build Your Career with PLP</h1>
            <p class="careers-hero-lead">
                Explore teaching and non-teaching opportunities at Pamantasan ng Lungsod ng Pasig. See what each role involves
                and offers, and apply online.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-light btn-lg" href="#open-positions" data-careers-explore>Explore Open Positions</a>
                <a class="btn btn-outline-light btn-lg" href="{{ route('applicant.register') }}" data-terms-continue>Create Account</a>
            </div>
            <dl class="careers-hero-stats" aria-label="Hiring right now">
                <div><dt>Open positions</dt><dd class="tabular-nums" data-careers-stat="positions">0</dd></div>
                <div><dt>Departments hiring</dt><dd class="tabular-nums" data-careers-stat="departments">0</dd></div>
                <div><dt>Openings in all</dt><dd class="tabular-nums" data-careers-stat="openings">0</dd></div>
            </dl>
        </section>

        {{-- Featured Job Openings --}}
        <section class="careers-section" id="featured" aria-labelledby="careers-featured-title">
            <div class="careers-section-header">
                <h2 class="careers-section-title" id="careers-featured-title">Featured Job Openings</h2>
                <p class="text-body-secondary mb-0">The open positions with the most openings right now.</p>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" data-positions-featured></div>
            <p class="text-body-secondary mb-0 d-none" data-positions-featured-empty>No positions are open right now. Please check back later.</p>
        </section>

        {{-- Open Positions --}}
        <section class="careers-section" id="open-positions" aria-label="Open Positions">
            <x-applicant-position-list title="Open Positions" subtitle="Every position currently accepting applications." />
        </section>

        {{-- About PLP Careers --}}
        <section class="careers-section" id="about" aria-labelledby="careers-about-title">
            <div class="careers-section-header">
                <h2 class="careers-section-title" id="careers-about-title">About PLP Careers</h2>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <p>
                        Pamantasan ng Lungsod ng Pasig (PLP) is the local university of the City of Pasig. PLP Careers is where
                        the university lists its open teaching and non-teaching positions.
                    </p>
                    <p class="mb-0">
                        Every listing shows the department, employment type, location, and base salary range, with the
                        responsibilities, qualifications, and requirements, so you know what a role involves before you apply.
                        After you apply, you can follow your application and your interview schedule from your Applicant
                        Dashboard.
                    </p>
                </div>

                <div class="col-lg-7">
                    <h3 class="fs-5 mb-3" id="careers-steps-title">How applying works</h3>
                    <ol class="row row-cols-1 row-cols-md-3 g-3 list-unstyled mb-0" aria-labelledby="careers-steps-title">
                        @foreach ($steps as $step)
                            <li class="col">
                                <div class="quick-link">
                                    <span class="quick-link-label">{{ $loop->iteration }}. {{ $step['title'] }}</span>
                                    <small>{{ $step['about'] }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <h3 class="fs-5 mt-4 mb-1" id="careers-benefits-title">Benefits of working at PLP</h3>
            <p class="text-body-secondary">Offered with the open positions; each position lists the benefits it includes.</p>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" aria-labelledby="careers-benefits-title" data-careers-benefits></div>
        </section>
    </div>
@endsection
