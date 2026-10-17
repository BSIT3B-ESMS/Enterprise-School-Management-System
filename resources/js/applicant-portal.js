import $ from './jquery.js';
import { formatDate, formatTime, initialsOf } from './format.js';
import { getApplicantApplication, onMockDataChange } from './mock-data.js';
import { progressSteps, statusClass } from './recruitment-workflow.js';

// The applicant is identified by the application reference in the address; there are no accounts.
export const reference = new URLSearchParams(window.location.search).get('ref') ?? '';

const stepText = { done: 'completed', current: 'current stage', pending: 'not reached yet' };

// Where the application stands, in the applicant's terms (each status follows what HR has completed).
export const statusMessages = {
    Applied: 'Your application has been received. HR will schedule your screening.',
    Screening: 'Your screening is done. HR will schedule your interview.',
    Interview: 'Your interview is done. HR is reviewing your application.',
    Offered: 'You have been offered the position. HR will be in touch about your response.',
    Hired: 'Congratulations, you have been hired.',
    Rejected: 'Your application was not selected for this position.',
};

// What happens next when nothing is scheduled, by status (who acts is HR; the applicant only waits).
export const nextStepHints = {
    Applied: 'HR will schedule your screening',
    Screening: 'HR will schedule your interview',
    Interview: 'HR is reviewing your application',
    Offered: 'HR will contact you about the offer',
    Hired: 'You have been hired',
    Rejected: 'There are no further steps',
};

function renderHeader($shell, application) {
    $shell.find('[data-applicant-initials]').text(application ? initialsOf(application.name) : '');
    $shell.find('[data-applicant-name]').text(application?.name ?? 'Applicant');
    $shell.find('[data-applicant-email]').text(application?.email ?? '');
}

// The brand and sidebar links keep the reference, so the applicant stays on their own application.
function renderLinks($shell) {
    $shell.find('[data-applicant-link]').each(function () {
        const base = String($(this).attr('href')).split('?')[0];

        $(this).attr('href', reference ? `${base}?ref=${encodeURIComponent(reference)}` : base);
    });
}

export function renderSteps($root, application) {
    const items = progressSteps(application).map(({ stage, state }) => (
        $('<li>', { class: `is-${state}`, 'aria-current': state === 'current' ? 'step' : null }).append(
            document.createTextNode(stage),
            $('<span>', { class: 'visually-hidden', text: ` (${stepText[state]})` }),
        )
    ));

    if (application.status === 'Rejected') {
        items.push($('<li>', { class: 'is-rejected', text: 'Rejected' }));
    }

    $root.find('[data-applicant-steps]').empty().append(...items);
}

function activityItem(activity) {
    const isCompleted = activity.status === 'Completed';

    return $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3 flex-wrap' }).append(
        $('<div>', { class: 'min-w-0' }).append(
            $('<div>', { class: 'fw-medium', text: activity.type }),
            $('<small>', { class: 'text-body-secondary', text: `${formatDate(activity.date)} at ${formatTime(activity.time)}` }),
        ),
        $('<span>', {
            class: `status-pill activity-${isCompleted ? 'completed' : 'scheduled'}`,
            text: isCompleted ? 'Completed' : 'Scheduled',
        }),
    );
}

function renderApplication($root, application) {
    const values = {
        initials: initialsOf(application.name),
        name: application.name,
        role: `${application.jobTitle} · ${application.department}`,
        jobTitle: application.jobTitle,
        department: application.department,
        appliedOn: formatDate(application.appliedOn),
        phone: application.phone,
        email: application.email,
    };
    const activities = [...application.activities].sort((first, second) => (
        `${second.date} ${second.time}`.localeCompare(`${first.date} ${first.time}`)
    ));

    Object.entries(values).forEach(([field, value]) => {
        $root.find(`[data-applicant-field="${field}"]`).text(value);
    });
    $root.find('[data-applicant-status-pill]').attr('class', `status-pill ${statusClass(application.status)}`).text(application.status);
    $root.find('[data-applicant-status-message]').text(statusMessages[application.status] ?? '');
    renderSteps($root, application);
    $root.find('[data-applicant-activities-empty]').toggleClass('d-none', activities.length > 0);
    $root.find('[data-applicant-activities]').empty().append(...activities.map(activityItem));
}

function render($shell, $page) {
    const application = getApplicantApplication(reference);

    renderHeader($shell, application);

    if (!$page.length) {
        return;
    }

    // No reference, or the application was deleted (for example, by HR in another tab).
    $page.find('[data-applicant-missing]').toggleClass('d-none', Boolean(application));
    $page.find('[data-applicant-content]').toggleClass('d-none', !application);

    if (application) {
        renderApplication($page, application);
    }
}

$(function () {
    const $shell = $('[data-app-shell]');

    if (!$shell.find('[data-applicant-name]').length) {
        return;
    }

    const $page = $('[data-page="applicant-application"]');

    renderLinks($shell);
    render($shell, $page);
    onMockDataChange(() => render($shell, $page));
});
