import { Toast } from 'bootstrap';

// The sign-in form posts to the server, which checks the email and password. This script only shows or hides the password and
// explains that password reset is not available yet.
function initLoginPage(root) {
    const form = root.querySelector('[data-login-form]');
    const password = form.elements.password;
    const passwordToggle = root.querySelector('[data-password-toggle]');
    const passwordToggleLabel = passwordToggle.querySelector('[data-password-toggle-label]');
    const forgotPasswordToast = Toast.getOrCreateInstance(document.querySelector('[data-login-toast]'));

    passwordToggle.addEventListener('click', () => {
        const isHidden = password.type === 'password';

        password.type = isHidden ? 'text' : 'password';
        passwordToggleLabel.textContent = isHidden ? 'Hide' : 'Show';
    });

    root.querySelector('[data-forgot-password]').addEventListener('click', () => {
        forgotPasswordToast.show();
    });
}

const loginRoot = document.querySelector('[data-page="login"]');

if (loginRoot) {
    initLoginPage(loginRoot);
}
