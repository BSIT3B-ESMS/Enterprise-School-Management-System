<?php
use App\Services\EmsService;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
it('does not invent EMS endpoints or accept malformed employee money',function(){
 expect(fn()=>app(EmsService::class)->sync('employees'))->toThrow(\Illuminate\Validation\ValidationException::class);
 expect(fn()=>app(EmsService::class)->importEmployees([['employee_no'=>'26-00001','full_name'=>'Test','monthly_rate'=>22000.0,'status'=>'active','salary_effective_on'=>'2026-01-01','updated_at'=>'2026-10-30T00:00:00Z']]))->toThrow(\Illuminate\Validation\ValidationException::class);
});
it('imports EMS-owned IDs and explicitly classified leave',function(){
 $count=app(EmsService::class)->importEmployees([['employee_no'=>'26-00123','full_name'=>'EMS Employee','monthly_rate'=>'22000.00','status'=>'active','salary_effective_on'=>'2026-01-01','updated_at'=>'2026-10-30T00:00:00Z']]);expect($count)->toBe(1);expect(Employee::first()->employee_no)->toBe('26-00123');
 app(EmsService::class)->importLeaves([['leave_id'=>'EMS-L-123','employee_no'=>'26-00123','starts_on'=>'2026-11-02','ends_on'=>'2026-11-03','is_paid'=>true,'status'=>'approved']]);$this->assertDatabaseHas('approved_leaves',['leave_id'=>'EMS-L-123','is_paid'=>true]);
});
it('uses the configured bearer contract and follows EMS cursors',function(){
 $row=['employee_no'=>'26-00999','full_name'=>'Contract Fixture','monthly_rate'=>'22000.00','status'=>'active','salary_effective_on'=>'2026-01-01','updated_at'=>'2026-10-30T00:00:00Z'];
 config(['ems.enabled'=>true,'ems.base_url'=>'https://ems.example.test','ems.token'=>'test-private-token','ems.employees_path'=>'/api/v1/personnel','ems.employee_fields'=>array_combine(array_keys($row),array_keys($row))]);
 Http::fakeSequence()->push(['data'=>[$row],'meta'=>['has_more'=>true,'next_cursor'=>'opaque-next']])->push(['data'=>[],'meta'=>['has_more'=>false,'next_cursor'=>null]]);
 expect(app(EmsService::class)->sync('employees'))->toBe(1);
 Http::assertSentCount(2);Http::assertSent(fn($r)=>$r->hasHeader('Authorization','Bearer test-private-token')&&($r['cursor']??null)==='opaque-next');
 $this->assertDatabaseHas('ems_sync_runs',['status'=>'success','record_count'=>1]);
});
it('preserves existing records when a remote page fails validation',function(){
 $row=['employee_no'=>'26-00999','full_name'=>'Contract Fixture','monthly_rate'=>'22000.00','status'=>'active','salary_effective_on'=>'2026-01-01','updated_at'=>'2026-10-30T00:00:00Z'];
 config(['ems.enabled'=>true,'ems.base_url'=>'https://ems.example.test','ems.token'=>'test-private-token','ems.employees_path'=>'/api/v1/personnel','ems.employee_fields'=>array_combine(array_keys($row),array_keys($row))]);
 Http::fake(['*'=>Http::response(['data'=>[$row,array_replace($row,['employee_no'=>'26-00998','monthly_rate'=>3.5])],'meta'=>['has_more'=>false]])]);
 expect(fn()=>app(EmsService::class)->sync('employees'))->toThrow(\Illuminate\Validation\ValidationException::class);expect(Employee::count())->toBe(0);$this->assertDatabaseHas('ems_sync_runs',['status'=>'failed']);
});

it('only reactivates accounts that were automatically suspended by EMS',function(){
 $employee=employeeFixture();$user=userFixture($employee);
 $row=['employee_no'=>$employee->employee_no,'full_name'=>$employee->full_name,'department'=>$employee->department,'position'=>$employee->position,'monthly_rate'=>'22000.00','status'=>'inactive','salary_effective_on'=>'2026-01-01','updated_at'=>'2026-10-30T06:00:00Z'];
 app(\App\Services\EmsService::class)->importEmployees([$row]);
 expect($user->fresh()->is_active)->toBeFalse()->and($user->fresh()->ems_suspended)->toBeTrue();
 $row['status']='active';$row['updated_at']='2026-10-30T06:05:00Z';
 app(\App\Services\EmsService::class)->importEmployees([$row]);
 expect($user->fresh()->is_active)->toBeTrue()->and($user->fresh()->ems_suspended)->toBeFalse();
 $user->update(['is_active'=>false,'ems_suspended'=>false]);
 app(\App\Services\EmsService::class)->importEmployees([$row]);
 expect($user->fresh()->is_active)->toBeFalse();
});
