<?php
use Illuminate\Support\Facades\Artisan;
use App\Models\IntegrationToken;
Artisan::command('pms:token {caller} {--permission=*} {--revoke}', function () {
    $caller=$this->argument('caller');
    if($this->option('revoke')) { IntegrationToken::where('caller',$caller)->update(['revoked_at'=>now()]);$this->info('Caller token revoked.');return; }
    $permissions=$this->option('permission');$allowed=['payroll-periods:read','payroll-periods:write','payslips:read','payslips:write'];
    if(!$permissions || array_diff($permissions,$allowed)){$this->error('Supply one or more supported --permission options.');return 1;}
    $plain=bin2hex(random_bytes(32));
    IntegrationToken::updateOrCreate(['caller'=>$caller],['token_hash'=>hash('sha256',$plain),'permissions'=>$permissions,'revoked_at'=>null,'last_used_at'=>null]);
    $this->warn('Shown once. Store privately; issuing again rotates this caller’s token.');$this->line($plain);
})->purpose('Issue, rotate or revoke one hashed API token per calling application');

Artisan::command('pms:import-ems {resource} {file}',function(){
 $kind=$this->argument('resource');if(!in_array($kind,['employees','leaves','balances'],true)){$this->error('Resource must be employees, leaves or balances.');return 1;}
 $path=$this->argument('file');if(!is_file($path)){$this->error('The canonical JSON file was not found.');return 1;}
 $rows=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if(!is_array($rows)||!array_is_list($rows)){$this->error('Supply a JSON array in the documented canonical format.');return 1;}
 $count=\Illuminate\Support\Facades\DB::transaction(fn()=>match ($kind) { 'employees' => app(\App\Services\EmsService::class)->importEmployees($rows), 'leaves' => app(\App\Services\EmsService::class)->importLeaves($rows), 'balances' => app(\App\Services\EmsService::class)->importBalances($rows) });
 \App\Services\Audit::record('ems_file_import','ems_import',null,['resource'=>$kind,'count'=>$count]);$this->info('Imported '.$count.' EMS records.');
})->purpose('Import operator-supplied canonical EMS records without inventing employee IDs');
Artisan::command('pms:demo-data {--confirm}',function(){
 if(!app()->environment('local')||!$this->option('confirm')){$this->error('Local testing only. Add --confirm to create synthetic examples.');return 1;}
 if(\App\Models\Employee::exists()){$this->error('The roster is not empty. Synthetic seeding was cancelled.');return 1;}
 \Illuminate\Support\Facades\DB::transaction(function(){
  $e=\App\Models\Employee::create(['employee_no'=>'26-TEST-00001','full_name'=>'Synthetic Payroll Employee','department'=>'Test department','position'=>'Test role','monthly_rate'=>'22000.00','status'=>'active','rfid_uid'=>'TEST-CARD-001','source_system'=>'SYNTHETIC']);
  \App\Models\SalaryHistory::create(['employee_id'=>$e->id,'effective_on'=>'2026-01-01','monthly_rate'=>'22000.00','source_reference'=>'SYNTHETIC']);
  \App\Models\WorkSchedule::create(['employee_id'=>$e->id,'effective_from'=>'2026-01-01','work_start'=>'08:00','lunch_start'=>'12:00','lunch_end'=>'13:00','work_end'=>'17:00','grace_minutes'=>0,'work_days'=>[1,2,3,4,5]]);
  $password=\Illuminate\Support\Str::password(20);\App\Models\User::create(['name'=>$e->full_name,'login_id'=>$e->employee_no,'email'=>null,'password'=>$password,'employee_id'=>$e->id,'role'=>'employee']);
  $this->warn('Synthetic employee login ID shown once: 26-TEST-00001');$this->line($password);
 });
 $this->info('Synthetic roster added. Set payroll policy and record/import attendance before computing. No real EMS data was created.');
})->purpose('Create an explicitly synthetic local test employee with a random password');

