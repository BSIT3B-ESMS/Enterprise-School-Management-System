import $ from './jquery.js';
import { findPosition, registerApplicant } from './mock-data.js';
import { currentAccountId, hasAcceptedTerms, signIn } from './applicant-session.js';
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
        window.location.assign(withPosition($root.attr('data-back-url')));
    }
}

/**
 * The Terms gate: the registration form is not shown until the terms are accepted, and declining leaves the page
 * without registering. Also the chosen position and Show/Hide on password fields.
 */
function initAuthPage($root) {
    const $form = $root.find('[data-applicant-register-form]');
    const position = selectedPosition;

    function showForm() {
        $form.removeClass('d-none');
        $form.find('input').first().trigger('focus');
    }

    if (position) {
        $root.find('[data-auth-position-name]').text(`${position.name} · ${position.department}`);
        $root.find('[data-auth-position]').removeClass('d-none');
    }

    $root.find('[data-login-here]').attr('href', withPosition($root.find('[data-login-here]').attr('href')));

    $root.on('click', '[data-password-toggle]', function () {
        const $input = $(`#${$(this).attr('aria-controls')}`);
        const isHidden = $input.attr('type') === 'password';

        $input.attr('type', isHidden ? 'text' : 'password');
        $(this).find('[data-password-toggle-label]').text(isHidden ? 'Hide' : 'Show');
    });

    requestTerms({ onAccept: showForm, onDecline: () => leave($root) });
}

function initRegisterForm($root) {
    const $form = $root.find('[data-applicant-register-form]');
    const rules = {
        name: ({ name }) => (name.trim() ? '' : 'Enter your full name.'),
        email: ({ email }) => emailRule(email),
        password: ({ password }) => newPasswordRule(password),
        confirmPassword: ({ password, confirmPassword }) => {
            if (!confirmPassword) {
                return 'Confirm your password.';
            }

            return confirmPassword === password ? '' : 'Passwords do not match.';
        },
    };

    revalidateOnInput($form, rules);

    $form.on('submit', function (event) {
        event.preventDefault();
        showError($root, '');

        if (!hasAcceptedTerms() || !validate($form, rules)) {
            return;
        }

        const result = registerApplicant({
            name: String($form.find('[name="name"]').val()),
            email: String($form.find('[name="email"]').val()),
            password: String($form.find('[name="password"]').val()),
        });

        if (!result.ok) {
            setFieldError($form.find('[name="email"]'), result.errors.email ?? '');
            showError($root, result.errors.email ? '' : result.error);
            $form.find('[name="email"]').trigger('focus');

            return;
        }

        signIn(result.account.id);
        window.location.assign(withPosition($root.attr('data-dashboard-url')));
    });
}

/**
 * The public Applicant Portal: Create Account and Login keep the selected position. Create Account shows the Terms
 * (see applicant-terms.js) and, when declined, returns to Open Positions with the position still selected.
 */
function initPortal($root) {
    if (selectedPosition) {
        $root.find('[data-portal-position-name]').text(`${selectedPosition.name} · ${selectedPosition.department}`);
        $root.find('[data-portal-position]').removeClass('d-none');
    } else if (positionParam) {
        $root.find('[data-portal-position-closed]').removeClass('d-none');
    }

    $root.find('[data-portal-create]')
        .attr('href', withPosition($root.attr('data-register-url')))
        .attr('data-terms-decline-url', withPosition($root.attr('data-careers-url')));
    $root.find('[data-portal-login]').attr('href', withPosition($root.attr('data-login-url')));
    $root.find('[data-portal-back]').attr('href', withPosition($root.attr('data-careers-url')));
}

$(function () {
    const $portal = $('[data-page="applicant-portal"]');

    if ($portal.length) {
        initPortal($portal);

        return;
    }

    const $root = $('[data-page="applicant-register"]');

    if (!$root.length) {
        return;
    }

    // Already signed in this session: continue to the dashboard instead of registering again.
    if (currentAccountId()) {
        window.location.replace(withPosition($root.attr('data-dashboard-url')));

        return;
    }

    initAuthPage($root);
    initRegisterForm($root);
});
