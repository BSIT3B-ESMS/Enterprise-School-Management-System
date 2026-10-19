import $ from './jquery.js';
import { formatDate, formatTime, greetingForHour } from './format.js';
import { accountId, isSignedIn, nextStepHints, plural } from './applicant-portal.js';
import { applicationBlockFor, findPosition, getApplicantAccount, listApplicantApplications, onMockDataChange } from './mock-data.js';
import { finalStatuses, statusClass } from './recruitment-workflow.js';

function todayIso() {
    const date = new Date();
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

// Whole days from one ISO date to another (negative when the second is earlier).
function daysUntil(isoDate, fromIsoDate) {
    const toDay = (iso) => Date.UTC(...iso.split('-').map((part, index) => Number(part) - (index === 1 ? 1 : 0))) / 86400000;

    return Math.round(toDay(isoDate) - toDay(fromIsoDate));
}

function whenText(activity) {
    const days = daysUntil(activity.date, todayIso());

    if (days < 0) {
        return 'Awaiting HR';
    }

    return days === 0 ? 'Today' : `In ${plural(days, 'day')}`;
}

// Every scheduled screening and interview across the applicant's applications, soonest first.
function upcomingActivities(applications) {
    return applications
        .flatMap((application) => application.activities
            .filter((activity) => activity.status === 'Scheduled')
            .map((activity) => ({ ...activity, application })))
        .sort((first, second) => `${first.date} ${first.time}`.localeCompare(`${second.date} ${second.time}`));
}

function renderKpis($root, applications, upcoming) {
    const active = applications.filter((application) => !finalStatuses.includes(application.status));
    const next = upcoming[0];
    const kpis = [
        { label: 'Applications', value: applications.length, caption: applications.length ? `${active.length} in progress` : 'None submitted yet' },
        { label: 'Latest Status', value: applications[0]?.status ?? 'None', caption: applications[0] ? `for ${applications[0].jobTitle}` : 'Apply to get started' },
        {
            label: 'Next Step',
            value: next ? next.type : 'None yet',
            caption: next
                ? `${formatDate(next.date)} at ${formatTime(next.time)}`
                : (applications[0] && nextStepHints[applications[0].status]) || 'Nothing scheduled',
        },
    ];

    $root.find('[data-applicant-kpis]').empty().append(...kpis.map((kpi) => $('<div>', { class: 'col' }).append(
        $('<div>', { class: 'card dashboard-kpi' }).append(
            $('<span>', { class: 'dashboard-kpi-label', text: kpi.label }),
            $('<span>', { class: 'dashboard-kpi-value tabular-nums', text: kpi.value }),
            $('<span>', { class: 'dashboard-kpi-caption', text: kpi.caption }),
        ),
    )));
}

function renderApplications($root, applications) {
    $root.find('[data-applicant-no-applications]').toggleClass('d-none', applications.length > 0);
    $root.find('[data-applicant-application-list]').empty().append(...applications.slice(0, 5).map((application) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<a>', {
                    class: 'fw-medium text-reset d-block text-truncate',
                    href: `${$root.attr('data-application-url')}/${encodeURIComponent(application.id)}`,
                    text: application.jobTitle,
                }),
                $('<small>', { class: 'text-body-secondary', text: `${application.department} · Applied ${formatDate(application.appliedOn)}` }),
            ),
            $('<span>', { class: `status-pill ${statusClass(application.status)}`, text: application.status }),
        )
    )));
}

function renderUpcoming($root, upcoming) {
    $root.find('[data-applicant-upcoming-empty]').toggleClass('d-none', upcoming.length > 0);
    $root.find('[data-applicant-upcoming]').empty().append(...upcoming.map((activity) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { class: 'fw-medium', text: `${activity.type} · ${activity.application.jobTitle}` }),
                $('<small>', { class: 'text-body-secondary', text: `${formatDate(activity.date)} at ${formatTime(activity.time)}` }),
            ),
            $('<span>', { class: 'ems-chip', text: whenText(activity) }),
        )
    )));
}

// The position chosen on Open Positions (?position=), offered while it can still be applied for.
function renderPendingPosition($root) {
    const id = new URLSearchParams(window.location.search).get('position') ?? '';
    const position = id ? findPosition(id) : undefined;
    const canApply = Boolean(position) && !applicationBlockFor(accountId, id);

    $root.find('[data-applicant-pending-position]').toggleClass('d-none', !canApply).toggleClass('d-flex', canApply);

    if (canApply) {
        $root.find('[data-applicant-pending-name]').text(`${position.name} · ${position.department}`);
        $root.find('[data-applicant-pending-apply]').attr('href', `${$root.attr('data-apply-url')}?position=${encodeURIComponent(id)}`);
    }
}

function renderDashboard($root) {
    const account = getApplicantAccount(accountId);

    if (!account) {
        return;
    }

    const applications = listApplicantApplications(accountId);
    const upcoming = upcomingActivities(applications);

    $root.find('[data-applicant-greeting]').text(`${greetingForHour(new Date().getHours())}, ${account.name.split(/\s+/)[0]}`);
    renderPendingPosition($root);
    renderKpis($root, applications, upcoming);
    renderApplications($root, applications);
    renderUpcoming($root, upcoming);
}

$(function () {
    const $dashboard = $('[data-page="applicant-dashboard"]');

    if (!$dashboard.length || !isSignedIn()) {
        return;
    }

    renderDashboard($dashboard);
    onMockDataChange(() => renderDashboard($dashboard));
});
