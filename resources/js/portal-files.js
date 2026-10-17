import $ from './jquery.js';
import { documentTypeOf } from './employee-documents.js';
import { formatDate, formatFileSize } from './format.js';
import { getPortalFiles, onMockDataChange } from './mock-data.js';

const plural = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;

// A record that carries file content is "File available"; one with only a filename is a placeholder.
function availabilityChip(file) {
    return file.hasContent
        ? $('<span>', { class: 'status-pill employment-active', text: 'File available' })
        : $('<span>', { class: 'ems-chip', text: 'Filename only', title: 'Only the file name is on record; there is no file to open.' });
}

function fileRow(file) {
    const details = [
        documentTypeOf(file.name),
        file.size ? formatFileSize(file.size) : '',
        file.addedOn ? `Added ${formatDate(file.addedOn)}` : '',
    ].filter(Boolean).join(' · ');

    return $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
        $('<div>', { class: 'min-w-0' }).append(
            $('<div>', { class: 'fw-medium text-truncate', title: file.name, text: file.name }),
            $('<small>', { class: 'text-body-secondary', text: details }),
        ),
        availabilityChip(file),
    );
}

function categoryCard(category) {
    return $('<div>', { class: 'col' }).append(
        $('<section>', { class: 'card dashboard-panel' }).append(
            $('<div>', { class: 'd-flex justify-content-between align-items-center gap-3 mb-3' }).append(
                $('<h2>', { class: 'mb-0', text: category.label }),
                $('<span>', { class: 'ems-chip tabular-nums', text: plural(category.files.length, 'file') }),
            ),
            category.files.length
                ? $('<ul>', { class: 'list-group list-group-flush dashboard-list' }).append(...category.files.map(fileRow))
                : $('<p>', { class: 'text-body-secondary mb-0', text: 'No files in this category.' }),
        ),
    );
}

function renderFiles($root) {
    const { employee, categories } = getPortalFiles();
    const fileCount = categories.reduce((total, category) => total + category.files.length, 0);
    const filledCount = categories.filter((category) => category.files.length).length;

    // HR can delete the employee's record, for example in another tab.
    $root.find('[data-portal-missing]').toggleClass('d-none', employee);
    $root.find('[data-files-none]').toggleClass('d-none', !employee || fileCount > 0);
    $root.find('[data-files-summary]').toggleClass('d-none', !employee).text(
        fileCount
            ? `${plural(fileCount, 'file')} in ${filledCount} ${filledCount === 1 ? 'category' : 'categories'}, from your 201 file.`
            : 'Your 201 file documents, by category.',
    );
    $root.find('[data-files-categories]').empty().append(...(employee && fileCount ? categories.map(categoryCard) : []));
}

$(function () {
    const $files = $('[data-page="portal-files"]');

    if (!$files.length) {
        return;
    }

    renderFiles($files);
    onMockDataChange(() => renderFiles($files));
});
