import $ from './jquery.js';
import { recordPasswordChange } from './mock-data.js';

const minimumLength = 8;

// Each rule returns an error message, or '' when the value is valid. Passwords are not trimmed.
const rules = {
    currentPassword: ({ currentPassword }) => (currentPassword ? '' : 'Enter your current password.'),
    newPassword: ({ currentPassword, newPassword }) => {
        if (!newPassword) {
            return 'Enter a new password.';
        }

        if (newPassword.length < minimumLength) {
            return `Password must be at least ${minimumLength} characters.`;
        }

        if (!/[A-Z]/.test(newPassword)) {
            return 'Include at least one uppercase letter.';
        }

        if (!/[a-z]/.test(newPassword)) {
            return 'Include at least one lowercase letter.';
        }

        if (!/[0-9]/.test(newPassword)) {
            return 'Include at least one number.';
        }

        return newPassword === currentPassword ? 'Choose a password different from your current one.' : '';
    },
    confirmPassword: ({ newPassword, confirmPassword }) => {
        if (!confirmPassword) {
            return 'Confirm your new password.';
        }

        return confirmPassword === newPassword ? '' : 'Passwords do not match.';
    },
};

function setFieldError($field, message) {
    const $feedback = $(`#${$field.attr('id')}-error`);

    // The error sits outside the input's wrapper, so Bootstrap's sibling selector can't show it.
    $feedback.text(message).toggleClass('d-block', Boolean(message));

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

function setRevealed($toggle, isRevealed) {
    $(`#${$toggle.attr('aria-controls')}`).attr('type', isRevealed ? 'text' : 'password');
    $toggle.find('[data-password-toggle-label]').text(isRevealed ? 'Hide' : 'Show');
}

// Mock only: the three passwords are checked here and cleared; none is stored, logged, or sent anywhere.
function initPasswordForm($form) {
    const $success = $form.find('[data-password-success]');
    const field = (name) => $form.find(`[name="${name}"]`);
    const values = () => Object.fromEntries(Object.keys(rules).map((name) => [name, String(field(name).val() ?? '')]));

    $form.on('click', '[data-password-toggle]', function () {
        setRevealed($(this), $(`#${$(this).attr('aria-controls')}`).attr('type') === 'password');
    });

    // Once a field has been flagged, re-check it as the user corrects it; the confirmation also
    // depends on the new password.
    $form.on('input', 'input', function () {
        $success.addClass('d-none');

        const current = values();

        Object.keys(rules).forEach((name) => {
            if (field(name).hasClass('is-invalid')) {
                setFieldError(field(name), rules[name](current));
            }
        });
    });

    $form.on('submit', function (event) {
        event.preventDefault();
        $success.addClass('d-none');

        const current = values();
        let $firstInvalid = null;

        Object.entries(rules).forEach(([name, rule]) => {
            const message = rule(current);

            setFieldError(field(name), message);

            if (message && !$firstInvalid) {
                $firstInvalid = field(name);
            }
        });

        if ($firstInvalid) {
            $firstInvalid.trigger('focus');

            return;
        }

        $form[0].reset();
        $form.find('[data-password-toggle]').each(function () {
            setRevealed($(this), false);
        });
        recordPasswordChange();
        $success.removeClass('d-none');
    });
}

$(function () {
    const $form = $('[data-password-form]');

    if ($form.length) {
        initPasswordForm($form);
    }
});
