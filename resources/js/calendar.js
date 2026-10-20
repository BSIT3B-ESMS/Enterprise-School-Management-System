import { Modal } from 'bootstrap';
import $ from './jquery.js';

// The HR calendar. The month grid, the chips, and their data are printed by the server (resources/views/calendar/index.blade.php).
// This script opens an interview or event from its chip, and lets HR drag one that has not happened to another day (never earlier
// than today). A drag only chooses the day: a form then asks for the reason and the time, and posts to the server.

function chipData($chip) {
    return {
        kind: $chip.attr('data-calendar-chip'),
        title: $chip.attr('data-title') ?? '',
        subtitle: $chip.attr('data-subtitle') ?? '',
        status: $chip.attr('data-status') ?? '',
        scheduled: $chip.attr('data-scheduled') === '1',
        started: $chip.attr('data-started') === '1',
        date: $chip.attr('data-date'),
        start: $chip.attr('data-start') ?? '',
        end: $chip.attr('data-end') ?? '',
        when: $chip.attr('data-when') ?? '',
        place: $chip.attr('data-place') ?? '',
        interviewer: $chip.attr('data-interviewer') ?? '',
        interviewerId: $chip.attr('data-interviewer-id') ?? '',
        conflict: $chip.attr('data-conflict') === '1',
        requested: $chip.attr('data-requested') ?? '',
        email: $chip.attr('data-email') ?? '',
        phone: $chip.attr('data-phone') ?? '',
        education: $chip.attr('data-education') ?? '',
        notes: $chip.attr('data-notes') ?? '',
        type: $chip.attr('data-type') ?? '',
        everyone: $chip.attr('data-everyone') === '1',
        applicationUrl: $chip.attr('data-application-url') ?? '',
        rescheduleUrl: $chip.attr('data-reschedule-url') ?? '',
        interviewerUrl: $chip.attr('data-interviewer-url') ?? '',
        cancelUrl: $chip.attr('data-cancel-url') ?? '',
        completeUrl: $chip.attr('data-complete-url') ?? '',
        rejectUrl: $chip.attr('data-reject-url') ?? '',
        updateUrl: $chip.attr('data-update-url') ?? '',
    };
}

function openDetails($root, chip) {
    const $modal = $('#calendar-detail-modal');
    const isInterview = chip.kind === 'interview';
    const canAct = chip.scheduled;

    ['title', 'subtitle', 'status', 'when', 'place', 'interviewer', 'email', 'phone', 'education'].forEach((field) => {
        $modal.find(`[data-detail="${field}"]`).text(chip[field] || '—');
    });

    $modal.find('[data-detail="requested"]').text(chip.requested).toggleClass('d-none', chip.requested === '');
    $modal.find('[data-detail="conflict"]').toggleClass('d-none', !chip.conflict);
    $modal.find('[data-detail="notes"]').text(chip.notes).toggleClass('d-none', chip.notes === '');
    $modal.find('[data-interview-only]').toggleClass('d-none', !isInterview);
    $modal.find('[data-event-only]').toggleClass('d-none', isInterview || (chip.notes === '' && false));
    $modal.find('[data-detail="notes"]').toggleClass('d-none', isInterview || chip.notes === '');
    $modal.find('[data-scheduled-only]').toggleClass('d-none', !canAct);
    $modal.find('[data-detail-link="application"]').attr('href', chip.applicationUrl);

    // Reschedule: today's rule is "never before today", and the date field enforces it too.
    $modal.find('[data-form="reschedule"]').attr('action', chip.rescheduleUrl)
        .find('[name="date"]').val(chip.date).end()
        .find('[name="start_time"]').val(chip.start).end()
        .find('[name="end_time"]').val(chip.end);
    // An applicant's request is answered without a reason; HR gives one only when it moves the interview for its own reasons.
    const answeringRequest = chip.requested !== '';
    $modal.find('[data-form="reschedule"] [name="reason"]').prop('required', !answeringRequest);
    $modal.find('[data-form="reschedule"] label[for="rs-reason"]').toggleClass('is-required', !answeringRequest);
    $modal.find('[data-reschedule-hint]').toggleClass('d-none', !answeringRequest);
    $modal.find('[data-form="interviewer"]').attr('action', chip.interviewerUrl).find('select').val(chip.interviewerId);
    $modal.find('[data-form="cancel"]').attr('action', chip.cancelUrl);
    $modal.find('[data-form="reject"]').attr('action', chip.rejectUrl);

    // Next stage: a demo interview that has started can be marked completed; one already completed leads to the offer.
    $modal.find('[data-form="complete"]').attr('action', chip.completeUrl).toggleClass('d-none', !(isInterview && chip.scheduled && chip.started));
    $modal.find('[data-needs-completed]').toggleClass('d-none', !(isInterview && chip.status === 'Completed'));

    if (!isInterview) {
        const $edit = $modal.find('[data-form="event-edit"]');

        $edit.attr('action', chip.updateUrl);
        $edit.find('[name="title"]').val(chip.title);
        $edit.find('[name="type"]').val(chip.type);
        $edit.find('[name="date"]').val(chip.date);
        $edit.find('[name="start_time"]').val(chip.start);
        $edit.find('[name="end_time"]').val(chip.end);
        $edit.find('[name="venue"]').val(chip.place);
        $edit.find('[name="notes"]').val(chip.notes);
        $edit.find('[name="for_everyone"]').prop('checked', chip.everyone);
    }

    Modal.getOrCreateInstance($modal[0]).show();
}

