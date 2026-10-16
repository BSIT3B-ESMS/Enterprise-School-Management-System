<?php

use App\Http\Controllers\Api\Applicant\ApplicationController;
use App\Http\Controllers\Api\Applicant\DocumentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Office;
use Illuminate\Support\Facades\Route;

/*
| JSON API used by the applicant portal and the admission office panel.
| Nginx routes /api to Laravel (see the architecture diagram).
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('2fa/verify', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');

    Route::middleware(['auth:api', 'active'])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Applicant portal: the signed-in applicant's own application only
Route::prefix('applicant')->middleware(['auth:api', 'active', 'role:applicant'])->group(function () {
    Route::get('portal', [ApplicationController::class, 'show']);
    Route::put('application', [ApplicationController::class, 'update']);
    Route::post('submit', [ApplicationController::class, 'submit']);

    Route::post('documents/{key}', [DocumentController::class, 'store'])->middleware('throttle:uploads');
    Route::delete('documents/{key}', [DocumentController::class, 'destroy']);
    Route::get('documents/{key}/file', [DocumentController::class, 'file']);

    Route::post('medical/submit', [DocumentController::class, 'submitMedical']);
    Route::post('medical/{key}', [DocumentController::class, 'storeMedical'])->middleware('throttle:uploads');
    Route::delete('medical/{key}', [DocumentController::class, 'destroyMedical']);
});

// Admission office panel: admission personnel and the clinic. There are no
// routes here for accounts, backups or security settings (NFR-01).
Route::prefix('admin')->middleware(['auth:api', 'active', 'role:admission|clinic'])->group(function () {
    Route::get('state', [Office\StateController::class, 'show']);

    Route::prefix('applicants/{application}')->group(function () {
        Route::patch('documents/{key}', [Office\DocumentController::class, 'review']);
        Route::post('documents/verify-pending', [Office\DocumentController::class, 'verifyPending']);
        Route::get('documents/{key}/file', [Office\DocumentController::class, 'file']);

        Route::patch('medical/{key}', [Office\DocumentController::class, 'reviewMedical']);
        Route::post('medical/verify-pending', [Office\DocumentController::class, 'verifyPendingMedical']);
    });
});
