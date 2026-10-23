import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { bindDocumentFields, readDocuments, setDocuments, todayIsoDate } from './employee-documents.js';
import { estimateLeaveBalance, fileLeaveRequest, getLeaveTypes, getPortalProfile, validateLeaveRequest } from './mock-data.js';
import { showToast } from './toast.js';

const errorIds = { type: 'leave-type-error', startDate: 'leave-start-error', endDate: 'leave-end-error', reason: 'leave-reason-error' };

function showErrors($form, errors) {
    Object.entries(errorIds).forEach(([name, id]) => {
        const $field = $form.find(`[name="${name}"]`);
        const message = errors[name] ?? '';

        $(`#${id}`).text(message);

        if (message) {
            $field.addClass('is-invalid').attr('aria-invalid', 'true');
        } else {
            $field.removeClass('is-invalid').attr('aria-invalid', 'false');
        }
    });
}

const daysText = (count) => `${count} ${Math.abs(count) === 1 ? 'day' : 'days'}`;

// Available credits, the days requested, and the estimate if HR approves it. Informational only: nothing is deducted until
// HR approves, and a request is never blocked for exceeding the credits.
function renderCreditNote($form) {
    const { days, balance, remainingAfter } = estimateLeaveBalance({
        startDate: String($form.find('[name="startDate"]').val() ?? ''),
        endDate: String($form.find('[name="endDate"]').val() ?? ''),
    });
    const $note = $form.find('[data-leave-credit-note]');

    if (balance.credits === null) {
        $note.addClass('d-none');

        return;
    }

    const parts = [`Available leave credits for ${balance.year}: ${daysText(balance.remaining)} of ${daysText(balance.credits)}.`];

    if (days) {
        parts.push(`Requested: ${daysText(days)}.`, remainingAfter < 0
            ? `If approved, this would be ${daysText(-remainingAfter)} over your available credits.`
            : `Estimated remaining if approved: ${daysText(remainingAfter)}.`);
    } else {
        parts.push('Choose the start and end dates to see the days requested.');
    }

    $note.removeClass('d-none alert-secondary alert-warning').addClass(days && remainingAfter < 0 ? 'alert-warning' : 'alert-secondary')
        .text(parts.join(' '));
}

const valuesOf = ($form) => ({
    ...Object.fromEntries(['type', 'startDate', 'endDate', 'reason'].map((name) => [name, String($form.find(`[name="${name}"]`).val() ?? '')])),
    supportingDocuments: readDocuments($form).supportingDocuments ?? [],
});

// Request Leave on the employee Calendar: files leave for HR to approve. It is Pending and appears at once on the
// Calendar, on the Leave Request page, and on HR's Leave Requests and Calendar; nothing here approves or changes a status.
// What was typed is kept when a check fails, and when the form is closed without submitting; it starts empty again
// only after a request is filed.
function initLeaveRequest($modal) {
    const $form = $modal.find('[data-leave-request-form]');
    let startsEmpty = true;

    bindDocumentFields($form);

    $modal.on('show.bs.modal', function () {
        const employee = getPortalProfile();

        $modal.find('[data-leave-employee="name"]').text(employee?.name ?? '—');
        $modal.find('[data-leave-employee="id"]').text(employee?.id ?? '—');
        $modal.find('[data-leave-employee="department"]').text(employee?.department ?? '—');
        $modal.find('[data-leave-employee="jobTitle"]').text(employee?.jobTitle ?? '—');
        $modal.find('[data-leave-request-error]').addClass('d-none');

        if (startsEmpty) {
            $form[0].reset();
            $form.find('[data-leave-type-options]').empty().append(
                $('<option>', { value: '', text: 'Select leave type' }),
                ...getLeaveTypes().map((type) => $('<option>', { value: type, text: type })),
            );
            setDocuments($form, {});
            showErrors($form, {});
            startsEmpty = false;
        }

        $form.find('[name="startDate"]').attr('min', todayIsoDate());
        $form.find('[name="endDate"]').attr('min', String($form.find('[name="startDate"]').val() || todayIsoDate()));
        renderCreditNote($form);
    });
    $modal.on('shown.bs.modal', () => $form.find('[name="type"]').trigger('focus'));

    // Once flagged, fields are re-checked as they are corrected; the end date cannot be before the start date.
    $form.on('input change', 'select, input, textarea', function () {
        if ($(this).attr('name') === 'startDate') {
            $form.find('[name="endDate"]').attr('min', String($(this).val() || todayIsoDate()));
        }

        if ($form.find('.is-invalid').length) {
            showErrors($form, validateLeaveRequest(valuesOf($form)));
        }

        renderCreditNote($form);
    });

    let isSubmitting = false;

    $form.on('submit', function (event) {
        event.preventDefault();

        // A second Enter or click while the request is being filed must not file it twice.
        if (isSubmitting) {
            return;
        }

        isSubmitting = true;

        const result = fileLeaveRequest(valuesOf($form));

        isSubmitting = false;

        if (!result.ok) {
            showErrors($form, result.errors ?? {});

            if (result.error) {
                $modal.find('[data-leave-request-error]').text(result.error).removeClass('d-none');
            } else {
                $form.find('.is-invalid').first().trigger('focus');
            }

            return;
        }

        startsEmpty = true;
        Modal.getOrCreateInstance($modal[0]).hide();
        showToast(`${result.leave.type} request filed. It is Pending until HR approves or rejects it.`);
    });
}

$(function () {
    const $modal = $('[data-leave-request-modal]');

    if ($modal.length) {
        initLeaveRequest($modal);
    }
});
