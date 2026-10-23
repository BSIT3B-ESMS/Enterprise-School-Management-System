@extends('layouts.admin')

@section('title', 'Account Settings · '.config('app.name'))

@php
    $themes = ['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'];

    $notifications = [
        'leave' => ['Leave requests', 'Leave that is filed or approved.'],
        'recruitment' => ['Recruitment updates', 'New applicants and changes to applications.'],
        'employees' => ['Employee updates', 'Changes to employees, colleges, and job titles.'],
    ];
@endphp

{{--
    The HR user's Account Settings, filled in by resources/js/account-settings.js. Appearance uses the same theme
    choice as the header's toggle (resources/js/theme.js), and the notification switches decide what the header's
    notifications menu shows. Separate from My Profile and its Security section. Each change applies right away.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="account-settings">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Account Settings</h1>
                <p class="text-body-secondary mt-1 mb-0">Choose how the EMS looks and what it tells you about.</p>
            </div>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                <x-icon name="back" />
                Back to Dashboard
            </a>
        </div>

        <section class="card employee-form-card" aria-labelledby="settings-appearance-title">
            <h2 class="employee-form-section" id="settings-appearance-title">Appearance</h2>

            <fieldset>
                <legend class="form-label small fw-semibold mb-2">Theme</legend>
                <div class="d-flex flex-wrap gap-2" role="radiogroup" aria-describedby="settings-theme-hint">
                    @foreach ($themes as $value => $label)
                        <input type="radio" class="btn-check" name="theme" id="settings-theme-{{ $value }}" value="{{ $value }}"
                            autocomplete="off" data-settings-theme>
                        <label class="btn btn-sm btn-outline-primary" for="settings-theme-{{ $value }}">{{ $label }}</label>
                    @endforeach
                </div>
                <div class="form-text" id="settings-theme-hint">System follows your device’s light or dark setting.</div>
            </fieldset>
        </section>

        <section class="card employee-form-card" aria-labelledby="settings-notifications-title">
            <h2 class="employee-form-section" id="settings-notifications-title">
                Notifications <small>What the notifications menu in the header shows</small>
            </h2>

            <div class="d-grid gap-3">
                @foreach ($notifications as $kind => [$label, $about])
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="settings-notify-{{ $kind }}"
                            aria-describedby="settings-notify-{{ $kind }}-about" data-settings-notification="{{ $kind }}">
                        <label class="form-check-label fw-medium" for="settings-notify-{{ $kind }}">{{ $label }}</label>
                        <div class="form-text mt-0" id="settings-notify-{{ $kind }}-about">{{ $about }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
