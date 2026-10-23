import $ from './jquery.js';
import { bindDocumentFields, readDocuments, setDocuments, todayIsoDate } from './employee-documents.js';
import { formatDate, formatDateTime } from './format.js';
import {
    applicationBlockFor,
    findPosition,
    getApplicantAccount,
    getApplicationDraft,
    listApplicantApplications,
    listOpenPositions,
    onMockDataChange,
    saveApplicationDraft,
    submitApplication,
} from './mock-data.js';
import { accountId, isSignedIn } from './applicant-portal.js';
import { clearApplicationProgress, getApplicationProgress, saveApplicationProgress } from './applicant-session.js';
import { showToast } from './toast.js';
import { bindMasks, maskedForDisplay } from './input-masks.js';
import { birthdateRule, choiceRule, civilStatusOptions, phoneRule, sexOptions } from './validation-rules.js';

// The personal details on the form, with the employee record's field names, so they carry over when the applicant is
// hired. The emergency contact and government numbers are not asked for here: they are pre-employment information.
const detailFields = ['birthdate', 'sex', 'civilStatus', 'address'];

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

// Each rule returns an error message, or '' when the value is valid (the same rules as Create Account and the profile).
const validationRules = {
    jobTitleId: (value) => (value ? applicationBlockFor(accountId, value) : 'Select the position you are applying for.'),
    birthdate: birthdateRule('Enter your birthdate.'),
    sex: choiceRule(sexOptions, 'Select your sex.'),
    civilStatus: choiceRule(civilStatusOptions, 'Select your civil status.'),
    phone: phoneRule,
};

