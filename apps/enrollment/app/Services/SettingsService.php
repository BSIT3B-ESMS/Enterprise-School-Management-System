<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class SettingsService {
 public function get(string $key,string $fallback=''):string {
  return DB::table('system_settings')->where('key',$key)->value('value') ?? $fallback;
 }
 public function corIdentity():array {
  return ['institution'=>$this->get('institution_name','Pamantasan ng Lungsod ng Pasig'),
   'logo'=>'data:image/png;base64,'.base64_encode(file_get_contents(public_path('branding/plp-seal.png')))];
 }
}
