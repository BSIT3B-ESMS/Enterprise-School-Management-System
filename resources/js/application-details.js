import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { documentItem } from './employee-documents.js';
import { formatDate, formatDateTime, formatTime } from './format.js';
import {
    cancelActivity,
    completeActivity,
    completeHiring,
    findApplication,
    getApplicationHistory,
    issueOffer,
    onMockDataChange,
    recordOfferAccepted,
    rejectApplication,
    rescheduleActivity,
    scheduleActivity,
    validateActivitySlot,
} from './mock-data.js';
import { milestoneLabels, progressSteps, statusClass, waitingMessage, workflowFor } from './recruitment-workflow.js';
import { showToast } from './toast.js';

// Steps that cannot be undone ask for confirmation first, inside the modal.
const confirmations = {
    'issue-offer': {
        message: (application) => `Issue a job offer to ${application.name} for ${application.jobTitle}? The application moves to Offered.`,
        label: 'Issue Offer',
        run: (id) => issueOffer(id),
        success: (application) => `Offer issued to ${application.name}.`,
    },
    'complete-hiring': {
        message: (application) => `Complete hiring ${application.name}? They are added to Employees as ${application.jobTitle} in ${application.department}, and the application moves to Hired.`,
        label: 'Complete Hiring',
        run: (id) => completeHiring(id),
        success: (application, result) => result.linked
            ? `Hired: ${application.name} is already an employee (${result.employee.id}); that record was updated.`
            : `${application.name} hired and added to Employees as ${result.employee.id}.`,
    },
    reject: {
        message: (application) => `Reject ${application.name}? Any scheduled screening or interview is cancelled, and a rejected application cannot move forward again.`,
        label: 'Reject Applicant',
        run: (id) => rejectApplication(id),
        success: (application) => `${application.name} was rejected.`,
    },
};

const stepText = { done: 'completed', current: 'current stage', pending: 'not reached yet' };

