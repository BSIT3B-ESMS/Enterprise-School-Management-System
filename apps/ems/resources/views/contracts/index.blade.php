@extends('layouts.admin')

@section('title', 'Contracts · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Contracts</h1>
            <p class="text-body-secondary mt-1 mb-0">Contracts that end within 60 days or already ended. Send a new contract offer, or terminate the employee. Nothing changes on its own at the end date.</p>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0 employees-table">
                    <thead><tr><th scope="col">Employee</th><th scope="col">Employment</th><th scope="col">Contract ends</th><th scope="col">Offer</th><th scope="col"><span class="visually-hidden">Decide</span></th></tr></thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr @class(['table-warning' => $employee->contract_ends_on->isPast()])>
                                <td>
                                    <a class="text-reset fw-medium" href="{{ route('employees.show', $employee) }}">{{ $employee->last_name }}, {{ $employee->first_name }}</a>
                                    <small class="d-block text-body-secondary">{{ $employee->jobTitle->title }} · {{ $employee->college?->code ?? $employee->office?->code }}</small>
                                </td>
                                <td>{{ $employee->employment_type->label() }}, {{ $employee->weekly_hours }} h/week</td>
                                <td class="text-nowrap">{{ $employee->contract_ends_on->format('M j, Y') }} @if ($employee->contract_ends_on->isPast()) <span class="badge text-bg-danger">Ended</span> @endif</td>
                                <td>{{ $employee->offers->isNotEmpty() ? 'Waiting for the employee' : 'None sent' }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('employees.show', $employee) }}#contract-title">Decide</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="employees-empty text-center"><strong>No contracts need a decision</strong></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="employees-footer">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection
