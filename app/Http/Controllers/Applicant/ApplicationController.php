<?php

namespace App\Http\Controllers\Applicant;

use App\Actions\Recruitment\CancelApplication;
use App\Actions\Recruitment\RecruitmentException;
use App\Actions\Recruitment\RequestInterviewReschedule;
use App\Actions\Recruitment\RespondToOffer;
use App\Actions\Recruitment\SubmitApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitApplicationRequest;
use App\Models\Application;
use App\Models\Interview;
use App\Models\JobTitle;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * An applicant's own applications: the application form for a position, the list, one application with its schedule and offer, and
 * the applicant's answers (cancel, ask to reschedule, accept or decline the offer).
 */
class ApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $position = JobTitle::query()->openForApplications()->with(['college', 'office'])->find($request->integer('position'));

        if (! $position) {
            return to_route('applicant.positions')->with('error', 'Choose an open position to apply for.');
        }

        if (! $request->user()->applicantProfile->isComplete()) {
            return to_route('applicant.profile', ['position' => $position->id])->with('error', 'Complete your profile first. You only do this once.');
        }

        $existing = Application::query()->where('user_id', $request->user()->id)->where('job_title_id', $position->id)->open()->first();

        if ($existing) {
            return to_route('applicant.applications.show', $existing)->with('error', 'You already applied for this position.');
        }

        return view('applicant.apply', ['position' => $position, 'profile' => $request->user()->applicantProfile]);
    }

    public function store(SubmitApplicationRequest $request, SubmitApplication $submit): RedirectResponse
    {
        $position = JobTitle::query()->findOrFail($request->integer('job_title_id'));

        try {
            $application = $submit->handle(
                $request->user(),
                $position,
                [
                    'education' => $request->educationRows(),
                    'work_history' => $request->workRows(),
                    'portfolio_url' => $request->input('portfolio_url'),
                    'cover_note' => $request->input('cover_note'),
                ],
                $request->file('resume'),
                $request->file('portfolio'),
            );
        } catch (RecruitmentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('applicant.applications.show', $application)->with('success', 'Your application was sent. HR will review your documents.');
    }

    public function index(Request $request): View
    {
        return view('applicant.applications', [
            'applications' => Application::query()
                ->where('user_id', $request->user()->id)
                ->with(['jobTitle.college', 'jobTitle.office', 'interviews'])
                ->latest('id')
                ->get(),
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        abort_unless($application->user_id === $request->user()->id, 404);

        $application->load(['jobTitle.college', 'jobTitle.office', 'interviews.interviewer', 'offers.jobTitle', 'events', 'employee', 'documents']);

        return view('applicant.application', [
            'application' => $application,
            'interview' => $application->activeInterview(),
            'offer' => $application->pendingOffer(),
            'cutoffHours' => Interview::CANCEL_CUTOFF_HOURS,
        ]);
    }

    public function cancel(Request $request, Application $application, CancelApplication $cancelApplication): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 404);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $cancelApplication->handle($request->user(), $application, $data['reason'] ?? null);
        } catch (RecruitmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('applicant.applications.show', $application)->with('success', 'Your application was cancelled.');
    }

    public function requestReschedule(Request $request, Application $application, Interview $interview, RequestInterviewReschedule $requestReschedule): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id && $interview->application_id === $application->id, 404);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'available_days' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Say why you cannot attend.',
            'available_days.required' => 'Tell HR which days you are available.',
        ]);

        try {
            $requestReschedule->handle($request->user(), $interview, $data['reason'], $data['available_days']);
        } catch (RecruitmentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('applicant.applications.show', $application)->with('success', 'HR was told. They will set a new time and notify you.');
    }

    public function acceptOffer(Request $request, Application $application, Offer $offer, RespondToOffer $respond): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id && $offer->application_id === $application->id, 404);

        $data = $request->validate(['signed_name' => ['required', 'string', 'max:150'], 'agree' => ['accepted']], [
            'signed_name.required' => 'Type your full name to sign.',
            'agree.accepted' => 'Confirm that you have read the offer.',
        ]);

        try {
            $respond->accept($request->user(), $offer, $data['signed_name'], $request->ip());
        } catch (RecruitmentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('applicant.applications.show', $application)->with('success', 'You accepted the offer. HR will complete your hiring.');
    }

    public function declineOffer(Request $request, Application $application, Offer $offer, RespondToOffer $respond): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id && $offer->application_id === $application->id, 404);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $respond->decline($request->user(), $offer, $data['reason'] ?? null);
        } catch (RecruitmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('applicant.applications.show', $application)->with('success', 'You declined the offer.');
    }
}
