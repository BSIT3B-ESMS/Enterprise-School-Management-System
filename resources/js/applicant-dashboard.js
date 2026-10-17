import $ from './jquery.js';
import { formatDate, formatTime, greetingForHour } from './format.js';
import { nextStepHints, reference, renderSteps, statusMessages } from './applicant-portal.js';
import { getApplicantApplication, onMockDataChange } from './mock-data.js';
import { statusClass } from './recruitment-workflow.js';

const plural = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;

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

function ago(isoDate) {
    const days = -daysUntil(isoDate, todayIso());

    if (days <= 0) {
        return 'today';
    }

    return days === 1 ? 'yesterday' : `${days} days ago`;
}

function scheduledActivities(application) {
    return application.activities
        .filter((activity) => activity.status === 'Scheduled')
        .sort((first, second) => `${first.date} ${first.time}`.localeCompare(`${second.date} ${second.time}`));
}

function whenText(activity) {
    const days = daysUntil(activity.date, todayIso());

    if (days < 0) {
        return 'Awaiting HR';
    }

    return days === 0 ? 'Today' : `In ${plural(days, 'day')}`;
}

function renderKpis($root, application, upcoming) {
    const next = upcoming[0];
    const kpis = [
        { label: 'Status', value: application.status, caption: `for ${application.jobTitle}` },
        { label: 'Applied', value: formatDate(application.appliedOn), caption: ago(application.appliedOn) },
        {
            label: 'Next Step',
            value: next ? next.type : 'None yet',
            caption: next ? `${formatDate(next.date)} at ${formatTime(next.time)}` : nextStepHints[application.status] ?? '',
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

function renderUpcoming($root, upcoming) {
    $root.find('[data-applicant-upcoming-empty]').toggleClass('d-none', upcoming.length > 0);
    $root.find('[data-applicant-upcoming]').empty().append(...upcoming.map((activity) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { class: 'fw-medium', text: activity.type }),
                $('<small>', { class: 'text-body-secondary', text: `${formatDate(activity.date)} at ${formatTime(activity.time)}` }),
            ),
            $('<span>', { class: 'ems-chip', text: whenText(activity) }),
        )
    )));
}

function renderDashboard($root) {
    const application = getApplicantApplication(reference);

    // No reference, or the application was deleted (for example, by HR in another tab).
    $root.find('[data-applicant-missing]').toggleClass('d-none', Boolean(application));
    $root.find('[data-applicant-content]').toggleClass('d-none', !application);

    if (!application) {
        $root.find('[data-applicant-greeting]').text('Welcome');

        return;
    }

    const upcoming = scheduledActivities(application);
    const values = {
        role: `${application.jobTitle} · ${application.department}`,
        jobTitle: application.jobTitle,
        department: application.department,
        appliedOn: formatDate(application.appliedOn),
        phone: application.phone,
        email: application.email,
    };

    $root.find('[data-applicant-greeting]').text(`${greetingForHour(new Date().getHours())}, ${application.name.split(/\s+/)[0]}`);
    Object.entries(values).forEach(([field, value]) => {
        $root.find(`[data-applicant-field="${field}"]`).text(value);
    });
    $root.find('[data-applicant-status-pill]').attr('class', `status-pill ${statusClass(application.status)}`).text(application.status);
    $root.find('[data-applicant-status-message]').text(statusMessages[application.status] ?? '');
    renderKpis($root, application, upcoming);
    renderSteps($root, application);
    renderUpcoming($root, upcoming);
}

$(function () {
    const $dashboard = $('[data-page="applicant-dashboard"]');

    if (!$dashboard.length) {
        return;
    }

    renderDashboard($dashboard);
    onMockDataChange(() => renderDashboard($dashboard));
});
