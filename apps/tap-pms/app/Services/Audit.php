<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class Audit {
 public static function record(string $action,string $entity,?string $id=null,array $details=[]):void{
  DB::table('audit_events')->insert(['user_id'=>auth()->id(),'action'=>$action,'entity'=>$entity,'entity_id'=>$id,'details'=>json_encode($details,JSON_THROW_ON_ERROR),'created_at'=>now()]);
 }
}
