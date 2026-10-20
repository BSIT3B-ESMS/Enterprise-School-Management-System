<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\CompleteOnboardingRequest;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * The first-login setup for a new hire: an emergency contact and the government and tax IDs. Shown after the
 * required password change and before anything else.
 */
class OnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->employee_id === null || $user->onboarding_completed_at !== null) {
            return redirect($user->homeUrl());
        }

        return view('auth.onboarding', ['employee' => $user->employee]);
    }

    public function store(CompleteOnboardingRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        DB::transaction(function () use ($request, $user, $employee, $audit): void {
            $employee->setGovernmentIds(collect($request->safe()->only(['tin', 'gsis', 'sss', 'philhealth', 'pagibig', 'bir']))->filter(fn ($number): bool => filled($number))->all());
            $employee->save();

            $employee->emergencyContacts()->update(['is_primary' => false]);
            $contact = $employee->emergencyContacts()->create([
                'name' => $request->string('contact_name')->trim()->toString(),
                'relationship' => $request->string('contact_relationship')->trim()->toString(),
                'phone' => $request->string('contact_phone')->toString(),
                'address' => $request->filled('contact_address') ? $request->string('contact_address')->trim()->toString() : null,
                'is_primary' => true,
            ]);

            $user->forceFill(['onboarding_completed_at' => now()])->save();

            $audit->log(AuditAction::Update, $employee, ['event' => 'onboarding_completed', 'emergency_contact_id' => $contact->id], $user);
        });

        return redirect($user->homeUrl())->with('success', 'Thank you. Your details are saved.');
    }
}
