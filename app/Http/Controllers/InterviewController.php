<?php

namespace App\Http\Controllers;

use App\Actions\Recruitment\CancelInterview;
use App\Actions\Recruitment\ChangeInterviewer;
use App\Actions\Recruitment\CompleteInterview;
use App\Actions\Recruitment\InterviewConflictException;
use App\Actions\Recruitment\RecruitmentException;
use App\Actions\Recruitment\RescheduleInterview;
use App\Actions\Recruitment\ScheduleDemoInterview;
use App\Enums\InterviewMode;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * HR's demo-interview actions: schedule (date, time, venue or online link, interviewer), reschedule (a reason is optional
 * when answering an applicant's request), change interviewer, cancel, and mark completed. The same actions serve the
 * Recruitment page and the Calendar (including its drag and drop, which answers in JSON).
 */
class InterviewController extends Controller
{
    public function store(Request $request, Application $application, ScheduleDemoInterview $schedule): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'mode' => ['required', Rule::enum(InterviewMode::class)],
            'venue' => ['nullable', 'string', 'max:255', 'required_if:mode,face_to_face'],
            'meeting_link' => ['nullable', 'url', 'max:255', 'required_if:mode,online'],
            'interviewer_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'venue.required_if' => 'Give the venue for a face-to-face interview.',
            'meeting_link.required_if' => 'Give the link for an online interview.',
            'end_time.after' => 'The interview must end after it starts.',
        ]);

        try {
            $schedule->handle(
                $request->user(),
                $application,
                User::query()->findOrFail($data['interviewer_id']),
                $this->at($data['date'], $data['start_time']),
                $this->at($data['date'], $data['end_time']),
                InterviewMode::from($data['mode']),
                $data['venue'] ?? null,
                $data['meeting_link'] ?? null,
                $data['notes'] ?? null,
            );
        } catch (InterviewConflictException $exception) {
            return $this->withConflicts(back()->withInput(), $exception, 'schedule');
        } catch (RecruitmentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('recruitment.show', $application)->with('success', 'The demo interview is scheduled. The applicant and the interviewer were notified.');
    }

    /**
     * Moves an interview to another day and/or time with a reason. Dragging on the Calendar sends only the new date (and the
     * reason); the times stay and the interview keeps its length.
     */
    public function reschedule(Request $request, Interview $interview, RescheduleInterview $reschedule): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'reason' => [$interview->reschedule_requested_at ? 'nullable' : 'required', 'string', 'max:500'],
            'mode' => ['nullable', Rule::enum(InterviewMode::class)],
            'venue' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
        ], ['reason.required' => 'Give a reason for moving the interview.']);

        $duration = $interview->starts_at->diffInMinutes($interview->ends_at);
        $start = $this->at($data['date'], $data['start_time'] ?? $interview->starts_at->format('H:i'));
        $end = isset($data['end_time']) ? $this->at($data['date'], $data['end_time']) : $start->copy()->addMinutes((int) $duration);

        try {
            $reschedule->handle(
                $request->user(),
                $interview,
                $start,
                $end,
                filled($data['reason'] ?? null) ? $data['reason'] : 'Rescheduled at the request of the applicant',
                isset($data['mode']) ? InterviewMode::from($data['mode']) : null,
                $data['venue'] ?? null,
                $data['meeting_link'] ?? null,
            );
        } catch (InterviewConflictException $exception) {
            return $request->expectsJson() ? $this->conflictJson($exception) : $this->withConflicts(back()->withInput(), $exception, 'reschedule');
        } catch (RecruitmentException $exception) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $exception->getMessage()], 422)
                : back()->withInput()->with('error', $exception->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'The interview was moved and the applicant was notified.'])
            : back()->with('success', 'The interview was moved. The applicant was notified.');
    }

    public function changeInterviewer(Request $request, Interview $interview, ChangeInterviewer $change): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['interviewer_id' => ['required', 'integer', 'exists:users,id']]);

        try {
            $change->handle($request->user(), $interview, User::query()->findOrFail($data['interviewer_id']));
        } catch (InterviewConflictException $exception) {
            return $request->expectsJson() ? $this->conflictJson($exception) : $this->withConflicts(back()->withInput(), $exception, 'interviewer');
        } catch (RecruitmentException $exception) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $exception->getMessage()], 422)
                : back()->withInput()->with('error', $exception->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'The interviewer was changed. Both interviewers were notified.'])
            : back()->with('success', 'The interviewer was changed. Both interviewers were notified.');
    }

    public function cancel(Request $request, Interview $interview, CancelInterview $cancel): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Give a reason for cancelling the interview.']);

        try {
            $cancel->handle($request->user(), $interview, $data['reason']);
        } catch (RecruitmentException $exception) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $exception->getMessage()], 422)
                : back()->with('error', $exception->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'The interview was cancelled and the applicant was notified.'])
            : back()->with('success', 'The interview was cancelled. The applicant was notified.');
    }

    public function complete(Request $request, Interview $interview, CompleteInterview $complete): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        try {
            $complete->handle($request->user(), $interview, $data['notes'] ?? null);
        } catch (RecruitmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'The demo interview is marked as completed. Now reject the application or issue an offer.');
    }

    private function at(string $date, string $time): Carbon
    {
        return Carbon::parse("{$date} {$time}");
    }

    /**
     * Sends the overlap back as a flash message. The form name is kept so the page can reopen the form that caused it: the
     * Reschedule and Change interviewer forms are collapsible, and a warning inside a collapsed form would be invisible.
     */
    private function withConflicts(RedirectResponse $response, InterviewConflictException $exception, string $form): RedirectResponse
    {
        return $response->with('conflicts', [
            'form' => $form,
            'messages' => $exception->conflicts,
            'free' => $exception->freeInterviewers->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])->values()->all(),
        ]);
    }

    private function conflictJson(InterviewConflictException $exception): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'conflict' => true,
            'message' => $exception->getMessage(),
            'conflicts' => $exception->conflicts,
            'free' => $exception->freeInterviewers->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])->values()->all(),
        ], 409);
    }
}
