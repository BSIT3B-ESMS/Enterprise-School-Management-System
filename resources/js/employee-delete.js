import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { deleteArchivedEmployee, listArchivedEmployees } from './mock-data.js';
import { showToast } from './toast.js';

// Permanent delete confirmation, opened from an Archived Employees row (data-employee-delete="EMP-…"). Only archived
// employees can be deleted. The list updates itself afterwards, so the page stays where it is.
function initDeleteModal($modal) {
    let deletingId = null;

    $modal.on('show.bs.modal', function (event) {
        const employee = listArchivedEmployees().find((candidate) => candidate.id === $(event.relatedTarget).attr('data-employee-delete'));

        if (!employee) {
            event.preventDefault();

            return;
        }

        deletingId = employee.id;
        $modal.find('[data-employee-delete-message]')
            .text(`Permanently delete ${employee.name} (${employee.id})? Their record is removed from the system and this cannot be undone.`);
    });

    $modal.on('click', '[data-employee-delete-confirm]', function () {
        const employee = deleteArchivedEmployee(deletingId);

        Modal.getOrCreateInstance($modal[0]).hide();

        if (!employee) {
            showToast('That employee is no longer in the archive.', { isError: true });

            return;
        }

        showToast(`Employee deleted: ${employee.name} (${employee.id}).`);
    });
}

$(function () {
    const $deleteModal = $('[data-employee-delete-modal]');

    if ($deleteModal.length) {
        initDeleteModal($deleteModal);
    }
});
