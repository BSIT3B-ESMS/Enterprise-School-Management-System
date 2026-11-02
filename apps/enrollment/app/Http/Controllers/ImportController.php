<?php
namespace App\Http\Controllers;
use App\Services\AcademicService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ImportController extends Controller {
public function index(){$logs=DB::table('external_sync_logs')->orderByDesc('id')->paginate(10);$curriculums=DB::table('curriculums')->where('active',true)->get();return view('imports',compact('logs','curriculums'));}
public function store(Request $request,ImportService $service){$data=$request->validate(['source'=>'required|in:admission,ems','file'=>'required_without:json|nullable|file|max:2048','json'=>'required_without:file|nullable|string|max:2097152','curriculum_id'=>'nullable|exists:curriculums,id']);try{$payload=json_decode(($request->hasFile('file')?file_get_contents($request->file('file')->getRealPath()):$data['json']),true,64,JSON_THROW_ON_ERROR);}catch(\JsonException){AcademicService::fail('The uploaded file is not valid JSON.');}if(!is_array($payload))AcademicService::fail('The JSON envelope must be an object.');$result=$service->run($data['source'],$payload,$request->filled('curriculum_id')?$request->integer('curriculum_id'):null);return back()->with('status','Import complete: '.$result['created'].' created, '.$result['updated'].' updated, '.$result['rejected'].' rejected.');}
}