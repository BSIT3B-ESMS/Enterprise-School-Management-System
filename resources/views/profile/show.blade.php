@extends('layouts.admin')

@section('title', 'My Profile Â· '.config('app.name'))

{{--
    The signed-in HR user's profile. Values are filled in by resources/js/hr-profile.js from the HR profile in
    resources/js/mock-data.js, which is separate from employee and applicant records. Edit Profile swaps the
    details for a form on this page; Back returns to the HR page the user came from.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="hr-profile">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h1 class="page-title">My Profile</h1>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('dashboard') }}"
                data-hr-back-link data-fallback-url="{{ route('dashboard') }}">
                <x-icon name="back" />
                Back
            </a>
        </div>

        <section class="card employee-form-card">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="profile-avatar" aria-hidden="true" data-hr-field="initials"></span>
                <div class="min-w-0 flex-grow-1">
                    <h2 class="fs-4 mb-1" data-hr-field="name"></h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap text-body-secondary">
                        <span class="ems-chip" data-hr-field="role"></span>
                        <span data-hr-field="email"></span>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" data-hr-edit-start>Edit Profile</button>
            </div>
        </section>

        <div class="card employee-form-card" data-hr-view>
            <section class="mb-4">
                <h2 class="employee-form-section">Personal Information</h2>
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    <div class="col"><dt>Full Name</dt><dd class="mb-0" data-hr-field="name"></dd></div>
                </dl>
            </section>

            <section>
                <h2 class="employee-form-section">Account Information</h2>
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    <div class="col"><dt>Email</dt><dd class="mb-0" data-hr-field="email"></dd></div>
                    <div class="col"><dt>Role</dt><dd class="mb-0" data-hr-field="role"></dd></div>
                </dl>
            </section>
        </div>

        <form class="card employee-form-card d-none" novalidate data-hr-form>
            <section class="mb-4">
                <h2 class="employee-form-section">Personal Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="hr-name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="hr-name" name="name" autocomplete="off"
                            aria-describedby="hr-name-error" required>
                        <div class="invalid-feedback" id="hr-name-error"></div>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Account Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="hr-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="hr-email" name="email" autocomplete="off"
                            aria-describedby="hr-email-error" required>
                        <div class="invalid-feedback" id="hr-email-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="hr-role" class="form-label">Role</label>
                        <input type="text" class="form-control" id="hr-role" readonly data-hr-field-input="role">
                    </div>
                </div>
            </section>

            <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary" data-hr-edit-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

        <x-password-change-section />
    </div>
@endsection
