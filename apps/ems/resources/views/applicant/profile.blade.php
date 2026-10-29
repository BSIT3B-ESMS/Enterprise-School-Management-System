@extends('layouts.applicant')

@section('title', 'My Profile · '.config('app.name'))

{{--
    The applicant's one-time profile: Personal Information and Contact Information only. The education, work history, resume, and
    portfolio are filled in per application. The emergency contact and government and tax details are asked for after hiring.
--}}
@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">My Profile</h1>
            <p class="text-body-secondary mt-1 mb-0">
                Fill this in once. Your education, work history, and resume go with each application.
            </p>
        </div>

        @unless ($profile->isComplete())
            <div class="alert alert-warning mb-0" role="status">Complete your profile to be able to apply for a position.</div>
        @endunless

        <form method="post" action="{{ route('applicant.profile.update') }}" class="d-grid gap-4" novalidate>
            @csrf
            @method('PUT')
            @if ($position) <input type="hidden" name="position" value="{{ $position }}"> @endif

            <section class="card employee-form-card" aria-labelledby="personal-title">
                <h2 class="employee-form-section" id="personal-title">Personal Information</h2>
                <div class="row g-3">
                    <div class="col-md-3"><x-field name="first_name" label="First Name" :value="$profile->first_name" required /></div>
                    <div class="col-md-3"><x-field name="middle_name" label="Middle Name" :value="$profile->middle_name" /></div>
                    <div class="col-md-4"><x-field name="last_name" label="Surname" :value="$profile->last_name" required /></div>
                    <div class="col-md-2"><x-field name="suffix" label="Suffix" :value="$profile->suffix" /></div>
                    <div class="col-md-4"><x-field name="birthdate" type="date" label="Birthdate" :value="$profile->birthdate?->toDateString()" required /></div>
                    <div class="col-md-4"><x-select-field name="sex" label="Sex" :options="$sexes" :value="$profile->sex?->value" placeholder="Select" required /></div>
                    <div class="col-md-4"><x-select-field name="marital_status" label="Civil Status" :options="$civilStatuses" :value="$profile->marital_status?->value" placeholder="Select" required /></div>
                </div>
            </section>

            <section class="card employee-form-card" aria-labelledby="contact-title">
                <h2 class="employee-form-section" id="contact-title">Contact Information</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="account-email">Email</label>
                        <input type="email" class="form-control" id="account-email" value="{{ auth()->user()->email }}" readonly aria-describedby="account-email-hint">
                        <div class="form-text" id="account-email-hint">This is your login. HR also uses it to reach you.</div>
                    </div>
                    <div class="col-md-4"><x-field name="phone" label="Mobile Number" :value="$profile->phone" placeholder="09XXXXXXXXX" inputmode="tel" required /></div>
                    <div class="col-md-12"><x-field name="address" label="Address" :value="$profile->address" required /></div>
                </div>
            </section>

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <a href="{{ route('password.change') }}">Change my password</a>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
    </div>
@endsection
