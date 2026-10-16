@extends('layouts.admin')

@section('title', 'New Employee · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4" data-page="employee-create">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('employees.index') }}" aria-label="Back to employees">
                <x-icon name="back" />
            </a>
            <h1 class="page-title">New Employee</h1>
        </div>

        <form class="card employee-form-card" novalidate data-employee-form
            data-employee-form-redirect="{{ route('employees.index') }}">
            <x-employee-form-fields heading-tag="h2" />

            <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                <a class="btn btn-outline-secondary" href="{{ route('employees.index') }}">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Employee</button>
            </div>
        </form>
    </div>
@endsection
