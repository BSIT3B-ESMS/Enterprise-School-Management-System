<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApprovedLeave extends Model {protected $table='approved_leaves';protected $guarded=[];protected function casts():array{return ['is_paid'=>'boolean','source_payload'=>'array'];}public function employee(){return $this->belongsTo(Employee::class);}}
