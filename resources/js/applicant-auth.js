import $ from './jquery.js';
import { findPosition, registerApplicant, validateApplicantPersonal } from './mock-data.js';
import { currentAccountId, hasAcceptedTerms, signIn } from './applicant-session.js';
import { todayIsoDate } from './employee-documents.js';
import { requestTerms } from './applicant-terms.js';
import { emailRule, newPasswordRule } from './validation-rules.js';

// The position chosen on the Careers website (Apply), kept through the portal, Create Account, and Login.
const positionParam = new URLSearchParams(window.location.search).get('position') ?? '';

// The selected position when it exists and is still open (a closed one cannot be applied for).
const selectedPosition = (() => {
    const position = positionParam ? findPosition(positionParam) : undefined;

    return position?.isOpen ? position : undefined;
})();

// An address that keeps the selected position.
const withPosition = (url) => (selectedPosition ? `${url}?position=${encodeURIComponent(selectedPosition.id)}` : url);

function setFieldError($field, message) {
    // Password errors sit outside the input's wrapper, so Bootstrap's sibling selector can't show them.
    $(`#${$field.attr('id')}-error`).text(message).toggleClass('d-block', Boolean(message));

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

/**
 * Checks every rule, marks each field, and focuses the first invalid one. Returns whether all passed.
 *
 * @param {Object<string, (values: object) => string>} rules
 */
function validate($form, rules) {
    const values = Object.fromEntries(Object.keys(rules).map((name) => [name, String($form.find(`[name="${name}"]`).val() ?? '')]));
    let $firstInvalid = null;

    Object.entries(rules).forEach(([name, rule]) => {
        const $field = $form.find(`[name="${name}"]`);
        const message = rule(values);

        setFieldError($field, message);

        if (message && !$firstInvalid) {
            $firstInvalid = $field;
        }
    });

    $firstInvalid?.trigger('focus');

    return !$firstInvalid;
}

function revalidateOnInput($form, rules) {
    $form.on('input change', '.is-invalid', function () {
        const values = Object.fromEntries(Object.keys(rules).map((name) => [name, String($form.find(`[name="${name}"]`).val() ?? '')]));

        setFieldError($(this), rules[$(this).attr('name')](values));
    });
}

function showError($root, message) {
    $root.find('[data-applicant-auth-error]').text(message).toggleClass('d-none', !message);
}

/**
 * Returns to the page the applicant came from (the Careers website), or to the Careers website when this page was
 * opened directly.
 */
function leave($root) {
    let cameFromHere = false;

    try {
        cameFromHere = Boolean(document.referrer) && new URL(document.referrer).origin === window.location.origin;
    } catch {
        // An unreadable referrer counts as opened directly.
    }

    if (cameFromHere && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.assign(withPosition($root.attr('data-careers-url')));
    }
}

/**
 * One portal page, one form at a time. Log In is the default; Create Account replaces it (and Log In replaces it again),
 * keeping what was typed in each. Create Account is not shown until the Terms and Conditions are accepted: declining
 * stays on Log In, or, when the page was opened on Create Account, leaves without registering. Also the chosen position
 * and Show/Hide on the register password fields (Log In's are handled in login.js).
 */
function initAuthPage($root) {
    const $login = $root.find('[data-portal-form="login"]');
    const $register = $root.find('[data-applicant-register-form]');
    const position = selectedPosition;

    function show(name) {
        $login.toggleClass('d-none', name !== 'login');
        $register.toggleClass('d-none', name !== 'register');
        $root.find(`[data-portal-form="${name}"] input`).first().trigger('focus');
    }

    function showRegister() {
        show('register');
    }

    if (position) {
        $root.find('[data-auth-position-name]').text(`${position.name} · ${position.department}`);
        $root.find('[data-auth-position]').removeClass('d-none');
    } else if (positionParam) {
        $root.find('[data-portal-position-closed]').removeClass('d-none');
    }

    $root.find('[data-portal-back]').attr('href', withPosition($root.attr('data-careers-url')));

    $register.on('click', '[data-password-toggle]', function () {
        const $input = $(`#${$(this).attr('aria-controls')}`);
        const isHidden = $input.attr('type') === 'password';

        $input.attr('type', isHidden ? 'text' : 'password');
        $(this).find('[data-password-toggle-label]').text(isHidden ? 'Hide' : 'Show');
    });

    // The links keep working as links (to the Log In and Create Account addresses) if scripts do not load.
    $root.on('click', '[data-portal-switch="login"]', (event) => {
        event.preventDefault();
        show('login');
    });

    $root.on('click', '[data-portal-switch="register"]', (event) => {
        event.preventDefault();
        requestTerms({ force: true, onAccept: showRegister, onDecline: () => {} });
    });

    if ($root.attr('data-initial-form') === 'register') {
        $login.addClass('d-none');
        requestTerms({ onAccept: showRegister, onDecline: () => leave($root) });
    }
}

function initRegisterForm($root) {
    const $form = $root.find('[data-applicant-register-form]');
    // The name parts, birthdate, sex, and civil status use the same checks the account itself is saved with.
    const personalRule = (field) => (values) => validateApplicantPersonal({ [field]: values[field] })[field] ?? '';
    const rules = {
        lastName: personalRule('lastName'),
        firstName: personalRule('firstName'),
        middleName: personalRule('middleName'),
        birthdate: personalRule('birthdate'),
        sex: personalRule('sex'),
        civilStatus: personalRule('civilStatus'),
        email: ({ email }) => (email.trim() ? emailRule(email) : 'Enter your email address.'),
        password: ({ password }) => newPasswordRule(password),
        confirmPassword: ({ password, confirmPassword }) => {
            if (!confirmPassword) {
                return 'Confirm your password.';
            }

            return confirmPassword === password ? '' : 'Passwords do not match.';
        },
    };

    revalidateOnInput($form, rules);
    $form.find('[name="birthdate"]').attr('max', todayIsoDate());

    $form.on('submit', function (event) {
        event.preventDefault();
        showError($root, '');

        if (!hasAcceptedTerms() || !validate($form, rules)) {
            return;
        }

        const value = (name) => String($form.find(`[name="${name}"]`).val() ?? '').trim();
        const result = registerApplicant({
            lastName: value('lastName'),
            firstName: value('firstName'),
            middleName: value('middleName'),
            birthdate: value('birthdate'),
            sex: value('sex'),
            civilStatus: value('civilStatus'),
            email: value('email'),
            password: String($form.find('[name="password"]').val()),
        });

        if (!result.ok) {
            const [field] = Object.keys(result.errors);

            Object.entries(result.errors).forEach(([name, message]) => setFieldError($form.find(`[name="${name}"]`), message));
            showError($root, field ? '' : result.error);
            $form.find(`[name="${field ?? 'email'}"]`).trigger('focus');

            return;
        }

        signIn(result.account.id);
        window.location.assign(withPosition($root.attr('data-dashboard-url')));
    });
}

$(function () {
    const $root = $('[data-page="applicant-portal"]');

    if (!$root.length) {
        return;
    }

    // Already signed in this session: continue to the dashboard instead of logging in or registering again.
    if (currentAccountId()) {
        window.location.replace(withPosition($root.attr('data-dashboard-url')));

        return;
    }

    initAuthPage($root);
    initRegisterForm($root);
});
