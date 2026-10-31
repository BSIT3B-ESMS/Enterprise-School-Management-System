<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable {
 protected $attributes=['is_active'=>true,'role'=>'employee'];
 protected $fillable=['name','login_id','email','password','role','employee_id','is_active','ems_suspended'];
 protected $hidden=['password','remember_token','totp_secret','recovery_codes'];
 protected function casts(): array{return ['password'=>'hashed','is_active'=>'boolean','ems_suspended'=>'boolean','totp_secret'=>'encrypted','recovery_codes'=>'encrypted:array','totp_confirmed_at'=>'datetime'];}
 public function employee(){return $this->belongsTo(Employee::class);}
}
