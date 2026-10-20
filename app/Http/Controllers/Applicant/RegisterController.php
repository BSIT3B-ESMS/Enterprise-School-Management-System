<?php

namespace App\Http\Controllers\Applicant;

use App\Enums\AuditAction;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterApplicantRequest;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Creating an applicant account: separate first, middle, and last name, a personal email, and a password. The applicant then
 * fills in their one-time profile (personal and contact information).
 */
class RegisterController extends Controller
{
    public function show(): View
    {
        $position = request()->integer('position') ?: null;

        return view('applicant.register', [
            'position' => $position ? JobTitle::query()->openForApplications()->find($position) : null,
        ]);
    }

    public function store(RegisterApplicantRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $audit): User {
            $user = new User(['name' => '', 'email' => $request->input('email'), 'password' => $request->input('password')]);
            $user->name = implode(' ', array_filter([$request->input('first_name'), $request->input('middle_name'), $request->input('last_name'), $request->input('suffix')]));
            $user->forceFill(['role' => Role::Applicant, 'is_active' => true, 'password_changed_at' => now(), 'privacy_acknowledged_at' => now()])->save();

            $user->applicantProfile()->create($request->safe()->only(['first_name', 'middle_name', 'last_name', 'suffix']));

            $audit->log(AuditAction::Create, $user, ['role' => 'applicant'], $user, 'applicant');

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $position = $request->integer('position') ?: null;

        return to_route('applicant.profile', array_filter(['position' => $position]))
            ->with('success', 'Your account is ready. Complete your profile once, then you can apply.');
    }
}
