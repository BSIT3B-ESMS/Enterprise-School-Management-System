<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payslip extends Model {protected $guarded=[];protected function casts():array{return ['gross_pay'=>'decimal:2','deductions'=>'decimal:2','net_pay'=>'decimal:2','released_at'=>'datetime','calculation'=>'array'];}}
