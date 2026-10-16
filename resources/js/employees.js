import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { isInDateRange, syncDateRange } from './date-filter.js';
import { formatDate, initialsOf } from './format.js';
import { getEmployeeFormOptions, listEmployees, onMockDataChange } from './mock-data.js';
import { renderPagination } from './pagination.js';
import { showToast } from './toast.js';

const pageSize = 8;

const sortValue = {
    id: (employee) => employee.id,
    name: (employee) => employee.name.toLowerCase(),
    department: (employee) => employee.department,
    jobTitle: (employee) => employee.jobTitle,
    hiredOn: (employee) => employee.hiredOn,
};

// Table view state: search text, filters, sort column and direction (1 ascending, -1 descending), page.
const view = { query: '', jobTitleId: '', departmentId: '', startDate: '', endDate: '', sortKey: 'hiredOn', sortDirection: -1, page: 1 };

/**
 * Employees matching the search and filters, in the current sort order.
 */
function visibleEmployees() {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];

    return listEmployees()
        .filter((employee) => !query || `${employee.name} ${employee.id} ${employee.email}`.toLowerCase().includes(query))
        .filter((employee) => !view.jobTitleId || employee.jobTitleId === view.jobTitleId)
        .filter((employee) => !view.departmentId || employee.departmentId === view.departmentId)
        .filter((employee) => isInDateRange(employee.hiredOn, view.startDate, view.endDate))
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);

            return (firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0) * view.sortDirection;
        });
}

function renderFilterOptions($root) {
    const options = getEmployeeFormOptions();
    const fill = ($select, placeholder, items, selected) => {
        $select.empty().append(
            $('<option>', { value: '', text: placeholder }),
            ...items.map((item) => $('<option>', { value: item.id, text: item.name })),
        ).val(selected);
    };

    // A department or job title deleted elsewhere drops out of the options, so fall back to all.
    if (!options.departments.some((department) => department.id === view.departmentId)) {
        view.departmentId = '';
    }

    if (!options.jobTitles.some((jobTitle) => jobTitle.id === view.jobTitleId)) {
        view.jobTitleId = '';
    }

    fill($root.find('[data-employee-filter="jobTitleId"]'), 'All Job Titles', options.jobTitles, view.jobTitleId);
    fill($root.find('[data-employee-filter="departmentId"]'), 'All Departments', options.departments, view.departmentId);
}

function renderSortHeaders($root) {
    $root.find('[data-sort-column]').each(function () {
        const isSorted = $(this).attr('data-sort-column') === view.sortKey;
        const direction = view.sortDirection > 0 ? 'ascending' : 'descending';

        $(this).attr('aria-sort', isSorted ? direction : 'none');
    });
}

function rowFor(employee) {
    return $('<tr>', {
        class: 'employee-row',
        tabindex: 0,
        'aria-haspopup': 'dialog',
        title: `View ${employee.name}`,
        'data-employee-id': employee.id,
    }).append(
        $('<td>', { class: 'tabular-nums', text: employee.id }),
        $('<td>').append(
            $('<div>', { class: 'd-flex align-items-center gap-2 fw-medium' }).append(
                $('<span>', { class: 'ems-avatar-sm', 'aria-hidden': 'true', text: initialsOf(employee.name) }),
                document.createTextNode(employee.name),
            ),
        ),
        $('<td>', { text: employee.department }),
        $('<td>', { text: employee.jobTitle }),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatDate(employee.hiredOn) }),
    );
}

/**
 * Row clicks and Enter/Space open the details modal; clicks on controls inside a row are left alone.
 */
