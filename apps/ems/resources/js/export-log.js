// Every report export is recorded in the audit log before the file is built. The report page marks its root with
// data-export-url (the logging address); the export stops, with a message, if the record cannot be saved.

/**
 * Returns the `beforeExport` hook for a report generator.
 *
 * @param {JQuery} $root The report page's root element.
 * @param {'employees'|'recruitment'|'audit'} report
 */
export function exportLogger($root, report) {
    return async ({ format, count, fields }) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch($root.attr('data-export-url'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ report, format, count, fields }),
        });

        if (!response.ok) {
            throw new Error('The export could not be recorded in the audit log, so no file was made.');
        }
    };
}
