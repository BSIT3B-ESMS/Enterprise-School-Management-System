<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
Route::pattern('resource','[a-z_]+');
Route::pattern('id','[0-9]+');
Route::middleware(['auth','password.changed','role:registrar'])->group(function(){
 Route::get('/term-calendar',[\App\Http\Controllers\ReferenceWorkspaceController::class,'calendar']);
 Route::post('/term-calendar',[\App\Http\Controllers\ReferenceWorkspaceController::class,'createTerm']);
 Route::post('/term-calendar/{id}',[\App\Http\Controllers\ReferenceWorkspaceController::class,'term']);
 Route::get('/students',[\App\Http\Controllers\ReferenceWorkspaceController::class,'students']);
 Route::get('/admission-years',[\App\Http\Controllers\ReferenceWorkspaceController::class,'students']);
 Route::get('/academic-years',[\App\Http\Controllers\ReferenceWorkspaceController::class,'academicYears']);
 Route::post('/sections/send',[\App\Http\Controllers\ReferenceWorkspaceController::class,'sendSections']);
 Route::post('/sections/promote',[\App\Http\Controllers\ReferenceWorkspaceController::class,'promoteSections']);
 Route::post('/students/actions',[\App\Http\Controllers\ReferenceWorkspaceController::class,'studentAction']);
 Route::get('/sections',[\App\Http\Controllers\ReferenceWorkspaceController::class,'sections']);
 Route::post('/sections',[\App\Http\Controllers\ReferenceWorkspaceController::class,'section']);
 Route::post('/sections/{id}',[\App\Http\Controllers\ReferenceWorkspaceController::class,'section']);
 Route::get('/colleges',[\App\Http\Controllers\ReferenceWorkspaceController::class,'colleges']);
 Route::get('/special-enrollment',[\App\Http\Controllers\ReferenceWorkspaceController::class,'specialEnrollment']);
 Route::post('/special-enrollment',[\App\Http\Controllers\ReferenceWorkspaceController::class,'sendSpecial']);
 Route::get('/scheduler/rooms',[\App\Http\Controllers\SchedulerController::class,'rooms']);
 Route::post('/scheduler/book',[\App\Http\Controllers\SchedulerController::class,'book']);
});
Route::get('/special-students',[\App\Http\Controllers\ReferenceWorkspaceController::class,'students'])->middleware(['auth','password.changed','role:department_head']);
Route::middleware(['auth','password.changed','role:student'])->group(function(){
 Route::get('/waiting-list',[\App\Http\Controllers\ReferenceWorkspaceController::class,'waiting']);
 Route::post('/waiting-list',[\App\Http\Controllers\ReferenceWorkspaceController::class,'requestSubjects']);
});
Route::middleware(['auth','password.changed','role:professor'])->group(function(){
 Route::get('/professor',[\App\Http\Controllers\ProfessorPortalController::class,'index']);
 Route::post('/professor/classes/{id}/grades',[\App\Http\Controllers\ProfessorPortalController::class,'submit']);
});
Route::get('/grades',[\App\Http\Controllers\ProfessorPortalController::class,'grades'])->middleware(['auth','password.changed','role:student']);
Route::middleware(['auth','password.changed','role:registrar'])->group(function(){
 Route::get('/settings',[\App\Http\Controllers\SettingsController::class,'index']);
 Route::post('/settings',[\App\Http\Controllers\SettingsController::class,'update']);
 Route::post('/imports/reference',function(){app(\App\Services\ReferenceCatalogService::class)->import();return back()->with('status','Supplied 2022–2023 BSIT/BSCS catalog imported. Existing students and curriculum assignments were preserved. Verify room types and permitted teaching days before scheduling.');});
 Route::post('/scheduler/preview',[\App\Http\Controllers\SchedulerController::class,'preview']);
});
Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::view('/login','auth.login')->name('login');
    Route::post('/login', [AuthController::class,'login'])->middleware('throttle:6,1');
});
Route::middleware(['auth','password.changed'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect('/'.str_replace('_','-',auth()->user()->role));
    })->name('dashboard');
    Route::get('/registrar',[\App\Http\Controllers\DashboardController::class,'index'])->middleware('role:registrar')->name('registrar.dashboard');
    Route::get('/department-head',[\App\Http\Controllers\DashboardController::class,'index'])->middleware('role:department_head')->name('department-head.dashboard');
    Route::get('/student',[\App\Http\Controllers\DashboardController::class,'index'])->middleware('role:student')->name('student.dashboard');
    Route::get('/account/password',[\App\Http\Controllers\AccountController::class,'show'])->name('password.edit');
    Route::post('/account/password',[AuthController::class,'password'])->middleware('throttle:6,1')->name('password.update');
    Route::post('/logout',[AuthController::class,'logout'])->name('logout');
});
Route::middleware(['auth','password.changed'])->group(function(){
 Route::get('/enrollments',[\App\Http\Controllers\EnrollmentController::class,'index']);
 Route::post('/enrollments',[\App\Http\Controllers\EnrollmentController::class,'propose'])->middleware('role:registrar,department_head');
 Route::get('/enrollments/{id}',[\App\Http\Controllers\EnrollmentController::class,'show'])->whereNumber('id');
 Route::post('/enrollments/{id}/decision',[\App\Http\Controllers\EnrollmentController::class,'decision'])->middleware('role:student');
 Route::post('/enrollments/{id}/finalize',[\App\Http\Controllers\EnrollmentController::class,'finalize'])->middleware('role:registrar');
 Route::get('/enrollments/{id}/cor',[\App\Http\Controllers\EnrollmentController::class,'cor']);
 Route::get('/special-cases',[\App\Http\Controllers\SpecialCaseController::class,'index']);
 Route::post('/special-cases',[\App\Http\Controllers\SpecialCaseController::class,'store'])->middleware('role:student');
 Route::post('/special-cases/{id}/evaluate',[\App\Http\Controllers\SpecialCaseController::class,'evaluate'])->middleware('role:department_head');
 Route::middleware('role:registrar')->group(function(){
  Route::get('/academic/{resource}',[\App\Http\Controllers\AcademicController::class,'index']);
  Route::post('/academic/{resource}',[\App\Http\Controllers\AcademicController::class,'store']);
  Route::post('/academic/{resource}/{id}/deactivate',[\App\Http\Controllers\AcademicController::class,'deactivate']);
  Route::get('/imports',[\App\Http\Controllers\ImportController::class,'index']);
  Route::post('/imports',[\App\Http\Controllers\ImportController::class,'store'])->middleware('throttle:10,1');
  Route::get('/scheduler',[\App\Http\Controllers\SchedulerController::class,'index']);
 });
});Route::middleware(['auth','password.changed'])->group(function(){
 Route::get('/records',[\App\Http\Controllers\RecordsController::class,'index']);
 Route::get('/records/{id}',[\App\Http\Controllers\RecordsController::class,'show']);
 Route::post('/records/{id}',[\App\Http\Controllers\RecordsController::class,'update'])->middleware('role:registrar');
});Route::middleware('guest')->group(function(){
 Route::view('/forgot-password','auth.recovery')->name('password.request');
 Route::post('/forgot-password',[\App\Http\Controllers\AccountRecoveryController::class,'request'])->middleware('throttle:3,1');
 Route::get('/reset-password/{token}',fn(string $token)=>view('auth.recovery',compact('token')))->name('password.reset');
 Route::post('/reset-password',[\App\Http\Controllers\AccountRecoveryController::class,'reset'])->middleware('throttle:6,1');
});
Route::middleware(['auth','password.changed'])->group(function(){
 Route::get('/activity',[\App\Http\Controllers\ActivityController::class,'index']);
 Route::get('/audit',[\App\Http\Controllers\ActivityController::class,'audit'])->middleware('role:registrar');
 Route::get('/enrollments/{id}/pdf',[\App\Http\Controllers\EnrollmentController::class,'pdf']);
 Route::post('/enrollments/{id}/revise',[\App\Http\Controllers\EnrollmentController::class,'revise'])->middleware('role:registrar');
});
Route::post('/academic/{resource}/{id}',[\App\Http\Controllers\AcademicController::class,'update'])->middleware(['auth','password.changed','role:registrar']);

