import $ from './jquery.js';
import {
    buildCalendarEvents,
    confirmedStatuses,
    eventTypes,
    eventsInMonth,
    eventsOn,
    filterEvents,
    isoDateOf,
    monthGrid,
} from './calendar-events.js';
import { formatDate, formatTime } from './format.js';
import { approveLeave, getPortalLeave, listApplications, listLeaveRequests, onMockDataChange } from './mock-data.js';
import { showToast } from './toast.js';

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const weekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Events shown in a day cell before "+N more".
const visiblePerDay = 3;

const today = () => isoDateOf(new Date());

function longDate(isoDate) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return `${weekdayNames[new Date(year, month - 1, day).getDay()]}, ${formatDate(isoDate)}`;
}

function dayCount(startDate, endDate) {
    const [startYear, startMonth, startDay] = startDate.split('-').map(Number);
    const [endYear, endMonth, endDay] = endDate.split('-').map(Number);

    return Math.round((new Date(endYear, endMonth - 1, endDay) - new Date(startYear, startMonth - 1, startDay)) / 86400000) + 1;
}

function dateText(event) {
    if (event.startDate === event.endDate) {
        return formatDate(event.startDate);
    }

    const days = dayCount(event.startDate, event.endDate);

    return `${formatDate(event.startDate)} to ${formatDate(event.endDate)} (${days} days)`;
}

function styleClass(event) {
    return `type-${event.type} ${confirmedStatuses.includes(event.status) ? 'is-confirmed' : 'is-tentative'}`;
}

function statusPill(status) {
    return $('<span>', { class: `status-pill calendar-status-${status.toLowerCase()}`, text: status });
}

function eventSummary(event, labels) {
    return `${labels[event.type]}: ${event.name}, ${event.status}${event.time ? `, ${formatTime(event.time)}` : ''}`;
}

/**
 * The Calendar page. State: the month shown, the selected date and event, and the selected event types
 * and statuses. Everything re-renders from the mock data, so changes on other pages (or tabs) show here.
 *
 * With data-calendar-scope="own" (the Employee interface) it shows only the signed-in employee's own leave,
 * named by leave type, with no actions: nothing on it changes a request's status.
 */
