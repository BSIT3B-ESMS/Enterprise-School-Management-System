<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendancePunch extends Model {protected $table='attendance_punches';protected $guarded=[];protected function casts():array{return ['occurred_at'=>'datetime'];}public function employee(){return $this->belongsTo(Employee::class);}}
