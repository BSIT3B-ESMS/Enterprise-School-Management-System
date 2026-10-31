<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkSchedule extends Model {protected $guarded=[];protected function casts():array{return ['work_days'=>'array'];}public function employee(){return $this->belongsTo(Employee::class);}}
