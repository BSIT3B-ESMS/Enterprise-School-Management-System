@extends('layouts.admin')

@section('title', $heading.' · '.config('app.name'))

{{-- Add or edit a job title: its name, college or office, category, pay band, and whether it is in use. --}}
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
            @endif

            <div class="col-md-6"><x-field name="title" label="Job title" :value="$jobTitle->title" required /></div>
            <div class="col-md-6">
                <x-select-field name="category" label="Category" :options="$categories" :value="$jobTitle->category?->value" required />
            </div>
            <div class="col-md-6">
                <x-select-field name="unit" label="College or office" :options="$unitOptions" :value="$unitValue" placeholder="Select" grouped required />
            </div>
            <div class="col-md-6">
                <x-select-field name="salary_grade" label="Pay band" :options="$bandOptions" :value="$jobTitle->salary_grade" placeholder="Select"
                    hint="The amounts are the monthly base pay from step 1 to step 8. Change them on the Pay Rates page." required />
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="field-is_active" name="is_active" value="1"
                        @checked(old('is_active', $jobTitle->is_active))>
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
