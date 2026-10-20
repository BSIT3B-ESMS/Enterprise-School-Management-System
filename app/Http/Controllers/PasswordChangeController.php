<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\ChangeOwnPassword;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth.change-password', ['isForced' => auth()->user()->must_change_password]);
    }

    public function update(ChangePasswordRequest $request, ChangeOwnPassword $changePassword): RedirectResponse
    {
        $changePassword->handle($request->user(), $request->string('password')->toString());

        return redirect()->intended($request->user()->homeUrl())
            ->with('success', 'Your password was changed. Your other sessions were signed out.');
    }
}
