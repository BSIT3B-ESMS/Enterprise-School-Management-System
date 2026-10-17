import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { formatDate, formatPeso, initialsOf } from './format.js';
import {
    addDepartment,
    deleteDepartment,
    findDepartment,
    isDepartmentNameTaken,
    listDepartments,
    onMockDataChange,
    updateDepartment,
} from './mock-data.js';
import { renderPagination } from './pagination.js';
import { showToast } from './toast.js';

const pageSize = 8;

const sortValue = {
    name: (department) => department.name.toLowerCase(),
    headcount: (department) => department.headcount,
};

// Table view state: search text, sort column and direction (1 ascending, -1 descending), page.
const view = { query: '', sortKey: 'name', sortDirection: 1, page: 1 };

function plural(count, noun) {
    return `${count} ${noun}${count === 1 ? '' : 's'}`;
}

/**
 * Departments matching the search, in the current sort order (ties fall back to the name).
 */
function visibleDepartments() {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];

    return listDepartments()
        .filter((department) => !query || department.name.toLowerCase().includes(query))
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);
            const order = firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0;

            return order * view.sortDirection || first.name.localeCompare(second.name);
        });
}

/**
 * Why a department cannot be deleted yet, or '' when nothing is assigned to it.
 */
function deleteBlockedMessage({ name, headcount, jobTitleCount, applicationCount }) {
    const assigned = [
        headcount ? plural(headcount, 'employee') : '',
        jobTitleCount ? plural(jobTitleCount, 'job title') : '',
        applicationCount ? plural(applicationCount, 'application') : '',
    ].filter(Boolean);
    const listed = assigned.length > 1 ? `${assigned.slice(0, -1).join(', ')} and ${assigned.at(-1)}` : assigned[0];

    return assigned.length ? `Reassign the ${listed} in ${name} first.` : '';
}

function rowFor(department) {
    return $('<tr>', {
        class: 'employee-row',
        tabindex: 0,
        'aria-haspopup': 'dialog',
        title: `View ${department.name}`,
        'data-department-row': department.id,
    }).append(
        $('<td>', { class: 'fw-medium', text: department.name }),
        $('<td>', { class: 'tabular-nums', text: department.headcount }),
    );
}

/**
 * Row clicks and Enter/Space open the details modal; clicks on controls inside a row are left alone.
 * Rows are re-rendered after every change, so focus returns to the row (found again by its ID) once
 * no modal is open.
 */
function initRowDetails($root) {
    const $detailsModal = $('[data-department-details-modal]');
    const interactiveSelector = 'a, button, input, select, textarea, label, [data-bs-toggle]';
    let lastOpenedId = null;

    function openDetails(row) {
        // Ignore a repeat click or keypress while a modal is already open.
        if ($('.modal.show').length) {
            return;
        }

        lastOpenedId = $(row).attr('data-department-row');
        Modal.getOrCreateInstance($detailsModal[0]).show(row);
    }

    $root.on('click', 'tr[data-department-row]', function (event) {
        if ($(event.target).closest(interactiveSelector).length) {
            return;
        }

        openDetails(this);
    });

    $root.on('keydown', 'tr[data-department-row]', function (event) {
        if (event.target !== this || (event.key !== 'Enter' && event.key !== ' ')) {
            return;
        }

        event.preventDefault();
        openDetails(this);
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        if (!lastOpenedId || $('.modal.show').length) {
            return;
        }

        const $row = $root.find(`tr[data-department-row="${lastOpenedId}"]`);

        ($row.length ? $row : $root.find('[data-department-add]')).trigger('focus');
        lastOpenedId = null;
    });

    $root.on('click', '[data-department-add]', () => {
        lastOpenedId = null;
    });
}

function renderSortHeaders($root) {
    $root.find('[data-sort-column]').each(function () {
        const isSorted = $(this).attr('data-sort-column') === view.sortKey;
        const direction = view.sortDirection > 0 ? 'ascending' : 'descending';

        $(this).attr('aria-sort', isSorted ? direction : 'none');
    });
}

