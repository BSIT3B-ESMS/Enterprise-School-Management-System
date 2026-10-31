<?php
use App\Models\User;
use App\Services\Totp;
it('creates the administrator once and requires authenticator enrollment',function(){
 $this->get('/')->assertRedirect('/login');$this->get('/setup')->assertOk()->assertSee('Create administrator');
 $this->post('/setup',['name'=>'Payroll Admin','admin_id'=>'ADMIN-TEST','password'=>'CorrectPassword123','password_confirmation'=>'CorrectPassword123'])->assertRedirect('/mfa');
 $u=User::first();expect($u->role)->toBe('admin');expect($u->login_id)->toBe('ADMIN-TEST');expect($u->email)->toBeNull();expect($u->password)->not->toBe('CorrectPassword123');
 $this->get('/?page=payroll')->assertRedirect('/mfa');$this->get('/setup')->assertNotFound();
 $this->get('/mfa')->assertOk();$u->refresh();expect($u->getRawOriginal('totp_secret'))->not->toBe($u->totp_secret);
 $code=Totp::code($u->totp_secret,intdiv(now()->timestamp,30));$this->post('/mfa',['code'=>$code])->assertRedirect('/')->assertSessionHas('mfa_verified',true)->assertSessionHas('recovery_codes');
 $this->get('/')->assertOk()->assertSee('Overview');
});
it('checks employee passwords and rejects inactive employees on the server',function(){
 $e=employeeFixture();$u=userFixture($e);userFixture();
 $this->post('/login',['identifier'=>$u->login_id,'password'=>'wrong'])->assertSessionHasErrors('identifier');
 $this->post('/login',['identifier'=>$u->login_id,'password'=>'CorrectPassword123'])->assertRedirect('/');$this->assertAuthenticatedAs($u);
 $e->update(['status'=>'retired']);$this->get('/')->assertForbidden();$this->assertGuest();
});
it('enforces roles and invalidates sessions after an administrator password reset',function(){
 $e=employeeFixture();$u=userFixture($e);$this->actingAs($u)->get('/?page=settings')->assertNotFound();
 $this->actingAs($u)->post('/payroll/compute',['period_start'=>'2026-10-01'])->assertForbidden();
 $this->actingAs($u)->withSession(['auth_password_hash'=>$u->password])->get('/')->assertOk();$u->update(['password'=>'ChangedPassword123']);
 $this->get('/')->assertRedirect('/login');
});
it('renders every administrator page from normalized database data',function(){
 $e=employeeFixture();policyFixture();$a=userFixture();
 foreach(['dashboard','workforce','attendance','requests','payroll','payslips','reports','settings','audit']as$page)$this->actingAs($a)->withSession(['mfa_verified'=>true])->get('/?page='.$page)->assertOk();
 $this->get('/?page=workforce&employee_id='.$e->id)->assertOk()->assertSee($e->employee_no);
 $employeeUser=userFixture($e);foreach(['attendance','requests','leave','payslips','profile']as$page)$this->actingAs($employeeUser)->get('/?page='.$page)->assertOk();
});
it('has no public legacy bulk-state mutation routes',function(){
 $this->postJson('/api/internal/browser/sync',['state'=>['role'=>'admin']])->assertNotFound();$this->get('/tap-pms-ui/index.html')->assertNotFound();
});
it('denies an employee login without a linked EMS employee record',function(){
 $u=\App\Models\User::create(['name'=>'Unlinked','email'=>'unlinked@example.test','password'=>'CorrectPassword123','role'=>'employee']);
 $this->actingAs($u)->get('/?page=attendance')->assertForbidden();
});

it('renders editable schedule fields instead of a literal include directive',function(){
 $e=employeeFixture();$admin=userFixture();$this->actingAs($admin)->withSession(['mfa_verified'=>true])->get('/?page=workforce&employee_id='.$e->id)->assertOk()->assertSee('name="work_start"',false)->assertSee('name="work_days[]"',false)->assertDontSee("@include(",false);
});

it('issues employee credentials from the official EMS employee number without asking for email',function(){
 $employee=employeeFixture('26-00041');$admin=userFixture();
 $this->actingAs($admin)->withSession(['mfa_verified'=>true])->post('/employees/'.$employee->id.'/account',[
  'password'=>'TemporaryPassword123','password_confirmation'=>'TemporaryPassword123'
 ])->assertSessionHasNoErrors();
 $user=User::where('employee_id',$employee->id)->firstOrFail();
 expect($user->login_id)->toBe('26-00041');expect($user->email)->toBeNull();
 $this->post('/logout')->assertRedirect('/login');
 $this->post('/login',['identifier'=>'26-00041','password'=>'TemporaryPassword123'])->assertRedirect('/');
 $this->assertAuthenticatedAs($user);
});

it('supports previously created email accounts without requiring email for new accounts',function(){
 $legacy=User::create(['name'=>'Old Payroll Admin','email'=>'old-admin@example.test','password'=>'CorrectPassword123','role'=>'admin']);
 $this->post('/login',['identifier'=>'old-admin@example.test','password'=>'CorrectPassword123'])->assertRedirect('/mfa');
 $this->assertAuthenticatedAs($legacy);
});
