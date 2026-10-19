import $ from './jquery.js';
import { formatDate, formatTime, initialsOf } from './format.js';
import { getApplicantAccount, onMockDataChange } from './mock-data.js';
import { currentAccountId, signOut } from './applicant-session.js';
import { progressSteps } from './recruitment-workflow.js';

const $shell = $('[data-app-shell][data-applicant-shell]');

/**
 * The signed-in applicant's account ID on an Applicant interface page ('' anywhere else). Pages show only this
 * account's applications, schedules, and profile.
 */
export const accountId = $shell.length ? currentAccountId() : '';

/**
 * Whether someone is signed in (this tab's session) with an account that still exists. When not,
 * the page sends them to the shared Login page and the page scripts draw nothing.
 */
export function isSignedIn() {
    return Boolean(accountId) && Boolean(getApplicantAccount(accountId));
}

const stepText = { done: 'completed', current: 'current stage', pending: 'not reached yet' };

// Where an application stands, in the applicant's terms (each status follows what HR has completed).
export const statusMessages = {
    Applied: 'Your application has been received. HR will schedule your demo interview.',
    'Demo Interview': 'Your demo interview is done. HR will schedule your interview.',
    Interview: 'Your interview is done. HR is reviewing your application.',
    Offered: 'You have been offered the position. HR will be in touch about your response.',
    Hired: 'Congratulations, you have been hired.',
    Rejected: 'Your application was not selected for this position.',
};

// What happens next when nothing is scheduled, by status (who acts is HR; the applicant only waits).
export const nextStepHints = {
    Applied: 'HR will schedule your demo interview',
    'Demo Interview': 'HR will schedule your interview',
    Interview: 'HR is reviewing your application',
    Offered: 'HR will contact you about the offer',
    Hired: 'You have been hired',
    Rejected: 'There are no further steps',
};

export const plural = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;

/**
 * The progress tracker for one application: its stages, with those reached marked done, the current one marked, and
 * Rejected added when HR rejected it. It follows the application's status in the shared mock data.
 */
export function stepsList(application) {
    const items = progressSteps(application).map(({ stage, state }) => (
        $('<li>', { class: `is-${state}`, 'aria-current': state === 'current' ? 'step' : null }).append(
            document.createTextNode(stage),
            $('<span>', { class: 'visually-hidden', text: ` (${stepText[state]})` }),
        )
    ));

    if (application.status === 'Rejected') {
        items.push($('<li>', { class: 'is-rejected', text: 'Rejected' }));
    }

    return $('<ol>', { class: 'recruitment-steps mb-0', 'aria-label': `Progress of your application for ${application.jobTitle}` }).append(...items);
}

export function renderSteps($root, application) {
    $root.find('[data-applicant-steps]').empty().append(...stepsList(application).children());
}

/**
 * One demo interview or interview as a list row: its type (and position, when given), date, time, and status.
 */
export function activityItem(activity, jobTitle = '') {
    const isCompleted = activity.status === 'Completed';

    return $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3 flex-wrap' }).append(
        $('<div>', { class: 'min-w-0' }).append(
            $('<div>', { class: 'fw-medium', text: jobTitle ? `${activity.type} · ${jobTitle}` : activity.type }),
            $('<small>', { class: 'text-body-secondary', text: `${formatDate(activity.date)} at ${formatTime(activity.time)}` }),
        ),
        $('<span>', {
            class: `status-pill activity-${isCompleted ? 'completed' : 'scheduled'}`,
            text: isCompleted ? 'Completed' : 'Scheduled',
        }),
    );
}

function renderHeader() {
    const account = getApplicantAccount(accountId);

    $shell.find('[data-applicant-initials]').text(account ? initialsOf(account.name) : '');
    $shell.find('[data-applicant-name]').text(account?.name ?? 'Applicant');
    $shell.find('[data-applicant-email]').text(account?.email ?? '');
}

function sendToLogIn() {
    signOut();
    window.location.replace($shell.attr('data-login-url'));
}

$(function () {
    if (!$shell.length) {
        return;
    }

    if (!isSignedIn()) {
        sendToLogIn();

        return;
    }

    renderHeader();

    // The account can disappear while the page is open (for example, the mock data was reset in another tab).
    onMockDataChange(() => (isSignedIn() ? renderHeader() : sendToLogIn()));

    // Log Out ends this tab's session; the link then opens the shared Login page.
    $shell.on('click', '[data-applicant-logout]', () => signOut());
});