function today() {
    const date = new Date();
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function button(label, attributes, { variant = 'outline-primary', size = 'sm' } = {}) {
    return $('<button>', { type: 'button', class: `btn btn-${variant} btn-${size}`, text: label, ...attributes });
}

/**
 * The applicant record: the 201 file details and document lists the applicant submitted (blank for applications
 * HR added by hand and the samples).
 */
function renderSubmitted($modal, application) {
    Object.entries({
        birthdate: application.birthdate ? formatDate(application.birthdate) : '',
        sex: application.sex,
        civilStatus: application.civilStatus,
        address: application.address,
        emergencyContactName: application.emergencyContactName,
        emergencyContactNumber: application.emergencyContactNumber,
        emergencyContactRelationship: application.emergencyContactRelationship,
        sssNumber: application.sssNumber,
        philhealthNumber: application.philhealthNumber,
        pagibigNumber: application.pagibigNumber,
        tin: application.tin,
        birNumber: application.birNumber,
    }).forEach(([field, value]) => {
        $modal.find(`[data-application-field="${field}"]`)
            .text(value || 'Not provided')
            .toggleClass('text-body-secondary', !value);
    });

    $modal.find('[data-application-documents]').each(function () {
        const key = $(this).attr('data-application-documents');
        const files = application.documents?.[key] ?? [];

        $(this).empty().append(...files.map((file) => documentItem(file)));
        $modal.find(`[data-application-documents-empty="${key}"]`).toggleClass('d-none', files.length > 0);
    });
}

function renderSteps($modal, application) {
    const $steps = $modal.find('[data-application-steps]');
    const items = progressSteps(application).map(({ stage, state }) => (
        $('<li>', { class: `is-${state}`, 'aria-current': state === 'current' ? 'step' : null }).append(
            document.createTextNode(stage),
            $('<span>', { class: 'visually-hidden', text: ` (${stepText[state]})` }),
        )
    ));

    if (application.status === 'Rejected') {
        items.push($('<li>', { class: 'is-rejected', text: 'Rejected' }));
    }

    $steps.empty().append(...items);
}

function renderNextSteps($modal, application) {
    const workflow = workflowFor(application);
    const actions = [];

    if (workflow.scheduleType) {
        actions.push(button(`Schedule ${workflow.scheduleType}`, {
            'data-application-action': 'schedule',
            'data-activity-type': workflow.scheduleType,
        }, { variant: 'primary' }));
    }

    if (workflow.canIssueOffer) {
        actions.push(button('Issue Offer', { 'data-application-action': 'issue-offer' }, { variant: 'primary' }));
    }

    if (workflow.canRecordAcceptance) {
        actions.push(button('Record Offer Accepted', { 'data-application-action': 'accept-offer' }, { variant: 'primary' }));
    }

    if (workflow.canCompleteHiring) {
        actions.push(button('Complete Hiring', { 'data-application-action': 'complete-hiring' }, { variant: 'primary' }));
    }

    if (workflow.canReject) {
        actions.push(button('Reject Applicant', { 'data-application-action': 'reject' }, { variant: 'outline-danger' }));
    }

    $modal.find('[data-application-waiting]').text(waitingMessage(application));
    $modal.find('[data-application-next-actions]').empty().append(...actions);
}

function activityItem(activity) {
    const isCompleted = activity.status === 'Completed';
    const isDue = !isCompleted && activity.date <= today();
    const label = isCompleted ? 'Completed' : isDue ? 'Due' : 'Scheduled';
    const $item = $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3 flex-wrap' });
    const $details = $('<div>', { class: 'min-w-0' }).append(
        $('<div>', { class: 'd-flex align-items-center gap-2 flex-wrap' }).append(
            $('<span>', { class: 'fw-medium', text: activity.type }),
            $('<span>', { class: `status-pill activity-${label.toLowerCase()}`, text: label }),
        ),
        $('<small>', { class: 'text-body-secondary d-block', text: `${formatDate(activity.date)} at ${formatTime(activity.time)}` }),
    );

    if (isCompleted) {
        $details.append($('<small>', { class: 'text-body-secondary d-block', text: `Completed ${formatDate(activity.completedOn)}` }));
    } else if (!isDue) {
        $details.append($('<small>', { class: 'text-body-secondary d-block', text: `Can be completed on or after ${formatDate(activity.date)}` }));
    }

    $item.append($details);

    if (!isCompleted) {
        const attributes = { 'data-activity-id': activity.id };

        $item.append($('<div>', { class: 'd-flex gap-2' }).append(
            button('Complete', { ...attributes, 'data-activity-action': 'complete', disabled: !isDue }, { variant: 'primary' }),
            button('Reschedule', { ...attributes, 'data-activity-action': 'reschedule' }),
            button('Cancel', { ...attributes, 'data-activity-action': 'cancel' }, { variant: 'outline-secondary' }),
        ));
    }

    return $item;
}

function renderActivities($modal, application) {
    const activities = [...application.activities].sort((first, second) => (
        `${second.date} ${second.time}`.localeCompare(`${first.date} ${first.time}`)
    ));

    $modal.find('[data-application-activities]').empty().append(
        ...(activities.length
            ? activities.map(activityItem)
            : [$('<li>', { class: 'list-group-item text-body-secondary', text: 'No screening or interview has been scheduled yet.' })]),
    );
}

function renderMilestones($modal, application) {
    $modal.find('[data-application-milestones]').empty().append(...application.milestones.map(({ key, at }) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<span>', { text: milestoneLabels[key] ?? key }),
            $('<small>', { class: 'text-body-secondary text-nowrap', text: formatDate(at) }),
        )
    )));
}

function renderHistory($modal, applicationId) {
    const history = getApplicationHistory(applicationId);
    const $section = $modal.find('[data-application-history-section]');

    $section.toggleClass('d-none', !history.length);
    $modal.find('[data-application-history]').empty().append(...history.map((entry) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { text: entry.detail }),
                $('<small>', { class: 'text-body-secondary', text: `by ${entry.user}` }),
            ),
            $('<small>', { class: 'text-body-secondary text-nowrap', text: formatDateTime(entry.at) }),
        )
    )));
}

