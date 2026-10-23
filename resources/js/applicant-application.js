import $ from './jquery.js';
import { documentItem } from './employee-documents.js';
import { formatDate, formatDateTime, formatPesoAmount } from './format.js';
import { accountId, activityItem, isSignedIn, renderSteps, statusMessageFor } from './applicant-portal.js';
import { getApplicantAccount, getApplicantApplication, onMockDataChange, respondToOffer, submitPreEmployment, validatePreEmployment } from './mock-data.js';
import { statusClass } from './recruitment-workflow.js';
import { bindMasks, maskedForDisplay } from './input-masks.js';
import { showToast } from './toast.js';

// The pre-employment information the applicant gives after accepting an offer.
const preEmploymentFields = [
    'emergencyContactName', 'emergencyContactNumber', 'emergencyContactRelationship',
    'sssNumber', 'philhealthNumber', 'pagibigNumber', 'tin', 'birNumber',
];

const responseText = { Pending: 'Not answered yet', Accepted: 'Accepted', Declined: 'Declined' };

/**
 * Shows each value, or "Not provided" (muted) when it is blank.
 */
export function fillFields($root, values) {
    Object.entries(values).forEach(([field, value]) => {
        const $fields = $root.find(`[data-applicant-field="${field}"]`);

        if (value) {
            $fields.text(value).removeClass('text-body-secondary');
        } else {
            $fields.text('Not provided').addClass('text-body-secondary');
        }
    });
}

/**
 * Lists the submitted documents by category (file names only).
 */
export function fillDocuments($root, documents) {
    $root.find('[data-applicant-documents]').each(function () {
        const key = $(this).attr('data-applicant-documents');
        const files = documents?.[key] ?? [];

        $(this).empty().append(...files.map((file) => documentItem(file)));
        $root.find(`[data-applicant-documents-empty="${key}"]`).toggleClass('d-none', files.length > 0);
    });
}