function openMoveModal(chip, date) {
    const $modal = $('#calendar-move-modal');
    const label = new Date(`${date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

    $modal.find('[data-move="label"]').text(label);
    $modal.find('[data-move="title"]').text(chip.title);
    $modal.find('[data-move="date"]').val(date);
    $modal.find('[name="start_time"]').val(chip.start);
    $modal.find('[name="end_time"]').val(chip.end);
    $modal.find('[name="reason"]').val('').prop('required', chip.requested === '');
    $modal.find('[data-move="reason-label"]').toggleClass('is-required', chip.requested === '');
    $modal.find('[data-move="reason-hint"]').toggleClass('d-none', chip.requested === '');
    $modal.find('[data-interview-only]').toggleClass('d-none', chip.kind !== 'interview');
    $modal.find('[data-form="move"]').attr('action', chip.rescheduleUrl);

    Modal.getOrCreateInstance($modal[0]).show();
    window.setTimeout(() => $modal.find('[name="reason"]').trigger('focus'), 300);
}

function initDragAndDrop($root) {
    const today = $root.attr('data-today');

    $root.find('[data-calendar-chip][data-movable]').each(function () {
        $(this).draggable({
            helper: 'clone',
            appendTo: 'body',
            revert: 'invalid',
            zIndex: 1100,
            distance: 6,
            start: () => $root.addClass('is-dragging'),
            stop: () => $root.removeClass('is-dragging'),
        });
    });

    $root.find('[data-calendar-day]').each(function () {
        const $day = $(this);
        const date = $day.attr('data-calendar-day');

        // A day before today cannot receive anything, so the interviews and events can never be moved into the past.
        if (date < today) {
            return;
        }

        $day.droppable({
            accept: '[data-calendar-chip][data-movable]',
            tolerance: 'pointer',
            classes: { 'ui-droppable-hover': 'is-selected' },
            drop: (event, ui) => {
                const chip = chipData(ui.draggable);

                if (chip.date === date) {
                    return;
                }

                openMoveModal(chip, date);
            },
        });
    });
}

function initAddEvent($root) {
    $root.find('[data-calendar-add-event]').on('click', () => {
        const $modal = $('#calendar-event-modal');

        $modal.find('[name="date"]').val($root.attr('data-today'));
        Modal.getOrCreateInstance($modal[0]).show();
    });

    // A day with nothing on it (and not in the past) opens the event form for that date.
    $root.on('dblclick', '[data-calendar-day]', function (event) {
        if ($(event.target).closest('[data-calendar-chip]').length) {
            return;
        }

        const date = $(this).attr('data-calendar-day');

        if (date < $root.attr('data-today')) {
            return;
        }

        const $modal = $('#calendar-event-modal');

        $modal.find('[name="date"]').val(date);
        Modal.getOrCreateInstance($modal[0]).show();
    });
}

$(function () {
    const $root = $('[data-page="calendar"]');

    if (!$root.length) {
        return;
    }

    $root.on('click', '[data-calendar-chip]', function () {
        openDetails($root, chipData($(this)));
    });

    initDragAndDrop($root);
    initAddEvent($root);
});
