<?php

use Illuminate\Support\Facades\Route;

// The EMS opens on PLP Careers, the public careers website; HR and employees sign in at /login as before.
Route::get('/', fn () => redirect()->route('careers.index'))->name('home');

Route::view('/login', 'auth.login')->name('login');

Route::view('/dashboard', 'dashboard')->name('dashboard');

Route::view('/employees', 'employees.index')->name('employees.index');

Route::view('/employees/create', 'employees.create')->name('employees.create');

// Reads the Employees list's search, filters, and sort from its query string (see resources/js/employee-list.js).
Route::view('/employees/report', 'employees.report')->name('employees.report');

// Employees HR has archived: restored to the Employees list, or deleted for good, from here (resources/js/archived-employees.js).
Route::view('/employees/archived', 'employees.archived')->name('employees.archived');

// The profile is filled in by resources/js/employee-profile.js from the mock data, so only the ID is passed.
Route::view('/employees/{employee}', 'employees.show')
    ->where('employee', 'EMP-[0-9]+')
    ->name('employees.show');

Route::view('/departments', 'departments.index')->name('departments.index');

Route::view('/job-titles', 'job-titles.index')->name('job-titles.index');

Route::view('/recruitment', 'recruitment.index')->name('recruitment.index');

// HR manages the job postings here (add, view, edit, open, close, remove); the open ones are the positions on the public
// Careers website, because both read the same job title records (resources/js/job-positions.js).
Route::view('/job-positions', 'job-positions.index')->name('job-positions.index');

Route::view('/calendar', 'calendar.index')->name('calendar.index');

Route::view('/audit-logs', 'audit.index')->name('audit.index');

// The signed-in HR user's own profile, filled in by resources/js/hr-profile.js.
Route::view('/profile', 'profile.show')->name('profile.show');

// The HR user's Account Settings (appearance and notification preferences), separate from My Profile and its
// Security section; filled in by resources/js/account-settings.js.
Route::view('/account-settings', 'account.settings')->name('account.settings');

// The public Careers website: open positions, job details, benefits, and contact details anyone can browse without an
// account, filled in by resources/js/applicant-positions.js from the job titles HR manages. Create Account and Apply
// Now ask for the Terms and Conditions first.
Route::view('/careers', 'careers.index')->name('careers.index');

Route::view('/careers/{position}', 'careers.show')
    ->where('position', 't[0-9]+')
    ->name('careers.show');

// The Applicant interface. Create Account (resources/js/applicant-auth.js) adds a mock account to the shared mock data
// after the Terms and Conditions are accepted and signs it in for the browser tab's session only
// (resources/js/applicant-session.js). Signing in again goes through the shared Login page. The other pages are filled
// in from the mock data for the signed-in account and send anyone who is not signed in to Login
// (resources/js/applicant-portal.js).
Route::redirect('/applicant', '/applicant/dashboard');

Route::prefix('applicant')->name('applicant.')->group(function () {
    // The public Applicant Portal Apply leads to: Create Account (after the Terms and Conditions) or Login, with the
    // chosen position kept in ?position=.
    Route::view('/portal', 'applicant.portal')->name('portal');

    Route::view('/register', 'applicant.register')->name('register');

    Route::view('/dashboard', 'applicant.dashboard')->name('dashboard');

    Route::view('/profile', 'applicant.profile')->name('profile');

    Route::view('/calendar', 'applicant.calendar')->name('calendar');

    Route::view('/positions', 'applicant.positions')->name('positions');

    Route::view('/positions/{position}', 'applicant.position')
        ->where('position', 't[0-9]+')
        ->name('positions.show');

    Route::view('/apply', 'applicant.apply')->name('apply');

    Route::view('/applications', 'applicant.applications')->name('applications');

    Route::view('/applications/{application}', 'applicant.application')
        ->where('application', 'c[0-9]+')
        ->name('applications.show');
});

// The Employee interface: a separate dashboard and sidebar from the HR pages above, filled in from the
// mock data by resources/js/employee-shell.js and employee-dashboard.js.
Route::redirect('/employee', '/employee/dashboard');

Route::prefix('employee')->name('employee.')->group(function () {
    Route::view('/dashboard', 'employee.dashboard')->name('dashboard');

    Route::view('/profile', 'employee.profile')->name('profile');

    Route::view('/files', 'employee.files')->name('files');

    Route::view('/benefits', 'employee.benefits')->name('benefits');

    Route::view('/directory', 'employee.directory')->name('directory');

    Route::view('/calendar', 'employee.calendar')->name('calendar');
});
