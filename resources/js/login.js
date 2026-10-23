import { Toast } from 'bootstrap';
import { authenticateApplicant } from './mock-data.js';
import { signIn } from './applicant-session.js';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const minimumPasswordLength = 6;

function setFieldError(input, message) {
    const feedback = document.getElementById(`${input.id}-error`);
    const hasError = message !== '';

    input.classList.toggle('is-invalid', hasError);
    input.setAttribute('aria-invalid', String(hasError));
    feedback.textContent = message;
    feedback.classList.toggle('d-block', hasError);
}

function validateEmail(input) {
    if (!input.value.trim()) {
        return 'Enter your email address.';
    }

    return emailPattern.test(input.value.trim()) ? '' : 'Enter a valid email address.';
}

function validatePassword(input) {
    if (!input.value) {
        return 'Enter your password.';
    }

    return input.value.length >= minimumPasswordLength
        ? ''
        : `Password must be at least ${minimumPasswordLength} characters.`;
}

/**
 * A sign-in form: the EMS Login for HR and employees (data-login-for="staff", the default) or the Applicant Portal's
 * Log In (data-login-for="applicant"). Both check the mock applicant accounts (HR and Employee accounts are not checked
 * yet). An applicant signs in only at the Applicant Portal, which opens their dashboard; at the EMS Login, applicant
 * credentials are pointed to the Applicant Portal instead.
 */
function initLoginForm(form) {
    const isApplicantLogin = form.dataset.loginFor === 'applicant';
    const email = form.elements.email;
    const password = form.elements.password;
    const error = form.querySelector('[data-login-error]');

    function showError(message) {
        error.textContent = message;
        error.classList.toggle('d-none', !message);
    }

    form.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const isHidden = password.type === 'password';

            password.type = isHidden ? 'text' : 'password';
            toggle.querySelector('[data-password-toggle-label]').textContent = isHidden ? 'Hide' : 'Show';
        });
    });

    form.addEventListener('input', (event) => {
        if (event.target === email && email.classList.contains('is-invalid')) {
            setFieldError(email, validateEmail(email));
        }

        if (event.target === password && password.classList.contains('is-invalid')) {
            setFieldError(password, validatePassword(password));
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const emailError = validateEmail(email);
        const passwordError = validatePassword(password);

        setFieldError(email, emailError);
        setFieldError(password, passwordError);
        showError('');

        if (emailError || passwordError) {
            (emailError ? email : password).focus();

            return;
        }

        const result = authenticateApplicant(email.value.trim(), password.value);

        if (!isApplicantLogin && result.ok) {
            showError('This login is for HR and employees. Applicants log in at the PLP Careers Applicant Portal.');
            form.querySelector('[data-login-applicant-hint]')?.classList.remove('d-none');

            return;
        }

        if (!result.ok) {
            showError(result.error);
            password.value = '';
            password.focus();

            return;
        }

        signIn(result.account.id);

        // The position chosen on the Careers website (?position=) stays selected through to the dashboard.
        const position = new URLSearchParams(window.location.search).get('position');

        window.location.assign(position
            ? `${form.dataset.applicantDashboardUrl}?position=${encodeURIComponent(position)}`
            : form.dataset.applicantDashboardUrl);
    });
}

const loginRoot = document.querySelector('[data-page="login"]');

if (loginRoot) {
    // The notice sits beside the page's main block, not inside it, so it is looked up on the whole document.
    const forgotPasswordToast = Toast.getOrCreateInstance(document.querySelector('[data-login-toast]'));

    loginRoot.querySelector('[data-forgot-password]').addEventListener('click', () => forgotPasswordToast.show());
}

document.querySelectorAll('[data-login-form]').forEach(initLoginForm);
