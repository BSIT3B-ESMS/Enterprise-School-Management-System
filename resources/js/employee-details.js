import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { formatDate, formatDateTime, formatPeso } from './format.js';
import { deleteEmployee, findEmployee, getEmployeeHistory } from './mock-data.js';
import { showToast } from './toast.js';

function displayValues(employee) {
    return {
        id: employee.id,
        name: employee.name,
        jobTitle: employee.jobTitle,
        department: employee.department,
        hiredOn: formatDate(employee.hiredOn),
        salary: formatPeso(Number(employee.salary)),
        email: employee.email,
        phone: employee.phone,
        birthdate: employee.birthdate ? formatDate(employee.birthdate) : '',
        sex: employee.sex,
        civilStatus: employee.civilStatus,
        address: employee.address,
        benefits: (employee.benefits ?? []).join(', '),
    };
}

function renderHistory($modal, history) {
    const $section = $modal.find('[data-employee-history-section]');

    if (!history.length) {
        $section.addClass('d-none');

        return;
    }

    $section.removeClass('d-none');
    $modal.find('[data-employee-history]').empty().append(...history.map((entry) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { text: entry.detail }),
                $('<small>', { class: 'text-body-secondary', text: `by ${entry.user}` }),
            ),
            $('<small>', { class: 'text-body-secondary text-nowrap', text: formatDateTime(entry.at) }),
        )
    )));
}

function initDetailsModal($modal) {
    // Opened through data-bs-toggle="modal" on any element carrying data-employee-id.
    $modal.on('show.bs.modal', function (event) {
        const employee = findEmployee($(event.relatedTarget).attr('data-employee-id'));

        if (!employee) {
            event.preventDefault();
            showToast('That employee no longer exists.', { isError: true });

            return;
        }

        Object.entries(displayValues(employee)).forEach(([field, value]) => {
            const $field = $modal.find(`[data-employee-field="${field}"]`);

            if (value) {
                $field.text(value).removeClass('text-body-secondary');
            } else {
                $field.text('Not provided').addClass('text-body-secondary');
            }
        });

        $modal.find('[data-employee-edit]').attr('data-employee-edit', employee.id);
        $modal.find('[data-employee-delete]').attr('data-employee-delete', employee.id);
        renderHistory($modal, getEmployeeHistory(employee.id));
    });
}

function initDeleteModal($modal) {
    let deletingId = null;

    $modal.on('show.bs.modal', function (event) {
        const employee = findEmployee($(event.relatedTarget).attr('data-employee-delete'));

        if (!employee) {
            event.preventDefault();

            return;
        }

        deletingId = employee.id;
        $modal.find('[data-employee-delete-message]')
            .text(`Remove ${employee.name} (${employee.id}) from the system? This cannot be undone.`);
    });

    $modal.on('click', '[data-employee-delete-confirm]', function () {
        const employee = deleteEmployee(deletingId);

        Modal.getOrCreateInstance($modal[0]).hide();

        if (employee) {
            showToast(`Employee deleted: ${employee.name} (${employee.id}).`);
        }
    });
}

$(function () {
    const $detailsModal = $('[data-employee-details-modal]');
    const $deleteModal = $('[data-employee-delete-modal]');

    if ($detailsModal.length) {
        initDetailsModal($detailsModal);
    }

    if ($deleteModal.length) {
        initDeleteModal($deleteModal);
    }
});
