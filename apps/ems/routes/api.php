<?php

use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ReferenceController;
use Illuminate\Support\Facades\Route;

// The read-only API for external systems such as Payroll, served at /api/v1 with one bearer token per consumer.
Route::middleware('throttle:ems-api')->name('api.v1.')->group(function () {
    Route::middleware('api.token:employees:read')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{employeeNo}', [EmployeeController::class, 'show'])->where('employeeNo', '[0-9]{2}-[0-9]{5}')->name('employees.show');
        Route::get('/employees/{employeeNo}/status', [EmployeeController::class, 'status'])->where('employeeNo', '[0-9]{2}-[0-9]{5}')->name('employees.status');
    });

    Route::middleware('api.token:reference:read')->group(function () {
        Route::get('/colleges', [ReferenceController::class, 'colleges'])->name('colleges');
        Route::get('/job-titles', [ReferenceController::class, 'jobTitles'])->name('job-titles');
        Route::get('/rate-tiers', [ReferenceController::class, 'rateTiers'])->name('rate-tiers');
    });
});
