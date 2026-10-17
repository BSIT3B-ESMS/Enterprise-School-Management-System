<?php

use App\Http\Controllers\Api\Applicant\ApplicationController;
use App\Http\Controllers\Api\Applicant\DocumentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Integration\EnrollmentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Clinic;
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

// Other ERP systems (architecture diagram: REST + X-API-Key, IP allowlist at Nginx)
Route::prefix('integrations')->middleware('throttle:api')->group(function () {
    Route::post('enrollment/student-number', [EnrollmentController::class, 'studentNumber'])->middleware('integration:enrollment');
});

// In-app notifications for any signed-in user
Route::prefix('notifications')->middleware(['auth:api', 'active'])->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('read-all', [NotificationController::class, 'readAll']);
    Route::post('{id}/read', [NotificationController::class, 'read']);
});

// Applicant portal: the signed-in applicant's own application only
Route::prefix('applicant')->middleware(['auth:api', 'active', 'role:applicant'])->group(function () {
    Route::get('portal', [ApplicationController::class, 'show']);
    Route::put('application', [ApplicationController::class, 'update']);
    Route::put('profile', [ApplicationController::class, 'updateProfile']);
    Route::post('submit', [ApplicationController::class, 'submit']);
    Route::post('offer/accept', [ApplicationController::class, 'acceptOffer']);

    Route::post('documents/{key}', [DocumentController::class, 'store'])->middleware('throttle:uploads');
    Route::delete('documents/{key}', [DocumentController::class, 'destroy']);
    Route::get('documents/{key}/file', [DocumentController::class, 'file']);

    Route::post('medical/submit', [DocumentController::class, 'submitMedical']);
    Route::post('medical/{key}', [DocumentController::class, 'storeMedical'])->middleware('throttle:uploads');
    Route::delete('medical/{key}', [DocumentController::class, 'destroyMedical']);
});

// Admission office panel: admission personnel only. Medical clearance shows
// there read-only; the clinic has its own portal below. There are no routes
// here for accounts, backups or security settings (NFR-01).
Route::prefix('admin')->middleware(['auth:api', 'active', 'role:admission'])->group(function () {
    Route::get('state', [Office\StateController::class, 'show']);

    Route::prefix('applicants/{application}')->group(function () {
        Route::patch('documents/{key}', [Office\DocumentController::class, 'review']);
        Route::post('documents/verify-pending', [Office\DocumentController::class, 'verifyPending']);
        Route::get('documents/{key}/file', [Office\DocumentController::class, 'file']);
    });

    Route::post('applicants/{application}/approve', [Office\ExamController::class, 'approve']);
    Route::post('applicants/{application}/reschedule', [Office\ExamController::class, 'reschedule']);
    Route::patch('applicants/{application}/exam', [Office\ExamController::class, 'roster']);
    Route::post('applicants/{application}/result', [Office\ExamController::class, 'recordResult']);
    Route::post('results/release', [Office\ExamController::class, 'release']);
    Route::post('exam-sessions', [Office\ExamController::class, 'storeSession']);

    Route::post('programs', [Office\SetupController::class, 'storeProgram']);
    Route::patch('programs/{program:code}', [Office\SetupController::class, 'updateProgram']);
    Route::patch('requirements/{key}', [Office\SetupController::class, 'updateRequirement']);
    Route::patch('admission-period', [Office\SetupController::class, 'updatePeriod']);
});

// Clinic portal (nurse/): the clinic nurse sees medical files only, sends a
// file back or approves. Medical files are confidential to the clinic.
Route::prefix('clinic')->middleware(['auth:api', 'active', 'role:clinic'])->group(function () {
    Route::get('state', [Clinic\MedicalController::class, 'state']);

    Route::prefix('applicants/{application}/medical')->group(function () {
        Route::post('approve', [Clinic\MedicalController::class, 'approve']);
        Route::get('{key}/file', [Clinic\MedicalController::class, 'file']);
        Route::patch('{key}', [Clinic\MedicalController::class, 'review']);
    });
});
