<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowRequest extends Model {protected $table='workflow_requests';protected $guarded=[];protected function casts():array{return ['payload'=>'array','reviewed_at'=>'datetime'];}public function employee(){return $this->belongsTo(Employee::class);}}
