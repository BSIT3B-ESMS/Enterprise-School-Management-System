import $ from './jquery.js';
import { bindDocumentFields, readDocuments, setDocuments, todayIsoDate } from './employee-documents.js';
import { formatDate } from './format.js';
import {
    applicationBlockFor,
    findPosition,
    getApplicantAccount,
    listApplicantApplications,
    listOpenPositions,
    onMockDataChange,
    submitApplication,
} from './mock-data.js';
import { accountId, isSignedIn } from './applicant-portal.js';
import { idNumberRule, phoneRule } from './validation-rules.js';

// The 201 file details on the form, with the employee record's field names, so they carry over when the applicant is hired.
const detailFields = ['birthdate', 'sex', 'civilStatus', 'address', 'sssNumber', 'philhealthNumber', 'pagibigNumber', 'tin', 'birNumber'];

function fieldFor($form, name) {
    return $form.find(`[name="${name}"]`);
}

function setFieldError($field, message) {
    $(`#${$field.attr('aria-describedby').split(' ').find((id) => id.endsWith('-error'))}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

// Each rule returns an error message, or '' when the value is valid (the same rules as the HR employee form).
const validationRules = {
    birthdate: (value) => (value && value > todayIsoDate() ? 'The birthdate cannot be in the future.' : ''),
    jobTitleId: (value) => (value ? applicationBlockFor(accountId, value) : 'Select the position you are applying for.'),
    phone: phoneRule,
    sssNumber: idNumberRule,
    philhealthNumber: idNumberRule,
    pagibigNumber: idNumberRule,
    tin: idNumberRule,
    birNumber: idNumberRule,
};

/**
 * Lists the open positions under their department, keeping the current choice while it can still be chosen.
 * Positions the applicant has already applied for are listed but cannot be chosen again. Returns how many can be chosen.
 */
function renderPositions($form) {
    const $select = fieldFor($form, 'jobTitleId');
    const selected = String($select.val() ?? '');
    const positions = listOpenPositions();
    const departments = [...new Set(positions.map((position) => position.department))];
    const applied = new Set(listApplicantApplications(accountId).map((application) => application.jobTitleId));

    $select.empty().append(
        $('<option>', { value: '', text: 'Select position' }),
        ...departments.map((department) => $('<optgroup>', { label: department }).append(
            ...positions.filter((position) => position.department === department).map((position) => $('<option>', {
                value: position.id,
                disabled: applied.has(position.id),
                text: applied.has(position.id)
                    ? `${position.name} (already applied)`
                    : `${position.name} (${position.openings} ${position.openings === 1 ? 'opening' : 'openings'})`,
            })),
        )),
    );

    if (positions.some((position) => position.id === selected && !applied.has(position.id))) {
        $select.val(selected);
    }

    return positions.filter((position) => !applied.has(position.id)).length;
}

// The chosen position's employment type, location, and department under the select.
function renderPositionSummary($form) {
    const position = findPosition(String(fieldFor($form, 'jobTitleId').val() ?? ''));

    $form.find('[data-apply-position-summary]').text(position
        ? [position.department, position.employmentType, position.location].filter(Boolean).join(' · ')
        : '');
}

// The applicant's profile fills in the form, so nothing they gave before is asked for twice.
function prefill($form) {
    const account = getApplicantAccount(accountId);

    $form.find('[data-apply-account="name"]').val(account.name);
    $form.find('[data-apply-account="email"]').val(account.email);
    fieldFor($form, 'phone').val(account.phone);
    detailFields.filter((field) => account[field]).forEach((field) => fieldFor($form, field).val(account[field]));
    setDocuments($form, account.documents);
}

function initApplyPage($root) {
    const $form = $root.find('[data-apply-form]');
    const $success = $root.find('[data-apply-success]');
    let submitted = false;

    setDocuments($form);
    bindDocumentFields($form);
    fieldFor($form, 'birthdate').attr('max', todayIsoDate());
    prefill($form);

    function render() {
        // Once submitted, the confirmation stays as it is.
        if (submitted) {
            return;
        }

        const hasPositions = renderPositions($form) > 0;

        $root.find('[data-apply-none]').toggleClass('d-none', hasPositions);
        $form.toggleClass('d-none', !hasPositions);
        renderPositionSummary($form);
    }

    // Once a field has been flagged, re-check it as it is corrected.
    $form.on('input change', '.is-invalid', function () {
        setFieldError($(this), validationRules[$(this).attr('name')](String($(this).val() ?? '')));
    });

    $form.on('change', '[name="jobTitleId"]', () => renderPositionSummary($form));

    $form.on('submit', function (event) {
        event.preventDefault();

        let $firstInvalid = null;

        Object.entries(validationRules).forEach(([name, rule]) => {
            const $field = fieldFor($form, name);
            const message = rule(String($field.val() ?? ''));

            setFieldError($field, message);

            if (message && !$firstInvalid) {
                $firstInvalid = $field;
            }
        });

        if ($firstInvalid) {
            $firstInvalid.trigger('focus');

            return;
        }

        const result = submitApplication(accountId, {
            jobTitleId: String(fieldFor($form, 'jobTitleId').val()),
            phone: String(fieldFor($form, 'phone').val()),
            ...Object.fromEntries(detailFields.map((field) => [field, String(fieldFor($form, field).val() ?? '')])),
            documents: readDocuments($form),
        });

        // The position closed, or an application for it came in, while the form was open (for example, from HR in another tab).
        if (!result.ok) {
            render();
            setFieldError(fieldFor($form, 'jobTitleId'), result.error);
            fieldFor($form, 'jobTitleId').trigger('focus');

            return;
        }

        const { application } = result;

        submitted = true;
        $success.find('[data-apply-field="firstName"]').text(application.name.split(/\s+/)[0]);
        $success.find('[data-apply-field="jobTitle"]').text(application.jobTitle);
        $success.find('[data-apply-field="department"]').text(application.department);
        $success.find('[data-apply-field="appliedOn"]').text(formatDate(application.appliedOn));
        $success.find('[data-apply-application-link]').attr('href', `${$root.attr('data-application-url')}/${encodeURIComponent(application.id)}`);
        $root.find('[data-apply-position-notice]').addClass('d-none');
        $form.addClass('d-none');
        $success.removeClass('d-none');
        $success.find('#apply-success-title').trigger('focus');
    });

    render();
    onMockDataChange(render);

    // From a position's Apply Now (also after Create Account): choose that position, unless it is closed or
    // already applied for, which is explained instead.
    const requested = new URLSearchParams(window.location.search).get('position');

    if (requested) {
        const block = applicationBlockFor(accountId, requested);

        if (block) {
            $root.find('[data-apply-position-notice]').text(`${block} Choose another position.`).removeClass('d-none');
        } else {
            fieldFor($form, 'jobTitleId').val(requested);
            renderPositionSummary($form);
        }
    }
}

$(function () {
    const $apply = $('[data-page="applicant-apply"]');

    if ($apply.length && isSignedIn()) {
        initApplyPage($apply);
    }
});
