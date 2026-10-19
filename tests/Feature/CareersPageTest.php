<?php

it('lays out PLP Careers in order: navigation, hero, featured openings, open positions, about, and footer', function () {
    $this->get(route('careers.index'))
        ->assertOk()
        ->assertSee('PLP Careers')
        ->assertSeeInOrder([
            // Navigation bar
            'class="public-header"', 'PLP Careers', 'Pamantasan ng Lungsod ng Pasig',
            '>Home</a>', '#open-positions" data-public-nav="positions">Open Positions</a>', '#about" data-public-nav="about">About</a>',
            'href="'.route('login').'">Login</a>',
            // Hero
            'Build Your Career with PLP', 'href="#open-positions" data-careers-explore>Explore Open Positions',
            // Featured Job Openings
            'id="featured"', 'Featured Job Openings', 'data-positions-featured',
            // Open Positions
            'id="open-positions"', 'Open Positions', 'data-positions-search', 'data-positions-type', 'data-positions-open>',
            // About PLP Careers
            'id="about"', 'About PLP Careers', 'How applying works', 'Benefits of working at PLP', 'data-careers-benefits',
            // Footer
            'class="public-footer"', 'Contact', 'Explore',
        ], escape: false)
        ->assertDontSee('data-app-shell', escape: false);
});

it('marks Home as the current page and folds the links into a menu on narrow screens', function () {
    $this->get(route('careers.index'))
        ->assertSeeInOrder(['class="nav-link active" aria-current="page"', 'href="'.route('careers.index').'" data-public-nav="home">Home</a>'], escape: false)
        ->assertSee('data-bs-toggle="collapse" data-bs-target="#public-nav"', escape: false)
        ->assertSee('id="public-nav"', escape: false);
});

it('offers only Full-Time and Part-Time in the Employment Type filter', function (string $route) {
    $html = $this->get(route($route))->getContent();
    $filter = substr($html, strpos($html, 'data-positions-type'), strpos($html, '</select>', strpos($html, 'data-positions-type')) - strpos($html, 'data-positions-type'));

    expect($filter)->toContain('All Employment Types', '<option value="Full-time">Full-Time</option>', '<option value="Part-time">Part-Time</option>')
        ->and(substr_count($filter, '<option'))->toBe(3);
})->with(['careers.index', 'applicant.positions']);

it('shows the same navigation and footer on the other public pages', function (string $route) {
    $this->get(route($route, ['position' => 't5']))
        ->assertSee('class="public-header"', escape: false)
        ->assertSee('class="public-footer"', escape: false)
        ->assertSee('href="'.route('careers.index').'#open-positions"', escape: false);
})->with(['careers.show', 'applicant.portal']);

it('shows PLP\'s published contact details on the Careers website', function () {
    $this->get(route('careers.index'))
        ->assertSeeInOrder([
            '12-B Alcalde Jose, Pasig, 1600 Metro Manila',
            'href="tel:+63286431014">2-8643-1014',
            'href="mailto:inquiry@plpasig.edu.ph"',
            'href="https://plpasig.edu.ph"',
            'Contact details as published on the official PLP website.',
        ], escape: false);
});

it('serves Create Account with Login here back to the shared Login page and continues to the dashboard', function () {
    $this->get(route('applicant.register', ['position' => 't5']))
        ->assertOk()
        ->assertSee('data-page="applicant-register"', escape: false)
        ->assertSee('data-back-url="'.route('careers.index').'"', escape: false)
        ->assertSee('novalidate data-applicant-register-form', escape: false)
        ->assertSee('data-dashboard-url="'.route('applicant.dashboard').'"', escape: false)
        ->assertSeeInOrder([
            'Create an Applicant Account',
            'data-auth-position',
            'name="name"', 'name="email"', 'name="password"', 'name="confirmPassword"',
            'Create Account</button>',
            'Already have an account?', 'href="'.route('login').'" data-login-here>Login here</a>',
            'href="'.route('careers.index').'">Back to Careers',
        ], escape: false);
});

it('has no separate applicant login page', function () {
    $this->get('/applicant/login')->assertNotFound();
    $this->get('/jobs')->assertNotFound();
});

it('lays the open positions out three to a row on larger screens', function (string $route) {
    $this->get(route($route))
        ->assertSee('row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3" data-positions-open', escape: false);
})->with(['careers.index', 'applicant.positions']);
