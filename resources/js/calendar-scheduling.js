import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { formatDate, formatDateTime, formatTime } from './format.js';
import { eventTypes, isoDateOf } from './calendar-events.js';
import {
    addCalendarEvent,
    calendarEventTypes,
    cancelActivity,
    findApplication,
    interviewTypeLabel,
    interviewTypes,
    listUnscheduledApplicants,
    onMockDataChange,
    rescheduleActivity,
    rescheduleCalendarEvent,
    rescheduleReasons,
    scheduleActivity,
    updateCalendarEvent,
    validateCalendarEvent,
    validateCalendarSchedule,
    validateEventReschedule,
    validateRescheduleSlot,
} from './mock-data.js';
import { showToast } from './toast.js';

// The HR Calendar's interaction: a date opens its events (with notes), an event opens its details (venue, notes,
// rescheduling history, Reschedule and Cancel Schedule), and Unscheduled Applicants opens the scheduling form. Every
// change goes through one confirmation modal: Confirm applies it, Go Back returns to the form exactly as it was left,
// and Cancel closes everything without changing anything. All data comes from the shared mock data, so the calendar,
// the panel, the event details, and their history stay in step (also with Recruitment and the applicant's pages).

const weekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const today = () => isoDateOf(new Date());

function longDate(isoDate) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return `${weekdayNames[new Date(year, month - 1, day).getDay()]}, ${formatDate(isoDate)}`;
}

function timeRange(time, endTime) {
    return endTime ? `${formatTime(time)} to ${formatTime(endTime)}` : formatTime(time);
}

function scheduleText(date, time, endTime) {
    return `${longDate(date)}, ${timeRange(time, endTime)}`;
}

function statusPill(status) {
    return $('<span>', { class: `status-pill calendar-status-${status.toLowerCase()}`, text: status });
}

function detailRow(label, value, wide = false) {
    return $('<div>', { class: wide ? 'col-12' : 'col' }).append($('<dt>', { text: label }), $('<dd>', { class: 'mb-0' }).append(value));
}

function reasonText(entry) {
    if (!entry.reason) {
        return 'No reason given';
    }

    return entry.reason === 'Other' ? `Other: ${entry.reasonDetail}` : entry.reason;
}

/**
 * @param {JQuery} $root The calendar page.
 * @param {{ eventsOn: (date: string) => { shown: object[], all: object[] }, findEvent: (id: string) => object|undefined, goToDate: (date: string) => void }} calendar
 */
