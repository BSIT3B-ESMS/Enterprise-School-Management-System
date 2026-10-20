@extends('layouts.admin')

@section('title', $heading.' · '.config('app.name'))

{{-- Add or edit a shared job title: one name, category, and salary grade, used in every college and office ticked below. --}}
@section('content')
    <div class="d-grid gap-4" data-page="job-title-form">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('job-titles.index') }}" aria-label="Back to job titles"><x-icon name="back" /></a>
            <h1 class="page-title">{{ $heading }}</h1>
        </div>

        <form method="post" action="{{ $action }}" class="card employee-form-card row g-3" novalidate>
            @csrf
            @if ($jobTitle->exists)
                @method('PUT')
                <div class="col-12"><div class="alert alert-info mb-0" role="note">This job title is shared. Changing its name, category, salary grade, or status here changes it in every college and office that uses it.</div></div>
            @endif

            <div class="col-md-6"><x-field name="title" label="Job title" :value="$jobTitle->title" required /></div>
            <div class="col-md-6"><x-select-field name="category" label="Category" :options="$categories" :value="$jobTitle->category?->value" required /></div>
            <div class="col-md-6">
                <x-select-field name="salary_grade" label="Salary grade" :options="$gradeOptions" :value="$jobTitle->salary_grade" placeholder="Select"
                    hint="The monthly pay of the grade (Step 1). Change it on the Pay Rates page." required />
            </div>

            <div class="col-12">
                <fieldset>
                    <legend class="form-label is-required">Used in</legend>
                    @error('units') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                    @foreach ($unitOptions as $group => $options)
                        <div class="fw-medium small mt-2">{{ $group }}</div>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-1">
                            @foreach ($options as $value => $label)
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="units[]" value="{{ $value }}" id="unit-{{ str_replace(':', '-', $value) }}" @checked(in_array($value, $selectedUnits, true))>
                                        <label class="form-check-label" for="unit-{{ str_replace(':', '-', $value) }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </fieldset>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="field-is_active" name="is_active" value="1" @checked(old('is_active', $jobTitle->is_active))>
                    <label class="form-check-label" for="field-is_active">Active</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('job-titles.index') }}">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
@endsection
