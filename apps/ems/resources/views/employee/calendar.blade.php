@extends('layouts.employee')

@section('title', 'Calendar · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Calendar</h1>
            <p class="text-body-secondary mt-1 mb-0">Events HR shared with everyone, and your approved leave.</p>
        </div>

        <section class="card employee-form-card">
            <h2 class="fs-5">Events</h2>
            <ul class="list-group list-group-flush">
                @forelse ($events as $event)
                    <li class="list-group-item px-0"><span class="fw-medium">{{ $event->title }}</span> <span class="ems-chip">{{ $event->typeLabel() }}</span>
                        <small class="d-block text-body-secondary">{{ $event->starts_at->format('D, M j, Y g:i A') }}{{ $event->venue ? ' · '.$event->venue : '' }}</small></li>
                @empty
                    <li class="list-group-item px-0 text-body-secondary">No events.</li>
                @endforelse
            </ul>
        </section>

        <section class="card employee-form-card">
            <h2 class="fs-5">My leave</h2>
            <ul class="list-group list-group-flush">
                @forelse ($leave as $request)
                    <li class="list-group-item px-0">{{ $request->leave_type->label() }}: {{ $request->start_date->format('M j') }} to {{ $request->end_date->format('M j, Y') }}</li>
                @empty
                    <li class="list-group-item px-0 text-body-secondary">No approved leave coming up.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
