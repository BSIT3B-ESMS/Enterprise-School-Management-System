<?php

namespace App\Http\Controllers\Applicant;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $applications = Application::query()->where('user_id', $user->id)->with(['jobTitle.college', 'jobTitle.office', 'interviews', 'offers', 'employee.user'])->latest('id')->get();

        return view('applicant.dashboard', [
            'profile' => $user->applicantProfile,
            'applications' => $applications,
            'nextInterview' => Interview::query()
                ->scheduled()
                ->whereHas('application', fn ($query) => $query->where('user_id', $user->id))
                ->with('application.jobTitle')
                ->orderBy('starts_at')
                ->first(),
            'offers' => $applications->filter(fn (Application $application): bool => $application->status === ApplicationStatus::Offered && $application->pendingOffer() !== null),
            'hired' => $applications->first(fn (Application $application): bool => $application->status === ApplicationStatus::Hired),
            'counts' => collect(ApplicationStatus::pipeline())->mapWithKeys(fn (ApplicationStatus $status): array => [$status->value => $applications->where('status', $status)->count()]),
        ]);
    }
}
