<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    private function account(string $role='student', bool $change=false): User
    {
        return User::factory()->create(['role'=>$role,'must_change_password'=>$change,'password'=>Hash::make('InitialPassword123')]);
    }
    public function test_guests_must_sign_in(): void
    {
        $this->get('/registrar')->assertRedirect('/login');
        $this->get('/student')->assertRedirect('/login');
    }
    public function test_valid_login_and_logout(): void
    {
        $user = $this->account();
        $this->post('/login',['email'=>$user->email,'password'=>'InitialPassword123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
    public function test_invalid_password_does_not_authenticate(): void
    {
        $user = $this->account();
        $this->post('/login',['email'=>$user->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
    public function test_each_role_only_accesses_its_own_portal(): void
    {
        $portals = ['student'=>'/student','registrar'=>'/registrar','department_head'=>'/department-head'];
        foreach ($portals as $role=>$portal) {
            $this->actingAs($this->account($role))->get('/dashboard')->assertRedirect($portal);
            foreach ($portals as $otherRole=>$otherPortal) {
                $this->get($otherPortal)->assertStatus($role === $otherRole ? 200 : 403);
            }
        }
    }
    public function test_temporary_accounts_must_change_password(): void
    {
        $this->actingAs($this->account('student',true))->get('/student')->assertRedirect('/account/password');
        $this->get('/account/password')->assertOk();
        $this->post('/account/password',[
            'current_password'=>'InitialPassword123','password'=>'ChangedPassword456','password_confirmation'=>'ChangedPassword456',
        ])->assertRedirect('/dashboard');
        $this->assertFalse(auth()->user()->fresh()->must_change_password);
        $this->assertTrue(Hash::check('ChangedPassword456',auth()->user()->fresh()->password));
        $this->get('/student')->assertOk();
    }
    public function test_password_change_checks_current_password(): void
    {
        $user=$this->account();
        $this->actingAs($user)->post('/account/password',[
            'current_password'=>'wrong','password'=>'ChangedPassword456','password_confirmation'=>'ChangedPassword456',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('InitialPassword123',$user->fresh()->password));
    }
    public function test_public_registration_is_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register',['role'=>'registrar'])->assertNotFound();
    }
    public function test_login_is_rate_limited(): void
    {
        for ($i=0;$i<6;$i++) { $this->post('/login',['email'=>'missing@example.test','password'=>'wrong']); }
        $this->post('/login',['email'=>'missing@example.test','password'=>'wrong'])->assertStatus(429);
    }
    public function test_recovery_token_expires_after_use():void {
        $user=$this->account('student',true);$token=\Illuminate\Support\Facades\Password::createToken($user);
        $data=['email'=>$user->email,'token'=>$token,'password'=>'RecoveredPassword123','password_confirmation'=>'RecoveredPassword123'];
        $this->post('/reset-password',$data)->assertRedirect('/login');$this->assertTrue(Hash::check($data['password'],$user->fresh()->password));$this->assertFalse($user->fresh()->must_change_password);$this->post('/reset-password',$data)->assertSessionHasErrors('email');
    }
    public function test_recovery_does_not_disclose_account_existence():void {
        \Illuminate\Support\Facades\Notification::fake();$user=$this->account();
        $this->post('/forgot-password',['email'=>$user->email])->assertSessionHas('status','If the address has an account, password recovery instructions have been sent.');
        $this->post('/forgot-password',['email'=>'missing@example.test'])->assertSessionHas('status','If the address has an account, password recovery instructions have been sent.');
    }
}
