<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\{SettingsService,AcademicService};
class SettingsController extends Controller {
 public function index(SettingsService $settings){
  $values=['institution_name'=>$settings->get('institution_name','Pamantasan ng Lungsod ng Pasig'),
   'grade_order'=>$settings->get('grade_order',config('enrollment.grade_order')),
   'default_section_capacity'=>$settings->get('default_section_capacity','40')];
  return view('settings',compact('values'));
 }
 public function update(Request $request,AcademicService $academic){
  $data=$request->validate(['institution_name'=>'required|string|max:200','grade_order'=>'required|in:unconfigured,lower,higher','default_section_capacity'=>'required|integer|min:1|max:500']);
  DB::transaction(function()use($data,$academic){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   foreach($data as $key=>$value)DB::table('system_settings')->updateOrInsert(['key'=>$key],['value'=>(string)$value,'updated_at'=>now(),'created_at'=>now()]);
   $academic->audit('settings_updated','system_settings',null,$data);
  });
  return back()->with('status','Settings saved. Existing section capacities and historical CORs remain unchanged.');
 }
}
