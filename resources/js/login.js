import { Toast } from 'bootstrap';

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
    const forgotPasswordToast = Toast.getOrCreateInstance(root.querySelector('[data-login-toast]'));

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
        }
    });
}

const loginRoot = document.querySelector('[data-page="login"]');

if (loginRoot) {
    initLoginPage(loginRoot);
}
