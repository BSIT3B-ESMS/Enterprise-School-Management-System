import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { todayIsoDate } from './employee-documents.js';
import { fileLeaveRequest, getLeaveTypes, validateLeaveRequest } from './mock-data.js';
import { showToast } from './toast.js';

const errorIds = { type: 'leave-type-error', startDate: 'leave-start-error', endDate: 'leave-end-error' };

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

const valuesOf = ($form) => Object.fromEntries(['type', 'startDate', 'endDate'].map((name) => [name, String($form.find(`[name="${name}"]`).val() ?? '')]));

// Request Leave on the employee Calendar: files leave for HR to approve. It appears at once on the Calendar as
// Filed (and on HR's Calendar); nothing here approves or changes a request's status.
function initLeaveRequest($modal) {
    const $form = $modal.find('[data-leave-request-form]');

    $modal.on('show.bs.modal', function () {
        $form[0].reset();
        $form.find('[data-leave-type-options]').empty().append(
            $('<option>', { value: '', text: 'Select leave type' }),
            ...getLeaveTypes().map((type) => $('<option>', { value: type, text: type })),
        );
        $form.find('[name="startDate"], [name="endDate"]').attr('min', todayIsoDate());
        showErrors($form, {});
    });
    $modal.on('shown.bs.modal', () => $form.find('[name="type"]').trigger('focus'));

    // Once flagged, fields are re-checked as they are corrected; the last day cannot start before the first.
    $form.on('input change', 'select, input', function () {
        if ($(this).attr('name') === 'startDate') {
            $form.find('[name="endDate"]').attr('min', String($(this).val() || todayIsoDate()));
        }

        if ($form.find('.is-invalid').length) {
            showErrors($form, validateLeaveRequest(valuesOf($form)));
        }
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        const result = fileLeaveRequest(valuesOf($form));

        if (!result.ok) {
            showErrors($form, result.errors ?? {});

            if (result.error) {
                showToast(result.error, { isError: true });
            } else {
                $form.find('.is-invalid').first().trigger('focus');
            }

            return;
        }

        Modal.getOrCreateInstance($modal[0]).hide();
        showToast('Leave request filed.');
    });
}

$(function () {
    const $modal = $('[data-leave-request-modal]');

    if ($modal.length) {
        initLeaveRequest($modal);
    }
});
