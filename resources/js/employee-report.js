import $ from './jquery.js';
import { formatDate, formatPesoAmount } from './format.js';
import { localIsoDate } from './date-filter.js';
import { describeEmployeeView, employeeViewQuery, filterAndSortEmployees, readEmployeeView } from './employee-list.js';
import { getEmployeeFormOptions, onMockDataChange } from './mock-data.js';
import { showToast } from './toast.js';
import { buildXlsx } from './xlsx.js';

const previewRowLimit = 10;

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

const allFields = fieldGroups.flatMap(({ fields }) => fields);

// Selected when the page opens: the columns the Employees table shows.
const defaultFieldKeys = ['id', 'name', 'department', 'jobTitle', 'hiredOn'];

const formats = {
    pdf: { label: 'PDF', action: 'Generate PDF', hint: 'Opens your browser’s print dialog. Choose “Save as PDF” as the destination.' },
    xlsx: { label: 'Excel (.xlsx)', action: 'Download Excel', hint: 'Downloads an Excel workbook (.xlsx).' },
    csv: { label: 'CSV', action: 'Download CSV', hint: 'Downloads a comma-separated file that opens in any spreadsheet app.' },
};

function displayValue(field, employee) {
    return String((field.display ?? field.value)(employee) ?? '');
}

function fileName(extension) {
    return `employees-report-${localIsoDate(new Date())}.${extension}`;
}

/**
 * Starts a browser download of the blob. Browsers do not report whether the user kept the file,
 * so callers only say the download was started.
 */
function downloadBlob(blob, name) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = name;
    link.hidden = true;
    document.body.append(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 10000);
}

