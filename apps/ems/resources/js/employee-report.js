import $ from './jquery.js';
import { exportLogger } from './export-log.js';
import { formatDate, formatPesoAmount } from './format.js';
import { initReportGenerator } from './report-generator.js';

const dateOrBlank = (isoDate) => (isoDate ? formatDate(isoDate) : '');

/**
 * Report fields, grouped and in display order (also the column order). `value` is what CSV and Excel export (ISO dates,
 * plain numbers); `display` is what the preview and printed report show. Government IDs are only ever the last four
 * digits: the full numbers are never in a report.
 *
 * @type {Array<{ group: string, fields: Array<{ key: string, label: string, value: Function, display?: Function }> }>}
 */
const fieldGroups = [
    {
        group: 'Personal Information',
        fields: [
            { key: 'id', label: 'Employee ID', value: (employee) => employee.id },
            { key: 'name', label: 'Full Name', value: (employee) => employee.name },
            { key: 'birthdate', label: 'Birthdate', value: (employee) => employee.birthdate, display: (employee) => dateOrBlank(employee.birthdate) },
            { key: 'sex', label: 'Sex', value: (employee) => employee.sex },
            { key: 'maritalStatus', label: 'Marital Status', value: (employee) => employee.maritalStatus },
        ],
    },
    {
        group: 'Employment Information',
        fields: [
            { key: 'college', label: 'College', value: (employee) => employee.college },
            { key: 'office', label: 'Office', value: (employee) => employee.office },
            { key: 'jobTitle', label: 'Job Title', value: (employee) => employee.jobTitle },
            { key: 'employmentType', label: 'Full-time / Part-time', value: (employee) => employee.employmentType },
            { key: 'hiredOn', label: 'Date Hired', value: (employee) => employee.hiredOn, display: (employee) => dateOrBlank(employee.hiredOn) },
            { key: 'status', label: 'Employment Status', value: (employee) => employee.status },
        ],
    },
    {
        group: 'Contact Details',
        fields: [
            { key: 'officialEmail', label: 'Official Email', value: (employee) => employee.officialEmail },
            { key: 'personalEmail', label: 'Personal Email', value: (employee) => employee.personalEmail },
            { key: 'phone', label: 'Mobile Number', value: (employee) => employee.phone },
            { key: 'address', label: 'Home Address', value: (employee) => employee.address },
        ],
    },
    {
        group: 'Government IDs (last four digits only)',
        fields: [
            { key: 'tin', label: 'TIN', value: (employee) => employee.ids.tin },
            { key: 'gsis', label: 'GSIS', value: (employee) => employee.ids.gsis },
            { key: 'sss', label: 'SSS', value: (employee) => employee.ids.sss },
            { key: 'philhealth', label: 'PhilHealth', value: (employee) => employee.ids.philhealth },
            { key: 'pagibig', label: 'Pag-IBIG', value: (employee) => employee.ids.pagibig },
            { key: 'bir', label: 'BIR', value: (employee) => employee.ids.bir },
        ],
    },
    {
        group: 'Compensation',
        fields: [
            {
                key: 'basePay',
                label: 'Base Pay (monthly)',
                value: (employee) => (employee.basePay === null ? '' : employee.basePay),
                display: (employee) => (employee.basePay === null ? '' : formatPesoAmount(employee.basePay)),
            },
        ],
    },
];

// Selected when the page opens.
const defaultFieldKeys = ['id', 'name', 'college', 'jobTitle', 'hiredOn'];

/**
 * The Generate Report page. The server sends the rows that match the Employees page's search and filters; this page
 * narrows them further by college, office, and job title, and builds the file in the browser.
 */
function initReportPage($root) {
    const allRows = JSON.parse($root.attr('data-rows'));
    const summary = JSON.parse($root.attr('data-summary'));
    const choices = { college: '', office: '', jobTitle: '' };
    const report = initReportGenerator($root, {
        title: 'Employee Report',
        fileStem: 'employees-report',
        sheetName: 'Employees',
        noun: ['employee', 'employees'],
        countText: (count) => `${count} ${count === 1 ? 'employee matches' : 'employees match'} the current search and filters.`,
        noFiltersText: 'No filters applied: all employees',
        fieldGroups,
        defaultFieldKeys,
        rows: () => allRows.filter((row) => (
            (!choices.college || String(row.collegeId) === choices.college)
            && (!choices.office || String(row.officeId) === choices.office)
            && (!choices.jobTitle || String(row.jobTitleId) === choices.jobTitle)
        )),
        summary: () => ({
            filters: [
                ...summary.filters,
                ...[['college', 'College'], ['office', 'Office'], ['jobTitle', 'Job title']]
                    .filter(([key]) => choices[key])
                    .map(([key, label]) => `${label} (this report): ${$root.find(`[data-report-filter="${key}"] option:selected`).text()}`),
            ],
            sort: summary.sort,
        }),
        beforeExport: exportLogger($root, 'employees'),
    });

    $root.on('change', '[data-report-filter]', function () {
        choices[$(this).attr('data-report-filter')] = String($(this).val());
        report.clearError();
        report.refresh();
    });

    report.refresh();
}

$(function () {
    const $report = $('[data-page="employee-report"]');

    if ($report.length) {
        initReportPage($report);
    }
});
