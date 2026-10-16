import $ from './jquery.js';

function pageButton(label, pageNumber, { isDisabled = false, isCurrent = false } = {}) {
    const $item = $('<li>', { class: 'page-item' });
    const $button = $('<button>', { type: 'button', class: 'page-link', text: label, 'data-page-number': pageNumber });

    if (isDisabled) {
        $item.addClass('disabled');
        $button.prop('disabled', true);
    }

    if (isCurrent) {
        $item.addClass('active');
        $button.attr('aria-current', 'page');
    }

    return $item.append($button);
}

/**
 * Fills a `.pagination` list with Previous, up to five page numbers around the current page, and Next.
 * Each button carries data-page-number for the page's click handler.
 */
export function renderPagination($list, currentPage, pageCount) {
    const firstShown = Math.max(1, Math.min(currentPage - 2, pageCount - 4));
    const lastShown = Math.min(pageCount, firstShown + 4);
    const numbers = [];

    for (let pageNumber = firstShown; pageNumber <= lastShown; pageNumber++) {
        numbers.push(pageButton(String(pageNumber), pageNumber, { isCurrent: pageNumber === currentPage }));
    }

    $list.empty().append(
        pageButton('Previous', currentPage - 1, { isDisabled: currentPage <= 1 }),
        ...numbers,
        pageButton('Next', currentPage + 1, { isDisabled: currentPage >= pageCount }),
    );
}
