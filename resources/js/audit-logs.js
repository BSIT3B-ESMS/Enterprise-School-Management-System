import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { localIsoDate } from './date-filter.js';
import { formatDateTime } from './format.js';
import { activityActions, findActivity, listActivity, onMockDataChange } from './mock-data.js';
import { renderPagination } from './pagination.js';

const pageSize = 8;

const sortValue = {
    at: (entry) => entry.at.getTime(),
    user: (entry) => entry.user.toLowerCase(),
    action: (entry) => entry.action,
    entity: (entry) => entry.entity.toLowerCase(),
};

// Table view state: search text, filters (including one exact date), sort column and direction
// (1 ascending, -1 descending), page.
const view = { query: '', action: '', recordType: '', date: '', sortKey: 'at', sortDirection: -1, page: 1 };

/**
 * "Employee · Maria Clara Santos (EMP-1001)" → type "Employee", record "Maria Clara Santos (EMP-1001)".
 * Session entries ("Session") have no separate record name.
 */
export function splitEntity(entity) {
    const [recordType, ...rest] = entity.split(' · ');

    return { recordType, record: rest.join(' · ') || recordType };
}

function actionClass(action) {
    return `action-${action.toLowerCase()}`;
}

function actionPill(action) {
    return $('<span>', { class: `status-pill ${actionClass(action)}`, text: action });
}

/**
 * Entries matching the search and filters, in the current sort order (ties fall back to the newest).
 */
function visibleEntries() {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];

    return listActivity()
        .filter((entry) => !query || `${entry.action} ${entry.entity} ${entry.detail} ${entry.user}`.toLowerCase().includes(query))
        .filter((entry) => !view.action || entry.action === view.action)
        .filter((entry) => !view.recordType || splitEntity(entry.entity).recordType === view.recordType)
        // By calendar day, so an entry at any time on the chosen date is included. No date means every entry.
        .filter((entry) => !view.date || localIsoDate(entry.at) === view.date)
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);
            const order = firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0;

            return order * view.sortDirection || second.at - first.at;
        });
}

/**
 * Action options are fixed; record types come from the log itself, so new kinds appear as they are logged.
 */
function renderFilterOptions($root) {
    const recordTypes = [...new Set(listActivity().map((entry) => splitEntity(entry.entity).recordType))].sort();
    const fill = ($select, placeholder, values, selected) => {
        $select.empty().append(
            $('<option>', { value: '', text: placeholder }),
            ...values.map((value) => $('<option>', { value, text: value })),
        ).val(values.includes(selected) ? selected : '');
    };

    if (!recordTypes.includes(view.recordType)) {
        view.recordType = '';
    }

    fill($root.find('[data-audit-filter="action"]'), 'All Actions', activityActions, view.action);
    fill($root.find('[data-audit-filter="recordType"]'), 'All Records', recordTypes, view.recordType);
}

function rowFor(entry) {
    return $('<tr>', {
        class: 'employee-row',
        tabindex: 0,
        'aria-haspopup': 'dialog',
        title: 'View entry details',
        'data-audit-row': entry.id,
    }).append(
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatDateTime(entry.at) }),
        $('<td>', { class: 'text-nowrap', text: entry.user }),
        $('<td>').append(actionPill(entry.action)),
        $('<td>', { text: entry.entity }),
        $('<td>', { class: 'text-body-secondary', text: entry.detail }),
    );
}

function renderSortHeaders($root) {
    $root.find('[data-sort-column]').each(function () {
        const isSorted = $(this).attr('data-sort-column') === view.sortKey;
        const direction = view.sortDirection > 0 ? 'ascending' : 'descending';

        $(this).attr('aria-sort', isSorted ? direction : 'none');
    });
}

