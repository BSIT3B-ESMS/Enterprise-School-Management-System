<?php

use Illuminate\Support\Facades\Route;

// Applicant portal
Route::view('/', 'applicant')->name('applicant');

// Admission office panel
Route::view('/admin', 'admin.index')->name('admin');

// Clinic portal: the clinic nurse's medical-files-only screen
Route::view('/nurse', 'nurse')->name('nurse');

// Old static-site URLs
Route::redirect('/index.html', '/', 301);
Route::redirect('/admin/index.html', '/admin', 301);
Route::redirect('/nurse/index.html', '/nurse', 301);
