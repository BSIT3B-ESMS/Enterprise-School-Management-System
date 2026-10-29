@extends('layouts.admin')

@section('title', $heading.' · '.config('app.name'))

@php($isEditing = $employee->exists)

{{--
    Add or edit an employee. Adding chooses the job title (which sets the college or office), the employment type, and the
    salary step; editing changes personal and contact details only, because the HR Admin changes assignments through a
    transfer on the profile. Government ID numbers are write-only: they are never shown here, and a blank box keeps the
    number on file.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="employee-form">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ $isEditing ? route('employees.show', $employee) : route('employees.index') }}" aria-label="Back"><x-icon name="back" /></a>
            <h1 class="page-title">{{ $heading }}</h1>
        </div>

        <form method="post" action="{{ $action }}" class="card employee-form-card" novalidate>
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <section class="mb-4">
                <h2 class="employee-form-section">Personal Information</h2>
                <div class="row g-3">
                    <div class="col-md-4"><x-field name="last_name" label="Last name" :value="$employee->last_name" required /></div>
                    <div class="col-md-4"><x-field name="first_name" label="First name" :value="$employee->first_name" required /></div>
                    <div class="col-md-3"><x-field name="middle_name" label="Middle name" :value="$employee->middle_name" /></div>
                    <div class="col-md-1"><x-field name="suffix" label="Suffix" :value="$employee->suffix" placeholder="Jr." /></div>
                    <div class="col-md-4"><x-field name="birthdate" type="date" label="Birthdate" :value="$employee->birthdate?->toDateString()" /></div>
                    <div class="col-md-4"><x-select-field name="sex" label="Sex" :options="$sexes" :value="$employee->sex?->value" placeholder="Not stated" /></div>
                    <div class="col-md-4"><x-select-field name="marital_status" label="Marital status" :options="$maritalStatuses" :value="$employee->marital_status?->value" placeholder="Not stated" /></div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Employment Information</h2>
                <div class="row g-3">
                    @unless ($isEditing)
                        <div class="col-md-6">
                            <x-select-field name="job_title_id" label="Job title" :options="$jobTitleOptions" placeholder="Select job title" grouped required
                                hint="The job title sets the employee's college or office." />
                        </div>
                        <div class="col-md-3"><x-field name="weekly_hours" type="number" min="1" max="40" label="Weekly hours" :value="$employee->weekly_hours ?? null" hint="Part-time: leave credits follow it. Blank means 20." /></div>
                        <div class="col-md-3"><x-select-field name="employment_type" label="Employment type" :options="$employmentTypes" value="full_time" required /></div>
                        <div class="col-md-6">
                            <x-select-field name="extra_college_id" label="Also teaches in (optional)" :options="$colleges" placeholder="None"
                                hint="For office staff who also teach. Ignored when the job title already belongs to a college." />
                        </div>
                        <div class="col-md-6">
                            <x-select-field name="extra_office_id" label="Also assigned to office (optional)" :options="$offices" placeholder="None"
                                hint="For teachers who also work in an office. Ignored when the job title already belongs to an office." />
                        </div>
                    @endunless
                    <div class="col-md-4"><x-field name="hired_on" type="date" label="Date hired" :value="$employee->hired_on?->toDateString()" :required="! $isEditing" :hint="$isEditing ? null : 'The year of this date is the first part of the employee number.'" /></div>
                    @if ($isEditing)
                        <p class="form-text mb-0">
                            {{ $employee->jobTitle->title }} · {{ collect([$employee->college?->name, $employee->office?->name])->filter()->implode(' · ') }}.
                            The HR Admin changes the job title, college, office, and salary step with a transfer on the profile.
                        </p>
                    @endif
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Contact Information</h2>
                <div class="row g-3">
                    <div class="col-md-4"><x-field name="phone" type="tel" label="Mobile number" :value="$employee->phone" placeholder="09XXXXXXXXX" /></div>
                    <div class="col-md-4"><x-field name="personal_email" type="email" label="Personal email" :value="$employee->personal_email" /></div>
                    <div class="col-12"><x-field name="address" label="Home address" :value="$employee->address" /></div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Government IDs</h2>
                <p class="text-body-secondary">
                    Numbers are stored encrypted and never shown again in full.
                    @if ($isEditing) Leave a box blank to keep the number on file. @endif
                </p>
                <div class="row g-3">
                    @foreach ($governmentIdLabels as $key => $label)
                        <div class="col-md-4">
                            <x-field :name="$key" :label="$label" type="text" inputmode="numeric" autocomplete="off"
                                :placeholder="$isEditing && isset($employee->maskedGovernmentIds()[$key]) ? '•••• '.$employee->maskedGovernmentIds()[$key] : ''" />
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ $isEditing ? route('employees.show', $employee) : route('employees.index') }}">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Save Changes' : 'Create Employee' }}</button>
            </div>
        </form>
    </div>
@endsection
