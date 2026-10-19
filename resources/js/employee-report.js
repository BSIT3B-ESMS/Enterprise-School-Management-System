import $ from './jquery.js';
import { formatDate, formatPesoAmount } from './format.js';
import { describeEmployeeView, employeeViewQuery, filterAndSortEmployees, readEmployeeView } from './employee-list.js';
import { getEmployeeFormOptions, onMockDataChange } from './mock-data.js';
import { initReportGenerator } from './report-generator.js';

const blankIfEmpty = (value) => value ?? '';
const dateOrBlank = (isoDate) => (isoDate ? formatDate(isoDate) : '');

// Government contribution enrollment is read from the employee's benefits.
const enrollmentIn = (benefit) => (employee) => ((employee.benefits ?? []).includes(benefit) ? 'Enrolled' : 'Not enrolled');

/**
 * Report fields, grouped and in display order (also the column order). Only fields the employee
 * records carry. `value` is what CSV and Excel export (ISO dates, plain numbers); `display` is what
 * the preview and printed report show.
 *
 * @type {Array<{ group: string, fields: Array<{ key: string, label: string, value: Function, display?: Function }> }>}
 */
const fieldGroups = [
    {
        group: 'Personal Information',
        fields: [
            { key: 'id', label: 'Employee ID', value: (employee) => employee.id },
            { key: 'name', label: 'Full Name', value: (employee) => employee.name },
            { key: 'birthdate', label: 'Birthdate', value: (employee) => blankIfEmpty(employee.birthdate), display: (employee) => dateOrBlank(employee.birthdate) },
            { key: 'sex', label: 'Sex', value: (employee) => blankIfEmpty(employee.sex) },
            { key: 'civilStatus', label: 'Civil Status', value: (employee) => blankIfEmpty(employee.civilStatus) },
        ],
    },
    {
        group: 'Employment Information',
        fields: [
            { key: 'department', label: 'Department', value: (employee) => employee.department },
            { key: 'jobTitle', label: 'Job Title', value: (employee) => employee.jobTitle },
            { key: 'hiredOn', label: 'Date Hired', value: (employee) => blankIfEmpty(employee.hiredOn), display: (employee) => dateOrBlank(employee.hiredOn) },
            { key: 'status', label: 'Employment Status', value: (employee) => blankIfEmpty(employee.status) },
        ],
    },
    {
        group: 'Contact Details',
        fields: [
            { key: 'email', label: 'Email', value: (employee) => blankIfEmpty(employee.email) },
            { key: 'phone', label: 'Phone', value: (employee) => blankIfEmpty(employee.phone) },
            { key: 'address', label: 'Address', value: (employee) => blankIfEmpty(employee.address) },
        ],
    },
    {
        group: 'Government and Tax Information',
        fields: [
            { key: 'sssNumber', label: 'SSS Number', value: (employee) => blankIfEmpty(employee.sssNumber) },
            { key: 'philhealthNumber', label: 'PhilHealth Number', value: (employee) => blankIfEmpty(employee.philhealthNumber) },
            { key: 'pagibigNumber', label: 'Pag-IBIG (HDMF) Number', value: (employee) => blankIfEmpty(employee.pagibigNumber) },
            { key: 'tin', label: 'TIN', value: (employee) => blankIfEmpty(employee.tin) },
            { key: 'birNumber', label: 'BIR Number', value: (employee) => blankIfEmpty(employee.birNumber) },
            { key: 'sss', label: 'SSS Enrollment', value: enrollmentIn('SSS') },
            { key: 'philhealth', label: 'PhilHealth Enrollment', value: enrollmentIn('PhilHealth') },
            { key: 'pagibig', label: 'Pag-IBIG Enrollment', value: enrollmentIn('Pag-IBIG') },
        ],
    },
    {
        group: 'Compensation and Benefits',
        fields: [
            {
                key: 'salary',
                label: 'Base Salary (monthly)',
                value: (employee) => (employee.salary === '' || employee.salary === undefined ? '' : Number(employee.salary)),
                display: (employee) => (employee.salary === '' || employee.salary === undefined ? '' : formatPesoAmount(Number(employee.salary))),
            },
            { key: 'benefits', label: 'Benefits', value: (employee) => (employee.benefits ?? []).join('; ') },
        ],
    },
];

// Selected when the page opens: the columns the Employees table shows.
const defaultFieldKeys = ['id', 'name', 'department', 'jobTitle', 'hiredOn'];

/**
 * The Generate Report page. Field selections live only on this page; the rows always come from the
 * Employees list state in the URL.
 */
function initReportPage($root) {
    const view = readEmployeeView(window.location.search);
    // Back returns to the Employees page with the filters it came with; the report's own Department and Job Title choices
    // below change only this report.
    const employeesQuery = employeeViewQuery(view);
    const report = initReportGenerator($root, {
        title: 'Employee Report',
        fileStem: 'employees-report',
        sheetName: 'Employees',
        noun: ['employee', 'employees'],
        countText: (count) => `${count} ${count === 1 ? 'employee matches' : 'employees match'} the current search and filters.`,
        noFiltersText: 'No filters applied: all employees',
        fieldGroups,
        defaultFieldKeys,
        rows: () => filterAndSortEmployees(view),
        summary: () => describeEmployeeView(view),
    });

    // Back returns to the Employees page with its search, filters, sort, and page as they were.
    $root.find('[data-report-back]').attr('href', `${$root.attr('data-employees-url')}${employeesQuery}`);

    // Department and Job Title for this report: all of them, or the one chosen (they combine). A choice that no longer
    // exists falls back to all.
    function renderFilterOptions() {
        const options = getEmployeeFormOptions();
        const fill = (key, placeholder, items) => {
            if (!items.some((item) => item.id === view[key])) {
                view[key] = '';
            }

            $root.find(`[data-report-filter="${key}"]`).empty().append(
                $('<option>', { value: '', text: placeholder }),
                ...items.map((item) => $('<option>', { value: item.id, text: item.name })),
            ).val(view[key]);
        };

        fill('departmentId', 'All Departments', options.departments);
        fill('jobTitleId', 'All Job Titles', options.jobTitles);
    }

    $root.on('change', '[data-report-filter]', function () {
        view[$(this).attr('data-report-filter')] = String($(this).val());
        report.clearError();
        report.refresh();
    });

    renderFilterOptions();
    report.refresh();

    // Keep the count and preview current if the records change (for example, in another tab).
    onMockDataChange(() => {
        renderFilterOptions();
        report.refresh();
    });
}

$(function () {
    const $report = $('[data-page="employee-report"]');

    if ($report.length) {
        initReportPage($report);
    }
});
