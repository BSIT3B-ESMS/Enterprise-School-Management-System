<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
 protected $guarded=[];
 protected function casts():array{return ['monthly_rate'=>'decimal:2','source_updated_at'=>'datetime'];}
 public function schedules(){return $this->hasMany(WorkSchedule::class);}
 public function attendanceRecords(){return $this->hasMany(AttendanceRecord::class);}
 public function salaryHistory(){return $this->hasMany(SalaryHistory::class);}
 public function eligible():bool{return !in_array(strtolower($this->status),['inactive','retired','terminated','resigned'],true);}
}