function csvCell(value) {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

function exportCsv(fields, employees) {
    const lines = [fields.map((field) => field.label), ...employees.map((employee) => fields.map((field) => field.value(employee)))]
        .map((row) => row.map(csvCell).join(','));

    // The byte order mark lets Excel read accents and "₱" as UTF-8.
    downloadBlob(new Blob(['﻿', lines.join('\r\n')], { type: 'text/csv;charset=utf-8' }), fileName('csv'));

    return fileName('csv');
}

function exportXlsx(fields, employees) {
    const rows = [fields.map((field) => field.label), ...employees.map((employee) => fields.map((field) => field.value(employee)))];
    const type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    downloadBlob(new Blob(buildXlsx(rows, 'Employees'), { type }), fileName('xlsx'));

    return fileName('xlsx');
}

// Wide reports print in landscape so the columns stay readable.
const printStyles = (orientation) => `
    @page { size: ${orientation}; margin: 14mm; }
    body { font: 10pt/1.4 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #18231e; margin: 0; }
    h1 { font-size: 15pt; margin: 0 0 2pt; }
    p { margin: 0 0 2pt; color: #4b5852; }
    ul { margin: 4pt 0 10pt; padding-left: 14pt; color: #4b5852; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 4pt 6pt; border-bottom: 0.5pt solid #cfd8d2; text-align: left; vertical-align: top; }
    th { background: #eaf4ee; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.04em; }
    thead { display: table-header-group; }
    tr { break-inside: avoid; }
`;

/**
 * Prints the report from a hidden frame, so no pop-up is needed and the page keeps its state.
 * The frame is removed once printing finishes. Throws if the browser cannot print.
 */
function printReport(fields, employees, summary) {
    const frame = document.createElement('iframe');

    frame.setAttribute('aria-hidden', 'true');
    frame.tabIndex = -1;
    Object.assign(frame.style, { position: 'fixed', right: '0', bottom: '0', width: '0', height: '0', border: '0' });
    document.body.append(frame);

    const printWindow = frame.contentWindow;
    const printDocument = frame.contentDocument;

    if (!printWindow || !printDocument || typeof printWindow.print !== 'function') {
        frame.remove();
        throw new Error('Printing is not available in this browser.');
    }

    printDocument.open();
    printDocument.write('<!doctype html><html lang="en"><head><meta charset="utf-8"></head><body></body></html>');
    printDocument.close();

    // The title becomes the suggested file name when saving as PDF.
    printDocument.title = fileName('pdf').replace(/\.pdf$/, '');

    const element = (tag, text) => {
        const node = printDocument.createElement(tag);

        if (text !== undefined) {
            node.textContent = text;
        }

        return node;
    };
    const style = element('style', printStyles(fields.length > 6 ? 'landscape' : 'portrait'));
    const table = element('table');
    const headRow = element('tr');
    const body = element('tbody');
    const criteria = element('ul');

    fields.forEach((field) => headRow.append(element('th', field.label)));
    employees.forEach((employee) => {
        const row = element('tr');

        fields.forEach((field) => row.append(element('td', displayValue(field, employee))));
        body.append(row);
    });
    [...summary.filters, summary.sort].forEach((line) => criteria.append(element('li', line)));

    const head = element('thead');

    head.append(headRow);
    table.append(head, body);
    printDocument.head.append(style);
    printDocument.body.append(
        element('h1', 'Employee Report'),
        element('p', `${employees.length} ${employees.length === 1 ? 'employee' : 'employees'} · Generated ${new Date().toLocaleString('en-PH')}`),
        criteria,
        table,
    );

    printWindow.addEventListener('afterprint', () => frame.remove());
    setTimeout(() => frame.remove(), 60000);
    printWindow.focus();
    printWindow.print();
}

/**
 * The Generate Report page. Field selections live only on this page; the rows always come from the
 * Employees list state in the URL.
 */
function initReportPage($root) {
    const view = readEmployeeView(window.location.search);
    // Back returns to the Employees page with the filters it came with; the report's own Department and Job Title choices
    // below change only this report.
    const employeesQuery = employeeViewQuery(view);
    const source = {
        rows: () => filterAndSortEmployees(view),
        summary: () => describeEmployeeView(view),
    };
    const $fields = $root.find('[data-report-fields]');
    const $generate = $root.find('[data-report-generate]');
    let isGenerating = false;

    // Back returns to the Employees page with its search, filters, sort, and page as they were.
    $root.find('[data-report-back]').attr('href', `${$root.attr('data-employees-url')}${employeesQuery}`);

    function selectedFormat() {
        return String($root.find('[name="reportFormat"]:checked').val() ?? 'pdf');
    }

    function selectedFields() {
        const keys = $fields.find('[data-report-field]:checked').map(function () {
            return $(this).attr('data-report-field');
        }).get();

        return allFields.filter((field) => keys.includes(field.key));
    }

    function renderFieldOptions() {
        $fields.empty().append(...fieldGroups.map(({ group, fields }, groupIndex) => (
            $('<div>', { class: 'col' }).append($('<fieldset>', { class: 'report-field-group' }).append(
                $('<legend>', { class: 'form-check mb-2' }).append(
                    $('<input>', {
                        class: 'form-check-input',
                        type: 'checkbox',
                        id: `report-group-${groupIndex}`,
                        'data-report-group': groupIndex,
                    }),
                    $('<label>', { class: 'form-check-label fw-semibold', for: `report-group-${groupIndex}`, text: group }),
                ),
                $('<div>', { class: 'row row-cols-1 row-cols-sm-2 row-cols-md-1 row-cols-xxl-2 g-1' }).append(...fields.map((field) => (
                    $('<div>', { class: 'col' }).append(
                        $('<div>', { class: 'form-check' }).append(
                            $('<input>', {
                                class: 'form-check-input',
                                type: 'checkbox',
                                id: `report-field-${field.key}`,
                                'data-report-field': field.key,
                                'data-report-field-group': groupIndex,
                                checked: defaultFieldKeys.includes(field.key),
                            }),
                            $('<label>', { class: 'form-check-label', for: `report-field-${field.key}`, text: field.label }),
                        ),
                    )
                ))),
            ))
        )));
    }

    // Group boxes show checked, unchecked, or mixed for their fields.
    function syncGroupBoxes() {
        $fields.find('[data-report-group]').each(function () {
            const $groupFields = $fields.find(`[data-report-field-group="${$(this).attr('data-report-group')}"]`);
            const checkedCount = $groupFields.filter(':checked').length;

            $(this).prop('checked', checkedCount === $groupFields.length);
            $(this).prop('indeterminate', checkedCount > 0 && checkedCount < $groupFields.length);
        });
    }

    function renderPreview() {
        const employees = source.rows();
        const fields = selectedFields();
        const summary = source.summary();
        const hasRows = employees.length > 0;
        const hasFields = fields.length > 0;

        $root.find('[data-report-field-count]').text(`${fields.length} of ${allFields.length} selected`);
        $root.find('[data-report-fields-error]').toggleClass('d-none', hasFields);

        $root.find('[data-report-count]').text(
            `${employees.length} ${employees.length === 1 ? 'employee matches' : 'employees match'} the current search and filters.`,
        );
        $root.find('[data-report-criteria]').empty().append(
            ...(summary.filters.length ? summary.filters : ['No filters applied: all employees']).map((line) => $('<li>', { text: line })),
            $('<li>', { text: summary.sort }),
        );

        $root.find('[data-report-empty]').toggleClass('d-none', hasRows);
        $root.find('[data-report-preview]').toggleClass('d-none', !hasRows || !hasFields);

        if (hasRows && hasFields) {
            const shown = employees.slice(0, previewRowLimit);

            $root.find('[data-report-preview-head]').empty().append(
                $('<tr>').append(...fields.map((field) => $('<th>', { scope: 'col', text: field.label }))),
            );
            $root.find('[data-report-preview-rows]').empty().append(...shown.map((employee) => (
                $('<tr>').append(...fields.map((field) => $('<td>', { text: displayValue(field, employee) })))
            )));
            $root.find('[data-report-preview-note]').text(
                employees.length > shown.length ? `Showing the first ${shown.length} of ${employees.length} rows.` : `Showing all ${employees.length} rows.`,
            );
        }

        $generate.prop('disabled', isGenerating || !hasRows || !hasFields);
    }

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

    function renderFormat() {
        const format = formats[selectedFormat()];

        $root.find('[data-report-format-label]').text(format.label);
        $root.find('[data-report-format-hint]').text(format.hint);
        $root.find('[data-report-generate-label]').text(format.action);
    }

    function showError(message) {
        $root.find('[data-report-error]').text(message).toggleClass('d-none', !message);
    }

    function setGenerating(generating) {
        isGenerating = generating;
        $root.find('[data-report-spinner]').toggleClass('d-none', !generating);
        $root.find('[data-report-generate-label]').text(generating ? 'Generating…' : formats[selectedFormat()].action);
        $root.find('[name="reportFormat"], [data-report-field], [data-report-group], [data-report-select-all], [data-report-deselect-all]')
            .prop('disabled', generating);
        renderPreview();
    }

    $root.on('change', '[data-report-filter]', function () {
        view[$(this).attr('data-report-filter')] = String($(this).val());
        showError('');
        renderPreview();
    });

    $root.on('change', '[name="reportFormat"]', function () {
        showError('');
        renderFormat();
    });

    $root.on('change', '[data-report-field]', function () {
        syncGroupBoxes();
        renderPreview();
    });

    $root.on('change', '[data-report-group]', function () {
        $fields.find(`[data-report-field-group="${$(this).attr('data-report-group')}"]`).prop('checked', $(this).prop('checked'));
        syncGroupBoxes();
        renderPreview();
    });

    $root.on('click', '[data-report-select-all], [data-report-deselect-all]', function () {
        $fields.find('[data-report-field]').prop('checked', $(this).is('[data-report-select-all]'));
        syncGroupBoxes();
        renderPreview();
    });

    $generate.on('click', function () {
        const employees = source.rows();
        const fields = selectedFields();
        const format = selectedFormat();

        if (isGenerating || !employees.length || !fields.length) {
            return;
        }

        showError('');
        setGenerating(true);

        // Let the spinner paint before the work (and the print dialog, which blocks) starts.
        setTimeout(() => {
            try {
                if (format === 'pdf') {
                    printReport(fields, employees, source.summary());
                    setGenerating(false);
                    showToast('Print dialog opened. Choose “Save as PDF” to save the report.');

                    return;
                }

                const name = format === 'xlsx' ? exportXlsx(fields, employees) : exportCsv(fields, employees);

                setGenerating(false);
                showToast(`Report generated. Your browser is downloading ${name}.`);
            } catch (error) {
                setGenerating(false);
                showError(`The report could not be generated. ${error instanceof Error ? error.message : ''}`.trim());
            }
        }, 50);
    });

    // Starts with PDF and the default fields, against the latest data.
    renderFilterOptions();
    renderFieldOptions();
    syncGroupBoxes();
    renderFormat();
    renderPreview();

    // Keep the count and preview current if the records change (for example, in another tab).
    onMockDataChange(() => {
        renderFilterOptions();
        renderPreview();
    });
}

$(function () {
    const $report = $('[data-page="employee-report"]');

    if ($report.length) {
        initReportPage($report);
    }
});
