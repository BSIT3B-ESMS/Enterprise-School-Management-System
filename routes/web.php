<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\EmergencyContactController;
use App\Http\Controllers\EmployeeAssignmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeReportController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\ExportLogController;
use App\Http\Controllers\GovernmentIdController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\RateTierController;
use App\Http\Controllers\TwoFactorSetupController;
use Illuminate\Support\Facades\Route;

// The EMS opens on PLP Careers, the public careers website; HR and employees sign in at /login as before.
Route::get('/', fn () => redirect()->route('careers.index'))->name('home');

// The sign-in page, sign-out, two-factor challenge, and password confirmation are Laravel Fortify routes
// (app/Providers/FortifyServiceProvider.php). Staff sign in there; applicants still use the mock sign-in in
// resources/js/login.js until the Recruitment phase gives them real accounts.

// Pages for any signed-in staff account, before the temporary-password and two-factor checks: choosing a password and
// setting up the authenticator app must stay reachable.
Route::middleware('account')->group(function () {
    Route::get('/account/password', [PasswordChangeController::class, 'show'])->name('password.change');

    Route::put('/account/password', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::get('/account/two-factor', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
});

// HR pages (HR Head and HR Staff). These still read the browser's mock data until each page moves to the backend.
Route::middleware(['staff', 'role:hr_head,hr_staff'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Employees come from the database. Everyone in HR reads, adds, and edits them; the actions that change an
    // assignment, archive a record, or reveal a government ID are limited to the HR Head by EmployeePolicy.
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');

    Route::prefix('/employees/{employee}')->where(['employee' => 'EMP-[0-9]+'])->group(function () {
        Route::get('/', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('/transfer', [EmployeeAssignmentController::class, 'transfer'])->name('employees.transfer');
        Route::put('/step', [EmployeeAssignmentController::class, 'step'])->name('employees.step');
        Route::put('/status', [EmployeeStatusController::class, 'update'])->name('employees.status');
        Route::post('/emergency-contacts', [EmergencyContactController::class, 'store'])->name('emergency-contacts.store');
        Route::delete('/emergency-contacts/{contact}', [EmergencyContactController::class, 'destroy'])->name('emergency-contacts.destroy');
        Route::post('/government-ids/{type}/reveal', [GovernmentIdController::class, 'reveal'])->name('government-ids.reveal');
    });

    // Colleges, offices, job titles, and pay rates: everyone in HR reads them; the HR Head changes them (the
    // controllers and form requests check the manage-reference-data gate).
    Route::get('/colleges', [CollegeController::class, 'index'])->name('colleges.index');
    Route::post('/colleges', [CollegeController::class, 'store'])->name('colleges.store');
    Route::get('/colleges/{college}/edit', [CollegeController::class, 'edit'])->name('colleges.edit');
    Route::put('/colleges/{college}', [CollegeController::class, 'update'])->name('colleges.update');

    Route::get('/offices', [OfficeController::class, 'index'])->name('offices.index');
    Route::post('/offices', [OfficeController::class, 'store'])->name('offices.store');
    Route::get('/offices/{office}/edit', [OfficeController::class, 'edit'])->name('offices.edit');
    Route::put('/offices/{office}', [OfficeController::class, 'update'])->name('offices.update');

    Route::get('/job-titles', [JobTitleController::class, 'index'])->name('job-titles.index');
    Route::get('/job-titles/create', [JobTitleController::class, 'create'])->name('job-titles.create');
    Route::post('/job-titles', [JobTitleController::class, 'store'])->name('job-titles.store');
    Route::get('/job-titles/{jobTitle}/edit', [JobTitleController::class, 'edit'])->name('job-titles.edit');
    Route::put('/job-titles/{jobTitle}', [JobTitleController::class, 'update'])->name('job-titles.update');

    Route::get('/rates', [RateTierController::class, 'index'])->name('rates.index');
    Route::post('/rates', [RateTierController::class, 'store'])->name('rates.store');

    Route::view('/recruitment', 'recruitment.index')->name('recruitment.index');

    // HR manages the job postings here (add, view, edit, open, close, remove); the open ones are the positions on the
    // public Careers website, because both read the same job title records (resources/js/job-positions.js).
    Route::view('/job-positions', 'job-positions.index')->name('job-positions.index');

    Route::view('/calendar', 'calendar.index')->name('calendar.index');

    // The signed-in HR user's own profile, filled in by resources/js/hr-profile.js.
    Route::view('/profile', 'profile.show')->name('profile.show');

    // The HR user's Account Settings (appearance and notification preferences), separate from My Profile and its
    // Security section; filled in by resources/js/account-settings.js.
    Route::view('/account-settings', 'account.settings')->name('account.settings');
});

// HR Head only: reports and exports, the archive (where records are deleted for good), the audit ledger, and account
// administration. HR Staff cannot reach these.
Route::middleware(['staff', 'role:hr_head'])->group(function () {
    // Starts from the Employees list's tab, search, filters, and sort, carried in the query string.
    Route::get('/employees/report', [EmployeeReportController::class, 'show'])->name('employees.report');

    // Every report export is written to the audit log before the file is built.
    Route::post('/exports', [ExportLogController::class, 'store'])->name('exports.store');

    // Employees who were archived (Inactive, Resigned, Retired, or Terminated). Records are never deleted; they can be
    // restored from here.
    Route::get('/employees/archived', [EmployeeStatusController::class, 'index'])->name('employees.archived');

    // Starts from the Recruitment page's status, department, and application date filters, carried in the query string
    // (see resources/js/recruitment-report.js).
    Route::view('/recruitment/report', 'recruitment.report')->name('recruitment.report');

    Route::view('/audit-logs', 'audit.index')->name('audit.index');

    // Starts from the Audit Logs page's action, record type, and date filters, carried in the query string
    // (see resources/js/audit-report.js).
    Route::view('/audit-logs/report', 'audit.report')->name('audit.report');

    Route::prefix('accounts')->name('accounts.')->controller(AccountController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/{user}/reset-password', 'resetPassword')->name('reset-password');
        Route::post('/{user}/deactivate', 'deactivate')->name('deactivate');
        Route::post('/{user}/reactivate', 'reactivate')->name('reactivate');
        Route::put('/{user}/role', 'updateRole')->name('role');
    });
});

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
// mock data by resources/js/employee-shell.js and employee-dashboard.js. Only the Employee role reaches it.
Route::middleware(['staff', 'role:employee'])->group(function () {
    Route::redirect('/employee', '/employee/dashboard');

    Route::prefix('employee')->name('employee.')->group(function () {
        Route::view('/dashboard', 'employee.dashboard')->name('dashboard');

        Route::view('/profile', 'employee.profile')->name('profile');

        Route::view('/files', 'employee.files')->name('files');

        Route::view('/benefits', 'employee.benefits')->name('benefits');

        Route::view('/directory', 'employee.directory')->name('directory');

        Route::view('/calendar', 'employee.calendar')->name('calendar');
    });
});
