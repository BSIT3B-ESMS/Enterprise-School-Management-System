import $ from './jquery.js';
import { archiveEmployee, findEmployee } from './mock-data.js';
import { queueToast, showToast } from './toast.js';

// Archive confirmation, opened from the profile's Archive button (data-employee-archive="EMP-…"). The employee's
// information is kept and they leave the Employees list, so it returns there afterwards.
function initArchiveModal($modal) {
    let archivingId = null;

    $modal.on('show.bs.modal', function (event) {
        const employee = findEmployee($(event.relatedTarget).attr('data-employee-archive'));

        if (!employee) {
            event.preventDefault();

            return;
        }

        archivingId = employee.id;
        $modal.find('[data-employee-archive-message]')
            .text(`Archive ${employee.name} (${employee.id})? Their information is kept, and you can restore them from Archived Employees.`);
    });

    $modal.on('click', '[data-employee-archive-confirm]', function () {
        const employee = archiveEmployee(archivingId);

        if (!employee) {
            showToast('That employee no longer exists.', { isError: true });

            return;
        }

        queueToast(`Employee archived: ${employee.name} (${employee.id}).`);
        window.location.assign($('[data-employee-back-link]').attr('href'));
    });
}

$(function () {
    const $archiveModal = $('[data-employee-archive-modal]');

    if ($archiveModal.length) {
        initArchiveModal($archiveModal);
    }
});
