import $ from './jquery.js';
import { documentItem } from './employee-documents.js';
import { formatDate } from './format.js';
import { accountId, activityItem, isSignedIn, renderSteps, statusMessages } from './applicant-portal.js';
import { getApplicantApplication, onMockDataChange } from './mock-data.js';
import { statusClass } from './recruitment-workflow.js';

/**
 * Shows each value, or "Not provided" (muted) when it is blank.
 */
export function fillFields($root, values) {
    Object.entries(values).forEach(([field, value]) => {
        const $fields = $root.find(`[data-applicant-field="${field}"]`);

        if (value) {
            $fields.text(value).removeClass('text-body-secondary');
        } else {
            $fields.text('Not provided').addClass('text-body-secondary');
        }
    });
}

/**
 * Lists the submitted documents by category (file names only).
 */
export function fillDocuments($root, documents) {
    $root.find('[data-applicant-documents]').each(function () {
        const key = $(this).attr('data-applicant-documents');
        const files = documents?.[key] ?? [];

        $(this).empty().append(...files.map((file) => documentItem(file)));
        $root.find(`[data-applicant-documents-empty="${key}"]`).toggleClass('d-none', files.length > 0);
    });
}

function render($root) {
    const application = getApplicantApplication(accountId, $root.attr('data-applicant-application'));

    // Not one of this applicant's applications, or HR deleted it.
    $root.find('[data-applicant-missing]').toggleClass('d-none', Boolean(application));
    $root.find('[data-applicant-content]').toggleClass('d-none', !application);

    if (!application) {
        return;
    }

    // Newest first, so the latest demo interview or interview HR set is at the top.
    const activities = [...application.activities].sort((first, second) => (
        `${second.date} ${second.time}`.localeCompare(`${first.date} ${first.time}`)
    ));

    fillFields($root, {
        jobTitle: application.jobTitle,
        department: application.department,
        employmentType: application.employmentType,
        location: application.location,
        appliedOn: formatDate(application.appliedOn),
        status: application.status,
        name: application.name,
        email: application.email,
        phone: application.phone,
        birthdate: application.birthdate ? formatDate(application.birthdate) : '',
        sex: application.sex,
        civilStatus: application.civilStatus,
        address: application.address,
        emergencyContactName: application.emergencyContactName,
        emergencyContactNumber: application.emergencyContactNumber,
        emergencyContactRelationship: application.emergencyContactRelationship,
    });
    $root.find('[data-applicant-position-link]').attr('href', `${$root.attr('data-position-url')}/${encodeURIComponent(application.jobTitleId)}`);
    $root.find('[data-applicant-status-pill]').attr('class', `status-pill ${statusClass(application.status)}`).text(application.status);
    $root.find('[data-applicant-status-message]').text(statusMessages[application.status] ?? '');
    renderSteps($root, application);
    $root.find('[data-applicant-activities-empty]').toggleClass('d-none', activities.length > 0);
    $root.find('[data-applicant-activities]').empty().append(...activities.map((activity) => activityItem(activity)));
    fillDocuments($root, application.documents);
}

$(function () {
    const $page = $('[data-page="applicant-application"]');

    if (!$page.length || !isSignedIn()) {
        return;
    }

    render($page);
    onMockDataChange(() => render($page));
});
