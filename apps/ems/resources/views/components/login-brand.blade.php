@props(['title' => 'Employee Management System', 'tagline' => 'Secure access for HR, managers, and employees.'])

{{--
    The gradient brand panel beside the sign-in forms: the EMS Login for HR and employees, and (with the PLP Careers
    title) the applicant Create Account page.
--}}
<section class="col-md-6 login-brand d-flex flex-column align-items-center justify-content-center text-center text-white">
    <div class="login-brand-logo">
        <x-brand-logo />
    </div>

    <h1 class="login-brand-title">{{ $title }}</h1>

    <p class="login-brand-tagline">{{ $tagline }}</p>
</section>
