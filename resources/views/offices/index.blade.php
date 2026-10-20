@extends('layouts.admin')

@section('title', 'Offices · '.config('app.name'))

{{-- The non-teaching offices. Everyone in HR reads this page; only the HR Admin adds or edits. --}}
@section('content')
    <div class="d-grid gap-4" data-page="offices">
        <div>
            <h1 class="page-title">Offices</h1>
            <p class="text-body-secondary mt-1 mb-0">The non-teaching offices and their staff</p>
        </div>

        @can('manage-reference-data')
            <section class="card employee-form-card" aria-labelledby="add-office-title">
                <h2 class="fs-5" id="add-office-title">Add an office</h2>
                <form method="post" action="{{ route('offices.store') }}" class="row g-3 align-items-start" novalidate>
                    @csrf
                    <div class="col-md-3"><x-field name="code" label="Code" required /></div>
                    <div class="col-md-6"><x-field name="name" label="Name" required /></div>
                    <div class="col-md-3 align-self-end"><button type="submit" class="btn btn-primary w-100">Add Office</button></div>
                </form>
            </section>
        @endcan

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Office</th>
                            <th scope="col" class="text-end">Job titles</th>
                            <th scope="col" class="text-end">Staff</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($offices as $office)
                            <tr>
                                <td class="tabular-nums">{{ $office->code }}</td>
                                <td>
                                    <a class="text-reset fw-medium" href="{{ route('employees.index', ['office' => $office->id, 'type' => 'non-teaching']) }}">{{ $office->name }}</a>
                                </td>
                                <td class="text-end tabular-nums">{{ $office->job_titles_count }}</td>
                                <td class="text-end tabular-nums">{{ $office->employees_count }}</td>
                                <td>{{ $office->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    @can('manage-reference-data')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('offices.edit', $office) }}">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-body-secondary py-4">No offices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
