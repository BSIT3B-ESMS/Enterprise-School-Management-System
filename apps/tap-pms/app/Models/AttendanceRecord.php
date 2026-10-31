<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceRecord extends Model {protected $guarded=[];protected function casts():array{return ['metadata'=>'array','am_in'=>'datetime','am_out'=>'datetime','pm_in'=>'datetime','pm_out'=>'datetime'];}public function employee(){return $this->belongsTo(Employee::class);}}
