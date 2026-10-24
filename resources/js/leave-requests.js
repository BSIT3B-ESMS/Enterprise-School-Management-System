import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { documentItem } from './employee-documents.js';
import { formatDate, formatDateTime } from './format.js';
import {
    approveLeave,
    cancelLeaveRequest,
    findLeaveRequest,
    getLeaveBalance,
    getPortalLeave,
    leaveStatuses,
    listLeaveRequests,
    onMockDataChange,
    rejectLeave,
} from './mock-data.js';
import { renderPagination } from './pagination.js';
import { showToast } from './toast.js';

// Leave Requests (HR) and Leave Request (the signed-in employee), both read from the one shared leave request
// collection in the mock data, so a request filed, approved, rejected, or cancelled anywhere shows the same everywhere
// (both calendars, both lists, the employee's dashboard and profile). Only HR approves or rejects, and only a Pending
// request; the employee can only cancel their own Pending request. Nothing changes a status because of dates or time.

const pageSize = 8;

const sortValue = {
    employeeName: (leave) => leave.employeeName.toLowerCase(),
    type: (leave) => leave.type.toLowerCase(),
    startDate: (leave) => leave.startDate,
    endDate: (leave) => leave.endDate,
    filedOn: (leave) => leave.filedOn,
    status: (leave) => leaveStatuses.indexOf(leave.status),
};

function plural(count, noun) {
    return `${count} ${noun}${count === 1 ? '' : 's'}`;
}

function dayCount(startDate, endDate) {
    const [startYear, startMonth, startDay] = startDate.split('-').map(Number);
    const [endYear, endMonth, endDay] = endDate.split('-').map(Number);

    return Math.round((new Date(endYear, endMonth - 1, endDay) - new Date(startYear, startMonth - 1, startDay)) / 86400000) + 1;
}

function dateRange(leave) {
    return leave.startDate === leave.endDate
        ? formatDate(leave.startDate)
        : `${formatDate(leave.startDate)} to ${formatDate(leave.endDate)}`;
}

function statusPill(status) {
    return $('<span>', { class: `status-pill calendar-status-${status.toLowerCase()}`, text: status });
}

function detailRow(label, value, wide = false) {
    return $('<div>', { class: wide ? 'col-12' : 'col' }).append($('<dt>', { text: label }), $('<dd>', { class: 'mb-0' }).append(value));
}

function muted(text) {
    return $('<span>', { class: 'text-body-secondary', text });
}

// Shows one modal after another is fully hidden, so two are never open at once.
function switchModal(fromElement, toElement) {
    const open = fromElement && fromElement.classList.contains('show') ? fromElement : document.querySelector('.modal.show');

    if (open && open !== toElement) {
        $(open).one('hidden.bs.modal', () => Modal.getOrCreateInstance(toElement).show());
        Modal.getOrCreateInstance(open).hide();

        return;
    }

    Modal.getOrCreateInstance(toElement).show();
}

