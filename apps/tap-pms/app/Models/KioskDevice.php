<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class KioskDevice extends Model {protected $table='kiosk_devices';protected $guarded=[];protected function casts():array{return ['revoked_at'=>'datetime','last_used_at'=>'datetime'];}public function employee(){return $this->belongsTo(Employee::class);}}