// Run via scheduler or manually, once the official EMS contract and token are configured.
Artisan::command('pms:sync-ems {resource}',function(){
 $resource=$this->argument('resource');
 if(!in_array($resource,['employees','leaves','balances'],true)){$this->error('Resource must be employees, leaves or balances.');return 1;}
 if(!config('ems.enabled')){$this->error('EMS integration is disabled.');return 1;}
 try{$count=app(\App\Services\EmsService::class)->sync($resource);$this->info('EMS '.$resource.' synchronized: '.$count.' records.');return 0;}
 catch(\Throwable $e){$this->error('EMS sync failed. Check EMS connectivity, contract and application logs.');return 1;}
})->purpose('Synchronize the agreed EMS feed safely into the local PMS mirror');

if(config('ems.enabled')&&config('ems.auto_sync')){
 \Illuminate\Support\Facades\Schedule::command('pms:sync-ems employees')->everyFifteenMinutes()->withoutOverlapping();
 \Illuminate\Support\Facades\Schedule::command('pms:sync-ems leaves')->everyFifteenMinutes()->withoutOverlapping();
 if(config('ems.balances_path')&&config('ems.balance_fields')) \Illuminate\Support\Facades\Schedule::command('pms:sync-ems balances')->everyFifteenMinutes()->withoutOverlapping();
}

// Static configuration check: does not call EMS, print tokens or claim live connectivity.
Artisan::command('pms:api-contract-check', function () {
    $this->info('TAP-PMS API.md contract readiness (configuration only)');
    $this->line('App-to-app routes: /api/v1; bearer tokens are issued with pms:token.');
    $this->line('EMS enabled: '.(config('ems.enabled') ? 'yes' : 'no - offline testing only'));
    $missing = [];
    foreach (['base_url', 'token', 'employees_path', 'employee_fields', 'leaves_path', 'leave_fields',
              'leave_requests_path', 'leave_request_fields', 'balances_path', 'balance_fields'] as $key) {
        if (!config('ems.'.$key)) $missing[] = $key;
    }
    if ($missing) $this->warn('Missing required live EMS settings: '.implode(', ', $missing));
    else $this->info('Required EMS endpoint settings: configured, NOT live-verified.');
    if (!config('ems.balances_path') || !config('ems.balance_fields')) {
        $this->warn('EMS leave credits: REQUIRED for live integration. No official feed; balances shown unavailable, not zero.');
    } else $this->info('EMS leave credits: mapping configured, NOT live-verified.');
    if (!config('ems.leave_proof_field')) {
        $this->warn('EMS proof attachment: no agreed multipart field; requests with proof will not be sent.');
    } else $this->info('EMS proof: multipart field configured, NOT live-verified.');
    $this->warn('Full compliance requires live EMS contract, token permissions, and end-to-end tests.');
    return (config('ems.enabled') && $missing) ? 1 : 0;
})->purpose('Inspect API.md EMS settings without printing tokens or calling external systems');

Artisan::command('pms:reset-mfa {login_id} {--confirm}',function(){
 if(!app()->environment('local','testing')||!$this->option('confirm')){$this->error('Local recovery only; add --confirm after verifying this administrator account.');return 1;}
 $u=\App\Models\User::where('login_id',$this->argument('login_id'))->where('role','admin')->first();if(!$u){$this->error('Administrator account not found.');return 1;}
 \Illuminate\Support\Facades\DB::table('users')->where('id',$u->id)->update(['totp_secret'=>null,'totp_confirmed_at'=>null,'totp_last_counter'=>null,'recovery_codes'=>null]);\App\Services\Audit::record('admin_mfa_recovery','users',(string)$u->id);$this->info('MFA enrollment reset for this administrator only. Sign in with the existing ID/password in a fresh browser session to enroll again. Keep APP_KEY unchanged.');
})->purpose('Recover one local administrator MFA enrollment without deleting application records');
