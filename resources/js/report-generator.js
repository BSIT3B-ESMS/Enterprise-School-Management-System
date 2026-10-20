import $ from './jquery.js';
import { localIsoDate } from './date-filter.js';
import { showToast } from './toast.js';
import { buildXlsx } from './xlsx.js';

// The Generate Report pages (Employees, Recruitment, and Audit Logs) share this: the export format, the fields to include,
// the preview, and the PDF, Excel, and CSV exports. Each page supplies its rows, its fields, and its own filters.

const previewRowLimit = 10;

const formats = {
    pdf: { label: 'PDF', action: 'Generate PDF', hint: 'Opens your browser’s print dialog. Choose “Save as PDF” as the destination.' },
    xlsx: { label: 'Excel (.xlsx)', action: 'Download Excel', hint: 'Downloads an Excel workbook (.xlsx).' },
    csv: { label: 'CSV', action: 'Download CSV', hint: 'Downloads a comma-separated file that opens in any spreadsheet app.' },
};

function displayValue(field, row) {
    return String((field.display ?? field.value)(row) ?? '');
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
 * Sets up a Generate Report page and returns `refresh()`, which the page calls whenever its rows may have changed (a
 * filter changed, or the records did).
 *
 * @param {JQuery} $root
 * @param {{
 *     title: string,
 *     fileStem: string,
 *     sheetName: string,
 *     noun: [string, string],
 *     countText: (count: number) => string,
 *     noFiltersText: string,
 *     fieldGroups: Array<{ group: string, fields: Array<{ key: string, label: string, value: Function, display?: Function }> }>,
 *     defaultFieldKeys: string[],
 *     rows: () => Array<object>,
 *     summary: () => { filters: string[], sort: string },
 *     beforeExport?: (details: { format: string, count: number, fields: string[] }) => Promise<void>,
 * }} config
 * @returns {{ refresh: () => void, clearError: () => void }}
 */
export function initReportGenerator($root, config) {
    const allFields = config.fieldGroups.flatMap(({ fields }) => fields);
    const $fields = $root.find('[data-report-fields]');
    const $generate = $root.find('[data-report-generate]');
    const countOf = (count) => `${count} ${count === 1 ? config.noun[0] : config.noun[1]}`;
    const fileName = (extension) => `${config.fileStem}-${localIsoDate(new Date())}.${extension}`;
    let isGenerating = false;

    function exportCsv(fields, rows) {
        const lines = [fields.map((field) => field.label), ...rows.map((row) => fields.map((field) => field.value(row)))]
            .map((line) => line.map(csvCell).join(','));

        // The byte order mark lets Excel read accents and "₱" as UTF-8.
        downloadBlob(new Blob(['﻿', lines.join('\r\n')], { type: 'text/csv;charset=utf-8' }), fileName('csv'));

        return fileName('csv');
    }

    function exportXlsx(fields, rows) {
        const sheet = [fields.map((field) => field.label), ...rows.map((row) => fields.map((field) => field.value(row)))];
        const type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        downloadBlob(new Blob(buildXlsx(sheet, config.sheetName), { type }), fileName('xlsx'));

        return fileName('xlsx');
    }

    /**
     * Prints the report from a hidden frame, so no pop-up is needed and the page keeps its state.
     * The frame is removed once printing finishes. Throws if the browser cannot print.
     */
    function printReport(fields, rows, summary) {
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
        rows.forEach((item) => {
            const row = element('tr');

            fields.forEach((field) => row.append(element('td', displayValue(field, item))));
            body.append(row);
        });
        [...(summary.filters.length ? summary.filters : [config.noFiltersText]), summary.sort].forEach((line) => criteria.append(element('li', line)));

        const head = element('thead');

        head.append(headRow);
        table.append(head, body);
        printDocument.head.append(style);
        printDocument.body.append(
            element('h1', config.title),
            element('p', `${countOf(rows.length)} · Generated ${new Date().toLocaleString('en-PH')}`),
            criteria,
            table,
        );

        printWindow.addEventListener('afterprint', () => frame.remove());
        setTimeout(() => frame.remove(), 60000);
        printWindow.focus();
        printWindow.print();
    }

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
        $fields.empty().append(...config.fieldGroups.map(({ group, fields }, groupIndex) => (
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
                                checked: config.defaultFieldKeys.includes(field.key),
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
        const rows = config.rows();
        const fields = selectedFields();
        const summary = config.summary();
        const hasRows = rows.length > 0;
        const hasFields = fields.length > 0;

        $root.find('[data-report-field-count]').text(`${fields.length} of ${allFields.length} selected`);
        $root.find('[data-report-fields-error]').toggleClass('d-none', hasFields);

        $root.find('[data-report-count]').text(config.countText(rows.length));
        $root.find('[data-report-criteria]').empty().append(
            ...(summary.filters.length ? summary.filters : [config.noFiltersText]).map((line) => $('<li>', { text: line })),
            $('<li>', { text: summary.sort }),
        );

        $root.find('[data-report-empty]').toggleClass('d-none', hasRows);
        $root.find('[data-report-preview]').toggleClass('d-none', !hasRows || !hasFields);

        if (hasRows && hasFields) {
            const shown = rows.slice(0, previewRowLimit);

            $root.find('[data-report-preview-head]').empty().append(
                $('<tr>').append(...fields.map((field) => $('<th>', { scope: 'col', text: field.label }))),
            );
            $root.find('[data-report-preview-rows]').empty().append(...shown.map((row) => (
                $('<tr>').append(...fields.map((field) => $('<td>', { text: displayValue(field, row) })))
            )));
            $root.find('[data-report-preview-note]').text(
                rows.length > shown.length ? `Showing the first ${shown.length} of ${rows.length} rows.` : `Showing all ${rows.length} rows.`,
            );
        }

        $generate.prop('disabled', isGenerating || !hasRows || !hasFields);
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

    // Generates from the rows matching the filters at the moment of the click.
    $generate.on('click', function () {
        const rows = config.rows();
        const fields = selectedFields();
        const format = selectedFormat();

        if (isGenerating || !rows.length || !fields.length) {
            return;
        }

        showError('');
        setGenerating(true);

        // Let the spinner paint before the work (and the print dialog, which blocks) starts.
        setTimeout(async () => {
            try {
                // The export is recorded first; if that fails, no file is made.
                await config.beforeExport?.({ format, count: rows.length, fields: fields.map((field) => field.key) });

                if (format === 'pdf') {
                    printReport(fields, rows, config.summary());
                    setGenerating(false);
                    showToast('Print dialog opened. Choose “Save as PDF” to save the report.');

                    return;
                }

                const name = format === 'xlsx' ? exportXlsx(fields, rows) : exportCsv(fields, rows);

                setGenerating(false);
                showToast(`Report generated. Your browser is downloading ${name}.`);
            } catch (error) {
                setGenerating(false);
                showError(`The report could not be generated. ${error instanceof Error ? error.message : ''}`.trim());
            }
        }, 50);
    });

    // Starts with PDF and the default fields.
    renderFieldOptions();
    syncGroupBoxes();
    renderFormat();
    renderPreview();

    return { refresh: renderPreview, clearError: () => showError('') };
}
