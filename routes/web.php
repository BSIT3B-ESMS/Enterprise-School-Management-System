<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

Route::view('/login', 'auth.login')->name('login');

Route::view('/dashboard', 'dashboard')->name('dashboard');

Route::view('/employees', 'employees.index')->name('employees.index');

Route::view('/employees/create', 'employees.create')->name('employees.create');

Route::view('/departments', 'departments.index')->name('departments.index');

Route::view('/job-titles', 'job-titles.index')->name('job-titles.index');

Route::view('/recruitment', 'recruitment.index')->name('recruitment.index');

Route::view('/audit-logs', 'audit.index')->name('audit.index');
