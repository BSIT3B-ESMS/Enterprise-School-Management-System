import $ from './jquery.js';
import { todayIsoDate } from './employee-documents.js';
import { formatDate, initialsOf } from './format.js';
import { fillDocuments, fillFields } from './applicant-application.js';
import { accountId, isSignedIn } from './applicant-portal.js';
import { getApplicantAccount, onMockDataChange, updateApplicantAccount, validateApplicantEmail } from './mock-data.js';
import { bindMasks, maskedForDisplay } from './input-masks.js';
import { emailRule, idNumberRule, pagibigRule, philhealthRule, phoneRule, sssRule, tinRule } from './validation-rules.js';
import { showToast } from './toast.js';

// The profile details the form edits (the 201 file's field names); the name is not one of them.
const editableFields = ['email', 'phone', 'address', 'birthdate', 'sex', 'civilStatus', 'sssNumber', 'philhealthNumber', 'pagibigNumber', 'tin', 'birNumber'];

// Each rule returns an error message, or '' when the value is valid (the same rules as the job application form).
const validationRules = {
    birthdate: (value) => (value && value > todayIsoDate() ? 'The birthdate cannot be in the future.' : ''),
    email: (value) => (value.trim() ? emailRule(value) || validateApplicantEmail(accountId, value).email || '' : 'Enter your email address.'),
    phone: phoneRule,
    sssNumber: sssRule,
    philhealthNumber: philhealthRule,
    pagibigNumber: pagibigRule,
    tin: tinRule,
    birNumber: idNumberRule,
};

function renderProfile($root) {
    const account = getApplicantAccount(accountId);

    if (!account) {
        return;
    }

    fillFields($root, {
        initials: initialsOf(account.name),
        name: account.name,
        createdOn: account.createdOn ? formatDate(account.createdOn) : '',
        birthdate: account.birthdate ? formatDate(account.birthdate) : '',
        sex: account.sex,
        civilStatus: account.civilStatus,
        email: account.email,
        phone: account.phone,
        address: account.address,
        sssNumber: account.sssNumber,
        philhealthNumber: account.philhealthNumber,
        pagibigNumber: account.pagibigNumber,
        tin: account.tin,
        birNumber: account.birNumber,
    });
    fillDocuments($root, account.documents);
}

function setFieldError($field, message) {
    $(`#${$field.attr('aria-describedby').split(' ').find((id) => id.endsWith('-error'))}`).text(message);

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

    field('birthdate').attr('max', todayIsoDate());
    bindMasks($form);

    $editButton.on('click', function () {
        const account = getApplicantAccount(accountId);

        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $form.find('.invalid-feedback').text('');
        $form.find('[data-applicant-input="name"]').val(account.name);
        editableFields.forEach((name) => field(name).val(maskedForDisplay(field(name).attr('data-mask'), account[name] ?? '')));
        setEditing(true);
        field('birthdate').trigger('focus');
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

        const result = updateApplicantAccount(accountId, Object.fromEntries(editableFields.map((name) => [name, String(field(name).val() ?? '')])));

        if (!result.ok) {
            if (result.errors.email) {
                setFieldError(field('email'), result.errors.email);
                field('email').trigger('focus');
            } else {
                showToast(result.error, { isError: true });
            }

            return;
        }

        stopEditing();
        showToast('Changes applied.');
    });
}

$(function () {
    const $profile = $('[data-page="applicant-profile"]');

    if (!$profile.length || !isSignedIn()) {
        return;
    }

    renderProfile($profile);
    onMockDataChange(() => renderProfile($profile));
    initEdit($profile);
});
