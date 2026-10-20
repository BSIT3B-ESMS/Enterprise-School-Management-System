<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Staff passwords are reset by PLP HR, not by a self-service email link.
     */
    public function __invoke(): View
    {
        return view('auth.forgot-password', ['hrEmail' => config('ems.hr_email')]);
    }
}
