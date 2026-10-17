import $ from './jquery.js';
import { bindDocumentFields, readDocuments, setDocuments, todayIsoDate } from './employee-documents.js';
import { formatDate } from './format.js';
import {
    addApplication,
    getApplicantApplication,
    isDuplicateApplication,
    listApplicantApplications,
    listOpenPositions,
    onMockDataChange,
} from './mock-data.js';
import { idNumberRule } from './validation-rules.js';

// An applicant who comes from the portal brings their application reference; anyone else applies as a newcomer.
const referenceParam = new URLSearchParams(window.location.search).get('ref') ?? '';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const phonePattern = /^\+?[\d\s().-]+$/;

// The 201 file details on the form that are copied to and from the application as they are typed (the employee form's names).
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

// Each rule returns an error message, or '' when the value is valid. The email is also checked against
// other applications for the chosen position, so it reads the position field too.
function validationRules($form) {
    return {
        name: (value) => (value.trim() ? '' : 'Enter your full name.'),
        email: (value) => {
            if (!emailPattern.test(value.trim())) {
                return 'Enter a valid email address.';
            }

            const jobTitleId = String(fieldFor($form, 'jobTitleId').val() ?? '');

            return jobTitleId && isDuplicateApplication(value, jobTitleId)
                ? 'An application from this email address already exists for this position.'
                : '';
        },
        phone: (value) => {
            const digitCount = value.replace(/\D/g, '').length;

            if (!value.trim()) {
                return 'Enter your phone number.';
            }

            return phonePattern.test(value.trim()) && digitCount >= 7 && digitCount <= 15 ? '' : 'Enter a valid phone number.';
        },
        birthdate: (value) => (value && value > todayIsoDate() ? 'The birthdate cannot be in the future.' : ''),
        sssNumber: idNumberRule,
        philhealthNumber: idNumberRule,
        pagibigNumber: idNumberRule,
        tin: idNumberRule,
        birNumber: idNumberRule,
        jobTitleId: (value) => (value ? '' : 'Select the position you are applying for.'),
    };
}

/**
 * Lists the open positions (those with at least one opening) under their department, keeping the
 * current choice while it is still open. Positions the applicant has already applied for are listed but
 * cannot be chosen again.
 */
function renderPositions($form) {
    const $select = fieldFor($form, 'jobTitleId');
    const selected = String($select.val() ?? '');
    const positions = listOpenPositions();
    const departments = [...new Set(positions.map((position) => position.department))];
    const applied = new Set(listApplicantApplications(referenceParam).applications.map((application) => application.jobTitleId));

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

    return positions.length;
}

function initApplyPage($root) {
    const $form = $root.find('[data-apply-form]');
    const $success = $root.find('[data-apply-success]');
    let submitted = false;

    setDocuments($form);
    bindDocumentFields($form);
    fieldFor($form, 'birthdate').attr('max', todayIsoDate());

    function render() {
        // Once submitted, the confirmation stays as it is.
        if (submitted) {
            return;
        }

        const hasPositions = renderPositions($form) > 0;

        $root.find('[data-apply-none]').toggleClass('d-none', hasPositions);
        $form.toggleClass('d-none', !hasPositions);
    }

    // Once a field has been flagged, re-check it as it is corrected.
    $form.on('input change', '.is-invalid', function () {
        setFieldError($(this), validationRules($form)[$(this).attr('name')](String($(this).val() ?? '')));
    });

    // A flagged email can depend on the position (one application per email per position).
    $form.on('change', '[name="jobTitleId"]', function () {
        const $email = fieldFor($form, 'email');

        if ($email.hasClass('is-invalid')) {
            setFieldError($email, validationRules($form).email(String($email.val() ?? '')));
        }
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        const rules = validationRules($form);
        let $firstInvalid = null;

        Object.entries(rules).forEach(([name, rule]) => {
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

        const position = listOpenPositions().find((candidate) => candidate.id === String(fieldFor($form, 'jobTitleId').val()));

        if (!position) {
            setFieldError(fieldFor($form, 'jobTitleId'), 'That position is no longer open. Choose another.');
            renderPositions($form);

            return;
        }

        const name = String(fieldFor($form, 'name').val()).trim();
        const application = addApplication({
            name,
            email: String(fieldFor($form, 'email').val()).trim(),
            phone: String(fieldFor($form, 'phone').val()).trim(),
            jobTitleId: position.id,
            departmentId: position.departmentId,
            appliedOn: todayIsoDate(),
            ...Object.fromEntries(detailFields.map((field) => [field, String(fieldFor($form, field).val() ?? '').trim()])),
            documents: readDocuments($form),
        }, name);

        submitted = true;
        $success.find('[data-apply-field="firstName"]').text(name.split(/\s+/)[0]);
        $success.find('[data-apply-field="jobTitle"]').text(position.name);
        $success.find('[data-apply-field="department"]').text(position.department);
        $success.find('[data-apply-field="appliedOn"]').text(formatDate(application.appliedOn));
        $success.find('[data-apply-portal-link]').attr('href', `${$root.attr('data-portal-url')}?ref=${encodeURIComponent(application.id)}`);
        $success.find('[data-apply-applications-link]').attr('href', `${$root.attr('data-applications-url')}?ref=${encodeURIComponent(application.id)}`);
        $form.addClass('d-none');
        $success.removeClass('d-none');
        $success.find('#apply-success-title').trigger('focus');
    });

    render();
    onMockDataChange(render);

    // From Open Positions: ?position= chooses the position and ?ref= is the applicant's existing application, whose
    // details and documents fill in the form, so nothing is asked for twice. Only an open position can be chosen; a closed or unknown one is refused.
    const params = new URLSearchParams(window.location.search);
    const requested = params.get('position');
    const existing = getApplicantApplication(params.get('ref') ?? '');

    if (existing) {
        fieldFor($form, 'name').val(existing.name);
        fieldFor($form, 'email').val(existing.email);
        fieldFor($form, 'phone').val(existing.phone);
        detailFields.filter((field) => existing[field]).forEach((field) => fieldFor($form, field).val(existing[field]));
        setDocuments($form, existing.documents);

        // Cancelling or going back returns to the applicant's applications instead of the sign-in page.
        $root.find('[data-apply-back]').attr('href', `${$root.attr('data-applications-url')}?ref=${encodeURIComponent(referenceParam)}`);
        $root.find('[data-apply-back-label]').text('Back to Applications');
    }

    if (requested) {
        const alreadyApplied = listApplicantApplications(referenceParam).applications.some((application) => application.jobTitleId === requested);

        if (alreadyApplied) {
            $root.find('[data-apply-position-notice]').text('You have already applied for that position. Choose another.').removeClass('d-none');
        } else if (listOpenPositions().some((position) => position.id === requested)) {
            fieldFor($form, 'jobTitleId').val(requested);
        } else {
            $root.find('[data-apply-position-notice]').removeClass('d-none');
        }
    }
}

$(function () {
    const $apply = $('[data-page="applicant-apply"]');

    if ($apply.length) {
        initApplyPage($apply);
    }
});
