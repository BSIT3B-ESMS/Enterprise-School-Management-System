// The Employees list's search, filters, and sort, shared by the Employees table and the Generate Report
// page so both always show the same rows in the same order. The state lives in the page URL
// (?q=…&hiredOn=…&department=…&jobTitle=…&sort=…&dir=…&page=…), which is how pages already pass filters
// to each other, so opening the report, returning, and browser Back all keep it.
import { formatDate } from './format.js';
import { getEmployeeFormOptions, listEmployees } from './mock-data.js';

export const employeeSortLabels = { id: 'Employee ID', name: 'Name', department: 'Department', jobTitle: 'Job Title', hiredOn: 'Date Hired' };

const sortValue = {
    id: (employee) => employee.id,
    name: (employee) => employee.name.toLowerCase(),
    department: (employee) => employee.department,
    jobTitle: (employee) => employee.jobTitle,
    hiredOn: (employee) => employee.hiredOn,
};

const defaultView = { query: '', hiredOn: '', departmentId: '', jobTitleId: '', sortKey: 'hiredOn', sortDirection: -1, page: 1 };

const isoDatePattern = /^\d{4}-\d{2}-\d{2}$/;

/**
 * Columns that sort newest or highest first when chosen; the rest sort A to Z.
 */
export function defaultSortDirection(key) {
    return key === 'hiredOn' ? -1 : 1;
}

/**
 * The list state from a query string, falling back to the defaults for anything missing or invalid.
 * Also accepts the ?department= and ?jobTitle= links from the Departments and Job Titles pages.
 *
 * @returns {{ query: string, hiredOn: string, departmentId: string, jobTitleId: string, sortKey: string, sortDirection: number, page: number }}
 */
export function readEmployeeView(search) {
    const params = new URLSearchParams(search);
    const sortKey = params.get('sort') ?? '';
    const hiredOn = params.get('hiredOn') ?? '';
    const page = Number.parseInt(params.get('page') ?? '', 10);
    const view = {
        ...defaultView,
        query: params.get('q') ?? '',
        hiredOn: isoDatePattern.test(hiredOn) ? hiredOn : '',
        departmentId: params.get('department') ?? '',
        jobTitleId: params.get('jobTitle') ?? '',
        page: page >= 1 ? page : 1,
    };

    if (sortValue[sortKey]) {
        view.sortKey = sortKey;
        view.sortDirection = params.get('dir') === 'asc' ? 1 : params.get('dir') === 'desc' ? -1 : defaultSortDirection(sortKey);
    }

    return view;
}

/**
 * The query string ("?…", or "" when everything is at its default) for a list state.
 */
export function employeeViewQuery(view) {
    const params = new URLSearchParams();

    if (view.query) {
        params.set('q', view.query);
    }

    if (view.hiredOn) {
        params.set('hiredOn', view.hiredOn);
    }

    if (view.departmentId) {
        params.set('department', view.departmentId);
    }

    if (view.jobTitleId) {
        params.set('jobTitle', view.jobTitleId);
    }

    if (view.sortKey !== defaultView.sortKey || view.sortDirection !== defaultView.sortDirection) {
        params.set('sort', view.sortKey);
        params.set('dir', view.sortDirection > 0 ? 'asc' : 'desc');
    }

    if (view.page > 1) {
        params.set('page', String(view.page));
    }

    const query = params.toString();

    return query ? `?${query}` : '';
}

/**
 * Employees matching every active criterion (search, Date Hired, job title, department), in the
 * list's sort order. The date matches one exact day; with no date there is no date restriction.
 */
export function filterAndSortEmployees(view) {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];

    return listEmployees()
        .filter((employee) => !query || `${employee.name} ${employee.id} ${employee.email}`.toLowerCase().includes(query))
        .filter((employee) => !view.jobTitleId || employee.jobTitleId === view.jobTitleId)
        .filter((employee) => !view.departmentId || employee.departmentId === view.departmentId)
        .filter((employee) => !view.hiredOn || employee.hiredOn === view.hiredOn)
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);

            return (firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0) * view.sortDirection;
        });
}

/**
 * The active search, filters, and sort in words, for the report page and the printed report.
 *
 * @returns {{ filters: string[], sort: string }}
 */
export function describeEmployeeView(view) {
    const options = getEmployeeFormOptions();
    const nameOf = (items, id) => items.find((item) => item.id === id)?.name ?? id;
    const filters = [];

    if (view.query.trim()) {
        filters.push(`Search: “${view.query.trim()}”`);
    }

    if (view.hiredOn) {
        filters.push(`Date hired: ${formatDate(view.hiredOn)}`);
    }

    if (view.departmentId) {
        filters.push(`Department: ${nameOf(options.departments, view.departmentId)}`);
    }

    if (view.jobTitleId) {
        filters.push(`Job title: ${nameOf(options.jobTitles, view.jobTitleId)}`);
    }

    return {
        filters,
        sort: `Sorted by ${employeeSortLabels[view.sortKey]}, ${view.sortDirection > 0 ? 'ascending' : 'descending'}`,
    };
}
