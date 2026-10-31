<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class Policy {
 public static function get():array {
  $row=DB::table('application_settings')->where('key','payroll_policy')->first();
  return $row?json_decode($row->value,true):[];
 }
 public static function save(array $value):void{DB::table('application_settings')->updateOrInsert(['key'=>'payroll_policy'],['value'=>json_encode($value),'created_at'=>now(),'updated_at'=>now()]);}
}
