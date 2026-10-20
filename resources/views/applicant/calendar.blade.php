@extends('layouts.applicant')

@section('title', 'Calendar · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">My Calendar</h1>
            <p class="text-body-secondary mt-1 mb-0">The demo interviews HR sets for you.</p>
        </div>

        @foreach (['Upcoming' => $upcoming, 'Earlier' => $past] as $heading => $interviews)
            <section class="card employee-form-card" aria-labelledby="cal-{{ $loop->index }}">
                <h2 class="fs-5" id="cal-{{ $loop->index }}">{{ $heading }}</h2>
                <ul class="list-group list-group-flush">
                    @forelse ($interviews as $interview)
                        <li class="list-group-item px-0">
                            <div class="fw-medium">
                                {{ $interview->starts_at->format('D, M j, Y') }} · {{ $interview->starts_at->format('g:i A') }} to {{ $interview->ends_at->format('g:i A') }}
                                <span class="status-pill">{{ $interview->status->label() }}</span>
                            </div>
                            <small class="d-block text-body-secondary">
                                {{ $interview->application->jobTitle->title }} · {{ $interview->placeLabel() }} · Interviewer: {{ $interview->interviewer->name }}
                            </small>
                            <a class="small" href="{{ route('applicant.applications.show', $interview->application) }}">Open application</a>
                        </li>
                    @empty
                        <li class="list-group-item px-0 text-body-secondary">Nothing here.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
@endsection