function setFieldError($field, message) {
    $(`#${$field.attr('aria-describedby').split(' ').find((id) => id.endsWith('-error'))}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

// The offer HR issued and, while it waits for an answer, Accept and Decline.
function renderOffer($root, application) {
    const { offer } = application;

    $root.find('[data-applicant-offer]').toggleClass('d-none', !offer);

    if (!offer) {
        return;
    }

    fillFields($root, {
        offerSalary: offer.salary ? formatPesoAmount(offer.salary) : '',
        offerStartDate: offer.startDate ? formatDate(offer.startDate) : '',
        offerResponse: responseText[application.offerResponse] ?? '',
        offerNotes: offer.notes,
    });
    $root.find('[data-applicant-offer-status]').text(application.canRespondToOffer
        ? 'Only you can accept or decline this offer. HR is notified of your answer.'
        : `You ${String(application.offerResponse).toLowerCase()} this offer.`);
    $root.find('[data-applicant-offer-actions]').toggleClass('d-none', !application.canRespondToOffer);

    if (!application.canRespondToOffer) {
        $root.find('[data-applicant-offer-confirm]').addClass('d-none');
    }
}

// After accepting, the pre-employment form (prefilled from what was given before), until hiring is completed.
function renderPreEmployment($root, application, $form) {
    const isOpen = application.canSubmitPreEmployment;
    const isShown = isOpen || Boolean(application.preEmploymentSubmittedAt);

    $root.find('[data-applicant-pre-employment]').toggleClass('d-none', !isShown);
    $form.toggleClass('d-none', !isOpen);
    $root.find('[data-applicant-pre-employment-status]').text(application.preEmploymentSubmittedAt
        ? `Submitted ${formatDateTime(new Date(application.preEmploymentSubmittedAt))}.${isOpen ? ' You can correct it until HR completes your hiring.' : ''}`
        : 'HR needs this before completing your hiring. Your emergency contact is required.');

    // Fill the form once (or again after a change elsewhere when nothing has been typed).
    if (isOpen && !$form.data('filled')) {
        const account = getApplicantAccount(accountId) ?? {};

        preEmploymentFields.forEach((field) => {
            const $field = $form.find(`[name="${field}"]`);

            $field.val(maskedForDisplay($field.attr('data-mask'), application[field] || account[field] || ''));
        });
        $form.data('filled', true);
    }
}

function render($root, $form) {
    const application = getApplicantApplication(accountId, $root.attr('data-applicant-application'));

    // Not one of this applicant's applications, or HR deleted it.
    $root.find('[data-applicant-missing]').toggleClass('d-none', Boolean(application));
    $root.find('[data-applicant-content]').toggleClass('d-none', !application);

    if (!application) {
        return;
    }

    // Newest first, so the latest demo interview or final interview HR set is at the top.
    const activities = [...application.activities].sort((first, second) => (
        `${second.date} ${second.time}`.localeCompare(`${first.date} ${first.time}`)
    ));

    fillFields($root, {
        jobTitle: application.jobTitle,
        department: application.department,
        employmentType: application.employmentType,
        location: application.location,
        appliedOn: formatDate(application.appliedOn),
        status: application.status,
        lastName: application.lastName,
        firstName: application.firstName,
        middleName: application.middleName,
        email: application.email,
        phone: application.phone,
        birthdate: application.birthdate ? formatDate(application.birthdate) : '',
        sex: application.sex,
        civilStatus: application.civilStatus,
        address: application.address,
    });
    $root.find('[data-applicant-position-link]').attr('href', `${$root.attr('data-position-url')}/${encodeURIComponent(application.jobTitleId)}`);
    $root.find('[data-applicant-status-pill]').attr('class', `status-pill ${statusClass(application.status)}`).text(application.status);
    $root.find('[data-applicant-status-message]').text(statusMessageFor(application));
    renderSteps($root, application);
    renderOffer($root, application);
    renderPreEmployment($root, application, $form);
    $root.find('[data-applicant-activities-empty]').toggleClass('d-none', activities.length > 0);
    $root.find('[data-applicant-activities]').empty().append(...activities.map((activity) => activityItem(activity)));
    fillDocuments($root, application.documents);
}

function initOfferResponse($root) {
    const $confirm = $root.find('[data-applicant-offer-confirm]');
    let pendingResponse = '';

    // Accept and Decline each ask for confirmation first; declining cannot be undone.
    $root.on('click', '[data-applicant-offer-respond]', function () {
        pendingResponse = $(this).attr('data-applicant-offer-respond');
        $root.find('[data-applicant-offer-confirm-message]').text(pendingResponse === 'Accepted'
            ? 'Accept this offer? You will then be asked for your pre-employment information.'
            : 'Decline this offer? Your application for this position will close and cannot be reopened.');
        $root.find('[data-applicant-offer-confirm-yes]')
            .text(pendingResponse === 'Accepted' ? 'Accept Offer' : 'Decline Offer')
            .toggleClass('btn-primary', pendingResponse === 'Accepted')
            .toggleClass('btn-danger', pendingResponse === 'Declined');
        $confirm.removeClass('d-none');
        $root.find('[data-applicant-offer-confirm-yes]').trigger('focus');
    });

    $root.on('click', '[data-applicant-offer-confirm-no]', function () {
        pendingResponse = '';
        $confirm.addClass('d-none');
    });

    $root.on('click', '[data-applicant-offer-confirm-yes]', function () {
        const result = respondToOffer(accountId, $root.attr('data-applicant-application'), pendingResponse);

        $confirm.addClass('d-none');

        if (!result.ok) {
            showToast(result.error, { isError: true });

            return;
        }

        showToast(pendingResponse === 'Accepted'
            ? 'Offer accepted. Please submit your pre-employment information.'
            : 'Offer declined.');
        pendingResponse = '';
    });
}

function initPreEmploymentForm($root, $form) {
    const valuesOf = () => Object.fromEntries(preEmploymentFields.map((field) => [field, String($form.find(`[name="${field}"]`).val() ?? '').trim()]));

    bindMasks($form);

    // Once a field has been flagged, re-check it as it is corrected.
    $form.on('input change', '.is-invalid', function () {
        setFieldError($(this), validatePreEmployment(valuesOf())[$(this).attr('name')] ?? '');
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        const errors = validatePreEmployment(valuesOf());
        let $firstInvalid = null;

        preEmploymentFields.forEach((field) => {
            const $field = $form.find(`[name="${field}"]`);

            setFieldError($field, errors[field] ?? '');

            if (errors[field] && !$firstInvalid) {
                $firstInvalid = $field;
            }
        });

        if ($firstInvalid) {
            $firstInvalid.trigger('focus');

            return;
        }

        const result = submitPreEmployment(accountId, $root.attr('data-applicant-application'), valuesOf());

        if (!result.ok) {
            showToast(result.error, { isError: true });

            return;
        }

        showToast('Pre-employment information submitted. HR can now complete your hiring.');
    });
}

$(function () {
    const $page = $('[data-page="applicant-application"]');

    if (!$page.length || !isSignedIn()) {
        return;
    }

    const $form = $page.find('[data-applicant-pre-employment-form]');

    initOfferResponse($page);
    initPreEmploymentForm($page, $form);
    render($page, $form);
    onMockDataChange(() => render($page, $form));
});
