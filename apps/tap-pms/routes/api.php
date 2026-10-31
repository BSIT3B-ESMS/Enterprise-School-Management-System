<?php
use App\Http\Controllers\PmsSyncController;
use App\Http\Controllers\PmsUploadController;
use App\Http\Middleware\LocalDevelopmentOnly;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function (): void {
    Route::middleware(\App\Http\Middleware\AppToken::class.':payroll-periods:read')->get('/payroll-periods', [\App\Http\Controllers\PayrollApiController::class, 'periods']);
    Route::middleware([\App\Http\Middleware\AppToken::class.':payroll-periods:write', \App\Http\Middleware\IdempotentPost::class])->post('/payroll-periods', [\App\Http\Controllers\PayrollApiController::class, 'createPeriod']);
    Route::middleware(\App\Http\Middleware\AppToken::class.':payslips:read')->get('/payslips', [\App\Http\Controllers\PayrollApiController::class, 'payslips']);
    Route::middleware(\App\Http\Middleware\AppToken::class.':payslips:read')->get('/payslips/{payslipId}', [\App\Http\Controllers\PayrollApiController::class, 'payslip']);
    Route::middleware([\App\Http\Middleware\AppToken::class.':payslips:write', \App\Http\Middleware\IdempotentPost::class])->post('/payslips', [\App\Http\Controllers\PayrollApiController::class, 'createPayslip']);
});
