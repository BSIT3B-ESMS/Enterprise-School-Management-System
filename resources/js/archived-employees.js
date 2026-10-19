import $ from './jquery.js';
import { formatDate, initialsOf } from './format.js';
import { localIsoDate } from './date-filter.js';
import { listArchivedEmployees, onMockDataChange, restoreEmployee } from './mock-data.js';
import { renderPagination } from './pagination.js';
import { showToast } from './toast.js';

const pageSize = 8;
const view = { query: '', page: 1 };

function visibleArchived() {
    const query = view.query.trim().toLowerCase();

    return listArchivedEmployees().filter((employee) => (
        !query || `${employee.name} ${employee.id} ${employee.email ?? ''} ${employee.department} ${employee.jobTitle}`.toLowerCase().includes(query)
    ));
}

function rowFor(employee) {
    return $('<tr>', { 'data-archived-id': employee.id }).append(
        $('<td>', { class: 'tabular-nums', text: employee.id }),
        $('<td>').append(
            $('<div>', { class: 'd-flex align-items-center gap-2 fw-medium' }).append(
                $('<span>', { class: 'ems-avatar-sm', 'aria-hidden': 'true', text: initialsOf(employee.name) }),
                $('<span>', { text: employee.name }),
            ),
        ),
        $('<td>', { text: employee.department }),
        $('<td>', { text: employee.jobTitle }),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatDate(employee.hiredOn) }),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatDate(localIsoDate(new Date(employee.archivedAt))) }),
        $('<td>', { class: 'text-end text-nowrap' }).append(
            $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-primary me-2',
                text: 'Restore',
                'aria-label': `Restore ${employee.name}`,
                'data-archived-restore': employee.id,
            }),
            $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-danger',
                text: 'Delete',
                'aria-label': `Delete ${employee.name}`,
                'data-bs-toggle': 'modal',
                'data-bs-target': '#employee-delete-modal',
                'data-employee-delete': employee.id,
            }),
        ),
    );
}

function render($root) {
    const employees = visibleArchived();
    const total = listArchivedEmployees().length;
    const pageCount = Math.max(1, Math.ceil(employees.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = employees.slice(start, start + pageSize);

    $root.find('[data-archived-rows]').empty().append(...pageRows.map(rowFor));
    $root.find('[data-archived-empty]').toggleClass('d-none', pageRows.length > 0);
    $root.find('[data-archived-empty-title]').text(total ? 'No archived employees match' : 'No archived employees');
    $root.find('[data-archived-empty-hint]').text(total ? 'Try a different search.' : 'Employees you archive from their profile appear here.');
    $root.find('[data-archived-summary]').text(
        `Showing ${employees.length ? start + 1 : 0} to ${start + pageRows.length} of ${employees.length} archived ${employees.length === 1 ? 'employee' : 'employees'}`,
    );
    renderPagination($root.find('[data-archived-pagination]'), view.page, pageCount);
}

$(function () {
    const $archived = $('[data-page="archived-employees"]');

    if (!$archived.length) {
        return;
    }

    $archived.on('input', '[data-archived-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        render($archived);
    });

    $archived.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        render($archived);
    });

    // Restore returns the employee to the Employees list with everything they were archived with.
    $archived.on('click', '[data-archived-restore]', function () {
        const employee = restoreEmployee($(this).attr('data-archived-restore'));

        if (!employee) {
            showToast('That employee is no longer in the archive.', { isError: true });

            return;
        }

        showToast(`Employee restored: ${employee.name} (${employee.id}).`);
    });

    render($archived);
    onMockDataChange(() => render($archived));
});
