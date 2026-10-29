@extends('layouts.applicant')

@section('title', 'My Applications · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">My Applications</h1>
            <p class="text-body-secondary mt-1 mb-0">Follow each application from Applied to Hired.</p>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            <th scope="col">Position</th>
                            <th scope="col">Applied On</th>
                            <th scope="col">Status</th>
                            <th scope="col">Next</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $application)
                            @php($interview = $application->activeInterview())
                            <tr>
                                <td>
                                    <a class="text-reset fw-medium" href="{{ route('applicant.applications.show', $application) }}">{{ $application->jobTitle->title }}</a>
                                    <small class="d-block text-body-secondary">{{ $application->jobTitle->college?->name ?? $application->jobTitle->office?->name }}</small>
                                </td>
                                <td class="text-nowrap">{{ $application->applied_on->format('M j, Y') }}</td>
                                <td><span class="status-pill {{ $application->status->cssClass() }}">{{ $application->status->label() }}</span></td>
                                <td>
                                    @if ($interview)
                                        Demo interview {{ $interview->starts_at->format('M j, Y g:i A') }}
                                    @elseif ($application->status === \App\Enums\ApplicationStatus::Offered)
                                        <strong>Answer your offer</strong>
                                    @elseif ($application->status === \App\Enums\ApplicationStatus::Applied)
                                        HR is reviewing your documents
                                    @else
                                        <span class="text-body-secondary">None</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="employees-empty text-center"><strong>No applications yet</strong> <a href="{{ route('applicant.positions') }}">Browse the open positions</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
