<?php
namespace App\Http\Controllers;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Employee;
use App\Support\ApiResponse;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
class PayrollApiController extends Controller {
    private function page(Request $r, $query, callable $map) {
        $v=$r->validate(['limit'=>['sometimes','integer','min:1','max:100'],'updated_since'=>['sometimes','date_format:Y-m-d\TH:i:s\Z'],'status'=>['sometimes','in:draft,released']]);
        if (isset($v['updated_since'])) $query->where('updated_at','>=',Carbon::parse($v['updated_since'])->utc());
        if (isset($v['status'])) $query->where('status',$v['status']);
        if ($r->filled('cursor') && \Illuminate\Pagination\Cursor::fromEncoded($r->input('cursor')) === null) return ApiResponse::error('Invalid cursor.',422,['cursor'=>['Use the opaque cursor returned by this API.']]);
        $page=$query->orderBy('updated_at')->orderBy('id')->cursorPaginate($v['limit']??50);
        return response()->json(['data'=>array_map($map,$page->items()),'meta'=>['next_cursor'=>$page->hasMorePages()?$page->nextCursor()->encode():null,'has_more'=>$page->hasMorePages()]]);
    }
    private function periodData($p): array { return ['period_id'=>$p->period_id,'starts_on'=>$p->starts_on,'ends_on'=>$p->ends_on,'status'=>$p->status,'updated_at'=>$p->updated_at->utc()->toIso8601ZuluString()]; }
    private function payslipData($p): array { return ['payslip_id'=>$p->payslip_id,'period_id'=>$p->period_id,'employee_no'=>$p->employee_no,'gross_pay'=>$p->gross_pay,'deductions'=>$p->deductions,'deduction_breakdown'=>(object)collect($p->calculation['statutory']??[])->only(['sss','gsis','philhealth','pagibig','tax','loan'])->all(),'net_pay'=>$p->net_pay,'status'=>$p->status,'released_at'=>$p->released_at?->utc()->toIso8601ZuluString(),'updated_at'=>$p->updated_at->utc()->toIso8601ZuluString()]; }
    public function periods(Request $r) { return $this->page($r,PayrollPeriod::query(),fn($p)=>$this->periodData($p)); }
    public function payslips(Request $r) {
        $r->validate(['employee_no'=>['sometimes','string','max:255'],'period_id'=>['sometimes','uuid']]);
        $q=Payslip::query(); foreach(['employee_no','period_id'] as $f) if($r->filled($f)) $q->where($f,$r->input($f));
        return $this->page($r,$q,fn($p)=>$this->payslipData($p));
    }
    public function payslip(string $payslipId) { return ApiResponse::success($this->payslipData(Payslip::where('payslip_id',$payslipId)->firstOrFail())); }
    public function createPeriod(Request $r) {
        $v=$r->validate(['starts_on'=>['required','date_format:Y-m-d'],'ends_on'=>['required','date_format:Y-m-d','after_or_equal:starts_on']]);
        $start=Carbon::parse($v['starts_on']);$end=Carbon::parse($v['ends_on']);
        if (!$start->isSameMonth($end) || !in_array($start->day,[1,16],true) || $end->day!==($start->day===1?15:$start->daysInMonth)) return ApiResponse::error('Use a semi-monthly period: 1–15 or 16–month-end.',422,['code'=>'invalid_payroll_period']);
        if(PayrollPeriod::where('starts_on',$v['starts_on'])->where('ends_on',$v['ends_on'])->exists()) return ApiResponse::error('Payroll period already exists.',409,['code'=>'period_exists']);
        $p=PayrollPeriod::create($v+['period_id'=>(string)Str::uuid(),'status'=>'draft']);
        return ApiResponse::success($this->periodData($p),'Created',201);
    }
    public function createPayslip(Request $r) {
        $v=$r->validate(['period_id'=>['required','uuid','exists:payroll_periods,period_id'],'employee_no'=>['required','string','exists:employees,employee_no'],'gross_pay'=>['prohibited'],'deductions'=>['prohibited'],'net_pay'=>['prohibited']]);
        $employee=Employee::where('employee_no',$v['employee_no'])->firstOrFail();$period=PayrollPeriod::where('period_id',$v['period_id'])->firstOrFail();
        if($period->status==='released')return ApiResponse::error('Withdraw the released period before computing drafts.',409,['code'=>'period_released']);
        if(! $employee->eligible())return ApiResponse::error('Employee is inactive.',422,['code'=>'employee_inactive']);
        if(Payslip::where('period_id',$period->period_id)->where('employee_no',$employee->employee_no)->exists())return ApiResponse::error('Payslip already exists; use the payroll computation workflow.',409,['code'=>'payslip_exists']);
        $c=app(\App\Services\PayrollService::class)->calculate($employee,$period);
        $p=Payslip::create(['period_id'=>$period->period_id,'employee_no'=>$employee->employee_no,'payslip_id'=>(string)Str::uuid(),'gross_pay'=>$c['gross_pay'],'deductions'=>$c['deductions'],'net_pay'=>$c['net_pay'],'calculation'=>$c,'status'=>'draft']);
        \App\Services\Audit::record('api_draft_computed','payslips',$p->payslip_id);
        return ApiResponse::success($this->payslipData($p),'Created',201);
    }
}