function initCalendar($root) {
    const isOwn = $root.attr('data-calendar-scope') === 'own';
    const labels = isOwn ? { ...eventTypes, leave: 'My Leave' } : eventTypes;
    const now = new Date();
    const state = {
        year: now.getFullYear(),
        month: now.getMonth(),
        selectedDate: today(),
        selectedEventId: null,
    };

    function selectedValues(selector) {
        return $root.find(`${selector}:checked`).map(function () {
            return String($(this).val());
        }).get();
    }

    function filters() {
        return {
            types: isOwn ? ['leave'] : selectedValues('[data-calendar-type-filter]'),
            statuses: selectedValues('[data-calendar-status-filter]'),
        };
    }

    function allEvents() {
        if (isOwn) {
            return buildCalendarEvents({
                leaveRequests: getPortalLeave().map((leave) => ({ ...leave, employeeName: leave.type })),
                applications: [],
            });
        }

        return buildCalendarEvents({ leaveRequests: listLeaveRequests(), applications: listApplications() });
    }

    function renderLegend({ types, statuses }) {
        const items = Object.entries(labels)
            .filter(([type]) => types.includes(type))
            .map(([type, label]) => $('<span>', { class: 'd-inline-flex align-items-center gap-2', role: 'listitem' }).append(
                $('<span>', { class: `calendar-swatch type-${type} is-confirmed`, 'aria-hidden': 'true' }),
                document.createTextNode(label),
            ));
        const hasConfirmed = statuses.some((status) => confirmedStatuses.includes(status));
        const hasTentative = statuses.some((status) => !confirmedStatuses.includes(status));

        if (hasConfirmed) {
            items.push($('<span>', { class: 'd-inline-flex align-items-center gap-2', role: 'listitem' }).append(
                $('<span>', { class: 'calendar-swatch type-neutral is-confirmed', 'aria-hidden': 'true' }),
                document.createTextNode(`Solid: ${statuses.filter((status) => confirmedStatuses.includes(status)).join(' or ')}`),
            ));
        }

        if (hasTentative) {
            items.push($('<span>', { class: 'd-inline-flex align-items-center gap-2', role: 'listitem' }).append(
                $('<span>', { class: 'calendar-swatch type-neutral is-tentative', 'aria-hidden': 'true' }),
                document.createTextNode(`Dashed: ${statuses.filter((status) => !confirmedStatuses.includes(status)).join(', ')}`),
            ));
        }

        $root.find('[data-calendar-legend]').empty().append(
            ...(items.length ? items : [$('<span>', { class: 'text-body-secondary', role: 'listitem', text: 'No event types or statuses selected.' })]),
        );
    }

    // When a whole kind of data is missing, say so rather than showing an empty calendar silently.
    function renderDataNotice(events) {
        const missing = [];

        if (!events.some((event) => event.type === 'leave')) {
            missing.push(isOwn ? 'You have no leave requests on record.' : 'No employee leave requests are on record.');
        }

        if (!isOwn && !events.some((event) => event.type !== 'leave')) {
            missing.push('No applicant screenings or interviews are on record.');
        }

        $root.find('[data-calendar-data-notice]').text(missing.join(' ')).toggleClass('d-none', !missing.length);
    }

    function renderMonth(events, shown) {
        const monthEvents = eventsInMonth(events, state.year, state.month);
        const shownInMonth = eventsInMonth(shown, state.year, state.month);
        let notice = '';

        if (!monthEvents.length) {
            notice = isOwn ? 'You have no leave this month.' : 'No events scheduled for this month.';
        } else if (!shownInMonth.length) {
            notice = 'No events match your selected filters.';
        }

        $root.find('[data-calendar-month-title]').text(`${monthNames[state.month]} ${state.year}`);
        $root.find('[data-calendar-month-empty]').text(notice).toggleClass('d-none', !notice);

        $root.find('[data-calendar-grid]').empty().append(...monthGrid(state.year, state.month).flat().map(({ date, inMonth }) => {
            const dayEvents = eventsOn(shown, date);
            const isToday = date === today();
            const isSelected = date === state.selectedDate;
            const $cell = $('<div>', {
                class: `calendar-day${inMonth ? '' : ' is-outside'}${isToday ? ' is-today' : ''}${isSelected ? ' is-selected' : ''}`,
                'data-calendar-day': date,
            });
            const countText = dayEvents.length ? `, ${dayEvents.length} ${dayEvents.length === 1 ? 'event' : 'events'}` : ', no events';

            $cell.append($('<button>', {
                type: 'button',
                class: 'calendar-day-number',
                text: Number(date.slice(8)),
                'data-calendar-date': date,
                'aria-label': `${longDate(date)}${isToday ? ', today' : ''}${countText}`,
                'aria-pressed': String(isSelected),
                'aria-current': isToday ? 'date' : null,
            }));

            const $events = $('<div>', { class: 'calendar-day-events' }).append(...dayEvents.slice(0, visiblePerDay).map((event) => (
                $('<button>', {
                    type: 'button',
                    class: `calendar-event ${styleClass(event)}${event.id === state.selectedEventId ? ' is-active' : ''}`,
                    'data-calendar-event': event.id,
                    'data-calendar-event-date': date,
                    'aria-label': eventSummary(event, labels),
                    title: eventSummary(event, labels),
                }).append($('<span>', { class: 'calendar-event-label', text: event.time ? `${formatTime(event.time)} ${event.name}` : event.name }))
            )));

            if (dayEvents.length > visiblePerDay) {
                $events.append($('<button>', {
                    type: 'button',
                    class: 'calendar-more',
                    text: `+${dayEvents.length - visiblePerDay} more`,
                    'data-calendar-date': date,
                    'aria-label': `Show all ${dayEvents.length} events on ${longDate(date)}`,
                }));
            }

            return $cell.append($events);
        }));
    }

    function renderDay(events, shown) {
        const all = eventsOn(events, state.selectedDate);
        const matching = eventsOn(shown, state.selectedDate);

        $root.find('[data-calendar-day-title]').text(longDate(state.selectedDate));
        $root.find('[data-calendar-day-events]').empty().append(...matching.map((event) => (
            $('<button>', {
                type: 'button',
                class: `list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2${event.id === state.selectedEventId ? ' active' : ''}`,
                'data-calendar-event': event.id,
                'data-calendar-event-date': state.selectedDate,
                'aria-current': event.id === state.selectedEventId ? 'true' : null,
            }).append(
                $('<span>', { class: 'd-flex align-items-center gap-2 min-w-0' }).append(
                    $('<span>', { class: `calendar-swatch ${styleClass(event)}`, 'aria-hidden': 'true' }),
                    $('<span>', { class: 'min-w-0' }).append(
                        $('<span>', { class: 'd-block fw-medium text-truncate', text: event.name }),
                        $('<small>', { class: 'd-block opacity-75', text: `${labels[event.type]}${event.time ? ` · ${formatTime(event.time)}` : ''}` }),
                    ),
                ),
                statusPill(event.status),
            )
        )));

        let empty = '';

        if (!all.length) {
            empty = isOwn ? 'You have no leave on this date.' : 'No events scheduled for this date.';
        } else if (!matching.length) {
            empty = 'No events match your selected filters.';
        }

        $root.find('[data-calendar-day-empty]').text(empty).toggleClass('d-none', !empty);
    }

    function detailRow(label, value) {
        return $('<div>', { class: 'col' }).append($('<dt>', { text: label }), $('<dd>', { class: 'mb-0' }).append(value));
    }

    function renderDetails(shown) {
        const event = shown.find((candidate) => candidate.id === state.selectedEventId);
        const $details = $root.find('[data-calendar-details]').empty();

        $root.find('[data-calendar-details-empty]').toggleClass('d-none', Boolean(event));

        if (!event) {
            return;
        }

        // The employee's own leave: its type, dates, status, and when it was filed. No actions.
        if (isOwn) {
            $details.append($('<dl>', { class: 'row row-cols-1 row-cols-sm-2 row-cols-xl-1 g-2 mb-0 employee-details' }).append(
                detailRow('Leave Type', event.detail.type),
                detailRow('Date', dateText(event)),
                detailRow('Status', statusPill(event.status)),
                detailRow('Filed On', formatDate(event.detail.filedOn)),
            ));

            return;
        }

        const rows = [
            detailRow(event.type === 'leave' ? 'Employee' : 'Applicant', event.name),
            detailRow('Event Type', eventTypes[event.type]),
            detailRow('Date', dateText(event)),
        ];

        if (event.time) {
            rows.push(detailRow('Time', formatTime(event.time)));
        }

        rows.push(detailRow('Status', statusPill(event.status)));

        const $actions = $('<div>', { class: 'd-flex flex-wrap gap-2 mt-3' });

        if (event.type === 'leave') {
            const leave = event.detail;

            rows.push(
                detailRow('Leave Type', leave.type),
                detailRow('Department', leave.department),
                detailRow('Filed On', formatDate(leave.filedOn)),
            );

            if (leave.status !== 'Approved') {
                $actions.append($('<button>', { type: 'button', class: 'btn btn-sm btn-primary', text: 'Approve Leave', 'data-calendar-approve': leave.id }));
            }

            $actions.append($('<a>', {
                class: 'btn btn-sm btn-outline-secondary',
                href: `${$root.attr('data-employees-url')}/${encodeURIComponent(leave.employeeId)}`,
                text: 'View 201 File',
            }));
        } else {
            const { application } = event.detail;

            rows.push(
                detailRow('Position', application.jobTitle),
                detailRow('Department', application.department),
                detailRow('Application Status', application.status),
            );
            $actions.append($('<a>', { class: 'btn btn-sm btn-outline-secondary', href: $root.attr('data-recruitment-url'), text: 'Open Recruitment' }));
        }

        $details.append($('<dl>', { class: 'row row-cols-1 row-cols-sm-2 row-cols-xl-1 g-2 mb-0 employee-details' }).append(...rows), $actions);
    }

    /**
     * Draws everything. A selected event that the filters now hide is deselected. Errors are shown
     * above the calendar instead of leaving it half drawn.
     */
    function render() {
        try {
            const activeFilters = filters();
            const events = allEvents();
            const shown = filterEvents(events, activeFilters);

            if (state.selectedEventId && !shown.some((event) => event.id === state.selectedEventId)) {
                state.selectedEventId = null;
            }

            renderLegend(activeFilters);
            renderDataNotice(events);
            renderMonth(events, shown);
            renderDay(events, shown);
            renderDetails(shown);
            $root.find('[data-calendar-error]').addClass('d-none');
        } catch (error) {
            $root.find('[data-calendar-error]')
                .text(`The calendar could not be updated. ${error instanceof Error ? error.message : ''}`.trim())
                .removeClass('d-none');
        }
    }

    function showMonth(year, month) {
        const first = new Date(year, month, 1);

        state.year = first.getFullYear();
        state.month = first.getMonth();
        state.selectedDate = state.year === new Date().getFullYear() && state.month === new Date().getMonth() ? today() : isoDateOf(first);
        state.selectedEventId = null;
        render();
    }

    // Clicking a date (or empty space in its cell) selects it; an event outside this month moves there too.
    function selectDate(date) {
        const [year, month] = date.split('-').map(Number);

        state.year = year;
        state.month = month - 1;
        state.selectedDate = date;
        state.selectedEventId = null;
        render();
    }

    $root.on('click', '[data-calendar-nav]', function () {
        showMonth(state.year, state.month + Number($(this).attr('data-calendar-nav')));
    });

    // Back to the current month and today; the filters stay as they are.
    $root.on('click', '[data-calendar-today]', () => showMonth(new Date().getFullYear(), new Date().getMonth()));

    $root.on('click', '[data-calendar-day]', function (event) {
        if (!$(event.target).closest('button').length) {
            selectDate($(this).attr('data-calendar-day'));
        }
    });

    $root.on('click', '[data-calendar-date]', function () {
        selectDate($(this).attr('data-calendar-date'));
        $root.find(`[data-calendar-date="${state.selectedDate}"].calendar-day-number`).trigger('focus');
    });

    $root.on('click', '[data-calendar-event]', function () {
        const date = $(this).attr('data-calendar-event-date');
        const [year, month] = date.split('-').map(Number);

        Object.assign(state, { year, month: month - 1, selectedDate: date, selectedEventId: $(this).attr('data-calendar-event') });
        render();
        $root.find('[data-calendar-details]').closest('section').find('h2').attr('tabindex', '-1').trigger('focus');
    });

    $root.on('change', '[data-calendar-type-filter], [data-calendar-status-filter]', render);

    $root.on('click', '[data-calendar-clear]', function () {
        $root.find('[data-calendar-type-filter], [data-calendar-status-filter]').prop('checked', true);
        render();
    });

    $root.on('click', '[data-calendar-approve]', function () {
        const result = approveLeave($(this).attr('data-calendar-approve'));

        if (!result.ok) {
            $root.find('[data-calendar-details-error]').text(result.error).removeClass('d-none');

            return;
        }

        $root.find('[data-calendar-details-error]').addClass('d-none');
        showToast(`${result.leave.type} for ${result.leave.employeeName} approved.`);
    });

    onMockDataChange(render);
    render();
}

$(function () {
    const $calendar = $('[data-page="calendar"]');

    if ($calendar.length) {
        initCalendar($calendar);
    }
});
