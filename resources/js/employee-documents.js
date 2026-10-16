import $ from './jquery.js';
import { formatDate, formatFileSize } from './format.js';

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
