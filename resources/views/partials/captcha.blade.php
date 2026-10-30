{{-- NFR-06: the configured CAPTCHA widget (Turnstile or reCAPTCHA); nothing while CAPTCHA_DRIVER=none --}}
@if (\App\Services\Security\CaptchaVerifier::enabled())
  <div class="captcha-field">
    <div class="{{ \App\Services\Security\CaptchaVerifier::widgetClass() }}" data-sitekey="{{ config('admission.captcha.site_key') }}"></div>
    <div class="invalid-feedback d-block captcha-error"></div>
  </div>
@endif