Route::middleware(['auth','password.changed','role:registrar'])->group(function(){
 Route::get('/professors',[\App\Http\Controllers\DashboardController::class,'professors']);
 Route::get('/staff',[\App\Http\Controllers\StaffController::class,'index']);
 Route::post('/staff',[\App\Http\Controllers\StaffController::class,'store']);
});
Route::post('/academic-overrides',[\App\Http\Controllers\OverrideController::class,'store'])->middleware(['auth','password.changed','role:department_head']);

Route::middleware(['auth','password.changed'])->group(function(){
 Route::get('/notifications',[\App\Http\Controllers\NotificationController::class,'index']);
 Route::post('/notifications/{id}/read',[\App\Http\Controllers\NotificationController::class,'read']);
});
Route::post('/staff/{id}/toggle',[\App\Http\Controllers\StaffController::class,'toggle'])->middleware(['auth','password.changed','role:registrar']);

Route::middleware(['auth','password.changed'])->group(function(){
 Route::get('/enrollments/{id}/cor/{version}',[\App\Http\Controllers\EnrollmentController::class,'cor'])->whereNumber('version');
 Route::get('/enrollments/{id}/pdf/{version}',[\App\Http\Controllers\EnrollmentController::class,'pdf'])->whereNumber('version');
});
Route::get('/reports',[\App\Http\Controllers\EnrollmentController::class,'index'])->middleware(['auth','password.changed','role:registrar'])->name('reports');

Route::post('/academic/{resource}/{id}/activate',[\App\Http\Controllers\AcademicController::class,'activate'])->middleware(['auth','password.changed','role:registrar']);

Route::get('/special-cases/{id}',[\App\Http\Controllers\SpecialCaseController::class,'show'])->middleware(['auth','password.changed']);
Route::post('/special-cases/{id}/defer',[\App\Http\Controllers\SpecialCaseController::class,'defer'])->middleware(['auth','password.changed','role:department_head']);
Route::post('/special-cases/{id}/forward-to-registrar',[\App\Http\Controllers\SpecialCaseController::class,'forward'])->middleware(['auth','password.changed','role:department_head']);
Route::post('/records/{id}/curriculum',[\App\Http\Controllers\RecordsController::class,'assign'])->middleware(['auth','password.changed','role:registrar']);
Route::get('/curriculums/{id}',[\App\Http\Controllers\CurriculumController::class,'show'])->middleware(['auth','password.changed','role:registrar']);
