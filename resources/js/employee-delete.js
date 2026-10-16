import $ from './jquery.js';
import { deleteEmployee, findEmployee } from './mock-data.js';
import { queueToast, showToast } from './toast.js';

// Delete confirmation, opened from the profile's Delete button (data-employee-delete="EMP-…").
// Once deleted there is no profile left to show, so it returns to the Employees list.
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

        if (!employee) {
            showToast('That employee no longer exists.', { isError: true });

            return;
        }

        queueToast(`Employee deleted: ${employee.name} (${employee.id}).`);
        window.location.assign($('[data-employee-back-link]').attr('href'));
    });
}

$(function () {
    const $deleteModal = $('[data-employee-delete-modal]');

    if ($deleteModal.length) {
        initDeleteModal($deleteModal);
    }
});
