import $ from './jquery.js';
import { reference } from './applicant-portal.js';
import { listPositions, onMockDataChange } from './mock-data.js';

const plural = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;

// "Open · 4 openings" for an open position, "Closed" for one with no openings.
function statusBadge(position) {
    return position.isOpen
        ? $('<span>', { class: 'status-pill employment-active', text: `Open · ${plural(position.openings, 'opening')}` })
        : $('<span>', { class: 'ems-chip', text: 'Closed' });
}

// The application form for a position, keeping the applicant's reference so the form can use their details.
// Only open positions have one: a closed position can be viewed but not applied for.
function applyUrl($root, position) {
    const query = new URLSearchParams({ position: position.id, ...(reference ? { ref: reference } : {}) });

    return `${$root.attr('data-apply-url')}?${query}`;
}

function positionCard($root, position) {
    const $actions = $('<div>', { class: 'd-flex flex-wrap gap-2 mt-auto pt-2' }).append(
        $('<button>', {
            type: 'button',
            class: 'btn btn-sm btn-outline-secondary',
            text: 'View Details',
            'aria-label': `View details for ${position.name}`,
            'data-bs-toggle': 'modal',
            'data-bs-target': '#position-details-modal',
            'data-position-id': position.id,
        }),
        position.isOpen
            ? $('<a>', { class: 'btn btn-sm btn-primary', href: applyUrl($root, position), text: 'Apply', 'aria-label': `Apply for ${position.name}` })
            : $('<button>', { type: 'button', class: 'btn btn-sm btn-primary', disabled: true, text: 'Applications closed' }),
    );

    return $('<div>', { class: 'col' }).append(
        $('<article>', { class: `card dashboard-panel${position.isOpen ? '' : ' position-closed'}` }).append(
            $('<div>', { class: 'd-flex justify-content-between align-items-start gap-3 mb-1' }).append(
                $('<h3>', { class: 'fs-6 fw-semibold mb-0', text: position.name }),
                statusBadge(position),
            ),
            $('<div>', { class: 'text-body-secondary mb-2', text: position.department }),
            position.description
                ? $('<p>', { class: 'mb-2', text: position.description })
                : $('<p>', { class: 'text-body-secondary mb-2', text: 'No description is available.' }),
            $actions,
        ),
    );
}

function renderPositions($root) {
    const positions = listPositions();
    const open = positions.filter((position) => position.isOpen);
    const closed = positions.filter((position) => !position.isOpen);

    $root.find('[data-positions-none]').toggleClass('d-none', positions.length > 0);
    $root.find('[data-positions-content]').toggleClass('d-none', positions.length === 0);
    $root.find('[data-positions-open-count]').text(open.length);
    $root.find('[data-positions-open]').empty().append(...open.map((position) => positionCard($root, position)));
    $root.find('[data-positions-open-empty]').toggleClass('d-none', open.length > 0);
    $root.find('[data-positions-closed]').empty().append(...closed.map((position) => positionCard($root, position)));
    $root.find('[data-positions-closed-section]').toggleClass('d-none', closed.length === 0);
}

// The details modal is filled in when it opens, from the position's trigger (data-position-id).
function initDetailsModal($root, $modal) {
    $modal.on('show.bs.modal', function (event) {
        const position = listPositions().find((candidate) => candidate.id === $(event.relatedTarget).attr('data-position-id'));

        if (!position) {
            event.preventDefault();

            return;
        }

        $modal.find('[data-position-field="name"]').text(position.name);
        $modal.find('[data-position-field="department"]').text(position.department);
        $modal.find('[data-position-status]').empty().append(statusBadge(position));

        $modal.find('[data-position-field="description"]')
            .text(position.description || 'No description is available.')
            .toggleClass('text-body-secondary', !position.description);

        $modal.find('[data-position-qualifications]').empty().append(
            ...position.qualifications.map((qualification) => $('<li>', { text: qualification })),
        );
        $modal.find('[data-position-qualifications-empty]').toggleClass('d-none', position.qualifications.length > 0);

        $modal.find('[data-position-benefits]').empty().append(
            ...(position.benefits.length
                ? position.benefits.map((benefit) => $('<span>', { class: 'ems-chip', text: benefit }))
                : [$('<span>', { class: 'text-body-secondary', text: 'No benefits are listed.' })]),
        );

        // Only an open position can be applied for.
        $modal.find('[data-position-apply]').toggleClass('d-none', !position.isOpen).attr('href', applyUrl($root, position));
        $modal.find('[data-position-closed]').toggleClass('d-none', position.isOpen);
    });
}

$(function () {
    const $positions = $('[data-page="applicant-positions"]');

    if (!$positions.length) {
        return;
    }

    initDetailsModal($positions, $('[data-position-details-modal]'));
    renderPositions($positions);
    onMockDataChange(() => renderPositions($positions));
});
