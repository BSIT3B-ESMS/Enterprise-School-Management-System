import $ from './jquery.js';
import { getHrProfile, onMockDataChange, updateHrProfile } from './mock-data.js';
import { showToast } from './toast.js';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

// Each rule returns an error message, or '' when the value is valid.
const validationRules = {
    name: (value) => (value.trim() ? '' : 'Enter your full name.'),
    email: (value) => (emailPattern.test(value.trim()) ? '' : 'Enter a valid email address.'),
};

function renderProfile($root) {
    const profile = getHrProfile();

    Object.entries(profile).forEach(([field, value]) => {
        $root.find(`[data-hr-field="${field}"]`).text(value);
    });
}

function setFieldError($field, message) {
    $(`#${$field.attr('aria-describedby')}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

/**
 * Back goes to the HR page the user came from (found among the sidebar's pages, so the login page or
 * another site never counts), with its query string, and to the Dashboard when there is none.
 */
function initBackLink($root) {
    const $back = $root.find('[data-hr-back-link]');
    const pagePaths = $('#app-sidebar .app-nav a').map(function () {
        return new URL(this.href, window.location.href).pathname;
    }).get();

    try {
        const previous = new URL(document.referrer);
        const isHrPage = pagePaths.some((path) => previous.pathname === path || previous.pathname.startsWith(`${path}/`));

        if (previous.origin === window.location.origin && isHrPage) {
            $back.attr('href', previous.href);

            return;
        }
    } catch {
        // No usable referrer (opened directly); the link keeps its Dashboard address.
    }

    $back.attr('href', $back.attr('data-fallback-url'));
}

// Edit Profile swaps the details for the form, prefilled; Save Changes and Cancel both return to the
// details, and Cancel leaves the profile untouched.
function initEdit($root) {
    const $form = $root.find('[data-hr-form]');
    const $view = $root.find('[data-hr-view]');
    const $editButton = $root.find('[data-hr-edit-start]');
    const field = (name) => $form.find(`[name="${name}"]`);

    function setEditing(isEditing) {
        $form.toggleClass('d-none', !isEditing);
        $view.toggleClass('d-none', isEditing);
        $editButton.toggleClass('d-none', isEditing);
    }

    function stopEditing() {
        setEditing(false);
        $editButton.trigger('focus');
    }

    $editButton.on('click', function () {
        const profile = getHrProfile();

        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $form.find('.invalid-feedback').text('');
        field('name').val(profile.name);
        field('email').val(profile.email);
        $form.find('[data-hr-field-input="role"]').val(profile.role);
        setEditing(true);
        field('name').trigger('focus');
    });

    $form.on('click', '[data-hr-edit-cancel]', stopEditing);

    // Once a field has been flagged, re-check it as the user corrects it.
    $form.on('input change', '.is-invalid', function () {
        setFieldError($(this), validationRules[$(this).attr('name')](String($(this).val() ?? '')));
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        let $firstInvalid = null;

        Object.entries(validationRules).forEach(([name, rule]) => {
            const message = rule(String(field(name).val() ?? ''));

            setFieldError(field(name), message);

            if (message && !$firstInvalid) {
                $firstInvalid = field(name);
            }
        });

        if ($firstInvalid) {
            $firstInvalid.trigger('focus');

            return;
        }

        updateHrProfile({ name: String(field('name').val()), email: String(field('email').val()) });
        stopEditing();
        showToast('Changes saved.');
    });
}

$(function () {
    const $profile = $('[data-page="hr-profile"]');

    if (!$profile.length) {
        return;
    }

    renderProfile($profile);
    onMockDataChange(() => renderProfile($profile));
    initBackLink($profile);
    initEdit($profile);
});
