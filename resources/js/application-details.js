import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { documentItem } from './employee-documents.js';
import { formatDate, formatDateTime, formatPesoAmount, formatSalaryRange, formatTime } from './format.js';
import {
    activityEndTime,
    canHrEditApplication,
    cancelActivity,
    completeActivity,
    completeHiring,
    findApplication,
    findJobTitle,
    getApplicationHistory,
    interviewTypeLabel,
    issueOffer,
    onMockDataChange,
    rejectApplication,
    rescheduleActivity,
    rescheduleReasons,
    scheduleActivity,
    updateOffer,
    validateCalendarSchedule,
    validateOffer,
    validateRescheduleSlot,
} from './mock-data.js';
import { milestoneLabels, progressSteps, statusAfterCompleting, statusClass, waitingMessage, workflowFor } from './recruitment-workflow.js';
import { showToast } from './toast.js';

// Steps that cannot be undone ask for confirmation first, inside the modal.
const confirmations = {
    'complete-activity': {
        message: (application, activity) => {
            const nextStatus = statusAfterCompleting[activity.type];

            return `Mark the ${interviewTypeLabel(activity.type).toLowerCase()} on ${formatDate(activity.date)} as completed?${nextStatus && nextStatus !== application.status ? ` ${application.name} moves to ${nextStatus}.` : ''}`;
        },
        label: 'Mark Completed',
        run: (id, activity) => completeActivity(id, activity.id),
        success: (application, result) => `${interviewTypeLabel(result.activity.type)} completed. ${application.name} is ${application.status}.`,
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
        message: (application) => `Reject ${application.name}? Any scheduled demo interview or final interview is cancelled, and a rejected application cannot move forward again.`,
        label: 'Reject Applicant',
        run: (id) => rejectApplication(id),
        success: (application) => `${application.name} was rejected.`,
    },
};

const stepText = { done: 'completed', current: 'current stage', pending: 'not reached yet' };

// The offer's response as HR reads it.
const responseText = { Pending: 'Waiting for the applicant', Accepted: 'Accepted by the applicant', Declined: 'Declined by the applicant' };

