import $ from './jquery.js';
import { formatDate, formatFileSize } from './format.js';

const typeLabels = {
    pdf: 'PDF document',
    doc: 'Word document',
    docx: 'Word document',
    xls: 'Excel spreadsheet',
    xlsx: 'Excel spreadsheet',
    csv: 'CSV file',
    ppt: 'PowerPoint presentation',
    pptx: 'PowerPoint presentation',
    txt: 'Text file',
    jpg: 'Image',
    jpeg: 'Image',
    png: 'Image',
    gif: 'Image',
    webp: 'Image',
};

/**
 * "Transcript of Records.pdf" → "PDF document"; an unknown extension gives "XYZ file", none gives "File".
 */
export function documentTypeOf(fileName) {
    const extension = fileName.includes('.') ? fileName.split('.').pop().toLowerCase() : '';

    return typeLabels[extension] ?? (extension ? `${extension.toUpperCase()} file` : 'File');
}

/**
 * Today as "2026-10-17" in the viewer's local time (the date a document record is added).
 */
export function todayIsoDate() {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

/**
 * One 201 file document row, as listed on the employee form and the profile. Documents are mock
 * records: only the file's name, size, and the date it was added are kept; nothing is uploaded.
 *
 * @param {{ name: string, size: number, addedOn: string }} file
 * @param {number|null} removeIndex When set, adds a Remove button carrying this index.
 */
export function documentItem(file, removeIndex = null) {
    const $item = $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
        $('<div>', { class: 'min-w-0' }).append(
            $('<div>', { class: 'text-truncate', text: file.name }),
            $('<small>', { class: 'text-body-secondary', text: `${formatFileSize(file.size)} · Added ${formatDate(file.addedOn)}` }),
        ),
    );

    if (removeIndex !== null) {
        $item.append($('<button>', {
            type: 'button',
            class: 'btn btn-sm btn-outline-danger',
            text: 'Remove',
            'aria-label': `Remove ${file.name}`,
            'data-document-remove': removeIndex,
        }));
    }

    return $item;
}

// A form's document lists ({ key: [{ name, size, addedOn }] }) live on the form until it is submitted.
function documentsOf($form) {
    return $form.data('documents');
}

// "No file chosen", or the files listed for the field ("Transcript.pdf" or "2 files: Transcript.pdf, Diploma.pdf").
function documentStatus(files) {
    if (!files.length) {
        return 'No file chosen';
    }

    const names = files.map((file) => file.name).join(', ');

    return files.length === 1 ? names : `${files.length} files: ${names}`;
}

function renderDocuments($form) {
    $form.find('[data-document-field]').each(function () {
        const files = documentsOf($form)[$(this).attr('data-document-field')];

        $(this).find('[data-document-list]').empty().append(...files.map((file, index) => documentItem(file, index)));
        $(this).find('[data-document-status]').text(documentStatus(files)).toggleClass('text-body-secondary', !files.length);
    });
}

/**
 * Loads the form's document lists (one per `<x-employee-document-field>` on it) from saved records, or empty,
 * and lists them.
 *
 * @param {Object<string, Array<{ name: string, size: number, addedOn: string }>>} documents
 */
export function setDocuments($form, documents = {}) {
    const lists = {};

    $form.find('[data-document-field]').each(function () {
        const key = $(this).attr('data-document-field');

        lists[key] = (documents?.[key] ?? []).map((file) => ({ ...file }));
    });
    $form.data('documents', lists);
    renderDocuments($form);
}

/**
 * The form's document lists as they stand: file names, sizes, and dates only.
 */
export function readDocuments($form) {
    return documentsOf($form);
}

/**
 * Lets the form's document fields add files (listed by name only; the input is cleared so the same file can
 * be picked again later) and remove them. Nothing is uploaded or stored. `onChange` runs after files are added or removed.
 *
 * @param {{ onChange?: () => void }} [options]
 */
export function bindDocumentFields($form, { onChange = () => {} } = {}) {
    $form.on('change', '[data-document-input]', function () {
        const files = documentsOf($form)[$(this).closest('[data-document-field]').attr('data-document-field')];

        Array.from(this.files ?? []).forEach((file) => {
            if (!files.some((listed) => listed.name === file.name && listed.size === file.size)) {
                files.push({ name: file.name, size: file.size, addedOn: todayIsoDate() });
            }
        });
        this.value = '';
        renderDocuments($form);
        onChange();
    });

    $form.on('click', '[data-document-remove]', function () {
        const $field = $(this).closest('[data-document-field]');

        documentsOf($form)[$field.attr('data-document-field')].splice(Number($(this).attr('data-document-remove')), 1);
        renderDocuments($form);
        $field.find('[data-document-input]').trigger('focus');
        onChange();
    });
}