/**
 * Lists the open positions under their college, keeping the current choice while it can still be chosen.
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

// The chosen position's college, employment type, and location under the select; Back and Cancel return to its details.
function renderPositionSummary($root, $form) {
    const position = findPosition(String(fieldFor($form, 'jobTitleId').val() ?? ''));
    const positionsUrl = $root.attr('data-positions-url');

    $form.find('[data-apply-position-summary]').text(position
        ? [position.department, position.employmentType, position.location].filter(Boolean).join(' · ')
        : '');
    $root.find('[data-apply-back]').attr('href', position ? `${positionsUrl}/${encodeURIComponent(position.id)}` : positionsUrl);
    $root.find('[data-apply-back-label]').text(position ? 'Back to Position Details' : 'Back to Open Positions');
}

// The applicant's account and profile fill in the form, so nothing they gave before is asked for twice.
function prefill($form) {
    const account = getApplicantAccount(accountId);

    ['lastName', 'firstName', 'middleName', 'email'].forEach((field) => {
        $form.find(`[data-apply-account="${field}"]`).val(account[field] || (field === 'middleName' ? '' : '—'));
    });
    [...detailFields, 'phone'].filter((field) => account[field]).forEach((field) => {
        const $field = fieldFor($form, field);

        $field.val(maskedForDisplay($field.attr('data-mask'), account[field]));
    });
    setDocuments($form, account.documents);
}

// Everything on the form, trimmed, as Save Draft and Submit Application both send it.
function readForm($form) {
    return {
        jobTitleId: String(fieldFor($form, 'jobTitleId').val() ?? ''),
        phone: String(fieldFor($form, 'phone').val() ?? '').trim(),
        ...Object.fromEntries(detailFields.map((field) => [field, String(fieldFor($form, field).val() ?? '').trim()])),
        documents: readDocuments($form),
    };
}

// A saved draft (or what was typed earlier in this tab) fills in the form, including the position when it can still be chosen.
function fillFrom($root, $form, values) {
    ['phone', ...detailFields].forEach((field) => {
        const $field = fieldFor($form, field);

        if (values[field] !== undefined) {
            $field.val(maskedForDisplay($field.attr('data-mask'), values[field] ?? ''));
        }
    });

    if (values.documents) {
        setDocuments($form, values.documents);
    }

    if (values.jobTitleId && !applicationBlockFor(accountId, values.jobTitleId)) {
        fieldFor($form, 'jobTitleId').val(values.jobTitleId);
    }

    renderPositionSummary($root, $form);
}

function initApplyPage($root) {
    const $form = $root.find('[data-apply-form]');
    const $success = $root.find('[data-apply-success]');
    let submitted = false;
    let draftId = '';

    function showDraftNotice(draft) {
        $root.find('[data-apply-draft-notice]')
            .text(`Draft saved ${formatDateTime(new Date(draft.savedAt))}. It has not been submitted yet. Choose Submit Application when you are ready.`)
            .removeClass('d-none');
    }

    // Keeps what is typed for this tab, so going back to a position's details and returning does not lose it.
    function rememberProgress() {
        if (!submitted) {
            saveApplicationProgress({ ...readForm($form), draftId });
        }
    }

    setDocuments($form);
    bindDocumentFields($form, { onChange: rememberProgress });
    bindMasks($form);
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
        renderPositionSummary($root, $form);
    }

    // Once a field has been flagged, re-check it as it is corrected.
    $form.on('input change', '.is-invalid', function () {
        setFieldError($(this), validationRules[$(this).attr('name')]?.(String($(this).val() ?? '')) ?? '');
    });

    $form.on('input change', '[name]', rememberProgress);
    $form.on('change', '[name="jobTitleId"]', () => renderPositionSummary($root, $form));

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

        const result = submitApplication(accountId, { ...readForm($form), draftId });

        // The position closed, or an application for it came in, while the form was open (for example, from HR in another tab).
        if (!result.ok) {
            render();

            const [field] = Object.keys(result.errors ?? {});
            const $field = fieldFor($form, field ?? 'jobTitleId');

            setFieldError($field, result.errors?.[field] ?? result.error);
            $field.trigger('focus');

            return;
        }

        const { application } = result;

        submitted = true;
        clearApplicationProgress();
        $success.find('[data-apply-field="firstName"]').text(application.firstName || application.name.split(/\s+/)[0]);
        $success.find('[data-apply-field="jobTitle"]').text(application.jobTitle);
        $success.find('[data-apply-field="department"]').text(application.department);
        $success.find('[data-apply-field="appliedOn"]').text(formatDate(application.appliedOn));
        $success.find('[data-apply-application-link]').attr('href', `${$root.attr('data-application-url')}/${encodeURIComponent(application.id)}`);
        $root.find('[data-apply-position-notice], [data-apply-draft-notice]').addClass('d-none');
        $form.addClass('d-none');
        $success.removeClass('d-none');
        $success.find('#apply-success-title').trigger('focus');
    });

    // Save Draft keeps what is filled in so far to reopen later. Nothing is validated and no application is created, so
    // its status stays unset and HR Recruitment does not show it until Submit Application.
    $root.on('click', '[data-apply-save]', function () {
        const result = saveApplicationDraft(accountId, { ...readForm($form), draftId });

        if (!result.ok) {
            setFieldError(fieldFor($form, 'jobTitleId'), result.error);
            fieldFor($form, 'jobTitleId').trigger('focus');
            showToast(result.error, { isError: true });

            return;
        }

        setFieldError(fieldFor($form, 'jobTitleId'), '');
        draftId = result.draft.id;
        rememberProgress();
        window.history.replaceState(null, '', `${window.location.pathname}?draft=${encodeURIComponent(draftId)}`);
        showDraftNotice(result.draft);
        showToast('Draft saved. Your application has not been submitted yet.');
    });

    render();
    onMockDataChange(render);

    // Reopening a saved draft: from the dashboard (?draft=), or by choosing the same position (?position=).
    const params = new URLSearchParams(window.location.search);
    const requestedPosition = params.get('position') ?? '';
    const draft = getApplicationDraft(accountId, { id: params.get('draft') ?? '', jobTitleId: requestedPosition });

    if (draft) {
        draftId = draft.id;
        fillFrom($root, $form, draft);
        showDraftNotice(draft);
    }

    // What was typed earlier in this tab (and not submitted) comes back, unless it was for another position or draft.
    const progress = getApplicationProgress();

    if (progress && (progress.draftId || '') === draftId
        && (!requestedPosition || !progress.jobTitleId || progress.jobTitleId === requestedPosition)) {
        fillFrom($root, $form, progress);
    }

    // From a position's Apply Now (also after Create Account): choose that position, unless it is closed or
    // already applied for, which is explained instead.
    const requested = draft ? draft.jobTitleId : requestedPosition;

    if (requested) {
        const block = applicationBlockFor(accountId, requested);

        if (block) {
            $root.find('[data-apply-position-notice]').text(`${block} Choose another position.`).removeClass('d-none');
        } else {
            fieldFor($form, 'jobTitleId').val(requested);
            renderPositionSummary($root, $form);
        }
    }
}

$(function () {
    const $apply = $('[data-page="applicant-apply"]');

    if ($apply.length && isSignedIn()) {
        initApplyPage($apply);
    }
});