function initLeaveRequests($section) {
    const isHr = $section.attr('data-leave-requests') === 'hr';
    const detailsModal = document.querySelector('[data-leave-details-modal]');
    const decisionModal = document.querySelector('[data-leave-decision-modal]');
    const cancelModal = document.querySelector('[data-leave-cancel-modal]');
    const view = { query: '', status: '', type: '', sortKey: 'filedOn', sortDirection: -1, page: 1 };
    // The request open in the details modal, and the decision being confirmed ({ id, mode: 'approve'|'reject', step }).
    let detailsId = '';
    let decision = null;
    let cancellingId = '';
    let isSaving = false;

    // HR sees every request; the employee only their own, matched by their employee ID (never by name).
    const allRequests = () => (isHr ? listLeaveRequests() : getPortalLeave());
    const requestById = (id) => (isHr ? findLeaveRequest(id) : getPortalLeave().find((leave) => leave.id === id));

    function visibleRequests() {
        const query = view.query.trim().toLowerCase();
        const valueOf = sortValue[view.sortKey];

        return allRequests()
            .filter((leave) => !query || [leave.employeeName ?? '', leave.employeeId, leave.type, leave.reason, leave.hrRemarks, leave.status]
                .join(' ').toLowerCase().includes(query))
            .filter((leave) => !view.status || leave.status === view.status)
            .filter((leave) => !view.type || leave.type === view.type)
            .sort((first, second) => {
                const firstValue = valueOf(first);
                const secondValue = valueOf(second);
                const order = firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0;

                return order * view.sortDirection || second.filedOn.localeCompare(first.filedOn);
            });
    }

    function rowFor(leave) {
        const cells = [];

        if (isHr) {
            cells.push($('<td>').append(
                $('<span>', { class: 'd-block fw-medium', text: leave.employeeName }),
                $('<small>', { class: 'd-block text-body-secondary', text: `${leave.employeeId} · ${leave.jobTitle}` }),
            ));
        }

        cells.push(
            $('<td>', { text: leave.type }),
            $('<td>', { class: 'text-nowrap', text: formatDate(leave.startDate) }),
            $('<td>', { class: 'text-nowrap', text: formatDate(leave.endDate) }),
            $('<td>', { class: 'text-nowrap', text: formatDate(leave.filedOn) }),
            $('<td>').append(statusPill(leave.status)),
        );

        if (!isHr) {
            cells.push($('<td>').append(leave.hrRemarks
                ? $('<span>', { class: 'd-inline-block text-truncate', style: 'max-width: 16rem', text: leave.hrRemarks, title: leave.hrRemarks })
                : muted('—')));
        }

        return $('<tr>', {
            class: 'employee-row',
            tabindex: 0,
            'aria-haspopup': 'dialog',
            title: `View ${isHr ? `${leave.employeeName}’s ` : ''}${leave.type.toLowerCase()} request`,
            'data-leave-row': leave.id,
        }).append(...cells);
    }

    // The employee's leave credits for this year, from their job title; only Approved leave counts as used.
    function renderBalance() {
        const balance = getLeaveBalance();
        const days = (count) => `${count} ${Math.abs(count) === 1 ? 'day' : 'days'}`;

        $section.find('[data-leave-balance]').toggleClass('d-none', balance.credits === null);
        $section.find('[data-leave-balance-field="credits"]').text(balance.credits === null ? '' : `${days(balance.credits)} (${balance.year})`);
        $section.find('[data-leave-balance-field="used"]').text(days(balance.used));
        $section.find('[data-leave-balance-field="remaining"]').text(balance.remaining === null ? '' : days(balance.remaining));
    }

    function renderTable() {
        if (!isHr) {
            renderBalance();
        }

        const requests = visibleRequests();
        const total = allRequests().length;
        const pageCount = Math.max(1, Math.ceil(requests.length / pageSize));

        view.page = Math.min(view.page, pageCount);

        const start = (view.page - 1) * pageSize;

        $section.find('[data-leave-sort-column]').each(function () {
            const isSorted = $(this).attr('data-leave-sort-column') === view.sortKey;

            $(this).attr('aria-sort', isSorted ? (view.sortDirection > 0 ? 'ascending' : 'descending') : 'none');
        });

        $section.find('[data-leave-rows]').empty().append(...requests.slice(start, start + pageSize).map(rowFor));

        // Nothing on record at all, or nothing that matches the search and filters.
        if (!requests.length) {
            $section.find('[data-leave-empty-title]').text(total ? 'No leave requests match' : 'No leave requests yet');
            $section.find('[data-leave-empty-hint]').text(total
                ? 'Nothing matches the current search or filters. Try a different search or clear the filters.'
                : (isHr ? 'Requests employees file appear here.' : 'Use Request Leave above to file one. It stays Pending until HR decides.'));
        }

        $section.find('[data-leave-empty]').toggleClass('d-none', requests.length > 0);
        $section.find('[data-leave-summary]').text(requests.length
            ? `Showing ${start + 1} to ${Math.min(start + pageSize, requests.length)} of ${plural(requests.length, 'request')}`
            : `0 of ${plural(total, 'request')}`);
        renderPagination($section.find('[data-leave-pagination]'), view.page, pageCount);
    }

    // ---------------------------------------------------------------- details

    function renderDetails() {
        const leave = requestById(detailsId);
        const $modal = $(detailsModal);
        const $actions = $modal.find('[data-leave-details-actions]').empty();

        $modal.find('[data-leave-details-error]').addClass('d-none');

        if (!leave) {
            $modal.find('[data-leave-details-title]').text('Leave request');
            $modal.find('[data-leave-details-subtitle]').text('');
            $modal.find('[data-leave-details-body]').empty().append(muted('This leave request no longer exists.'));
            $actions.append($('<button>', { type: 'button', class: 'btn btn-outline-secondary', 'data-bs-dismiss': 'modal', text: 'Close' }));

            return;
        }

        const isPending = leave.status === 'Pending';
        const documents = leave.supportingDocuments ?? [];
        const rows = [];

        if (isHr) {
            rows.push(
                detailRow('Employee', `${leave.employeeName} (${leave.employeeId})`),
                detailRow('College', leave.department),
                detailRow('Job Title', leave.jobTitle),
            );
        }

        rows.push(
            detailRow('Leave Type', leave.type),
            detailRow('Status', statusPill(leave.status)),
            detailRow('Start Date', formatDate(leave.startDate)),
            detailRow('End Date', formatDate(leave.endDate)),
            detailRow('Number of Days', plural(dayCount(leave.startDate, leave.endDate), 'calendar day')),
            detailRow('Date Filed', formatDate(leave.filedOn)),
            detailRow('Reason', leave.reason || muted('No reason given'), true),
            detailRow('Supporting Documents', documents.length
                ? $('<ul>', { class: 'list-group document-list' }).append(...documents.map((file) => documentItem(file)))
                : muted('None attached'), true),
        );

        // HR's decision: the remarks (the rejection reason, for a rejected request), who decided, and when.
        if (leave.status === 'Approved' || leave.status === 'Rejected') {
            rows.push(
                detailRow(leave.status === 'Rejected' ? 'Rejection Reason' : 'HR Remarks', leave.hrRemarks || muted('No remarks'), true),
                detailRow(leave.status === 'Rejected' ? 'Rejected By' : 'Approved By', leave.reviewedBy || muted('Not recorded')),
                detailRow(leave.status === 'Rejected' ? 'Rejected On' : 'Approved On', leave.reviewedAt ? formatDateTime(new Date(leave.reviewedAt)) : muted('Not recorded')),
            );
        }

        if (leave.status === 'Cancelled') {
            rows.push(detailRow('Cancelled On', leave.cancelledAt ? formatDateTime(new Date(leave.cancelledAt)) : muted('Not recorded')));
        }

        $modal.find('[data-leave-details-title]').text(isHr ? `${leave.type}: ${leave.employeeName}` : leave.type);
        $modal.find('[data-leave-details-subtitle]').text(dateRange(leave));
        $modal.find('[data-leave-details-body]').empty().append(
            $('<dl>', { class: 'row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details' }).append(...rows),
            isPending
                ? $('<p>', { class: 'small text-body-secondary mt-3 mb-0', text: isHr ? 'Waiting for your decision.' : 'Waiting for HR to approve or reject it.' })
                : null,
        );

        if (isHr) {
            $actions.append(
                $('<a>', { class: 'btn btn-outline-secondary me-auto', href: `${$section.attr('data-employees-url')}/${encodeURIComponent(leave.employeeId)}`, text: 'View 201 File' }),
                $('<button>', { type: 'button', class: 'btn btn-outline-secondary', 'data-bs-dismiss': 'modal', text: 'Close' }),
                isPending ? $('<button>', { type: 'button', class: 'btn btn-outline-danger', text: 'Reject', 'data-leave-reject': leave.id }) : null,
                isPending ? $('<button>', { type: 'button', class: 'btn btn-primary', text: 'Approve', 'data-leave-approve': leave.id }) : null,
            );
        } else {
            $actions.append(
                isPending ? $('<button>', { type: 'button', class: 'btn btn-outline-danger me-auto', text: 'Cancel Request', 'data-leave-cancel': leave.id }) : null,
                $('<button>', { type: 'button', class: 'btn btn-outline-secondary', 'data-bs-dismiss': 'modal', text: 'Close' }),
            );
        }
    }

    function openDetails(id) {
        detailsId = id;
        renderDetails();
        switchModal(null, detailsModal);
    }

    // ---------------------------------------------------------------- HR: approve or reject

    function showDecisionError(message) {
        $(decisionModal).find('[data-leave-decision-error]').text(message).toggleClass('d-none', !message);
    }

    function showRemarksError(message) {
        const $field = $(decisionModal).find('[name="remarks"]');

        $(decisionModal).find('#leave-decision-remarks-error').text(message);
        $field.toggleClass('is-invalid', Boolean(message)).attr('aria-invalid', message ? 'true' : 'false');
    }

    function renderDecisionStep() {
        const $modal = $(decisionModal);
        const leave = findLeaveRequest(decision.id);
        const isReject = decision.mode === 'reject';
        const isConfirming = decision.step === 'confirm';

        $modal.find('[data-leave-decision-title]').text(isReject ? 'Reject Leave Request' : 'Approve Leave Request');
        $modal.find('[data-leave-decision-summary]').text(leave
            ? `${leave.employeeName}: ${leave.type}, ${dateRange(leave)} (${plural(dayCount(leave.startDate, leave.endDate), 'day')}).`
            : 'This leave request no longer exists.');
        $modal.find('[data-leave-decision-label]').text(isReject ? 'Reason for Rejection' : 'Remarks (optional)')
            .toggleClass('is-required', isReject);
        $modal.find('[data-leave-decision-hint]').text(isReject
            ? 'Required. The employee sees this reason with the request.'
            : 'Optional. The employee sees these remarks with the request.');
        $modal.find('[data-leave-decision-step="enter"]').toggleClass('d-none', isConfirming);
        $modal.find('[data-leave-decision-step="confirm"]').toggleClass('d-none', !isConfirming);
        $modal.find('[data-leave-decision-confirm-text]').text(leave
            ? `${leave.employeeName} will see the request as Rejected, with your reason: “${String($modal.find('[name="remarks"]').val()).trim()}”. This cannot be undone.`
            : '');
        $modal.find('[data-leave-decision-back]').toggleClass('d-none', !isConfirming);
        $modal.find('[data-leave-decision-submit]')
            .text(isReject ? (isConfirming ? 'Confirm Rejection' : 'Reject Request') : 'Approve Request')
            .removeClass('btn-primary btn-danger')
            .addClass(isReject ? 'btn-danger' : 'btn-primary');
    }

    function openDecision(id, mode) {
        const leave = findLeaveRequest(id);

        if (!leave || leave.status !== 'Pending') {
            showToast(leave ? `This leave request is already ${leave.status.toLowerCase()}.` : 'That leave request no longer exists.', { isError: true });

            return;
        }

        decision = { id, mode, step: 'enter' };
        isSaving = false;
        $(decisionModal).find('[data-leave-decision-form]')[0].reset();
        showRemarksError('');
        showDecisionError('');
        renderDecisionStep();
        switchModal(detailsModal, decisionModal);
    }

    function submitDecision() {
        if (isSaving || !decision) {
            return;
        }

        const remarks = String($(decisionModal).find('[name="remarks"]').val());

        // Rejecting asks for the reason first, then for confirmation; the reason is checked before the second step.
        if (decision.mode === 'reject' && decision.step === 'enter') {
            if (!remarks.trim()) {
                showRemarksError('Enter the reason for rejecting this request.');
                $(decisionModal).find('[name="remarks"]').trigger('focus');

                return;
            }

            showRemarksError('');
            decision.step = 'confirm';
            renderDecisionStep();
            $(decisionModal).find('[data-leave-decision-submit]').trigger('focus');

            return;
        }

        isSaving = true;

        const result = decision.mode === 'reject'
            ? rejectLeave(decision.id, { reason: remarks })
            : approveLeave(decision.id, { remarks });

        if (!result.ok) {
            isSaving = false;

            const fieldError = result.errors?.reason ?? result.errors?.remarks ?? '';

            if (fieldError) {
                decision.step = 'enter';
                renderDecisionStep();
                showRemarksError(fieldError);
                $(decisionModal).find('[name="remarks"]').trigger('focus');
            } else {
                showDecisionError(result.error);
            }

            return;
        }

        const { leave } = result;

        decision = null;
        Modal.getOrCreateInstance(decisionModal).hide();
        showToast(`${leave.type} for ${leave.employeeName} (${dateRange(leave)}) ${leave.status === 'Rejected' ? 'rejected' : 'approved'}.`);
    }

    // ---------------------------------------------------------------- employee: cancel

    function openCancel(id) {
        const leave = requestById(id);

        if (!leave || leave.status !== 'Pending') {
            showToast(leave ? `This leave request is already ${leave.status.toLowerCase()}.` : 'That leave request no longer exists.', { isError: true });

            return;
        }

        cancellingId = id;
        isSaving = false;
        $(cancelModal).find('[data-leave-cancel-error]').addClass('d-none');
        $(cancelModal).find('[data-leave-cancel-message]').text(
            `Cancel your ${leave.type.toLowerCase()} request for ${dateRange(leave)}? It stays on the Leave Request page as Cancelled, and HR can no longer approve it.`,
        );
        switchModal(detailsModal, cancelModal);
    }

    // ---------------------------------------------------------------- events

    $section.on('input', '[data-leave-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderTable();
    });

    $section.on('change', '[data-leave-filter]', function () {
        view[$(this).attr('data-leave-filter')] = String($(this).val());
        view.page = 1;
        renderTable();
    });

    $section.on('click', '[data-leave-clear]', function () {
        Object.assign(view, { query: '', status: '', type: '', page: 1 });
        $section.find('[data-leave-search], [data-leave-filter]').val('');
        renderTable();
    });

    $section.on('click', '[data-leave-sort-key]', function () {
        const key = $(this).attr('data-leave-sort-key');

        view.sortDirection = view.sortKey === key ? -view.sortDirection : 1;
        view.sortKey = key;
        view.page = 1;
        renderTable();
    });

    $section.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable();
    });

    // A row, or Enter/Space on it, opens the details, where Approve and Reject (HR) or Cancel (the employee) are offered
    // for a Pending request; there is no Actions column.
    $section.on('click', 'tr[data-leave-row]', function (event) {
        if (!$(event.target).closest('button, a').length) {
            openDetails($(this).attr('data-leave-row'));
        }
    });

    $section.on('keydown', 'tr[data-leave-row]', function (event) {
        if ((event.key === 'Enter' || event.key === ' ') && event.target === this) {
            event.preventDefault();
            openDetails($(this).attr('data-leave-row'));
        }
    });

    // View on a row, on the calendar's leave details, or anywhere else on the page.
    $(document).on('click', '[data-leave-view]', function () {
        openDetails($(this).attr('data-leave-view'));
    });

    if (isHr) {
        $(document).on('click', '[data-leave-approve]', function () {
            openDecision($(this).attr('data-leave-approve'), 'approve');
        });

        $(document).on('click', '[data-leave-reject]', function () {
            openDecision($(this).attr('data-leave-reject'), 'reject');
        });

        $(decisionModal).on('submit', '[data-leave-decision-form]', function (event) {
            event.preventDefault();
            submitDecision();
        });

        $(decisionModal).on('click', '[data-leave-decision-back]', function () {
            decision.step = 'enter';
            renderDecisionStep();
            $(decisionModal).find('[name="remarks"]').trigger('focus');
        });

        $(decisionModal).on('input', '[name="remarks"]', function () {
            if ($(this).hasClass('is-invalid') && String($(this).val()).trim()) {
                showRemarksError('');
            }
        });

        $(decisionModal).on('shown.bs.modal', () => $(decisionModal).find('[name="remarks"]').trigger('focus'));
    } else {
        $(document).on('click', '[data-leave-cancel]', function () {
            openCancel($(this).attr('data-leave-cancel'));
        });

        $(cancelModal).on('click', '[data-leave-cancel-confirm]', function () {
            if (isSaving || !cancellingId) {
                return;
            }

            isSaving = true;

            const result = cancelLeaveRequest(cancellingId);

            if (!result.ok) {
                isSaving = false;
                $(cancelModal).find('[data-leave-cancel-error]').text(result.error).removeClass('d-none');

                return;
            }

            cancellingId = '';
            Modal.getOrCreateInstance(cancelModal).hide();
            showToast(`Your ${result.leave.type.toLowerCase()} request for ${dateRange(result.leave)} was cancelled.`);
        });
    }

    // Changes from anywhere (this page, the calendar, another tab) redraw the list and an open details modal.
    onMockDataChange(() => {
        renderTable();

        if (detailsModal.classList.contains('show')) {
            renderDetails();
        }
    });

    renderTable();

    // Arriving from a calendar's View Request / Review Request opens that request's details (?request=lv…).
    const requestedId = new URLSearchParams(window.location.search).get('request');

    if (requestedId && requestById(requestedId)) {
        openDetails(requestedId);
    }
}

$(function () {
    const $section = $('[data-leave-requests]');

    if ($section.length) {
        initLeaveRequests($section.first());
    }
});