function renderTable($root) {
    const departments = visibleDepartments();
    const pageCount = Math.max(1, Math.ceil(departments.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = departments.slice(start, start + pageSize);

    $root.find('[data-department-rows]').empty().append(...pageRows.map(rowFor));

    if (pageRows.length) {
        $root.find('[data-department-empty]').addClass('d-none');
    } else {
        $root.find('[data-department-empty]').removeClass('d-none');
        $root.find('[data-department-empty-hint]').text(
            view.query.trim() ? 'Try a different search.' : 'Add a department to get started.',
        );
    }

    $root.find('[data-department-summary]').text(
        `Showing ${departments.length ? start + 1 : 0} to ${start + pageRows.length} of ${departments.length} departments`,
    );
    renderSortHeaders($root);
    renderPagination($root.find('[data-department-pagination]'), view.page, pageCount);
}

/**
 * Moves the table to the page that holds the department, if it matches the current search.
 */
function showPageWith(departmentId) {
    const index = visibleDepartments().findIndex((department) => department.id === departmentId);

    if (index !== -1) {
        view.page = Math.floor(index / pageSize) + 1;
    }
}

/**
 * The details modal's Delete button stops here, before Bootstrap closes the details and opens the
 * confirmation, while the department still has employees, job titles, or applications.
 */
function guardDeleteTrigger($modal) {
    $modal.on('click', '[data-department-delete]', function (event) {
        const department = findDepartment($(this).attr('data-department-delete'));
        const message = department ? deleteBlockedMessage(department) : '';

        if (message) {
            event.preventDefault();
            event.stopPropagation();
            showToast(message, { isError: true });
        }
    });
}

function listItem(primary, secondary, aside) {
    return $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
        $('<div>', { class: 'min-w-0' }).append(primary, secondary),
        $('<small>', { class: 'text-body-secondary text-nowrap tabular-nums', text: aside }),
    );
}

function emptyItem(text) {
    return $('<li>', { class: 'list-group-item text-body-secondary', text });
}

function initDetailsModal($modal, employeesUrl) {
    $modal.on('show.bs.modal', function (event) {
        const department = findDepartment($(event.relatedTarget).attr('data-department-row'));

        if (!department) {
            event.preventDefault();
            showToast('That department no longer exists.', { isError: true });

            return;
        }

        $modal.find('[data-department-field="name"]').text(department.name);
        $modal.find('[data-department-field="headcount"]').text(department.headcount);
        $modal.find('[data-department-field="jobTitleCount"]').text(department.jobTitleCount);

        $modal.find('[data-department-job-titles]').empty().append(
            ...(department.jobTitles.length
                ? department.jobTitles.map((jobTitle) => listItem(
                    $('<div>', { text: jobTitle.name }),
                    $('<small>', { class: 'text-body-secondary', text: 'Base salary' }),
                    formatPeso(jobTitle.salary),
                ))
                : [emptyItem('No job titles under this department yet.')]),
        );

        $modal.find('[data-department-faculty]').empty().append(
            ...(department.recentFaculty.length
                ? department.recentFaculty.map((employee) => listItem(
                    $('<div>', { class: 'd-flex align-items-center gap-2' }).append(
                        $('<span>', { class: 'ems-avatar-sm', 'aria-hidden': 'true', text: initialsOf(employee.name) }),
                        document.createTextNode(employee.name),
                    ),
                    $('<small>', { class: 'text-body-secondary', text: employee.jobTitle }),
                    `Hired ${formatDate(employee.hiredOn)}`,
                ))
                : [emptyItem('No faculty assigned yet.')]),
        );

        const $employeesLink = $modal.find('[data-department-employees-link]')
            .attr('href', `${employeesUrl}?department=${encodeURIComponent(department.id)}`)
            .text(`View all ${plural(department.headcount, 'faculty member')} in Employees`);

        if (department.headcount) {
            $employeesLink.removeClass('d-none');
        } else {
            $employeesLink.addClass('d-none');
        }

        $modal.find('[data-department-edit]').attr('data-department-edit', department.id);
        $modal.find('[data-department-delete]').attr('data-department-delete', department.id);
    });

    guardDeleteTrigger($modal);
}

function setNameError($field, message) {
    $('#department-name-error').text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

function nameError(name, editingId) {
    if (!name.trim()) {
        return 'Enter a department name.';
    }

    return isDepartmentNameTaken(name, editingId) ? 'A department with this name already exists.' : '';
}

function initFormModal($modal, $root) {
    const $form = $modal.find('[data-department-form]');
    const $name = $form.find('[name="name"]');
    let editingId = null;

    $modal.on('show.bs.modal', function (event) {
        const requestedId = $(event.relatedTarget).attr('data-department-edit');
        const department = requestedId ? findDepartment(requestedId) : null;

        if (requestedId && !department) {
            event.preventDefault();
            showToast('That department no longer exists.', { isError: true });

            return;
        }

        editingId = department?.id ?? null;
        $form[0].reset();
        setNameError($name, '');
        $name.val(department?.name ?? '');
        $modal.find('[data-department-form-title]').text(department ? 'Edit Department' : 'Add Department');
    });
    $modal.on('shown.bs.modal', () => $name.trigger('focus').trigger('select'));

    $name.on('input', function () {
        if ($name.hasClass('is-invalid')) {
            setNameError($name, nameError(String($name.val()), editingId));
        }
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        // A second Enter or click while the modal is closing must not save again.
        if (!$modal.hasClass('show')) {
            return;
        }

        const name = String($name.val()).trim();
        const message = nameError(name, editingId);

        setNameError($name, message);

        if (message) {
            $name.trigger('focus');

            return;
        }

        if (editingId) {
            updateDepartment(editingId, name);
            showToast('Department updated.');
        } else {
            const department = addDepartment(name);

            showPageWith(department.id);
            renderTable($root);
            showToast(`Department added: ${department.name}.`);
        }

        Modal.getOrCreateInstance($modal[0]).hide();
    });
}

function initDeleteModal($modal) {
    let deletingId = null;

    $modal.on('show.bs.modal', function (event) {
        const department = findDepartment($(event.relatedTarget).attr('data-department-delete'));

        if (!department || deleteBlockedMessage(department)) {
            event.preventDefault();

            return;
        }

        deletingId = department.id;
        $modal.find('[data-department-delete-message]').text(`Delete ${department.name}? This cannot be undone.`);
    });

    $modal.on('click', '[data-department-delete-confirm]', function () {
        // A second click while the modal is closing must not delete again.
        if (!deletingId) {
            return;
        }

        const { department, ...usage } = deleteDepartment(deletingId);

        deletingId = null;

        Modal.getOrCreateInstance($modal[0]).hide();

        if (department) {
            showToast(`Department deleted: ${department.name}.`);
        } else {
            showToast(deleteBlockedMessage({ name: 'this department', ...usage }) || 'That department no longer exists.', {
                isError: true,
            });
        }
    });
}

$(function () {
    const $departments = $('[data-page="departments"]');

    if (!$departments.length) {
        return;
    }

    $departments.on('input', '[data-department-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderTable($departments);
    });

    $departments.on('click', '[data-sort-key]', function () {
        const key = $(this).attr('data-sort-key');

        if (view.sortKey === key) {
            view.sortDirection *= -1;
        } else {
            view.sortKey = key;
            view.sortDirection = key === 'headcount' ? -1 : 1;
        }

        view.page = 1;
        renderTable($departments);
    });

    $departments.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable($departments);
    });

    initRowDetails($departments);
    initDetailsModal($('[data-department-details-modal]'), $departments.attr('data-employees-url'));
    initFormModal($('[data-department-form-modal]'), $departments);
    initDeleteModal($('[data-department-delete-modal]'));

    renderTable($departments);
    onMockDataChange(() => renderTable($departments));
});
