@props(['id', 'name', 'label', 'autocomplete', 'hint' => null, 'marked' => false])

{{--
    A password input with a Show/Hide control and an error line, using the sign-in page's password styles.
    resources/js/password-change.js toggles the field and fills in `{id}-error`.
--}}
<label for="{{ $id }}" class="form-label{{ $marked ? ' is-required' : '' }}">{{ $label }}</label>
<div class="login-password position-relative">
    <input type="password" class="form-control" id="{{ $id }}" name="{{ $name }}" autocomplete="{{ $autocomplete }}"
        aria-describedby="{{ $hint ? $id.'-hint ' : '' }}{{ $id }}-error" required>
    <button type="button" class="login-password-toggle" aria-controls="{{ $id }}" data-password-toggle>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
            stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
        <span data-password-toggle-label>Show</span><span class="visually-hidden"> {{ strtolower($label) }}</span>
    </button>
</div>
@if ($hint)
    <div class="form-text" id="{{ $id }}-hint">{{ $hint }}</div>
@endif
<div class="invalid-feedback" id="{{ $id }}-error"></div>
