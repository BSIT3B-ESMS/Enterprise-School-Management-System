<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class AccountProvisioningTest extends TestCase {
 use RefreshDatabase;
 public function test_empty_installation_has_login_without_public_account_creation():void {
  $this->get('/login')->assertOk()->assertSee('Students use the account provided through Admission.')->assertDontSee('Create Registrar account');
  foreach(['/setup','/register'] as $path){$this->get($path)->assertNotFound();$this->post($path,['name'=>'Unauthorized','email'=>'unauthorized@example.test','password'=>'UnauthorizedPassword123'])->assertNotFound();}
  $this->assertDatabaseCount('users',0);
 }
 public function test_admission_import_requires_authenticated_integration_access():void {
  $this->postJson('/api/v1/integrations/admission',['source'=>'admission','schema_version'=>'1.0','students'=>[]])->assertUnauthorized();
  $this->assertDatabaseCount('users',0);
 }
}