function setSlotError($field, message) {
    $(`#${$field.attr('aria-describedby')}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

/**
 * The applicant details modal on the Recruitment page: the applicant's information plus the whole
 * workflow. Every change goes through the data layer's workflow actions, so the status can only
 * follow valid progress; the table and Dashboard update from the data change.
 *
 * @param {JQuery} $modal
 */
export function initApplicationDetails($modal) {
    const employeesUrl = $modal.attr('data-employees-url');
    const $form = $modal.find('[data-application-activity-form]');
    const $confirm = $modal.find('[data-application-confirm]');
    let applicationId = null;
    let slotMode = null; // { kind: 'schedule', type } or { kind: 'reschedule', activityId }
    let confirmKey = null;

    function showFlash(message) {
        $modal.find('[data-application-flash]').text(message).toggleClass('d-none', !message);
    }

    function closeForm() {
        slotMode = null;
        $form.addClass('d-none');
    }

    function closeConfirm() {
        confirmKey = null;
        $confirm.addClass('d-none');
    }

    function readSlot() {
        return { date: String($form.find('[name="date"]').val() ?? ''), time: String($form.find('[name="time"]').val() ?? '') };
    }

    function focusNextStep() {
        $modal.find('[data-application-next]').trigger('focus');
    }

    function render() {
        const application = findApplication(applicationId);

        if (!application) {
            Modal.getOrCreateInstance($modal[0]).hide();
            showToast('That application no longer exists.', { isError: true });

            return;
        }

        $modal.find('[data-application-field="name"]').text(application.name);
        $modal.find('[data-application-status-pill]')
            .attr('class', `status-pill ${statusClass(application.status)}`)
            .text(application.status);

        Object.entries({
            email: application.email,
            phone: application.phone,
            jobTitle: application.jobTitle,
            department: application.department,
            appliedOn: formatDate(application.appliedOn),
            status: application.status,
        }).forEach(([field, value]) => {
            $modal.find(`[data-application-field="${field}"]`).text(value);
        });

        renderSubmitted($modal, application);

        $modal.find('[data-application-employee-row]').toggleClass('d-none', !application.employeeId);
        $modal.find('[data-application-employee-link]')
            .attr('href', `${employeesUrl}/${encodeURIComponent(application.employeeId)}`)
            .text(application.employeeId);

        $modal.find('[data-application-edit]').attr('data-application-edit', application.id);
        $modal.find('[data-application-delete]').attr('data-application-delete', application.id);

        renderSteps($modal, application);
        renderNextSteps($modal, application);
        renderActivities($modal, application);
        renderMilestones($modal, application);
        renderHistory($modal, application.id);
    }

    /**
     * Runs a workflow action. On success the view refreshes, the form and confirmation close, and a
     * toast reports it; on failure the reason shows inside the modal and nothing changes.
     */
    function perform(result, message) {
        if (!result.ok) {
            showFlash(result.error);

            return false;
        }

        showFlash('');
        closeForm();
        closeConfirm();
        render();
        showToast(typeof message === 'function' ? message(result.application, result) : message);
        focusNextStep();

        return true;
    }

    function openForm(mode, { title, date, time }) {
        closeConfirm();
        showFlash('');
        slotMode = mode;
        setSlotError($form.find('[name="date"]'), '');
        setSlotError($form.find('[name="time"]'), '');
        $form.find('[name="date"]').val(date).attr('min', today());
        $form.find('[name="time"]').val(time);
        $modal.find('[data-application-activity-form-title]').text(title);
        $form.removeClass('d-none');
        $form.find('[name="date"]').trigger('focus');
    }

    $modal.on('show.bs.modal', function (event) {
        applicationId = $(event.relatedTarget).attr('data-application-row');

        if (!findApplication(applicationId)) {
            event.preventDefault();
            showToast('That application no longer exists.', { isError: true });

            return;
        }

        showFlash('');
        closeForm();
        closeConfirm();
        render();
    });

    // Re-render when the data changes while the modal is open (for example, in another tab).
    onMockDataChange(() => {
        if ($modal.hasClass('show')) {
            render();
        }
    });

    $modal.on('click', '[data-application-action]', function () {
        const action = $(this).attr('data-application-action');
        const application = findApplication(applicationId);

        showFlash('');

        if (action === 'schedule') {
            const type = $(this).attr('data-activity-type');

            openForm({ kind: 'schedule', type }, { title: `Schedule ${type}`, date: today(), time: '' });

            return;
        }

        if (action === 'accept-offer') {
            perform(recordOfferAccepted(applicationId), (updated) => `${updated.name} accepted the offer. Complete hiring to finish.`);

            return;
        }

        const confirmation = confirmations[action];

        closeForm();
        confirmKey = action;
        $modal.find('[data-application-confirm-message]').text(confirmation.message(application));
        $modal.find('[data-application-confirm-yes]')
            .text(confirmation.label)
            .toggleClass('btn-danger', action === 'reject')
            .toggleClass('btn-primary', action !== 'reject');
        $confirm.removeClass('d-none');
        $modal.find('[data-application-confirm-yes]').trigger('focus');
    });

    $modal.on('click', '[data-application-confirm-no]', function () {
        closeConfirm();
        focusNextStep();
    });

    $modal.on('click', '[data-application-confirm-yes]', function () {
        const confirmation = confirmations[confirmKey];

        if (!confirmation) {
            return;
        }

        // On failure the confirmation closes so the reason is the only thing shown.
        if (!perform(confirmation.run(applicationId), confirmation.success)) {
            closeConfirm();
        }
    });

    $modal.on('click', '[data-activity-action]', function () {
        const action = $(this).attr('data-activity-action');
        const activityId = $(this).attr('data-activity-id');
        const application = findApplication(applicationId);
        const activity = application?.activities.find((candidate) => candidate.id === activityId);

        showFlash('');

        if (action === 'complete') {
            perform(completeActivity(applicationId, activityId), (updated) => (
                `${activity.type} completed. ${updated.name} is now ${updated.status}.`
            ));
        } else if (action === 'cancel') {
            perform(cancelActivity(applicationId, activityId), `${activity?.type ?? 'Activity'} cancelled.`);
        } else if (action === 'reschedule' && activity) {
            openForm(
                { kind: 'reschedule', activityId },
                { title: `Reschedule ${activity.type}`, date: activity.date < today() ? today() : activity.date, time: activity.time },
            );
        }
    });

    $form.on('input change', '[name]', function () {
        if ($(this).hasClass('is-invalid')) {
            setSlotError($(this), validateActivitySlot(readSlot())[$(this).attr('name')] ?? '');
        }
    });

    $modal.on('click', '[data-application-activity-cancel]', function () {
        closeForm();
        focusNextStep();
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        // Ignore a submit when no schedule or reschedule is in progress (the form is closed).
        if (!slotMode) {
            return;
        }

        const slot = readSlot();
        const errors = validateActivitySlot(slot);

        setSlotError($form.find('[name="date"]'), errors.date ?? '');
        setSlotError($form.find('[name="time"]'), errors.time ?? '');

        if (errors.date || errors.time) {
            $form.find(errors.date ? '[name="date"]' : '[name="time"]').trigger('focus');

            return;
        }

        if (slotMode.kind === 'schedule') {
            perform(
                scheduleActivity(applicationId, { type: slotMode.type, ...slot }),
                `${slotMode.type} scheduled for ${formatDate(slot.date)} at ${formatTime(slot.time)}. The status changes once it is completed.`,
            );
        } else {
            perform(rescheduleActivity(applicationId, slotMode.activityId, slot), `Rescheduled to ${formatDate(slot.date)} at ${formatTime(slot.time)}.`);
        }
    });
}
