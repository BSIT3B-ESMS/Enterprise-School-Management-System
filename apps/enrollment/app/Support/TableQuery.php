<?php
namespace App\Support;
use Illuminate\Http\Request;
class TableQuery {
 public static function sort($query,Request $request,array $columns,string $default,string $direction='asc') {
  $key=$request->query('sort',$default);
  if(!is_string($key) || !isset($columns[$key]))$key=$default;
  $requested=$request->query('direction',$direction);
  return $query->orderBy($columns[$key],$requested==='desc'?'desc':'asc');
 }
}
