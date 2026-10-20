@extends('layouts.admin')

@section('title', 'Job Titles · '.config('app.name'))

{{--
    The job titles of each college and office, with the pay band that sets each one's base rate (step 1 to step 8, in
    force today). Everyone in HR reads this page; only the HR Head adds or edits titles and rates.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="job-titles">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Job Titles</h1>
                <p class="text-body-secondary mt-1 mb-0">Positions of each college and office, and their base pay</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('rates.index') }}">Pay Rates</a>
                @can('manage-reference-data')
                    <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="{{ route('job-titles.create') }}">
                        <x-icon name="plus" />
                        Add Job Title
                    </a>
                @endcan
            </div>
        </div>

        <form method="get" class="card employees-filters" data-auto-submit>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="job-title-search" class="visually-hidden">Search job titles</label>
                    <div class="search-field">
                        <x-icon name="search" />
                        <input type="search" class="form-control" id="job-title-search" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Search job titles..." autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="job-title-unit" class="visually-hidden">College or office</label>
                    <select class="form-select" id="job-title-unit" name="unit">
                        <option value="">All colleges and offices</option>
                        @foreach ($unitOptions as $group => $options)
                            <optgroup label="{{ $group }}">
                                @foreach ($options as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['unit'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="job-title-category" class="visually-hidden">Category</label>
                    <select class="form-select" id="job-title-category" name="category">
                        <option value="">Faculty and non-teaching</option>
                        <option value="faculty" @selected(($filters['category'] ?? '') === 'faculty')>Faculty</option>
                        <option value="non_teaching" @selected(($filters['category'] ?? '') === 'non_teaching')>Non-teaching</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            <th scope="col">Job title</th>
                            <th scope="col">College or office</th>
                            <th scope="col">Category</th>
                            <th scope="col">Base pay (monthly)</th>
                            <th scope="col" class="text-end">People</th>
                            <th scope="col">Open positions</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobTitles as $jobTitle)
                            @php($steps = $schedule[$jobTitle->salary_grade] ?? [])
                            <tr>
                                <td class="fw-medium">{{ $jobTitle->title }}</td>
                                <td>{{ $jobTitle->college?->name ?? $jobTitle->office?->name }}</td>
                                <td>{{ $jobTitle->category === \App\Enums\JobTitleCategory::Faculty ? 'Faculty' : 'Non-teaching' }}</td>
                                <td class="tabular-nums text-nowrap">
                                    @if ($steps)
                                        ₱{{ number_format((float) reset($steps)) }} to ₱{{ number_format((float) end($steps)) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end tabular-nums">{{ $jobTitle->employees_count }}</td>
                                <td>
                                    @if ($jobTitle->openings > 0)
                                        {{ $jobTitle->openings }} {{ $jobTitle->is_published ? '· Published' : '· Not published' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $jobTitle->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    @can('manage-reference-data')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('job-titles.edit', $jobTitle) }}">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-body-secondary py-4">No job titles match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span>Showing {{ $jobTitles->firstItem() ?? 0 }} to {{ $jobTitles->lastItem() ?? 0 }} of {{ $jobTitles->total() }} job titles</span>
                {{ $jobTitles->links() }}
            </div>
        </div>
    </div>
@endsection
