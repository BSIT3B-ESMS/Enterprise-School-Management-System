import $ from './jquery.js';
import { documentItem } from './employee-documents.js';
import { formatDate, initialsOf } from './format.js';
import { reference } from './applicant-portal.js';
import { getApplicantApplication, isApplicantEmailTaken, onMockDataChange, updateApplicantProfile } from './mock-data.js';
import { statusClass } from './recruitment-workflow.js';
import { showToast } from './toast.js';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const phonePattern = /^\+?[\d\s().-]+$/;

// Each rule returns an error message, or '' when the value is valid. The email is also checked against other
// applications for the same position (one application per email per position).
const validationRules = {
    email: (value) => {
        if (!emailPattern.test(value.trim())) {
            return 'Enter a valid email address.';
        }

        return isApplicantEmailTaken(reference, value) ? 'An application from this email address already exists for this position.' : '';
    },
    phone: (value) => {
        const digitCount = value.replace(/\D/g, '').length;

        if (!value.trim()) {
            return 'Enter your phone number.';
        }

        return phonePattern.test(value.trim()) && digitCount >= 7 && digitCount <= 15 ? '' : 'Enter a valid phone number.';
    },
};

function renderProfile($root) {
    const application = getApplicantApplication(reference);

    // No reference, or the application was deleted (for example, by HR in another tab).
    $root.find('[data-applicant-missing]').toggleClass('d-none', Boolean(application));
    $root.find('[data-applicant-content]').toggleClass('d-none', !application);

    if (!application) {
        return;
    }

    const values = {
        initials: initialsOf(application.name),
        name: application.name,
        role: `${application.jobTitle} · ${application.department}`,
        birthdate: application.birthdate ? formatDate(application.birthdate) : '',
        sex: application.sex,
        civilStatus: application.civilStatus,
        email: application.email,
        phone: application.phone,
        address: application.address,
        sssNumber: application.sssNumber,
        philhealthNumber: application.philhealthNumber,
        pagibigNumber: application.pagibigNumber,
        tin: application.tin,
        birNumber: application.birNumber,
        jobTitle: application.jobTitle,
        department: application.department,
        appliedOn: formatDate(application.appliedOn),
        status: application.status,
    };

    Object.entries(values).forEach(([field, value]) => {
        const $fields = $root.find(`[data-applicant-field="${field}"]`);

        if (value) {
            $fields.text(value).removeClass('text-body-secondary');
        } else {
            $fields.text('Not provided').addClass('text-body-secondary');
        }
    });
    $root.find('[data-applicant-status-pill]').attr('class', `status-pill ${statusClass(application.status)}`).text(application.status);

    $root.find('[data-applicant-documents]').each(function () {
        const key = $(this).attr('data-applicant-documents');
        const files = application.documents?.[key] ?? [];

        $(this).empty().append(...files.map((file) => documentItem(file)));
        $root.find(`[data-applicant-documents-empty="${key}"]`).toggleClass('d-none', files.length > 0);
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

// Edit swaps the tabs for the form, prefilled; Save Changes and Cancel both return to the tabs, and Cancel
// leaves the profile untouched.
function initEdit($root) {
    const $form = $root.find('[data-applicant-form]');
    const $view = $root.find('[data-applicant-view]');
    const $editButton = $root.find('[data-applicant-edit-start]');
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
        const application = getApplicantApplication(reference);

        if (!application) {
            showToast('Your application no longer exists.', { isError: true });

            return;
        }

        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $form.find('.invalid-feedback').text('');
        field('email').val(application.email);
        field('phone').val(application.phone);
        $form.find('[data-applicant-input="name"]').val(application.name);
        setEditing(true);
        field('email').trigger('focus');
    });

    $form.on('click', '[data-applicant-edit-cancel]', stopEditing);

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

        if (!updateApplicantProfile(reference, { email: String(field('email').val()), phone: String(field('phone').val()) })) {
            showToast('Your application no longer exists.', { isError: true });

            return;
        }

        stopEditing();
        showToast('Changes applied.');
    });
}

$(function () {
    const $profile = $('[data-page="applicant-profile"]');

    if (!$profile.length) {
        return;
    }

    renderProfile($profile);
    onMockDataChange(() => renderProfile($profile));
    initEdit($profile);
});
