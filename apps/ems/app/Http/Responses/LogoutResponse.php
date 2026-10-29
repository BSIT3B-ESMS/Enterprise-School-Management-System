<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? response()->noContent()
            : redirect()->route('login')->with('success', 'You have been signed out.');
    }
}
