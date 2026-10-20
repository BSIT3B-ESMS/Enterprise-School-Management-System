<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * The page where staff set up, confirm, and manage their authenticator app. The actions themselves (enable, confirm,
 * regenerate recovery codes, disable) are Laravel Fortify routes.
 */
class TwoFactorSetupController extends Controller
{
    public function show(): View
    {
        return view('account.two-factor', ['user' => auth()->user()]);
    }
}
