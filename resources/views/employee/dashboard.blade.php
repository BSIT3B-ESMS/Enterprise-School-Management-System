@extends('layouts.employee')

@section('title', 'Dashboard · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Welcome, {{ $employee->first_name }}</h1>
            <p class="text-body-secondary mt-1 mb-0">{{ $employee->jobTitle->title }} · {{ $employee->college?->name ?? $employee->office?->name }} · {{ $employee->employee_no }}</p>
        </div>

        @if ($pendingOffers > 0)
            <div class="alert alert-primary mb-0" role="status">
                You have {{ $pendingOffers }} {{ \Illuminate\Support\Str::plural('agreement', $pendingOffers) }} waiting for your answer.
                <a href="{{ route('employee.offers') }}">Read and answer</a>.
            </div>
        @endif

        @if ($employee->contract_ends_on)
            <div class="alert {{ $employee->contractEndsWithin(30) ? 'alert-warning' : 'alert-secondary' }} mb-0" role="status">
                Your contract ends on <strong>{{ $employee->contract_ends_on->format('F j, Y') }}</strong>.
                @if ($employee->contractEndsWithin(30)) HR will tell you if a new contract is offered. @endif
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <section class="card dashboard-panel">
                    <h2>Leave credits</h2>
                    <p class="dashboard-panel-subtitle">Earned each month since you were hired</p>
                    <ul class="list-group list-group-flush dashboard-list">
                        @foreach ($balances as $row)
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>{{ $row['label'] }}</span><strong class="tabular-nums">{{ rtrim(rtrim(number_format($row['remaining'], 2), '0'), '.') ?: '0' }} left</strong></li>
                        @endforeach
                    </ul>
                    <div class="mt-2"><a href="{{ route('employee.leave') }}">File or follow a leave request</a></div>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="card dashboard-panel">
                    <h2>Upcoming</h2>
                    <ul class="list-group list-group-flush dashboard-list">
                        @foreach ($upcomingLeave as $leave)
                            <li class="list-group-item px-0">{{ $leave->leave_type->label() }}: {{ $leave->start_date->format('M j') }} to {{ $leave->end_date->format('M j, Y') }}</li>
                        @endforeach
                        @foreach ($events as $event)
                            <li class="list-group-item px-0">{{ $event->title }}: {{ $event->starts_at->format('M j, Y g:i A') }}</li>
                        @endforeach
                        @if ($upcomingLeave->isEmpty() && $events->isEmpty())
                            <li class="list-group-item px-0 text-body-secondary">Nothing coming up.</li>
                        @endif
                    </ul>
                </section>
            </div>
        </div>

        <section class="card dashboard-panel">
            <h2>Notifications</h2>
            <ul class="list-group list-group-flush dashboard-list">
                @forelse ($unread as $notification)
                    <li class="list-group-item px-0"><span class="fw-medium">{{ $notification->data['title'] }}</span><small class="d-block text-body-secondary">{{ $notification->data['body'] }}</small></li>
                @empty
                    <li class="list-group-item px-0 text-body-secondary">You are all caught up.</li>
                @endforelse
            </ul>
            <div class="mt-2"><a href="{{ route('notifications.index') }}">View all notifications</a></div>
        </section>
    </div>
@endsection