function renderTable($root) {
    const entries = visibleEntries();
    const pageCount = Math.max(1, Math.ceil(entries.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = entries.slice(start, start + pageSize);

    $root.find('[data-audit-rows]').empty().append(...pageRows.map(rowFor));

    if (pageRows.length) {
        $root.find('[data-audit-empty]').addClass('d-none');
    } else {
        $root.find('[data-audit-empty]').removeClass('d-none');
    }

    $root.find('[data-audit-summary]').text(
        `Showing ${entries.length ? start + 1 : 0} to ${start + pageRows.length} of ${entries.length} entries`,
    );
    renderSortHeaders($root);
    renderPagination($root.find('[data-audit-pagination]'), view.page, pageCount);
    syncReportLink($root);
}

/**
 * Generate Report starts from the action, record type, and date filtered here.
 */
function syncReportLink($root) {
    const $link = $root.find('[data-report-link]');
    const params = new URLSearchParams();

    [['action', view.action], ['record', view.recordType], ['date', view.date]].forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    $link.attr('href', `${$link.attr('data-report-url')}${params.toString() ? `?${params}` : ''}`);
}

/**
 * Row clicks and Enter/Space open the details modal; clicks on controls inside a row are left alone.
 * Rows are re-rendered when the log changes, so focus returns to the row (found again by its ID)
 * once the modal closes.
 */
function initRowDetails($root) {
    const $detailsModal = $('[data-audit-details-modal]');
    const interactiveSelector = 'a, button, input, select, textarea, label, [data-bs-toggle]';
    let lastOpenedId = null;

    function openDetails(row) {
        // Ignore a repeat click or keypress while a modal is already open.
        if ($('.modal.show').length) {
            return;
        }

        lastOpenedId = $(row).attr('data-audit-row');
        Modal.getOrCreateInstance($detailsModal[0]).show(row);
    }

    $root.on('click', 'tr[data-audit-row]', function (event) {
        if ($(event.target).closest(interactiveSelector).length) {
            return;
        }

        openDetails(this);
    });

    $root.on('keydown', 'tr[data-audit-row]', function (event) {
        if (event.target !== this || (event.key !== 'Enter' && event.key !== ' ')) {
            return;
        }

        event.preventDefault();
        openDetails(this);
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        if (!lastOpenedId || $('.modal.show').length) {
            return;
        }

        $root.find(`tr[data-audit-row="${lastOpenedId}"]`).trigger('focus');
        lastOpenedId = null;
    });
}

function initDetailsModal($modal) {
    $modal.on('show.bs.modal', function (event) {
        const entry = findActivity($(event.relatedTarget).attr('data-audit-row'));

        if (!entry) {
            event.preventDefault();

            return;
        }

        const { recordType, record } = splitEntity(entry.entity);
        const values = {
            title: entry.entity,
            action: entry.action,
            at: formatDateTime(entry.at),
            user: entry.user,
            recordType,
            record,
            detail: entry.detail,
        };

        Object.entries(values).forEach(([field, value]) => {
            $modal.find(`[data-audit-field="${field}"]`).text(value);
        });

        $modal.find('[data-audit-action-pill]').attr('class', `status-pill ${actionClass(entry.action)}`).text(entry.action);

        const $related = $modal.find('[data-audit-related-section]');

        if (entry.related.length) {
            $related.removeClass('d-none');
        } else {
            $related.addClass('d-none');
        }

        $modal.find('[data-audit-related]').empty().append(...entry.related.map((relatedEntry) => (
            $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
                $('<div>', { class: 'min-w-0' }).append(
                    $('<div>', { class: 'd-flex align-items-center gap-2' }).append(
                        actionPill(relatedEntry.action),
                        document.createTextNode(relatedEntry.detail),
                    ),
                    $('<small>', { class: 'text-body-secondary', text: `by ${relatedEntry.user}` }),
                ),
                $('<small>', { class: 'text-body-secondary text-nowrap', text: formatDateTime(relatedEntry.at) }),
            )
        )));
    });
}

$(function () {
    const $audit = $('[data-page="audit"]');

    if (!$audit.length) {
        return;
    }

    $audit.on('input', '[data-audit-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderTable($audit);
    });

    $audit.on('change', '[data-audit-filter]', function () {
        view[$(this).attr('data-audit-filter')] = String($(this).val());
        view.page = 1;
        renderTable($audit);
    });

    // Clears the search, the date, and every filter, and returns to page 1; the sort order is kept.
    $audit.on('click', '[data-audit-clear]', function () {
        Object.assign(view, { query: '', action: '', recordType: '', date: '', page: 1 });
        $audit.find('[data-audit-search], [data-audit-filter]').val('');
        renderTable($audit);
    });

    $audit.on('click', '[data-sort-key]', function () {
        const key = $(this).attr('data-sort-key');

        if (view.sortKey === key) {
            view.sortDirection *= -1;
        } else {
            view.sortKey = key;
            view.sortDirection = key === 'at' ? -1 : 1;
        }

        view.page = 1;
        renderTable($audit);
    });

    $audit.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable($audit);
    });

    initRowDetails($audit);
    initDetailsModal($('[data-audit-details-modal]'));

    renderFilterOptions($audit);
    renderTable($audit);
    onMockDataChange(() => {
        renderFilterOptions($audit);
        renderTable($audit);
    });
});
