<?php
use App\Services\{PayrollService,ScheduleService};
use App\Models\{AttendanceRecord,Payslip};
it('allows PMS administrators to assign schedules even when EMS is enabled',function(){
 $e=employeeFixture();$admin=userFixture();config(['ems.enabled'=>true]);
 $data=['effective_from'=>'2026-11-01','work_start'=>'09:00','work_end'=>'18:00','lunch_start'=>'12:00','lunch_end'=>'13:00','grace_minutes'=>10,'work_days'=>[1,2,3,4,5]];
 $this->actingAs($admin)->withSession(['mfa_verified'=>true])->post('/employees/'.$e->id.'/schedule',$data)->assertSessionHasNoErrors();
 $s=app(ScheduleService::class)->forDate($e,'2026-11-02');expect($s->work_start)->toBe('09:00:00');expect(app(ScheduleService::class)->minutes($s,'2026-11-02',null)['scheduled_minutes'])->toBe(480);
 $this->actingAs(userFixture($e))->get('/?page=profile')->assertOk()->assertSee('Assigned work schedules')->assertSee('09:00')->assertSee('8h 0m');
 $this->post('/employees/'.$e->id.'/schedule',$data)->assertForbidden();
});
it('uses assigned working hours and grace for payroll and flags stale drafts',function(){
 $e=employeeFixture();policyFixture();completedAttendance($e);$e->schedules()->update(['grace_minutes'=>15]);
 AttendanceRecord::where('employee_id',$e->id)->where('work_date','2026-10-02')->update(['am_in'=>\Illuminate\Support\Carbon::parse('2026-10-02 08:10','Asia/Manila')->utc()]);
 $pay=app(PayrollService::class);$period=$pay->period('2026-10-01');$pay->compute($period,null);expect(Payslip::first()->net_pay)->toBe('11000.00');
 $e->schedules()->update(['grace_minutes'=>0]);expect(fn()=>$pay->release(Payslip::first(),true,null))->toThrow(\Illuminate\Validation\ValidationException::class);
});
