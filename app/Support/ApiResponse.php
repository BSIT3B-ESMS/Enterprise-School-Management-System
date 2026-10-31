<?php
namespace App\Support;
use Illuminate\Http\JsonResponse;
class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => (object) []], $code)->header('Cache-Control', 'no-store');
    }
    public static function error(string $message, int $code, array $errors = [], mixed $data = null): JsonResponse
    {
        $body = ['message' => $message];
        if (isset($errors['code'])) { $body['code'] = strtolower($errors['code']); unset($errors['code']); }
        if ($errors) $body['errors'] = $errors;
        // Legacy browser conflict recovery metadata; never expose a full state in an app-to-app error.
        if ($data !== null) $body['meta'] = $data;
        return response()->json($body, $code)->header('Cache-Control', 'no-store');
    }
}
