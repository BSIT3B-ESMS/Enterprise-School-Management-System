<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\AcademicController;
use App\Http\Controllers\SpecialCaseController;
Route::prefix('v1')->group(function(){
 Route::post('/auth/forgot-password',[\App\Http\Controllers\AccountRecoveryController::class,'request'])->middleware('throttle:3,1');
 Route::post('/auth/reset-password',[\App\Http\Controllers\AccountRecoveryController::class,'reset'])->middleware('throttle:6,1');
 Route::post('/auth/login',[ApiController::class,'login'])->middleware('throttle:6,1');
 Route::middleware(['auth:sanctum','account.active','throttle:60,1'])->group(function(){
  Route::post('/auth/logout',[ApiController::class,'logout']);
  Route::post('/auth/change-password',[ApiController::class,'password'])->middleware('abilities:portal');
  Route::post('/integrations/admission',fn(\Illuminate\Http\Request $r,\App\Services\ImportService $s)=>(new ApiController)->integration($r,'admission',$s))->middleware(['role:registrar','ability:portal,import:admission']);
  Route::post('/integrations/ems',fn(\Illuminate\Http\Request $r,\App\Services\ImportService $s)=>(new ApiController)->integration($r,'ems',$s))->middleware(['role:registrar','ability:portal,import:ems']);
  Route::middleware(['abilities:portal','password.changed'])->group(function(){
   Route::get('/students',[ApiController::class,'students']);
   Route::get('/students/{id}',[ApiController::class,'student']);
   Route::get('/students/{id}/{part}',[ApiController::class,'academic'])->whereIn('part',['academic-records','curriculum','back-subjects']);
   Route::get('/enrollments',[EnrollmentController::class,'index']);
   Route::post('/enrollments',[EnrollmentController::class,'propose'])->middleware('role:registrar,department_head');
   Route::get('/enrollments/{id}',[EnrollmentController::class,'show']);
   Route::get('/enrollments/{id}/proposal',[EnrollmentController::class,'show']);
   Route::post('/enrollments/{id}/approve',function(\Illuminate\Http\Request $r,int $id,\App\Services\EnrollmentService $s){$r->merge(['decision'=>'accept']);return (new EnrollmentController)->decision($r,$id,$s);})->middleware('role:student');
   Route::post('/enrollments/{id}/decline',function(\Illuminate\Http\Request $r,int $id,\App\Services\EnrollmentService $s){$r->merge(['decision'=>'decline']);return (new EnrollmentController)->decision($r,$id,$s);})->middleware('role:student');
   Route::post('/enrollments/{id}/finalize',[EnrollmentController::class,'finalize'])->middleware('role:registrar');
   Route::get('/enrollments/{id}/cor',[EnrollmentController::class,'pdf']);
   Route::get('/special-cases',[SpecialCaseController::class,'index']);
   Route::post('/special-cases',[SpecialCaseController::class,'store'])->middleware('role:student');
   Route::post('/special-cases/{id}/evaluate',[SpecialCaseController::class,'evaluate'])->middleware('role:department_head');
   Route::middleware('role:registrar')->group(function(){
    Route::get('/{resource}',[ApiController::class,'resource'])->whereIn('resource',['programs','curriculums','subjects','sections','rooms','professors','class-offerings','class-schedules']);
    Route::post('/subjects',[AcademicController::class,'store'])->defaults('resource','subjects');
    Route::post('/curriculums',[AcademicController::class,'store'])->defaults('resource','curriculums');
    Route::post('/sections',[AcademicController::class,'store'])->defaults('resource','sections');
    Route::post('/rooms',[AcademicController::class,'store'])->defaults('resource','rooms');
    Route::post('/class-offerings',[AcademicController::class,'store'])->defaults('resource','offerings');
    Route::post('/class-schedules',[AcademicController::class,'store'])->defaults('resource','schedules');
    Route::post('/academic/{resource}',[AcademicController::class,'store']);
    Route::put('/academic/{resource}/{id}',[AcademicController::class,'update']);
   });
  });
 });
});