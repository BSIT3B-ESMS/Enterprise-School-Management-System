<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DeductionOverride extends Model {
 protected $guarded=[];
 protected function casts():array{return ['sss'=>'decimal:2','gsis'=>'decimal:2','philhealth'=>'decimal:2','pagibig'=>'decimal:2','tax'=>'decimal:2','loan'=>'decimal:2'];}
}
