<?php
use App\Models\{KioskDevice,AttendancePunch,AttendanceRecord};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
it('requires pairing and records server-timed punches with private evidence',function(){
 Storage::fake('local');$this->travelTo(Carbon::parse('2026-10-02 13:15','Asia/Manila'));$e=employeeFixture();$d=KioskDevice::create(['name'=>'Test kiosk','token_hash'=>hash('sha256','test-token')]);
 $this->post('/kiosk/punch',[])->assertForbidden();$this->post('/kiosk/pair',['device_token'=>'bad'])->assertSessionHasErrors('device_token');$this->post('/kiosk/pair',['device_token'=>'test-token'])->assertRedirect('/kiosk');
 $key=(string)Str::uuid();$data=['card_uid'=>$e->rfid_uid,'client_key'=>$key,'photo'=>UploadedFile::fake()->image('photo.jpg')];
 $this->post('/kiosk/punch',$data)->assertRedirect();$this->assertDatabaseCount('attendance_punches',1);$p=AttendancePunch::first();Storage::disk('local')->assertExists($p->photo_path);expect($p->occurred_at->utc()->format('H:i'))->toBe('05:15');
 $this->post('/kiosk/punch',$data)->assertRedirect();$this->assertDatabaseCount('attendance_punches',1);
 $d->update(['revoked_at'=>now()]);$this->post('/kiosk/punch',$data)->assertForbidden();$this->travelBack();
});
it('limits evidence to the owner and payroll administrator',function(){
 Storage::fake('local');$e=employeeFixture();$e2=employeeFixture('26-00002');Storage::disk('local')->put('attendance-proofs/example.jpg','photo');
 $p=AttendancePunch::create(['punch_id'=>(string)Str::uuid(),'employee_id'=>$e->id,'work_date'=>'2026-10-02','event_type'=>'am_in','occurred_at'=>now(),'photo_path'=>'attendance-proofs/example.jpg','client_key'=>(string)Str::uuid()]);
 $this->actingAs(userFixture($e2))->get('/evidence/attendance/'.$p->punch_id)->assertForbidden();$this->actingAs(userFixture($e))->get('/evidence/attendance/'.$p->punch_id)->assertOk();
});
