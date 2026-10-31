<?php
use App\Models\{AttendanceRecord,AttendancePunch,KioskDevice};
use App\Services\AttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('uses the original four-punch schedule windows with no manual event choice',function(){
 $employee=employeeFixture();$service=app(AttendanceService::class);
 $fixtures=[
  ['2026-10-02 07:45','am_in'],
  ['2026-10-02 11:58','am_out'],
  ['2026-10-02 13:02','pm_in'],
  ['2026-10-02 17:01','pm_out'],
 ];
 foreach($fixtures as [$time,$expected]){
  $this->travelTo(Carbon::parse($time,'Asia/Manila'));
  expect($service->nextEvent($employee))->toBe($expected);
  $this->post('/kiosk/punch',[])->assertForbidden();
  $this->travelBack();
  // Inference sees only database records. Record times are in UTC in the persistence layer.
  $record=AttendanceRecord::firstOrCreate(['employee_id'=>$employee->id,'work_date'=>'2026-10-02']);
  $record->$expected=Carbon::parse($time,'Asia/Manila')->utc();$record->save();
  AttendancePunch::create(['punch_id'=>(string)Str::uuid(),'employee_id'=>$employee->id,'event_type'=>$expected,'work_date'=>'2026-10-02','occurred_at'=>Carbon::parse($time,'Asia/Manila')->utc(),'photo_path'=>'test.jpg','client_key'=>(string)Str::uuid()]);
 }
 $this->travelTo(Carbon::parse('2026-10-02 17:04','Asia/Manila'));
 expect(fn()=> $service->nextEvent($employee))->toThrow(ValidationException::class);
 $this->travelBack();
});

it('allows PM-only arrivals including late arrivals without inventing AM punches',function(){
 $employee=employeeFixture();$service=app(AttendanceService::class);
 $this->travelTo(Carbon::parse('2026-10-02 13:15','Asia/Manila'));
 expect($service->nextEvent($employee))->toBe('pm_in');
 $this->travelTo(Carbon::parse('2026-10-02 13:45','Asia/Manila'));
 expect($service->nextEvent($employee))->toBe('pm_in');
 $this->travelBack();
});

it('records a server-resolved PM-only punch with the original kiosk and protected photo',function(){
 Storage::fake('local');$employee=employeeFixture();
 $admin=userFixture();
 $this->actingAs($admin)->withSession(['mfa_verified'=>true]);
 $this->post('/kiosk/authorize',['name'=>'Gate kiosk'])->assertRedirect('/kiosk');
 $device=KioskDevice::first();expect($device->name)->toBe('Gate kiosk');
 $this->travelTo(Carbon::parse('2026-10-02 13:15','Asia/Manila'));
 $response=$this->postJson('/kiosk/punch',['card_uid'=>$employee->rfid_uid,'client_key'=>(string)Str::uuid(),'photo'=>UploadedFile::fake()->image('punch.jpg')]);
 $response->assertOk()->assertJsonPath('data.event_type','pm_in')->assertJsonPath('data.employee_no',$employee->employee_no)->assertJsonPath('meta.saved',true);
 $this->assertDatabaseCount('attendance_punches',1);
 Storage::disk('local')->assertExists(AttendancePunch::first()->photo_path);
 $this->travelBack();
});

it('retains the original scanning presentation but does not allow an unauthorised browser to write',function(){
 $this->get('/kiosk')->assertOk()->assertSee('Tap your card')->assertSee('kioskScanBuffer')->assertDontSee('Select the attendance event');
 $this->post('/kiosk/punch',[])->assertForbidden();
});
