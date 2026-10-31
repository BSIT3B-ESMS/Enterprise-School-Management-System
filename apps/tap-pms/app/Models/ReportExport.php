<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReportExport extends Model {protected $table='report_exports';protected $guarded=[];protected function casts():array{return ['filters'=>'array','snapshot'=>'array'];}public function employee(){return $this->belongsTo(Employee::class);}}