function today() {
    const date = new Date();
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function button(label, attributes, { variant = 'outline-primary', size = 'sm' } = {}) {
    return $('<button>', { type: 'button', class: `btn btn-${variant} btn-${size}`, text: label, ...attributes });
}

// Shows each value, or "Not provided" (muted) when it is blank.
function fillFields($modal, values) {
    Object.entries(values).forEach(([field, value]) => {
        $modal.find(`[data-application-field="${field}"]`)
            .text(value || 'Not provided')
            .toggleClass('text-body-secondary', !value);
    });
}

/**
 * The applicant: their name in parts, contact details, and the personal details and documents they submitted (blank for
 * applications HR added by hand and the samples).
 */
function renderApplicant($modal, application) {
    fillFields($modal, {
        lastName: application.lastName,
        firstName: application.firstName,
        middleName: application.middleName,
        email: application.email,
        phone: application.phone,
        birthdate: application.birthdate ? formatDate(application.birthdate) : '',
        sex: application.sex,
        civilStatus: application.civilStatus,
        address: application.address,
    });

    const isEditable = canHrEditApplication(application);

    $modal.find('[data-application-source]').text(isEditable
        ? 'Added by HR. HR can edit these details.'
        : 'Submitted by the applicant. HR can read these answers but not change them.');
    $modal.find('[data-application-edit]').prop('disabled', !isEditable).attr('data-application-edit', application.id);
    $modal.find('[data-application-edit-note]').toggleClass('d-none', isEditable);

    $modal.find('[data-application-documents]').each(function () {
        const key = $(this).attr('data-application-documents');
        const files = application.documents?.[key] ?? [];

        $(this).empty().append(...files.map((file) => documentItem(file)));
        $modal.find(`[data-application-documents-empty="${key}"]`).toggleClass('d-none', files.length > 0);
    });
}

// The offer HR issued, and the pre-employment information the applicant gave after accepting it.
function renderOffer($modal, application) {
    const { offer } = application;
    const hasPreEmployment = Boolean(application.preEmploymentSubmittedAt);

    $modal.find('[data-application-offer-empty]').toggleClass('d-none', Boolean(offer));
    $modal.find('[data-application-offer-details]').toggleClass('d-none', !offer);

    if (offer) {
        fillFields($modal, {
            offerSalary: offer.salary ? formatPesoAmount(offer.salary) : '',
            offerStartDate: offer.startDate ? formatDate(offer.startDate) : '',
            offerResponse: responseText[application.offerResponse] ?? '',
            offerIssuedOn: offer.issuedOn
                ? `${formatDate(offer.issuedOn)}${offer.updatedOn ? `, updated ${formatDate(offer.updatedOn)}` : ''}`
                : '',
            offerNotes: offer.notes,
        });
    }

    $modal.find('[data-application-pre-employment-empty]')
        .toggleClass('d-none', hasPreEmployment)
        .text(application.offerResponse === 'Accepted' ? 'Waiting for the applicant to submit it.' : 'Asked for once the applicant accepts an offer.');
    $modal.find('[data-application-pre-employment]').toggleClass('d-none', !hasPreEmployment);
    fillFields($modal, {
        emergencyContactName: application.emergencyContactName,
        emergencyContactNumber: application.emergencyContactNumber,
        emergencyContactRelationship: application.emergencyContactRelationship,
        sssNumber: application.sssNumber,
        philhealthNumber: application.philhealthNumber,
        pagibigNumber: application.pagibigNumber,
        tin: application.tin,
        birNumber: application.birNumber,
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
        actions.push(button(`Schedule ${interviewTypeLabel(workflow.scheduleType)}`, {
            'data-application-action': 'schedule',
            'data-activity-type': workflow.scheduleType,
        }, { variant: 'primary' }));
    }

    if (workflow.canIssueOffer) {
        actions.push(button('Issue Offer', { 'data-application-action': 'issue-offer' }, { variant: 'primary' }));
    }

    if (workflow.canUpdateOffer) {
        actions.push(button('Update Offer', { 'data-application-action': 'update-offer' }));
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

// One demo interview or final interview: when and where, whether it is still scheduled or completed, and its actions.
function activityItem(activity) {
    const isCompleted = activity.status === 'Completed';
    const isDue = !isCompleted && activity.date <= today();
    const label = isCompleted ? 'Completed' : isDue ? 'Due' : 'Scheduled';
    const endTime = activity.endTime || (activity.time ? activityEndTime(activity) : '');
    const $item = $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3 flex-wrap' });
    const $details = $('<div>', { class: 'min-w-0' }).append(
        $('<div>', { class: 'd-flex align-items-center gap-2 flex-wrap' }).append(
            $('<span>', { class: 'fw-medium', text: interviewTypeLabel(activity.type) }),
            $('<span>', { class: `status-pill activity-${label.toLowerCase()}`, text: label }),
        ),
        $('<small>', {
            class: 'text-body-secondary d-block',
            text: `${formatDate(activity.date)}, ${formatTime(activity.time)}${endTime ? ` to ${formatTime(endTime)}` : ''}${activity.venue ? ` · ${activity.venue}` : ''}`,
        }),
    );

    if (isCompleted) {
        $details.append($('<small>', { class: 'text-body-secondary d-block', text: `Completed ${formatDate(activity.completedOn)}` }));
    } else if (!isDue) {
        $details.append($('<small>', { class: 'text-body-secondary d-block', text: `Can be marked completed on or after ${formatDate(activity.date)}` }));
    }

    $item.append($details);

    if (!isCompleted) {
        const attributes = { 'data-activity-id': activity.id };

        $item.append($('<div>', { class: 'd-flex gap-2' }).append(
            button('Mark Completed', { ...attributes, 'data-activity-action': 'complete', disabled: !isDue }, { variant: 'primary' }),
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
            : [$('<li>', { class: 'list-group-item text-body-secondary', text: 'No demo interview or final interview has been scheduled yet.' })]),
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

function setFieldError($field, message) {
    $(`#${$field.attr('aria-describedby').split(' ').find((id) => id.endsWith('-error'))}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

/**
 * Marks each field of a form with its error (clearing the rest) and focuses the first invalid one. Errors for fields the
 * form does not have are returned, so they can be shown elsewhere.
 */
function showErrors($form, errors) {
    let $first = null;

    $form.find('[name]').each(function () {
        const message = errors[$(this).attr('name')] ?? '';

        setFieldError($(this), message);

        if (message && !$first) {
            $first = $(this);
        }
    });

    $first?.trigger('focus');

    return Object.entries(errors).filter(([name]) => !$form.find(`[name="${name}"]`).length).map(([, message]) => message);
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
    const $offerForm = $modal.find('[data-application-offer-form]');
    const $confirm = $modal.find('[data-application-confirm]');
    let applicationId = null;
    let slotMode = null; // { kind: 'schedule', type } or { kind: 'reschedule', activityId }
    let offerMode = null; // 'issue' or 'update'
    let confirmKey = null;
    let confirmActivity = null;

    $form.find('[data-application-activity-reasons]').append(
        $('<option>', { value: '', text: 'Select a reason' }),
        ...rescheduleReasons.map((reason) => $('<option>', { value: reason, text: reason })),
    );

    function showFlash(message) {
        $modal.find('[data-application-flash]').text(message).toggleClass('d-none', !message);
    }

    function closeForm() {
        slotMode = null;
        $form.addClass('d-none');
    }

    function closeOfferForm() {
        offerMode = null;
        $offerForm.addClass('d-none');
    }

    function closeConfirm() {
        confirmKey = null;
        confirmActivity = null;
        $confirm.addClass('d-none');
    }

    function closeAll() {
        closeForm();
        closeOfferForm();
        closeConfirm();
    }

    function readForm($target) {
        return Object.fromEntries($target.find('[name]').map(function () {
            return [[$(this).attr('name'), String($(this).val() ?? '').trim()]];
        }).get());
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

        fillFields($modal, {
            jobTitle: application.jobTitle,
            department: application.department,
            appliedOn: formatDate(application.appliedOn),
            status: application.status,
        });

        renderApplicant($modal, application);
        renderOffer($modal, application);

        $modal.find('[data-application-employee-row]').toggleClass('d-none', !application.employeeId);
        $modal.find('[data-application-employee-link]')
            .attr('href', `${employeesUrl}/${encodeURIComponent(application.employeeId)}`)
            .text(application.employeeId);

        $modal.find('[data-application-delete]').attr('data-application-delete', application.id);

        renderSteps($modal, application);
        renderNextSteps($modal, application);
        renderActivities($modal, application);
        renderMilestones($modal, application);
        renderHistory($modal, application.id);
    }

    /**
     * Runs a workflow action. On success the view refreshes, the forms and confirmation close, and a
     * toast reports it; on failure the reason shows inside the modal and nothing changes.
     */
    function perform(result, message) {
        if (!result.ok) {
            showFlash(result.error);

            return false;
        }

        showFlash('');
        closeAll();
        render();
        showToast(typeof message === 'function' ? message(result.application, result) : message);
        focusNextStep();

        return true;
    }

    function openSlotForm(mode, { title, date, time, endTime }) {
        closeAll();
        showFlash('');
        slotMode = mode;
        $form[0].reset();
        showErrors($form, {});

        const isReschedule = mode.kind === 'reschedule';

        $form.find('[data-application-activity-venue-field]').toggleClass('d-none', isReschedule);
        $form.find('[name="venue"]').prop('disabled', isReschedule);
        $form.find('[data-application-activity-reason-field]').toggleClass('d-none', !isReschedule);
        $form.find('[name="reason"]').prop('disabled', !isReschedule);
        $form.find('[data-application-activity-reason-detail-field]').addClass('d-none');
        $form.find('[name="reasonDetail"]').prop('disabled', true);
        $form.find('[name="date"]').val(date).attr('min', today());
        $form.find('[name="time"]').val(time);
        $form.find('[name="endTime"]').val(endTime);
        $modal.find('[data-application-activity-form-title]').text(title);
        $form.removeClass('d-none');
        $form.find('[name="date"]').trigger('focus');
    }

    function openOfferForm(mode, application) {
        const jobTitle = findJobTitle(application.jobTitleId);
        const offer = application.offer ?? {};

        closeAll();
        showFlash('');
        offerMode = mode;
        $offerForm[0].reset();
        showErrors($offerForm, {});
        $modal.find('[data-application-offer-form-title]').text(mode === 'issue' ? `Issue an Offer to ${application.name}` : 'Update the Offer');
        $modal.find('[data-application-offer-range]').text(jobTitle
            ? `${jobTitle.name} base salary range: ${formatSalaryRange(jobTitle.salaryMin, jobTitle.salaryMax)} a month. Only the applicant can accept or decline the offer.`
            : 'Only the applicant can accept or decline the offer.');
        $offerForm.find('[name="salary"]').val(offer.salary || jobTitle?.salaryMin || '');
        $offerForm.find('[name="startDate"]').val(offer.startDate && offer.startDate >= today() ? offer.startDate : '').attr('min', today());
        $offerForm.find('[name="notes"]').val(offer.notes ?? '');
        $offerForm.find('[data-application-offer-save]').text(mode === 'issue' ? 'Issue Offer' : 'Save Offer');
        $offerForm.removeClass('d-none');
        $offerForm.find('[name="salary"]').trigger('focus');
    }

    function openConfirm(key, application, activity = null) {
        const confirmation = confirmations[key];

        closeAll();
        confirmKey = key;
        confirmActivity = activity;
        $modal.find('[data-application-confirm-message]').text(confirmation.message(application, activity));
        $modal.find('[data-application-confirm-yes]')
            .text(confirmation.label)
            .toggleClass('btn-danger', key === 'reject')
            .toggleClass('btn-primary', key !== 'reject');
        $confirm.removeClass('d-none');
        $modal.find('[data-application-confirm-yes]').trigger('focus');
    }

    $modal.on('show.bs.modal', function (event) {
        // Opened from a row; other modals (Edit, Delete) opened from this one do not change which application it shows.
        if (event.target !== this) {
            return;
        }

        applicationId = $(event.relatedTarget).attr('data-application-row');

        if (!findApplication(applicationId)) {
            event.preventDefault();
            showToast('That application no longer exists.', { isError: true });

            return;
        }

        showFlash('');
        closeAll();
        render();
    });

    // Re-render when the data changes while the modal is open (for example, in another tab).
    onMockDataChange(() => {
        if ($modal.hasClass('show') && applicationId) {
            render();
        }
    });

    $modal.on('click', '[data-application-action]', function () {
        const action = $(this).attr('data-application-action');
        const application = findApplication(applicationId);

        showFlash('');

        if (!application) {
            return;
        }

        if (action === 'schedule') {
            const type = $(this).attr('data-activity-type');

            openSlotForm({ kind: 'schedule', type }, { title: `Schedule ${interviewTypeLabel(type)}`, date: today(), time: '', endTime: '' });
        } else if (action === 'issue-offer' || action === 'update-offer') {
            openOfferForm(action === 'issue-offer' ? 'issue' : 'update', application);
        } else {
            openConfirm(action, application);
        }
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
        if (!perform(confirmation.run(applicationId, confirmActivity), confirmation.success)) {
            closeConfirm();
        }
    });

    $modal.on('click', '[data-activity-action]', function () {
        const action = $(this).attr('data-activity-action');
        const activityId = $(this).attr('data-activity-id');
        const application = findApplication(applicationId);
        const activity = application?.activities.find((candidate) => candidate.id === activityId);

        showFlash('');

        if (!activity) {
            return;
        }

        if (action === 'complete') {
            openConfirm('complete-activity', application, activity);
        } else if (action === 'cancel') {
            perform(cancelActivity(applicationId, activityId), `${interviewTypeLabel(activity.type)} cancelled.`);
        } else if (action === 'reschedule') {
            openSlotForm(
                { kind: 'reschedule', activityId },
                {
                    title: `Reschedule ${interviewTypeLabel(activity.type)}`,
                    date: activity.date < today() ? today() : activity.date,
                    time: activity.time,
                    endTime: activityEndTime(activity),
                },
            );
        }
    });

    // Other needs a description of the reason.
    $form.on('change', '[name="reason"]', function () {
        const isOther = $(this).val() === 'Other';

        $form.find('[data-application-activity-reason-detail-field]').toggleClass('d-none', !isOther);
        $form.find('[name="reasonDetail"]').prop('disabled', !isOther);
    });

    function slotErrors() {
        const values = readForm($form);

        return slotMode.kind === 'schedule'
            ? validateCalendarSchedule({ applicationId, type: slotMode.type, ...values })
            : validateRescheduleSlot(applicationId, slotMode.activityId, values);
    }

    $form.on('input change', '[name]', function () {
        if (slotMode && $(this).hasClass('is-invalid')) {
            setFieldError($(this), slotErrors()[$(this).attr('name')] ?? '');
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

        const values = readForm($form);
        const leftOver = showErrors($form, slotErrors());

        if ($form.find('.is-invalid').length || leftOver.length) {
            showFlash(leftOver.join(' '));

            return;
        }

        if (slotMode.kind === 'schedule') {
            const { type } = slotMode;

            perform(
                scheduleActivity(applicationId, { type, ...values, notes: '' }),
                `${interviewTypeLabel(type)} scheduled for ${formatDate(values.date)}, ${formatTime(values.time)} to ${formatTime(values.endTime)}. The status changes only once it is marked completed.`,
            );
        } else {
            perform(
                rescheduleActivity(applicationId, slotMode.activityId, values),
                `Rescheduled to ${formatDate(values.date)}, ${formatTime(values.time)} to ${formatTime(values.endTime)}.`,
            );
        }
    });

    $offerForm.on('input change', '[name]', function () {
        if (offerMode && $(this).hasClass('is-invalid')) {
            setFieldError($(this), validateOffer(applicationId, readForm($offerForm))[$(this).attr('name')] ?? '');
        }
    });

    $modal.on('click', '[data-application-offer-cancel]', function () {
        closeOfferForm();
        focusNextStep();
    });

    $offerForm.on('submit', function (event) {
        event.preventDefault();

        if (!offerMode) {
            return;
        }

        const values = readForm($offerForm);

        showErrors($offerForm, validateOffer(applicationId, values));

        if ($offerForm.find('.is-invalid').length) {
            return;
        }

        // perform() closes the form (and clears the mode) before the message is made, so the mode is read first.
        const isIssuing = offerMode === 'issue';

        perform(
            isIssuing ? issueOffer(applicationId, values) : updateOffer(applicationId, values),
            (application) => (isIssuing
                ? `Offer issued to ${application.name}. They can accept or decline it in their Applicant Portal.`
                : `Offer updated for ${application.name}.`),
        );
    });
}