export function initCalendarScheduling($root, calendar) {
    const dayModal = $('[data-calendar-day-modal]')[0];
    const eventModal = $('[data-calendar-event-modal]')[0];
    const scheduleModal = $('[data-calendar-schedule-modal]')[0];
    const eventFormModal = $('[data-calendar-event-form-modal]')[0];
    const $eventForm = $(eventFormModal).find('[data-calendar-event-form]');
    const rescheduleModal = $('[data-calendar-reschedule-modal]')[0];
    const confirmModal = $('[data-calendar-confirm-modal]')[0];
    const $scheduleForm = $(scheduleModal).find('[data-calendar-schedule-form]');
    const $rescheduleForm = $(rescheduleModal).find('[data-calendar-reschedule-form]');
    const $confirm = $(confirmModal);

    const state = {
        dayDate: '',
        eventId: '',
        // The applicant the scheduling form is for: chosen in the panel (fixed) or in the form (from a date).
        scheduleApplicantId: '',
        scheduleApplicantFixed: false,
        reschedule: null,
        editingEventId: '',
        pending: null,
    };

    // ---------------------------------------------------------------- moving between modals

    let openModal = null;

    [dayModal, eventModal, scheduleModal, eventFormModal, rescheduleModal, confirmModal].forEach((modal) => {
        $(modal).on('shown.bs.modal', () => {
            openModal = modal;
        });
        $(modal).on('hidden.bs.modal', () => {
            if (openModal === modal) {
                openModal = null;
            }
        });
    });

    // Bootstrap shows one modal at a time, so the open one closes first.
    function switchTo(target, afterShown = null) {
        const from = openModal ?? document.querySelector('.modal.show');
        const show = () => {
            if (afterShown) {
                $(target).one('shown.bs.modal', afterShown);
            }

            Modal.getOrCreateInstance(target).show();
        };

        if (from && from !== target) {
            $(from).one('hidden.bs.modal', show);
            Modal.getOrCreateInstance(from).hide();
        } else if (from === target) {
            afterShown?.();
        } else {
            show();
        }
    }

    function closeAll() {
        const from = openModal ?? document.querySelector('.modal.show');

        if (from) {
            Modal.getOrCreateInstance(from).hide();
        }
    }

    // ---------------------------------------------------------------- Unscheduled Applicants

    function renderPanel() {
        const applicants = listUnscheduledApplicants();

        $root.find('[data-unscheduled-count]').text(applicants.length);
        $root.find('[data-unscheduled-empty]').toggleClass('d-none', applicants.length > 0);
        $root.find('[data-unscheduled-list]').empty().append(...applicants.map((applicant) => (
            $('<div>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3', 'data-unscheduled-item': applicant.id }).append(
                $('<div>', { class: 'min-w-0' }).append(
                    $('<div>', { class: 'fw-medium text-truncate', text: applicant.name }),
                    $('<small>', { class: 'd-block text-body-secondary text-truncate', text: `${applicant.jobTitle} · ${applicant.status}` }),
                    $('<small>', { class: 'd-block text-body-secondary', text: `Next: ${interviewTypeLabel(applicant.nextType)}` }),
                ),
                $('<button>', {
                    type: 'button',
                    class: 'btn btn-sm btn-outline-primary flex-shrink-0',
                    text: 'Schedule',
                    'aria-label': `Schedule ${applicant.name}`,
                    'data-unscheduled-schedule': applicant.id,
                }),
            )
        )));
    }

    $root.on('click', '[data-unscheduled-schedule]', function () {
        startSchedule({ applicationId: $(this).attr('data-unscheduled-schedule') });
    });

    // ---------------------------------------------------------------- a date's events

    function eventItem(event) {
        const custom = event.detail.custom;
        const activity = event.detail.activity;
        let subtitle;
        let notes = '';

        if (custom) {
            subtitle = `${eventTypes[event.type]} · ${timeRange(event.time, event.endTime)} · ${custom.venue}`;
            notes = custom.notes;
        } else if (event.type === 'leave') {
            subtitle = `${event.detail.type} · ${event.status}`;
        } else {
            subtitle = `${interviewTypeLabel(activity.type)} · ${timeRange(event.time, event.endTime)}${activity.venue ? ` · ${activity.venue}` : ''}`;
            notes = activity.notes;
        }

        return $('<button>', {
            type: 'button',
            class: 'list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-2',
            'data-day-event': event.id,
        }).append(
            $('<span>', { class: 'd-flex align-items-start gap-2 min-w-0' }).append(
                $('<span>', { class: `calendar-swatch type-${event.type} is-confirmed mt-1`, 'aria-hidden': 'true' }),
                $('<span>', { class: 'min-w-0' }).append(
                    $('<span>', { class: 'd-block fw-medium text-truncate', text: event.name }),
                    $('<small>', { class: 'd-block opacity-75', text: subtitle }),
                    notes ? $('<small>', { class: 'd-block text-body-secondary', text: `Notes: ${notes}` }) : null,
                ),
            ),
            statusPill(event.status),
        );
    }

    function renderDay() {
        const { shown, all } = calendar.eventsOn(state.dayDate);
        const canSchedule = state.dayDate >= today();
        const hasApplicants = listUnscheduledApplicants().length > 0;
        let empty = '';

        if (!all.length) {
            empty = 'No events on this date.';
        } else if (!shown.length) {
            empty = 'No events on this date match your selected filters.';
        }

        $(dayModal).find('[data-calendar-day-modal-title]').text(longDate(state.dayDate));
        $(dayModal).find('[data-calendar-day-modal-events]').empty().append(...shown.map(eventItem));
        $(dayModal).find('[data-calendar-day-modal-empty]').text(empty).toggleClass('d-none', !empty);
        $(dayModal).find('[data-calendar-day-modal-hidden]')
            .text(shown.length && shown.length < all.length ? `${all.length - shown.length} more hidden by the filters.` : '')
            .toggleClass('d-none', !(shown.length && shown.length < all.length));
        $(dayModal).find('[data-calendar-day-schedule]')
            .toggleClass('d-none', !canSchedule)
            .prop('disabled', !hasApplicants)
            .text(hasApplicants ? 'Schedule on This Date' : 'No Applicants to Schedule');
    }

    function openDay(date) {
        state.dayDate = date;
        renderDay();
        switchTo(dayModal);
    }

    // Clicking a date: its events when it has any (the modal offers Add Event), otherwise the event form for that date.
    function openDate(date) {
        if (calendar.eventsOn(date).all.length) {
            openDay(date);
        } else {
            startEventForm({ date });
        }
    }

    $(dayModal).on('click', '[data-calendar-day-add]', () => startEventForm({ date: state.dayDate }));
    $root.on('click', '[data-calendar-add-event]', () => startEventForm({}));

    $(dayModal).on('click', '[data-day-event]', function () {
        openEvent($(this).attr('data-day-event'));
    });

    $(dayModal).on('click', '[data-calendar-day-schedule]', () => startSchedule({ date: state.dayDate }));

    // ---------------------------------------------------------------- an event's details

    function historyList(activity) {
        const history = activity.history ?? [];

        if (!history.length) {
            return $('<p>', { class: 'text-body-secondary mb-0', text: 'This schedule has not been changed.' });
        }

        return $('<ol>', { class: 'list-group list-group-numbered list-group-flush' }).append(...[...history].reverse().map((entry) => (
            $('<li>', { class: 'list-group-item' }).append(
                $('<div>', { text: `From ${scheduleText(entry.fromDate, entry.fromTime, entry.fromEndTime)}` }),
                $('<div>', { text: `To ${scheduleText(entry.toDate, entry.toTime, entry.toEndTime)}` }),
                $('<small>', { class: 'd-block text-body-secondary', text: `Reason: ${reasonText(entry)} · Changed ${formatDateTime(new Date(entry.changedAt))}` }),
            )
        )));
    }

    function renderEvent() {
        const event = calendar.findEvent(state.eventId);
        const $modal = $(eventModal);
        const $body = $modal.find('[data-calendar-event-modal-body]').empty();
        const $actions = $modal.find('[data-calendar-event-modal-actions]').empty();

        if (!event) {
            return false;
        }

        $modal.find('[data-calendar-event-modal-error]').addClass('d-none');

        if (event.detail.custom) {
            const custom = event.detail.custom;
            const canReschedule = !['Completed', 'Approved'].includes(custom.status);

            $modal.find('[data-calendar-event-modal-title]').text(custom.title);
            $modal.find('[data-calendar-event-modal-subtitle]').text(eventTypes[event.type]);
            $body.append(
                $('<dl>', { class: 'row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details' }).append(
                    detailRow('Event Title', custom.title),
                    detailRow('Event Type', eventTypes[event.type]),
                    detailRow('Date', longDate(custom.date)),
                    detailRow('Time', timeRange(custom.time, custom.endTime)),
                    detailRow('Venue', custom.venue),
                    detailRow('Status', statusPill(custom.status)),
                    detailRow('Notes', $('<span>', { class: custom.notes ? 'calendar-notes' : 'text-body-secondary', text: custom.notes || 'No notes' }), true),
                ),
                $('<section>').append(
                    $('<h3>', { class: 'employee-form-section', text: 'Rescheduling History' }),
                    historyList(custom),
                ),
            );
            $actions.append(
                $('<button>', { type: 'button', class: 'btn btn-outline-secondary me-auto', 'data-bs-dismiss': 'modal', text: 'Close' }),
                $('<button>', { type: 'button', class: 'btn btn-outline-primary', text: 'Edit', 'data-event-edit': custom.id }),
                canReschedule ? $('<button>', { type: 'button', class: 'btn btn-primary', text: 'Reschedule', 'data-event-reschedule': '' }) : null,
            );

            return true;
        }

        if (event.type === 'leave') {
            const leave = event.detail;

            $modal.find('[data-calendar-event-modal-title]').text(`${leave.type}: ${leave.employeeName}`);
            $modal.find('[data-calendar-event-modal-subtitle]').text(`${leave.jobTitle} · ${leave.department}`);
            $body.append($('<dl>', { class: 'row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details' }).append(
                detailRow('Employee', leave.employeeName),
                detailRow('Leave Type', leave.type),
                detailRow('Dates', leave.startDate === leave.endDate ? longDate(leave.startDate) : `${formatDate(leave.startDate)} to ${formatDate(leave.endDate)}`),
                detailRow('Status', statusPill(leave.status)),
                detailRow('College', leave.department),
                detailRow('Date Filed', formatDate(leave.filedOn)),
                detailRow('Reason', leave.reason || 'No reason given', true),
                ...(leave.hrRemarks ? [detailRow('HR Remarks', leave.hrRemarks, true)] : []),
            ));
            // Leave is approved or rejected only on the Leave Requests page (resources/js/leave-requests.js), where rejecting
            // asks for a reason; this opens the request there.
            $actions.append(
                $('<a>', { class: 'btn btn-outline-secondary me-auto', href: `${$root.attr('data-employees-url')}/${encodeURIComponent(leave.employeeId)}`, text: 'View 201 File' }),
                $('<button>', { type: 'button', class: 'btn btn-outline-secondary', 'data-bs-dismiss': 'modal', text: 'Close' }),
                $('<a>', {
                    class: 'btn btn-primary',
                    href: `${$root.attr('data-leave-requests-url')}&request=${encodeURIComponent(leave.id)}`,
                    text: leave.status === 'Pending' ? 'Review Request' : 'View Request',
                }),
            );

            return true;
        }

        const { application, activity } = event.detail;
        const isScheduled = activity.status === 'Scheduled';

        $modal.find('[data-calendar-event-modal-title]').text(`${interviewTypeLabel(activity.type)}: ${application.name}`);
        $modal.find('[data-calendar-event-modal-subtitle]').text(`${application.jobTitle} · ${application.department}`);
        $body.append(
            $('<dl>', { class: 'row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details' }).append(
                detailRow('Applicant Name', application.name),
                detailRow('Interview Type', interviewTypeLabel(activity.type)),
                detailRow('Current Schedule', scheduleText(activity.date, activity.time, activity.endTime)),
                detailRow('Venue', activity.venue || 'Not set'),
                detailRow('Status', statusPill(activity.status)),
                detailRow('Application Status', application.status),
                detailRow('Notes', $('<span>', { class: activity.notes ? 'calendar-notes' : 'text-body-secondary', text: activity.notes || 'No notes' }), true),
            ),
            $('<section>').append(
                $('<h3>', { class: 'employee-form-section', text: 'Rescheduling History' }),
                historyList(activity),
            ),
        );
        $actions.append(
            // Opens Recruitment at this applicant (their application ID), highlighted in the list.
            $('<a>', {
                class: 'btn btn-outline-secondary me-auto',
                href: `${$root.attr('data-recruitment-url')}?applicant=${encodeURIComponent(application.id)}`,
                text: 'View Applicant in Recruitment',
                'data-event-view-applicant': application.id,
            }),
            isScheduled ? $('<button>', { type: 'button', class: 'btn btn-outline-danger', text: 'Cancel Schedule', 'data-event-cancel': '' }) : null,
            isScheduled ? $('<button>', { type: 'button', class: 'btn btn-primary', text: 'Reschedule', 'data-event-reschedule': '' }) : null,
            isScheduled ? null : $('<button>', { type: 'button', class: 'btn btn-outline-secondary', 'data-bs-dismiss': 'modal', text: 'Close' }),
        );

        return true;
    }

    function openEvent(eventId) {
        state.eventId = eventId;

        if (renderEvent()) {
            switchTo(eventModal);
        }
    }

    $(eventModal).on('click', '[data-event-edit]', function () {
        startEventForm({ eventId: $(this).attr('data-event-edit') });
    });

    $(eventModal).on('click', '[data-event-reschedule]', () => startReschedule());
    $(eventModal).on('click', '[data-event-cancel]', () => confirmCancelSchedule());

    // ---------------------------------------------------------------- forms: errors and values

    function showErrors($form, attribute, errors) {
        $form.find(`[${attribute}]`).each(function () {
            const key = $(this).attr(attribute);
            const message = errors[key] ?? '';
            const $field = key === 'applicationId' ? $form.find('[data-schedule-applicant-select]') : $form.find(`[name="${key}"]`);

            $(this).text(message).toggleClass('d-block', Boolean(message));
            $field.toggleClass('is-invalid', Boolean(message)).attr('aria-invalid', String(Boolean(message)));
        });

        // The readonly applicant name shows its error too (when the applicant was chosen in the panel).
        if (state.scheduleApplicantFixed) {
            $form.find('[data-schedule-applicant-name]').toggleClass('is-invalid', Boolean(errors.applicationId));
        }
    }

    function focusFirstError($form, attribute) {
        const $first = $form.find(`[${attribute}]`).filter(function () {
            return Boolean($(this).text());
        }).first();

        if ($first.length) {
            const key = $first.attr(attribute);

            (key === 'applicationId' ? $form.find('[data-schedule-applicant-select]:visible, [data-schedule-applicant-name]:visible') : $form.find(`[name="${key}"]`))
                .first().trigger('focus');
        }
    }

    // ---------------------------------------------------------------- scheduling

    function scheduleValues() {
        const value = (name) => String($scheduleForm.find(`[name="${name}"]`).val() ?? '');

        return {
            applicationId: state.scheduleApplicantFixed ? state.scheduleApplicantId : value('applicationId'),
            type: value('type'),
            date: value('date'),
            time: value('time'),
            endTime: value('endTime'),
            venue: value('venue'),
            notes: value('notes'),
        };
    }

    function applicantSummary(applicationId) {
        return listUnscheduledApplicants().find((applicant) => applicant.id === applicationId);
    }

    // The applicant's next step sets the interview type (Other stays available).
    function showApplicantHint(applicationId, { setType = false } = {}) {
        const applicant = applicantSummary(applicationId);

        $scheduleForm.find('[data-schedule-applicant-hint]').text(applicant
            ? `${applicant.jobTitle} · ${applicant.status} · Next step: ${interviewTypeLabel(applicant.nextType)}`
            : '');

        if (applicant && setType) {
            $scheduleForm.find('[name="type"]').val(applicant.nextType);
        }
    }

    function renderApplicantOptions() {
        const $select = $scheduleForm.find('[data-schedule-applicant-select]');
        const selected = String($select.val() ?? '');
        const applicants = listUnscheduledApplicants();

        $select.empty().append(
            $('<option>', { value: '', text: 'Select an applicant' }),
            ...applicants.map((applicant) => $('<option>', { value: applicant.id, text: `${applicant.name} · ${applicant.jobTitle}` })),
        );

        if (applicants.some((applicant) => applicant.id === selected)) {
            $select.val(selected);
        }
    }

    /**
     * Opens a fresh scheduling form: for an applicant chosen in the panel (shown read-only), or for a date (the
     * applicant is then chosen in the form).
     */
    function startSchedule({ applicationId = '', date = '' }) {
        const applicant = applicationId ? applicantSummary(applicationId) : null;

        if (applicationId && !applicant) {
            showToast('That applicant already has a schedule.', { isError: true });
            renderPanel();

            return;
        }

        state.scheduleApplicantId = applicationId;
        state.scheduleApplicantFixed = Boolean(applicationId);
        state.pending = null;
        $scheduleForm[0].reset();
        showErrors($scheduleForm, 'data-schedule-error', {});

        $scheduleForm.find('[name="type"]').empty().append(
            $('<option>', { value: '', text: 'Select interview type' }),
            ...interviewTypes.map((type) => $('<option>', { value: type.value, text: type.label })),
        );
        renderApplicantOptions();
        $scheduleForm.find('[data-schedule-applicant-name]')
            .toggleClass('d-none', !applicant)
            .val(applicant ? applicant.name : '');
        $scheduleForm.find('[data-schedule-applicant-select]').toggleClass('d-none', Boolean(applicant)).val('');
        $scheduleForm.find('[data-schedule-applicant-label]').attr('for', applicant ? 'calendar-schedule-applicant' : 'calendar-schedule-applicant-select');
        $scheduleForm.find('[name="date"]').attr('min', today()).val(date);
        showApplicantHint(applicationId, { setType: true });

        switchTo(scheduleModal, () => $scheduleForm.find(applicant ? '[name="type"]' : '[data-schedule-applicant-select]').trigger('focus'));
    }

    $scheduleForm.on('change', '[data-schedule-applicant-select]', function () {
        showApplicantHint(String($(this).val() ?? ''), { setType: true });
    });

    // Once a field has been flagged, re-check the form as it is corrected.
    $scheduleForm.on('input change', '.is-invalid, [name]', function () {
        if ($scheduleForm.find('.is-invalid').length) {
            showErrors($scheduleForm, 'data-schedule-error', validateCalendarSchedule(scheduleValues()));
        }
    });

    $scheduleForm.on('submit', function (event) {
        event.preventDefault();

        const values = scheduleValues();
        const errors = validateCalendarSchedule(values);

        showErrors($scheduleForm, 'data-schedule-error', errors);

        if (Object.keys(errors).length) {
            focusFirstError($scheduleForm, 'data-schedule-error');

            return;
        }

        const application = findApplication(values.applicationId);

        openConfirm({
            title: 'Confirm Schedule',
            intro: 'Check the schedule before adding it to the calendar.',
            rows: [
                ['Applicant', application.name],
                ['Interview Type', interviewTypeLabel(values.type)],
                ['Date', longDate(values.date)],
                ['Time', timeRange(values.time, values.endTime)],
                ['Venue', values.venue.trim()],
                ...(values.notes.trim() ? [['Notes', values.notes.trim()]] : []),
            ],
            okLabel: 'Confirm Schedule',
            backTo: scheduleModal,
            onConfirm: () => scheduleActivity(values.applicationId, values),
            onDone: (result) => {
                calendar.goToDate(values.date);
                showToast(`${interviewTypeLabel(values.type)} scheduled for ${result.application.name} on ${formatDate(values.date)}, ${timeRange(values.time, values.endTime)}.`);
            },
            formErrors: (result) => showErrors($scheduleForm, 'data-schedule-error', result.errors),
        });
    });

    // ---------------------------------------------------------------- adding and editing an event

    function eventFormValues() {
        const value = (name) => String($eventForm.find(`[name="${name}"]`).val() ?? '');

        return {
            title: value('title'),
            type: value('type'),
            status: value('status'),
            date: value('date'),
            time: value('time'),
            endTime: value('endTime'),
            venue: value('venue'),
            notes: value('notes'),
        };
    }

    // The statuses follow the type: leave is Pending or Approved; everything else is Scheduled or Completed.
    function renderStatusOptions(type, selected = '') {
        const kind = calendarEventTypes.find((candidate) => candidate.value === type);
        const $status = $eventForm.find('[name="status"]');

        $status.empty();

        if (!kind) {
            $status.append($('<option>', { value: '', text: 'Select an event type first' }));

            return;
        }

        $status.append(...kind.statuses.map((status) => $('<option>', { value: status, text: status })));
        $status.val(kind.statuses.includes(selected) ? selected : (type === 'leave' ? 'Pending' : 'Scheduled'));
    }

    /**
     * Opens the event form: blank (with the date prefilled when one was clicked), or filled in with an event HR added.
     */
    function startEventForm({ date = '', eventId = '' }) {
        const custom = eventId ? calendar.findEvent(`custom-${eventId}`)?.detail.custom : null;

        if (eventId && !custom) {
            showToast('That event no longer exists.', { isError: true });

            return;
        }

        state.editingEventId = custom ? custom.id : '';
        state.pending = null;
        $eventForm[0].reset();
        showErrors($eventForm, 'data-eventform-error', {});
        $(eventFormModal).find('[data-event-form-title]').text(custom ? 'Edit Event' : 'Add Event');
        $eventForm.find('[name="type"]').empty().append(
            $('<option>', { value: '', text: 'Select event type' }),
            ...calendarEventTypes.map((kind) => $('<option>', { value: kind.value, text: kind.label })),
        );

        if (custom) {
            ['title', 'type', 'date', 'time', 'endTime', 'venue', 'notes'].forEach((name) => $eventForm.find(`[name="${name}"]`).val(custom[name]));
            renderStatusOptions(custom.type, custom.status);
        } else {
            $eventForm.find('[name="date"]').val(date);
            renderStatusOptions('');
        }

        switchTo(eventFormModal, () => $eventForm.find('[name="title"]').trigger('focus'));
    }

    $eventForm.on('change', '[name="type"]', function () {
        renderStatusOptions(String($(this).val() ?? ''), String($eventForm.find('[name="status"]').val() ?? ''));
    });

    // Once the form has been flagged, re-check it as it is corrected.
    $eventForm.on('input change', '[name]', function () {
        if ($eventForm.find('.is-invalid').length) {
            showErrors($eventForm, 'data-eventform-error', validateCalendarEvent(eventFormValues(), state.editingEventId));
        }
    });

    $eventForm.on('submit', function (event) {
        event.preventDefault();

        const values = eventFormValues();
        const editingId = state.editingEventId;
        const errors = validateCalendarEvent(values, editingId);

        showErrors($eventForm, 'data-eventform-error', errors);

        if (Object.keys(errors).length) {
            focusFirstError($eventForm, 'data-eventform-error');

            return;
        }

        openConfirm({
            title: editingId ? 'Confirm Changes' : 'Confirm Event',
            intro: editingId ? 'Check the changes before saving them.' : 'Check the event before adding it to the calendar.',
            rows: [
                ['Event Title', values.title.trim()],
                ['Event Type', eventTypes[values.type]],
                ['Date', longDate(values.date)],
                ['Time', timeRange(values.time, values.endTime)],
                ['Venue', values.venue.trim()],
                ['Status', values.status],
                ...(values.notes.trim() ? [['Notes', values.notes.trim()]] : []),
            ],
            okLabel: 'Confirm',
            backTo: eventFormModal,
            onConfirm: () => (editingId ? updateCalendarEvent(editingId, values) : addCalendarEvent(values)),
            onDone: (result) => {
                calendar.goToDate(values.date);
                showToast(`Event ${editingId ? 'updated' : 'added'}: ${result.event.title} on ${formatDate(values.date)}, ${timeRange(values.time, values.endTime)}.`);
            },
            formErrors: (result) => showErrors($eventForm, 'data-eventform-error', result.errors),
        });
    });

    // ---------------------------------------------------------------- rescheduling

    function rescheduleValues() {
        const value = (name) => String($rescheduleForm.find(`[name="${name}"]`).val() ?? '');

        return { date: value('date'), time: value('time'), endTime: value('endTime'), reason: value('reason'), reasonDetail: value('reasonDetail') };
    }

    function toggleReasonDetail() {
        const isOther = $rescheduleForm.find('[name="reason"]').val() === 'Other';

        $rescheduleForm.find('[data-reschedule-detail-field]').toggleClass('d-none', !isOther);
        $rescheduleForm.find('[name="reasonDetail"]').prop('required', isOther);
    }

    // What the form is rescheduling: an applicant's interview, or an event HR added.
    function rescheduleTarget() {
        const event = calendar.findEvent(state.eventId);

        if (!event) {
            return null;
        }

        if (event.detail.custom) {
            const custom = event.detail.custom;

            return custom.status === 'Completed' || custom.status === 'Approved'
                ? null
                : {
                    custom,
                    who: `${custom.title} · ${eventTypes[event.type]}`,
                    date: custom.date,
                    time: custom.time,
                    endTime: custom.endTime,
                    venue: custom.venue,
                };
        }

        if (event.type === 'leave' || event.detail.activity.status !== 'Scheduled') {
            return null;
        }

        const { application, activity } = event.detail;

        return {
            application,
            activity,
            who: `${application.name} · ${interviewTypeLabel(activity.type)}`,
            date: activity.date,
            time: activity.time,
            endTime: activity.endTime ?? '',
            venue: activity.venue ?? '',
        };
    }

    function rescheduleErrorsFor(values) {
        const { reschedule } = state;

        return reschedule.eventId
            ? validateEventReschedule(reschedule.eventId, values)
            : validateRescheduleSlot(reschedule.applicationId, reschedule.activityId, values);
    }

    function startReschedule() {
        const target = rescheduleTarget();

        if (!target) {
            return;
        }

        state.reschedule = target.custom
            ? { eventId: target.custom.id }
            : { applicationId: target.application.id, activityId: target.activity.id };
        state.pending = null;
        $rescheduleForm[0].reset();
        showErrors($rescheduleForm, 'data-reschedule-error', {});
        $rescheduleForm.find('[data-reschedule-field="applicant"]').text(target.who);
        $rescheduleForm.find('[data-reschedule-field="current"]').text(`${scheduleText(target.date, target.time, target.endTime)}${target.venue ? ` · ${target.venue}` : ''}`);
        $rescheduleForm.find('[name="reason"]').empty().append(
            $('<option>', { value: '', text: 'Select a reason' }),
            ...rescheduleReasons.map((reason) => $('<option>', { value: reason, text: reason })),
        );
        $rescheduleForm.find('[name="date"]').attr('min', today()).val(target.date >= today() ? target.date : today());
        $rescheduleForm.find('[name="time"]').val(target.time);
        $rescheduleForm.find('[name="endTime"]').val(target.endTime);
        toggleReasonDetail();

        switchTo(rescheduleModal, () => $rescheduleForm.find('[name="date"]').trigger('focus'));
    }

    $rescheduleForm.on('change', '[name="reason"]', toggleReasonDetail);

    $rescheduleForm.on('input change', '[name]', function () {
        if ($rescheduleForm.find('.is-invalid').length && state.reschedule) {
            showErrors($rescheduleForm, 'data-reschedule-error', rescheduleErrorsFor(rescheduleValues()));
        }
    });

    $rescheduleForm.on('submit', function (event) {
        event.preventDefault();

        if (!state.reschedule) {
            return;
        }

        const { reschedule } = state;
        const values = rescheduleValues();
        const errors = rescheduleErrorsFor(values);

        showErrors($rescheduleForm, 'data-reschedule-error', errors);

        if (Object.keys(errors).length) {
            focusFirstError($rescheduleForm, 'data-reschedule-error');

            return;
        }

        const custom = reschedule.eventId ? calendar.findEvent(`custom-${reschedule.eventId}`)?.detail.custom : null;
        const application = custom ? null : findApplication(reschedule.applicationId);
        const activity = custom ? null : application.activities.find((candidate) => candidate.id === reschedule.activityId);
        const current = custom ?? activity;
        const subject = custom ? custom.title : `${application.name} · ${interviewTypeLabel(activity.type)}`;

        openConfirm({
            title: 'Confirm Reschedule',
            intro: 'The previous schedule and the reason are kept in the event’s history.',
            rows: [
                [custom ? 'Event' : 'Applicant', subject],
                ['Old Schedule', scheduleText(current.date, current.time, current.endTime)],
                ['New Schedule', scheduleText(values.date, values.time, values.endTime)],
                ...(current.venue ? [['Venue', current.venue]] : []),
                ['Reason', reasonText({ reason: values.reason, reasonDetail: values.reasonDetail.trim() })],
            ],
            okLabel: 'Confirm Reschedule',
            backTo: rescheduleModal,
            onConfirm: () => (custom ? rescheduleCalendarEvent(custom.id, values) : rescheduleActivity(reschedule.applicationId, reschedule.activityId, values)),
            onDone: () => {
                calendar.goToDate(values.date);
                showToast(`${custom ? custom.title : `${application.name}'s ${interviewTypeLabel(activity.type).toLowerCase()}`} is now on ${formatDate(values.date)}, ${timeRange(values.time, values.endTime)}.`);
            },
            formErrors: (result) => showErrors($rescheduleForm, 'data-reschedule-error', result.errors),
        });
    });

    // ---------------------------------------------------------------- cancelling a schedule

    function confirmCancelSchedule() {
        const event = calendar.findEvent(state.eventId);

        if (!event || event.type === 'leave') {
            return;
        }

        const { application, activity } = event.detail;
        const returnsToPanel = activity.type !== 'Other' && ['Applied', 'Demo Interview'].includes(application.status);

        openConfirm({
            title: 'Cancel Schedule',
            intro: `This removes the schedule from the calendar without a replacement.${returnsToPanel ? ` ${application.name} returns to Unscheduled Applicants.` : ''}`,
            rows: [
                ['Applicant', application.name],
                ['Interview Type', interviewTypeLabel(activity.type)],
                ['Schedule', scheduleText(activity.date, activity.time, activity.endTime)],
                ...(activity.venue ? [['Venue', activity.venue]] : []),
            ],
            okLabel: 'Cancel Schedule',
            okClass: 'btn-danger',
            cancelLabel: 'Keep Schedule',
            backTo: eventModal,
            onConfirm: () => cancelActivity(application.id, activity.id),
            onDone: () => showToast(`${application.name}'s ${interviewTypeLabel(activity.type).toLowerCase()} was cancelled.${returnsToPanel ? ' They are back in Unscheduled Applicants.' : ''}`),
        });
    }

    // ---------------------------------------------------------------- the confirmation modal

    function openConfirm({ title, intro, rows, okLabel, okClass = 'btn-primary', cancelLabel = 'Cancel', backTo, onConfirm, onDone, formErrors = null }) {
        state.pending = { backTo, onConfirm, onDone, formErrors };
        $confirm.find('[data-confirm-title]').text(title);
        $confirm.find('[data-confirm-intro]').text(intro);
        $confirm.find('[data-confirm-summary]').empty().append(...rows.map(([label, value]) => detailRow(label, value)));
        $confirm.find('[data-confirm-error]').addClass('d-none').text('');
        $confirm.find('[data-confirm-ok]').attr('class', `btn ${okClass}`).text(okLabel);
        $confirm.find('[data-confirm-cancel]').text(cancelLabel);
        switchTo(confirmModal, () => $confirm.find('[data-confirm-ok]').trigger('focus'));
    }

    // Go Back: the form (or the event details) as it was left.
    $confirm.on('click', '[data-confirm-back]', function () {
        const backTo = state.pending?.backTo;

        state.pending = null;

        if (backTo === eventModal) {
            renderEvent();
        }

        switchTo(backTo ?? eventModal);
    });

    // Cancel: nothing changes.
    $confirm.on('click', '[data-confirm-cancel]', function () {
        state.pending = null;
        closeAll();
    });

    $confirm.on('click', '[data-confirm-ok]', function () {
        const pending = state.pending;

        if (!pending) {
            closeAll();

            return;
        }

        const result = pending.onConfirm();

        if (!result.ok) {
            // Something changed since the form was checked (for example, another schedule took the slot): back to the
            // form with the reason shown on its field, or here when it is not about a field.
            if (pending.formErrors && Object.keys(result.errors ?? {}).length) {
                state.pending = null;
                switchTo(pending.backTo, () => pending.formErrors(result));

                return;
            }

            $confirm.find('[data-confirm-error]').text(result.error).removeClass('d-none');

            return;
        }

        state.pending = null;
        closeAll();
        pending.onDone(result);
    });

    // ---------------------------------------------------------------- keeping everything in step

    function refresh() {
        renderPanel();

        if (openModal === dayModal) {
            renderDay();
        }

        // The event was cancelled or deleted elsewhere: there is nothing left to show.
        if (openModal === eventModal && !renderEvent()) {
            closeAll();
        }

        if (openModal === scheduleModal && !state.scheduleApplicantFixed) {
            renderApplicantOptions();
        }
    }

    renderPanel();
    onMockDataChange(refresh);

    return { openDay, openDate, openEvent };
}
