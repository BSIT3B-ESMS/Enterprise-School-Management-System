<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalaryHistory extends Model {protected $table='salary_history';protected $guarded=[];protected function casts():array{return ['monthly_rate'=>'decimal:2'];}public function employee(){return $this->belongsTo(Employee::class);}}
