<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $interviews = Interview::query()
            ->whereHas('application', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['application.jobTitle', 'interviewer'])
            ->orderBy('starts_at')
            ->get();

        return view('applicant.calendar', [
            'upcoming' => $interviews->filter(fn (Interview $interview): bool => $interview->status->value === 'scheduled' && $interview->starts_at->isFuture()),
            'past' => $interviews->reject(fn (Interview $interview): bool => $interview->status->value === 'scheduled' && $interview->starts_at->isFuture())->reverse(),
        ]);
    }
}
