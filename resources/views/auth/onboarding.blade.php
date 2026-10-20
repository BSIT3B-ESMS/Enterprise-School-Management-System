@extends('layouts.guest')

@section('title', 'Finish Setup · '.config('app.name'))

@section('content')
    <x-auth-card title="Finish setting up your account"
        subtitle="Before you continue, give us an emergency contact and your government and tax ID numbers. HR keeps these private.">
        <form method="post" action="{{ route('onboarding.store') }}" class="d-grid gap-3" novalidate>
            @csrf

            <h2 class="h6 mb-0">Emergency contact</h2>
            <div><x-field name="contact_name" label="Full name" required autocomplete="off" /></div>
            <div><x-field name="contact_relationship" label="Relationship" required placeholder="Spouse, parent, sibling" /></div>
            <div><x-field name="contact_phone" label="Mobile number" required placeholder="09XXXXXXXXX" inputmode="tel" /></div>
            <div><x-field name="contact_address" label="Address" /></div>

            <h2 class="h6 mb-0 mt-2">Government and tax</h2>
            <div><x-field name="tin" label="TIN" required placeholder="000-000-000-000" inputmode="numeric" /></div>
            <div><x-field name="gsis" label="GSIS" required placeholder="11 digits" inputmode="numeric" /></div>
            <div><x-field name="philhealth" label="PhilHealth" required placeholder="00-000000000-0" inputmode="numeric" /></div>
            <div><x-field name="pagibig" label="Pag-IBIG MID" required placeholder="0000-0000-0000" inputmode="numeric" /></div>
            <div><x-field name="sss" label="SSS (optional)" placeholder="00-0000000-0" inputmode="numeric" /></div>
            <div><x-field name="bir" label="BIR (optional)" placeholder="Registration number on your BIR Form 2316 or 1902" inputmode="numeric" /></div>

            <button type="submit" class="btn btn-primary login-submit">Save and continue</button>
        </form>

        <form method="post" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link p-0">Sign out</button>
        </form>
    </x-auth-card>
@endsection
