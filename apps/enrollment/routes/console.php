<?php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
Artisan::command('enrollment:backup',function(\App\Services\BackupService $service){$this->info('Encrypted backup created and verified: '.$service->create());})->purpose('Create an encrypted database backup and verify a separate restore');
Artisan::command('enrollment:verify-backup {file}',function(\App\Services\BackupService $service){$result=$service->verify($this->argument('file'));$this->info('Restore verified in '.$result['target'].' ('.$result['tables'].' tables). Application database untouched.');})->purpose('Verify an encrypted backup in an isolated database or archived file');
Artisan::command('enrollment:import-reference',function(\App\Services\ReferenceCatalogService $service){$result=$service->import();$this->info('Imported supplied 2022–2023 reference: '.$result['curriculums'].' curricula. Existing student assignments preserved.');})->purpose('Import the supplied historical PLP reference catalog');
Artisan::command('enrollment:readiness',function(\App\Services\SettingsService $settings){
 $checks=['MariaDB/MySQL'=>\Illuminate\Support\Facades\DB::getDriverName()==='mysql','Grading direction configured'=>in_array($settings->get('grade_order',config('enrollment.grade_order')),['lower','higher']),'SMTP configured'=>config('mail.default')==='smtp','Production environment'=>app()->environment('production'),'Debug disabled'=>!config('app.debug'),'HTTPS application URL'=>str_starts_with(config('app.url'),'https://')];
 foreach($checks as $label=>$passed)$this->line(($passed?'PASS':'ACTION NEEDED').' — '.$label);
 $this->comment('Configuration checks do not establish delivery, integration connectivity, school approval or production security.');
 return in_array(false,$checks,true)?1:0;
})->purpose('Report deployment configuration without displaying secrets');
Artisan::command('enrollment:create-staff', function () {
    $name = $this->ask('Full name');
    $email = $this->ask('Institutional email');
    $role = $this->choice('Role', ['registrar','department_head']);
    $password = $this->secret('Temporary password (12+ characters, upper/lower case and number)');
    $validator = Validator::make(compact('name','email','password'), [
        'name'=>'required|string|max:255',
        'email'=>'required|email|max:255|unique:users,email',
        'password'=>['required',\Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()],
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) { $this->error($error); }
        return 1;
    }
    $user = new User;
    $user->forceFill(['name'=>$name,'email'=>$email,'role'=>$role,'password'=>Hash::make($password),'must_change_password'=>true])->save();
    $this->info('Staff account created. A password change is required on first login.');
    return 0;
})->purpose('Create an authorized Registrar or Department Head account locally');
Artisan::command('enrollment:integration-token {source} {--user=}', function(){
 $source=$this->argument('source');if(!in_array($source,['admission','ems'])){$this->error('Source must be admission or ems.');return 1;}
 $user=User::where(['id'=>$this->option('user'),'role'=>'registrar','active'=>true])->first();if(!$user){$this->error('Provide the ID of an authorized active Registrar service account.');return 1;}
 $token=$user->createToken($source.'-integration',['import:'.$source],now()->addDays(30));$this->warn('This import-only token is displayed once. Store it securely; it expires in 30 days.');$this->line($token->plainTextToken);return 0;
})->purpose('Issue a source-scoped integration token without general portal access');
