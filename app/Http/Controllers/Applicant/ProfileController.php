<?php

namespace App\Http\Controllers\Applicant;

use App\Enums\AuditAction;
use App\Enums\MaritalStatus;
use App\Enums\Sex;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The applicant's one-time profile: personal information (name, birthdate, sex, civil status) and contact information (phone
 * and address; the email is the login). Education, work history, resume, and portfolio belong to each application.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('applicant.profile', [
            'profile' => $request->user()->applicantProfile,
            'sexes' => array_column(Sex::cases(), 'value', 'value'),
            'civilStatuses' => array_column(MaritalStatus::cases(), 'value', 'value'),
            'position' => $request->integer('position') ?: null,
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birthdate' => ['required', 'date', 'before:-15 years'],
            'sex' => ['required', Rule::enum(Sex::class)],
            'marital_status' => ['required', Rule::enum(MaritalStatus::class)],
            'phone' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'address' => ['required', 'string', 'max:255'],
        ], [
            'phone.regex' => 'Enter a valid mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
            'birthdate.before' => 'Check the birthdate: an applicant must be at least 15 years old.',
        ]);

        $user = $request->user();
        $profile = $user->applicantProfile;

        DB::transaction(function () use ($user, $profile, $data, $audit): void {
            $profile->fill($data);
            $changes = $audit->changesFor($profile);
            $profile->save();

            $user->forceFill(['name' => $profile->full_name])->save();

            if ($changes !== []) {
                $audit->log(AuditAction::Update, $profile, $changes, $user, 'applicant');
            }
        });

        $position = $request->integer('position');

        return $position && $profile->fresh()->isComplete()
            ? to_route('applicant.apply', ['position' => $position])->with('success', 'Profile saved. Now fill in your application.')
            : to_route('applicant.profile')->with('success', 'Profile saved.');
    }
}
