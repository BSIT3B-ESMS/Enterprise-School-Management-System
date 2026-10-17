@props(['account' => 'hr'])

{{--
    The Security card with the Change Password form, shared by the HR profile and the employee profile. Mock only:
    the passwords are checked in the form and never stored or sent anywhere (resources/js/password-change.js).
    $account is "hr" or "employee" and decides whose change the audit log records.
--}}
<section class="card employee-form-card security-section" id="security">
    <h2 class="employee-form-section">Security</h2>

    <form novalidate data-password-form{!! $account === 'employee' ? ' data-password-account="employee"' : '' !!}>
        <h3 class="fs-6 fw-semibold mb-3">Change Password</h3>

        <div class="row g-3">
            <div class="col-lg-4">
                <x-password-field id="current-password" name="currentPassword" label="Current Password"
                    autocomplete="current-password" />
            </div>

            <div class="col-lg-4">
                <x-password-field id="new-password" name="newPassword" label="New Password"
                    autocomplete="new-password"
                    hint="At least 8 characters, with an uppercase letter, a lowercase letter, and a number." />
            </div>

            <div class="col-lg-4">
                <x-password-field id="confirm-password" name="confirmPassword" label="Confirm New Password"
                    autocomplete="new-password" />
            </div>
        </div>

        <div class="alert alert-success d-none mt-3 mb-0" role="status" data-password-success>
            Your password has been changed.
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
    </form>
</section>
