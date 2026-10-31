<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeeHistory extends Model {public $timestamps=false;protected $guarded=[];protected function casts():array{return ['source_updated_at'=>'datetime','observed_at'=>'datetime'];}}
