import $ from './jquery.js';
import { formatDate, initialsOf } from './format.js';
import { getPortalProfile, onMockDataChange, updatePortalProfile } from './mock-data.js';
import { showToast } from './toast.js';

const statusClasses = { Active: 'employment-active', 'On Leave': 'employment-on-leave' };

// Optional, but when given: digits with an optional leading +, and spaces, dashes, or brackets between them.
const phonePattern = /^\+?[\d\s()-]{7,}$/;

// Each rule returns an error message, or '' when the value is valid.
const validationRules = {
    phone: (value) => (!value.trim() || phonePattern.test(value.trim()) ? '' : 'Enter a valid phone number.'),
};

function displayValues(employee) {
    return {
        initials: initialsOf(employee.name),
        name: employee.name,
        id: employee.id,
        role: `${employee.jobTitle} · ${employee.department}`,
        birthdate: employee.birthdate ? formatDate(employee.birthdate) : '',
        sex: employee.sex,
        civilStatus: employee.civilStatus,
        department: employee.department,
        jobTitle: employee.jobTitle,
        hiredOn: employee.hiredOn ? formatDate(employee.hiredOn) : '',
        status: employee.status,
        email: employee.email,
        phone: employee.phone,
        address: employee.address,
        sssNumber: employee.sssNumber,
        philhealthNumber: employee.philhealthNumber,
        pagibigNumber: employee.pagibigNumber,
        tin: employee.tin,
        birNumber: employee.birNumber,
    };
}

function renderProfile($root) {
    const employee = getPortalProfile();

    // HR can delete the employee's record, for example in another tab.
    $root.find('[data-portal-missing]').toggleClass('d-none', Boolean(employee));
    $root.find('[data-portal-content]').toggleClass('d-none', !employee);

    if (!employee) {
        return;
    }

    Object.entries(displayValues(employee)).forEach(([field, value]) => {
        const $fields = $root.find(`[data-portal-field="${field}"]`);

        if (value) {
            $fields.text(value).removeClass('text-body-secondary');
        } else {
            $fields.text('Not provided').addClass('text-body-secondary');
        }
    });

    $root.find('[data-portal-status-pill]')
        .attr('class', `status-pill ${statusClasses[employee.status] ?? ''}`.trim())
        .text(employee.status ?? '');
}

function setFieldError($field, message) {
    $(`#${$field.attr('aria-describedby')}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

// Edit swaps the tabs for the form, prefilled; Save Changes and Cancel both return to the tabs, and
// Cancel leaves the profile untouched.
function initEdit($root) {
    const $form = $root.find('[data-portal-form]');
    const $view = $root.find('[data-portal-view]');
    const $editButton = $root.find('[data-portal-edit-start]');
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
        const employee = getPortalProfile();

        if (!employee) {
            showToast('Your employee record no longer exists.', { isError: true });

            return;
        }

        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $form.find('.invalid-feedback').text('');
        field('civilStatus').val(employee.civilStatus);
        field('phone').val(employee.phone ?? '');
        field('address').val(employee.address ?? '');

        // Read-only context: the same values the tabs show, which only HR can change.
        $form.find('[data-portal-input]').each(function () {
            const key = $(this).attr('data-portal-input');

            $(this).val(key === 'birthdate' && employee.birthdate ? formatDate(employee.birthdate) : (employee[key] ?? ''));
        });
        setEditing(true);
        field('civilStatus').trigger('focus');
    });

    $form.on('click', '[data-portal-edit-cancel]', stopEditing);

    // Once a field has been flagged, re-check it as the user corrects it.
    $form.on('input change', '.is-invalid', function () {
        setFieldError($(this), validationRules[$(this).attr('name')](String($(this).val() ?? '')));
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        const message = validationRules.phone(String(field('phone').val() ?? ''));

        setFieldError(field('phone'), message);

        if (message) {
            field('phone').trigger('focus');

            return;
        }

        if (!updatePortalProfile({
            civilStatus: String(field('civilStatus').val()),
            phone: String(field('phone').val()),
            address: String(field('address').val()),
        })) {
            showToast('Your employee record no longer exists.', { isError: true });

            return;
        }

        stopEditing();
        showToast('Changes saved.');
    });
}

$(function () {
    const $profile = $('[data-page="portal-profile"]');

    if (!$profile.length) {
        return;
    }

    renderProfile($profile);
    onMockDataChange(() => renderProfile($profile));
    initEdit($profile);
});
