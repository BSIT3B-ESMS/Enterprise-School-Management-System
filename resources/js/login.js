import { Toast } from 'bootstrap';
import { authenticateApplicant, isApplicantAccountEmailTaken } from './mock-data.js';
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
    return emailPattern.test(input.value.trim()) ? '' : 'Enter a valid email address.';
}

function validatePassword(input) {
    return input.value.length >= minimumPasswordLength
        ? ''
        : `Password must be at least ${minimumPasswordLength} characters.`;
}

function initLoginPage(root) {
    const form = root.querySelector('[data-login-form]');
    const email = form.elements.email;
    const password = form.elements.password;
    const passwordToggle = root.querySelector('[data-password-toggle]');
    const passwordToggleLabel = passwordToggle.querySelector('[data-password-toggle-label]');
    // The notice sits beside the page's main block, not inside it, so it is looked up on the whole document.
    const forgotPasswordToast = Toast.getOrCreateInstance(document.querySelector('[data-login-toast]'));

    passwordToggle.addEventListener('click', () => {
        const isHidden = password.type === 'password';

        password.type = isHidden ? 'text' : 'password';
        passwordToggleLabel.textContent = isHidden ? 'Hide' : 'Show';
    });

    root.querySelector('[data-forgot-password]').addEventListener('click', () => {
        forgotPasswordToast.show();
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

        if (emailError || passwordError) {
            (emailError ? email : password).focus();

            return;
        }

        // An email that is not an applicant account belongs to staff (HR or Employee), whose sign-in is checked by the
        // server: let the form post to it. Applicant accounts still use the mock sign-in below until the Recruitment phase
        // gives them real accounts.
        if (!isApplicantAccountEmailTaken(email.value)) {
            form.submit();

            return;
        }

        // Mock sign-in: the email and password are checked against the applicant accounts in the shared mock data. A match
        // opens that applicant's dashboard; anything else shows an error.
        const result = authenticateApplicant(email.value, password.value);
        const $error = form.querySelector('[data-login-error]');

        $error.textContent = result.ok ? '' : result.error;
        $error.classList.toggle('d-none', result.ok);

        if (!result.ok) {
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
    initLoginPage(loginRoot);
}
