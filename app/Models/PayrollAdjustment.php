<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollAdjustment extends Model {protected $table='payroll_adjustments';protected $guarded=[];protected function casts():array{return ['amount'=>'decimal:2'];}public function employee(){return $this->belongsTo(Employee::class);}}
