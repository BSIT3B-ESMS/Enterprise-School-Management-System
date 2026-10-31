<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\ApiResponse;
class IdempotentPost {
    public function handle(Request $request, Closure $next) {
        $key = $request->header('Idempotency-Key');
        if (!is_string($key) || !Str::isUuid($key)) return ApiResponse::error('A UUID Idempotency-Key header is required.',422,['Idempotency-Key'=>['Supply a new UUID for this operation.']]);
        $token = $request->attributes->get('integration_token');
        $hash = hash('sha256',$request->method().'|'.$request->path().'|'.$request->getContent());
        return DB::transaction(function () use ($token,$key,$hash,$request,$next) {
            DB::table('api_idempotency_keys')->insertOrIgnore(['integration_token_id'=>$token->id,'key'=>$key,'request_hash'=>$hash,'expires_at'=>now()->addHours(24),'created_at'=>now(),'updated_at'=>now()]);
            $q = DB::table('api_idempotency_keys')->where('integration_token_id',$token->id)->where('key',$key);
            $row = (clone $q)->lockForUpdate()->first();
            if (\Illuminate\Support\Carbon::parse($row->expires_at)->isPast()) {
                $q->update(['request_hash'=>$hash,'response_status'=>null,'response_body'=>null,'expires_at'=>now()->addHours(24),'updated_at'=>now()]);
            } else {
                if (!hash_equals($row->request_hash,$hash)) return ApiResponse::error('Idempotency-Key was used with a different request.',409,['code'=>'idempotency_conflict']);
                if ($row->response_status !== null) return response()->json(json_decode($row->response_body),$row->response_status)->header('Idempotency-Replayed','true');
            }
            try { $response = $next($request); }
            catch (\Illuminate\Validation\ValidationException $e) { $response = ApiResponse::error('Validation failed',422,$e->errors()); }
            // A server exception rolls back both the reservation and the operation.
            if ($response->getStatusCode() >= 500) throw new \RuntimeException('Operation did not complete.');
            $q->update(['response_status'=>$response->getStatusCode(),'response_body'=>$response->getContent(),'updated_at'=>now()]);
            return $response;
        },3);
    }
}
