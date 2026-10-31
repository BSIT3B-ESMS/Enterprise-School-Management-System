<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,PortalController,WorkController,KioskController};
use App\Http\Middleware\StaffSession;
Route::get('/setup',[AuthController::class,'setupPage'])->name('setup');
Route::post('/setup',[AuthController::class,'setup'])->middleware('throttle:5,1');
Route::get('/login',[AuthController::class,'loginPage'])->name('login');
Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1');
Route::middleware(StaffSession::class)->group(function(){
 Route::post('/logout',[AuthController::class,'logout'])->name('logout');
 Route::get('/mfa',[AuthController::class,'mfa'])->name('mfa.show');
 Route::post('/mfa',[AuthController::class,'verifyMfa'])->middleware('throttle:5,1')->name('mfa.verify');
 Route::get('/',[PortalController::class,'index']);
 Route::post('/account/password',[WorkController::class,'password']);
 Route::get('/payslips/{id}',[PortalController::class,'payslip']);
 Route::get('/evidence/{kind}/{id}',[WorkController::class,'evidence']);
});
Route::middleware(StaffSession::class.':employee')->post('/requests',[WorkController::class,'request'])->middleware('throttle:30,1');
Route::middleware(StaffSession::class.':admin')->group(function(){
 Route::post('/employees/{employee}/schedule',[WorkController::class,'schedule']);
 Route::post('/employees/{employee}/contribution-profile',[WorkController::class,'contributionProfile']);
 Route::post('/employees/{employee}/deductions',[WorkController::class,'deductionOverride']);
 Route::post('/employees/{employee}/card',[WorkController::class,'card']);
 Route::post('/employees/{employee}/account',[WorkController::class,'account']);
 Route::post('/requests/{workflowRequest}/decision',[WorkController::class,'decide']);
 Route::post('/requests/{workflowRequest}/send-to-ems',[WorkController::class,'sendLeave']);
 Route::post('/payroll/periods/{period}/release-all',[PortalController::class,'releaseAll']);
 Route::post('/payroll/compute',[PortalController::class,'compute']);
 Route::post('/payroll/{payslip}/release',[PortalController::class,'release']);
 Route::post('/payroll/adjustments',[WorkController::class,'adjustment']);
 Route::post('/settings/policy',[WorkController::class,'policy']);
 Route::post('/devices',[WorkController::class,'device']);
 Route::post('/kiosk/authorize',[KioskController::class,'authorizeBrowser']);
 Route::post('/devices/{device}/revoke',[WorkController::class,'revoke']);
 Route::post('/ems/sync',[WorkController::class,'sync'])->middleware('throttle:5,1');
 Route::post('/ems/roster-import',\App\Http\Controllers\EmsRosterImportController::class)->middleware('throttle:3,1');
 Route::post('/reports',[PortalController::class,'createReport']);
 Route::get('/reports/{id}',[PortalController::class,'report']);
});
Route::get('/kiosk',[KioskController::class,'index']);
Route::post('/kiosk/pair',[KioskController::class,'pair'])->middleware('throttle:5,1');
Route::post('/kiosk/unpair',[KioskController::class,'unpair']);
Route::post('/kiosk/punch',[KioskController::class,'punch'])->middleware('throttle:30,1');

