<?php

use Illuminate\Support\Facades\Route;

// Applicant portal
Route::view('/', 'applicant')->name('applicant');

// Admission office panel
Route::view('/admin', 'admin.index')->name('admin');

// Old static-site URLs
Route::redirect('/index.html', '/', 301);
Route::redirect('/admin/index.html', '/admin', 301);
