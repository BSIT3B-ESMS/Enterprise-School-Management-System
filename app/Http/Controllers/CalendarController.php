<?php

namespace App\Http\Controllers;

use App\Enums\InterviewStatus;
use App\Enums\LeaveStatus;
use App\Models\CalendarEvent;
use App\Models\College;
use App\Models\Interview;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The HR calendar: demo interviews, HR events, and approved employee leave in a month grid, all from the database. An interview or an
 * event that has not happened can be dragged to another day (never earlier than today); the page then asks for the reason and the time.
 */
class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'who' => ['nullable', 'in:all,mine'],
            'show' => ['nullable', 'in:all,interviews,events,leave'],
            'college' => ['nullable', 'integer'],
        ]);

        $month = CarbonImmutable::parse(($data['month'] ?? now()->format('Y-m')).'-01')->startOfMonth();
        $who = $data['who'] ?? 'all';
        $show = $data['show'] ?? 'all';
        $collegeId = $data['college'] ?? null;
        $gridStart = $month->startOfWeek(CarbonImmutable::SUNDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SATURDAY);

        $interviews = Interview::query()
            ->with(['application.applicant.applicantProfile', 'application.jobTitle.college', 'interviewer'])
            ->whereBetween('starts_at', [$gridStart, $gridEnd])
            ->when($who === 'mine', fn ($query) => $query->where('interviewer_id', $request->user()->id))
            ->when($collegeId, fn ($query, $college) => $query->whereHas('application.jobTitle', fn ($titles) => $titles->where('college_id', $college)))
            ->orderBy('starts_at')
            ->get();

        if (! in_array($show, ['all', 'interviews'], true)) {
            $interviews = collect();
        }

        $scheduled = Interview::query()->scheduled()->whereBetween('starts_at', [$gridStart->subDay(), $gridEnd->addDay()])->get(['id', 'interviewer_id', 'starts_at', 'ends_at']);

        $events = in_array($show, ['all', 'events'], true)
            ? CalendarEvent::query()->whereBetween('starts_at', [$gridStart, $gridEnd])->orderBy('starts_at')->get()
            : collect();

        $leave = ! in_array($show, ['all', 'leave'], true) ? collect() : LeaveRequest::query()
            ->with('employee')
            ->when($collegeId, fn ($query, $college) => $query->whereHas('employee', fn ($people) => $people->where('college_id', $college)))
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $gridEnd)->whereDate('end_date', '>=', $gridStart)
            ->get();

        $days = [];

        for ($day = $gridStart; $day->lte($gridEnd); $day = $day->addDay()) {
            $key = $day->toDateString();
            $days[] = [
                'date' => $day,
                'inMonth' => $day->month === $month->month,
                'isToday' => $day->isToday(),
                'canDrop' => $day->gte(CarbonImmutable::today()),
                'interviews' => $interviews->filter(fn (Interview $interview): bool => $interview->starts_at->toDateString() === $key)->values(),
                'events' => $events->filter(fn (CalendarEvent $event): bool => $event->starts_at->toDateString() === $key)->values(),
                'leave' => $leave->filter(fn (LeaveRequest $request): bool => $request->start_date->toDateString() <= $key && $request->end_date->toDateString() >= $key)->values(),
            ];
        }

        return view('calendar.index', [
            'month' => $month,
            'weeks' => array_chunk($days, 7),
            'who' => $who,
            'show' => $show,
            'collegeId' => $collegeId,
            'colleges' => College::query()->orderBy('name')->pluck('name', 'id'),
            'interviewers' => User::query()->whereIn('role', ['hr_admin', 'hr_staff'])->where('is_active', true)->orderBy('name')->get(),
            'eventTypes' => CalendarEvent::TYPES,
            'conflictIds' => $scheduled->filter(fn (Interview $one): bool => $scheduled->contains(fn (Interview $other): bool => $other->id !== $one->id && $other->interviewer_id === $one->interviewer_id && $other->starts_at < $one->ends_at && $other->ends_at > $one->starts_at))->pluck('id')->all(),
            'interviewStatus' => InterviewStatus::class,
        ]);
    }
}
