@extends('layouts.admin')

@section('title', 'Colleges · '.config('app.name'))

{{-- The academic colleges, their programs, and their deans. Everyone in HR reads this page; only the HR Head adds or edits. --}}
@section('content')
    <div class="d-grid gap-4" data-page="colleges">
        <div>
            <h1 class="page-title">Colleges</h1>
            <p class="text-body-secondary mt-1 mb-0">The academic colleges, their programs, and their faculty</p>
        </div>

        @can('manage-reference-data')
            <section class="card employee-form-card" aria-labelledby="add-college-title">
                <h2 class="fs-5" id="add-college-title">Add a college</h2>
                <form method="post" action="{{ route('colleges.store') }}" class="row g-3 align-items-start" novalidate>
                    @csrf
                    <div class="col-md-3"><x-field name="code" label="Code" required /></div>
                    <div class="col-md-6"><x-field name="name" label="Name" required /></div>
                    <div class="col-md-3 align-self-end"><button type="submit" class="btn btn-primary w-100">Add College</button></div>
                </form>
            </section>
        @endcan

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">College</th>
                            <th scope="col">Dean</th>
                            <th scope="col">Programs</th>
                            <th scope="col" class="text-end">People</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($colleges as $college)
                            <tr>
                                <td class="tabular-nums">{{ $college->code }}</td>
                                <td>
                                    <a class="text-reset fw-medium" href="{{ route('employees.index', ['college' => $college->id]) }}">{{ $college->name }}</a>
                                </td>
                                <td>{{ $college->deans->first()?->employee->full_name ?? '—' }}</td>
                                <td>
                                    @forelse ($college->programs as $program)
                                        <span class="ems-chip" title="{{ $program->name }}">{{ $program->code }}</span>
                                    @empty
                                        <span class="text-body-secondary">—</span>
                                    @endforelse
                                </td>
                                <td class="text-end tabular-nums">{{ $college->employees_count }}</td>
                                <td>{{ $college->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    @can('manage-reference-data')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('colleges.edit', $college) }}">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-body-secondary py-4">No colleges yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
