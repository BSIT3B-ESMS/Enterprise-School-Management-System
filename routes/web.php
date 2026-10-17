<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

Route::view('/login', 'auth.login')->name('login');

Route::view('/dashboard', 'dashboard')->name('dashboard');

Route::view('/employees', 'employees.index')->name('employees.index');

Route::view('/employees/create', 'employees.create')->name('employees.create');

// Reads the Employees list's search, filters, and sort from its query string (see resources/js/employee-list.js).
Route::view('/employees/report', 'employees.report')->name('employees.report');

// The profile is filled in by resources/js/employee-profile.js from the mock data, so only the ID is passed.
Route::view('/employees/{employee}', 'employees.show')
    ->where('employee', 'EMP-[0-9]+')
    ->name('employees.show');

Route::view('/departments', 'departments.index')->name('departments.index');

Route::view('/job-titles', 'job-titles.index')->name('job-titles.index');

Route::view('/recruitment', 'recruitment.index')->name('recruitment.index');

Route::view('/calendar', 'calendar.index')->name('calendar.index');

Route::view('/audit-logs', 'audit.index')->name('audit.index');

// The signed-in HR user's own profile, filled in by resources/js/hr-profile.js.
Route::view('/profile', 'profile.show')->name('profile.show');

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
