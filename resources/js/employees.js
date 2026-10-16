import $ from './jquery.js';
import { formatDate, initialsOf } from './format.js';
import { defaultSortDirection, employeeViewQuery, filterAndSortEmployees, readEmployeeView } from './employee-list.js';
import { getEmployeeFormOptions, onMockDataChange } from './mock-data.js';
import { renderPagination } from './pagination.js';

const pageSize = 8;

// Table view state: search text, Date Hired, filters, sort column and direction (1 ascending,
// -1 descending), and page. Read from the URL, so returning from Generate Report (or pressing
// browser Back) restores it.
const view = readEmployeeView(window.location.search);

/**
 * Employees matching the search and filters, in the current sort order.
 */
function visibleEmployees() {
    return filterAndSortEmployees(view);
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

/**
 * The employee's profile page. The table's query string rides along so Back to Employees can restore it.
 */
function profileUrl(employeeId, query) {
    return `${$('[data-page="employees"]').attr('data-profile-base')}/${encodeURIComponent(employeeId)}${query}`;
}

function rowFor(employee, query) {
    const url = profileUrl(employee.id, query);

    return $('<tr>', {
        class: 'employee-row',
        title: `View ${employee.name}`,
        'data-employee-id': employee.id,
        'data-profile-url': url,
    }).append(
        $('<td>', { class: 'tabular-nums', text: employee.id }),
        $('<td>').append(
            $('<div>', { class: 'd-flex align-items-center gap-2 fw-medium' }).append(
                $('<span>', { class: 'ems-avatar-sm', 'aria-hidden': 'true', text: initialsOf(employee.name) }),
                $('<a>', { class: 'text-reset text-decoration-none', href: url, text: employee.name }),
            ),
        ),
        $('<td>', { text: employee.department }),
        $('<td>', { text: employee.jobTitle }),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatDate(employee.hiredOn) }),
    );
}

/**
 * A click anywhere on a row opens that employee's profile; the name inside it is a real link, so
 * keyboard users and open-in-new-tab work too.
 */
function initRowLinks($root) {
    $root.on('click', 'tr[data-employee-id]', function (event) {
        if ($(event.target).closest('a, button, input, select, textarea, label').length) {
            return;
        }

        window.location.assign($(this).attr('data-profile-url'));
    });
}

function renderTable($root) {
    const employees = visibleEmployees();
    const pageCount = Math.max(1, Math.ceil(employees.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = employees.slice(start, start + pageSize);
    const query = employeeViewQuery(view);

    $root.find('[data-employee-rows]').empty().append(...pageRows.map((employee) => rowFor(employee, query)));

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
    syncUrl($root);
}

/**
 * Keeps the URL (and the Generate Report link) in step with the table, without adding history
 * entries, so Back from the report or a reload shows the same search, filters, sort, and page.
 */
function syncUrl($root) {
    const query = employeeViewQuery(view);

    window.history.replaceState(window.history.state, '', `${window.location.pathname}${query}`);
    $root.find('[data-employee-report-link]').attr('href', `${$root.find('[data-employee-report-link]').attr('data-report-url')}${query}`);
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
        renderTable($employees);
    });

    // Clears the search, the date, and every filter (including any linked from another page) and
    // returns to page 1; the sort order is kept.
    $employees.on('click', '[data-employee-clear]', function () {
        Object.assign(view, { query: '', hiredOn: '', jobTitleId: '', departmentId: '', page: 1 });
        $employees.find('[data-employee-search], [data-employee-filter]').val('');
        renderTable($employees);
    });

    $employees.on('click', '[data-sort-key]', function () {
        const key = $(this).attr('data-sort-key');

        if (view.sortKey === key) {
            view.sortDirection *= -1;
        } else {
            view.sortKey = key;
            view.sortDirection = defaultSortDirection(key);
        }

        view.page = 1;
        renderTable($employees);
    });

    $employees.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable($employees);
    });

    initRowLinks($employees);

    // Show the restored search and date in their inputs (the selects are filled below).
    $employees.find('[data-employee-search]').val(view.query);
    $employees.find('[data-employee-filter="hiredOn"]').val(view.hiredOn);

    renderFilterOptions($employees);
    renderTable($employees);
    onMockDataChange(() => {
        renderFilterOptions($employees);
        renderTable($employees);
    });
});
