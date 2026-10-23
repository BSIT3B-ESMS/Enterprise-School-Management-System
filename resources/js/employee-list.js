// The Employees list's search, filters, and sort, shared by the Employees table and the Generate Report
// page so both always show the same rows in the same order. The state lives in the page URL
// (?q=…&month=…&from=…&to=…&department=…&jobTitle=…&sort=…&dir=…&page=…), which is how pages already pass filters
// to each other, so opening the report, returning, and browser Back all keep it.
import { formatDate } from './format.js';
import { getEmployeeFormOptions, listEmployees } from './mock-data.js';

export const employeeSortLabels = { id: 'Employee ID', name: 'Name', department: 'College', jobTitle: 'Job Title', salary: 'Base Salary', hiredOn: 'Date Hired' };

const sortValue = {
    id: (employee) => employee.id,
    name: (employee) => employee.name.toLowerCase(),
    department: (employee) => employee.department,
    jobTitle: (employee) => employee.jobTitle,
    salary: (employee) => Number(employee.salary || 0),
    hiredOn: (employee) => employee.hiredOn,
};

// `month` (YYYY-MM) is the period under review; `hiredFrom` and `hiredTo` narrow it to an exact range inside that month
// (either may be empty: the month's first or last day then stands in for it).
const defaultView = { query: '', month: '', hiredFrom: '', hiredTo: '', departmentId: '', jobTitleId: '', sortKey: 'hiredOn', sortDirection: -1, page: 1 };

const isoDatePattern = /^\d{4}-\d{2}-\d{2}$/;
const monthPattern = /^\d{4}-(0[1-9]|1[0-2])$/;
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

/**
 * "2026-10" → "October 2026".
 */
export function monthLabel(month) {
    const [year, number] = month.split('-');

    return `${monthNames[Number(number) - 1]} ${year}`;
}

/**
 * The first and last day of a month ("2026-10" → 2026-10-01 and 2026-10-31).
 */
export function monthBounds(month) {
    const [year, number] = month.split('-').map(Number);

    return { first: `${month}-01`, last: `${month}-${String(new Date(year, number, 0).getDate()).padStart(2, '0')}` };
}

/**
 * Field errors for a Date Hired period (empty when valid): a month is needed for the dates, both dates must fall in that
 * month, and From Date cannot be later than To Date. Nothing chosen is valid and means no date restriction.
 *
 * @returns {{ month?: string, from?: string, to?: string }}
 */
export function validateHiredRange({ month, from, to }) {
    const errors = {};

    if (!month) {
        if (from || to) {
            errors.month = 'Select a month first.';
        }

        return errors;
    }

    if (!monthPattern.test(month)) {
        return { month: 'Enter the month as YYYY-MM, for example 2026-10.' };
    }

    const { first, last } = monthBounds(month);

    [['from', 'From Date', from], ['to', 'To Date', to]].forEach(([key, label, value]) => {
        if (value && (!isoDatePattern.test(value) || value < first || value > last)) {
            errors[key] = `${label} must be a date in ${monthLabel(month)}.`;
        }
    });

    if (!errors.from && !errors.to && from && to && from > to) {
        errors.from = 'From Date cannot be later than To Date.';
    }

    return errors;
}

/**
 * The exact hiring dates a view keeps, or null when it has no Date Hired restriction.
 *
 * @returns {{ from: string, to: string }|null}
 */
export function hiredRange(view) {
    if (!view.month) {
        return null;
    }

    const { first, last } = monthBounds(view.month);

    return { from: view.hiredFrom || first, to: view.hiredTo || last };
}

/**
 * Columns that sort newest or highest first when chosen; the rest sort A to Z.
 */
export function defaultSortDirection(key) {
    return key === 'hiredOn' || key === 'salary' ? -1 : 1;
}

/**
 * The list state from a query string, falling back to the defaults for anything missing or invalid.
 * Also accepts the ?department= and ?jobTitle= links from the Departments and Job Titles pages, and a single ?hiredOn=
 * date from earlier links (read as that day within its month).
 *
 * @returns {{ query: string, month: string, hiredFrom: string, hiredTo: string, departmentId: string, jobTitleId: string, sortKey: string, sortDirection: number, page: number }}
 */
export function readEmployeeView(search) {
    const params = new URLSearchParams(search);
    const sortKey = params.get('sort') ?? '';
    const hiredOn = params.get('hiredOn') ?? '';
    const page = Number.parseInt(params.get('page') ?? '', 10);
    const period = isoDatePattern.test(hiredOn)
        ? { month: hiredOn.slice(0, 7), from: hiredOn, to: hiredOn }
        : { month: params.get('month') ?? '', from: params.get('from') ?? '', to: params.get('to') ?? '' };
    const hasValidPeriod = Boolean(period.month) && Object.keys(validateHiredRange(period)).length === 0;
    const view = {
        ...defaultView,
        query: params.get('q') ?? '',
        month: hasValidPeriod ? period.month : '',
        hiredFrom: hasValidPeriod ? period.from : '',
        hiredTo: hasValidPeriod ? period.to : '',
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

    if (view.month) {
        params.set('month', view.month);

        if (view.hiredFrom) {
            params.set('from', view.hiredFrom);
        }

        if (view.hiredTo) {
            params.set('to', view.hiredTo);
        }
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
 * list's sort order. Date Hired keeps those hired within the month's chosen range (the whole month when no dates are
 * given); with no month there is no date restriction. It looks at the hiring date only, never at employment status.
 */
export function filterAndSortEmployees(view) {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];
    const range = hiredRange(view);

    return listEmployees()
        .filter((employee) => !query || `${employee.name} ${employee.id} ${employee.email}`.toLowerCase().includes(query))
        .filter((employee) => !view.jobTitleId || employee.jobTitleId === view.jobTitleId)
        .filter((employee) => !view.departmentId || employee.departmentId === view.departmentId)
        .filter((employee) => !range || (employee.hiredOn >= range.from && employee.hiredOn <= range.to))
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);

            return (firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0) * view.sortDirection;
        });
}

/**
 * The Date Hired period in words: "October 2026", or "Oct 5, 2026 to Oct 20, 2026" when dates narrow the month.
 */
export function describeHiredRange(view) {
    const range = hiredRange(view);

    if (!range) {
        return '';
    }

    return view.hiredFrom || view.hiredTo ? `${formatDate(range.from)} to ${formatDate(range.to)}` : monthLabel(view.month);
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

    if (view.month) {
        filters.push(`Date hired: ${describeHiredRange(view)}`);
    }

    if (view.departmentId) {
        filters.push(`College: ${nameOf(options.departments, view.departmentId)}`);
    }

    if (view.jobTitleId) {
        filters.push(`Job title: ${nameOf(options.jobTitles, view.jobTitleId)}`);
    }

    return {
        filters,
        sort: `Sorted by ${employeeSortLabels[view.sortKey]}, ${view.sortDirection > 0 ? 'ascending' : 'descending'}`,
    };
}
