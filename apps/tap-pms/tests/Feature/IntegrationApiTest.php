<?php
use App\Models\{IntegrationToken,PayrollPeriod,Payslip};
use App\Services\PayrollService;
use Illuminate\Support\Str;
it('requires app bearer authentication, honors permissions and revocation',function(){
 $this->getJson('/api/v1/payroll-periods')->assertUnauthorized()->assertHeader('WWW-Authenticate','Bearer');$h=callerHeaders(['payslips:read']);$this->getJson('/api/v1/payroll-periods',$h)->assertForbidden();IntegrationToken::query()->update(['revoked_at'=>now()]);$this->getJson('/api/v1/payslips',$h)->assertUnauthorized();
});
it('creates periods once and rejects idempotency keys reused for different requests',function(){
 $h=callerHeaders(['payroll-periods:read','payroll-periods:write']);$body=['starts_on'=>'2026-10-16','ends_on'=>'2026-10-31'];$one=$this->postJson('/api/v1/payroll-periods',$body,$h)->assertCreated()->json();expect($one)->not->toHaveKey('status');$this->postJson('/api/v1/payroll-periods',$body,$h)->assertCreated()->assertExactJson($one)->assertHeader('Idempotency-Replayed','true');$body['starts_on']='2026-11-01';$body['ends_on']='2026-11-15';$this->postJson('/api/v1/payroll-periods',$body,$h)->assertConflict()->assertJsonPath('code','idempotency_conflict');$this->assertDatabaseCount('payroll_periods',1);
});
it('uses official employee numbers and computes API drafts from database inputs',function(){
 $e=employeeFixture();policyFixture();completedAttendance($e);$p=app(PayrollService::class)->period('2026-10-01');$h=callerHeaders(['payslips:write','payslips:read']);
 $body=['period_id'=>$p->period_id,'employee_no'=>$e->employee_no];$data=$this->postJson('/api/v1/payslips',$body,$h)->assertCreated()->assertJsonPath('data.net_pay','11000.00')->json('data');expect($data['updated_at'])->toEndWith('Z');expect($data)->not->toHaveKey('id');
 $h['Idempotency-Key']=(string)Str::uuid();$this->postJson('/api/v1/payslips',$body+['gross_pay'=>'999999.00'],$h)->assertUnprocessable()->assertJsonValidationErrors('gross_pay');
});
it('returns opaque cursor pages with filters and rejects invalid limits',function(){
 $h=callerHeaders(['payroll-periods:read']);app(PayrollService::class)->period('2026-10-01');app(PayrollService::class)->period('2026-10-16');
 $p=$this->getJson('/api/v1/payroll-periods?limit=1',$h)->assertOk()->assertJsonPath('meta.has_more',true)->json('meta.next_cursor');$this->getJson('/api/v1/payroll-periods?limit=1&cursor='.urlencode($p),$h)->assertOk()->assertJsonPath('meta.has_more',false)->assertJsonPath('meta.next_cursor',null);$this->getJson('/api/v1/payroll-periods?limit=101',$h)->assertUnprocessable();
});
