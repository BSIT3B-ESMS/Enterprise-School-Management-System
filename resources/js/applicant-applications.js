import $ from './jquery.js';
import { formatDate, formatTime } from './format.js';
import { accountId, isSignedIn, nextStepHints, plural } from './applicant-portal.js';
import { listApplicantApplications, onMockDataChange } from './mock-data.js';
import { statusClass } from './recruitment-workflow.js';

// An application's details page.
function detailsUrl($root, applicationId) {
    return `${$root.attr('data-details-url')}/${encodeURIComponent(applicationId)}`;
}

function nextCell(application) {
    if (application.next) {
        return $('<td>').append(
            $('<div>', { class: 'fw-medium', text: application.next.type }),
            $('<small>', { class: 'text-body-secondary', text: `${formatDate(application.next.date)} at ${formatTime(application.next.time)}` }),
        );
    }

    return $('<td>', { class: 'text-body-secondary', text: nextStepHints[application.status] ?? '—' });
}

function rowFor($root, application) {
    const url = detailsUrl($root, application.id);

    return $('<tr>', {
        class: 'employee-row',
        title: `View details for ${application.jobTitle}`,
        'data-application-id': application.id,
        'data-details-url': url,
    }).append(
        $('<td>').append(
            $('<a>', { class: 'fw-medium text-reset text-decoration-none d-block', href: url, text: application.jobTitle }),
            $('<small>', { class: 'text-body-secondary', text: application.department }),
        ),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatDate(application.appliedOn) }),
        $('<td>').append($('<span>', { class: `status-pill ${statusClass(application.status)}`, text: application.status })),
        nextCell(application),
    );
}

function renderApplications($root) {
    const applications = listApplicantApplications(accountId);

    $root.find('[data-applications-rows]').empty().append(...applications.map((application) => rowFor($root, application)));
    $root.find('[data-applications-empty]').toggleClass('d-none', applications.length > 0);
    $root.find('[data-applications-summary]').text(plural(applications.length, 'application'));
}

$(function () {
    const $applications = $('[data-page="applicant-applications"]');

    if (!$applications.length || !isSignedIn()) {
        return;
    }

    // A click anywhere on a row opens that application's details; the position inside it is a real link too.
    $applications.on('click', 'tr[data-application-id]', function (event) {
        if ($(event.target).closest('a, button').length) {
            return;
        }

        window.location.assign($(this).attr('data-details-url'));
    });

    renderApplications($applications);
    onMockDataChange(() => renderApplications($applications));
});