function initRowDetails($root) {
    const $detailsModal = $('[data-employee-details-modal]');
    const interactiveSelector = 'a, button, input, select, textarea, label, [data-bs-toggle]';
    let lastOpenedId = null;

    function openDetails(row) {
        lastOpenedId = $(row).attr('data-employee-id');
        Modal.getOrCreateInstance($detailsModal[0]).show(row);
    }

    $root.on('click', 'tr[data-employee-id]', function (event) {
        if ($(event.target).closest(interactiveSelector).length) {
            return;
        }

        openDetails(this);
    });

    $root.on('keydown', 'tr[data-employee-id]', function (event) {
        if (event.target !== this || (event.key !== 'Enter' && event.key !== ' ')) {
            return;
        }

        event.preventDefault();
        openDetails(this);
    });

    // Return focus to the row once no modal is open (edit and delete open from the details modal).
    $(document).on('hidden.bs.modal', '.modal', function () {
        if (lastOpenedId && !$('.modal.show').length) {
            $root.find(`tr[data-employee-id="${lastOpenedId}"]`).trigger('focus');
            lastOpenedId = null;
        }
    });
}

function renderTable($root) {
    const employees = visibleEmployees();
    const pageCount = Math.max(1, Math.ceil(employees.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = employees.slice(start, start + pageSize);

    $root.find('[data-employee-rows]').empty().append(...pageRows.map(rowFor));

    if (pageRows.length) {
        $root.find('[data-employee-empty]').addClass('d-none');
    } else {
        $root.find('[data-employee-empty]').removeClass('d-none');
    }

    $root.find('[data-employee-summary]').text(
        `Showing ${employees.length ? start + 1 : 0} to ${start + pageRows.length} of ${employees.length} employees`,
    );
    renderSortHeaders($root);
    renderPagination($root.find('[data-employee-pagination]'), view.page, pageCount);
}

function toCsv(employees) {
    const header = ['Employee ID', 'Name', 'Department', 'Job Title', 'Date Hired', 'Email', 'Base Salary'];
    const rows = employees.map((employee) => [
        employee.id, employee.name, employee.department, employee.jobTitle, employee.hiredOn, employee.email, employee.salary,
    ]);

    return [header, ...rows]
        .map((row) => row.map((cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(','))
        .join('\n');
}

function copyCsv() {
    const employees = visibleEmployees();

    if (!navigator.clipboard?.writeText) {
        showToast('Copy is not available in this browser.', { isError: true });

        return;
    }

    navigator.clipboard.writeText(toCsv(employees)).then(
        () => showToast(`Copied ${employees.length} rows as CSV.`),
        () => showToast('Copy was blocked by the browser.', { isError: true }),
    );
}

$(function () {
    const $employees = $('[data-page="employees"]');

    if (!$employees.length) {
        return;
    }

    $employees.on('input', '[data-employee-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderTable($employees);
    });

    $employees.on('change', '[data-employee-filter]', function () {
        view[$(this).attr('data-employee-filter')] = String($(this).val());
        view.page = 1;
        syncDateRange($employees);
        renderTable($employees);
    });

    // Clears the search, every filter (including any linked from another page), and returns to page 1.
    $employees.on('click', '[data-employee-clear]', function () {
        Object.assign(view, { query: '', jobTitleId: '', departmentId: '', startDate: '', endDate: '', page: 1 });
        $employees.find('[data-employee-search], [data-employee-filter]').val('');
        window.history.replaceState(null, '', window.location.pathname);
        syncDateRange($employees);
        renderTable($employees);
    });

    $employees.on('click', '[data-sort-key]', function () {
        const key = $(this).attr('data-sort-key');

        if (view.sortKey === key) {
            view.sortDirection *= -1;
        } else {
            view.sortKey = key;
            view.sortDirection = key === 'hiredOn' ? -1 : 1;
        }

        view.page = 1;
        renderTable($employees);
    });

    $employees.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable($employees);
    });

    $employees.on('click', '[data-copy-csv]', copyCsv);
    initRowDetails($employees);

    // The Departments and Job Titles pages link here with ?department=<id> or ?jobTitle=<id>.
    const linkedFilters = new URLSearchParams(window.location.search);

    view.departmentId = linkedFilters.get('department') ?? '';
    view.jobTitleId = linkedFilters.get('jobTitle') ?? '';

    renderFilterOptions($employees);
    renderTable($employees);
    onMockDataChange(() => {
        renderFilterOptions($employees);
        renderTable($employees);
    });
});
