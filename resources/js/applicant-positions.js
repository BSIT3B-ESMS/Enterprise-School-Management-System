import $ from './jquery.js';
import { formatSalaryRange } from './format.js';
import {
    applicationBlockFor,
    findPosition,
    getApplicantAccount,
    listApplicantApplications,
    listPositions,
    onMockDataChange,
} from './mock-data.js';
import { currentAccountId } from './applicant-session.js';
import { isSignedIn, plural } from './applicant-portal.js';

const notSpecified = 'Not specified';

/**
 * The signed-in applicant on a public page ('' when no one is signed in this session). In the Applicant interface
 * the portal decides who is signed in (see applicant-portal.js).
 */
function publicAccountId() {
    const accountId = currentAccountId();

    return accountId && getApplicantAccount(accountId) ? accountId : '';
}

function accountFor($root) {
    if ($root.attr('data-positions-context') === 'portal') {
        return isSignedIn() ? currentAccountId() : '';
    }

    return publicAccountId();
}

// "₱50,000.00–₱72,000.00 per month" for a position's base salary range (the job title's range in HR); a part-time
// position's figures are full-time equivalents. "Not specified" when HR has set none.
function salaryText(position) {
    if (!position.salaryMin && !position.salaryMax) {
        return notSpecified;
    }

    return `${formatSalaryRange(position.salaryMin, position.salaryMax)} per month${position.employmentType === 'Part-time' ? ' (full-time equivalent)' : ''}`;
}

// "Open · 4 openings" for an open position, "Closed" for one with no openings.
function statusBadge(position) {
    return position.isOpen
        ? $('<span>', { class: 'status-pill employment-active', text: `Open · ${plural(position.openings, 'opening')}` })
        : $('<span>', { class: 'ems-chip', text: 'Closed' });
}

// Apply: the application form for an applicant who is signed in. For anyone else it is the registration form, which
// the link reaches only through the Terms and Conditions modal (data-terms-continue, see applicant-terms.js). Either
// way it keeps the chosen position.
function applyHref($root, positionId, accountId) {
    const base = accountId ? $root.attr('data-apply-url') : $root.attr('data-register-url');

    return `${base}?position=${encodeURIComponent(positionId)}`;
}

// Marks an Apply link as one that shows the Terms first (nobody is signed in), and where declining goes: nowhere on the
// list (the visitor stays on Open Positions), or back to Open Positions from a position's details.
function setTermsGate($link, needsTerms, declineUrl = '') {
    if (needsTerms) {
        $link.attr('data-terms-continue', '');
    } else {
        $link.removeAttr('data-terms-continue');
    }

    if (needsTerms && declineUrl) {
        $link.attr('data-terms-decline-url', declineUrl);
    } else {
        $link.removeAttr('data-terms-decline-url');
    }
}

// One job card. The cards in the full list carry an id (position-t…) so a link can bring one into view; the featured
// copies above them do not, so no id appears twice.
function positionCard($root, position, appliedIds, accountId, isSelected, { withId = true } = {}) {
    const detailsUrl = `${$root.attr('data-details-url')}/${encodeURIComponent(position.id)}`;
    const facts = [position.employmentType || notSpecified, position.location || notSpecified];

    return $('<div>', { class: 'col' }).append(
        $('<article>', {
            ...(withId ? { id: `position-${position.id}` } : {}),
            class: `card dashboard-panel h-100 d-flex flex-column${isSelected ? ' border-primary' : ''}`,
        }).append(
            $('<div>', { class: 'd-flex justify-content-between align-items-start gap-3 mb-1' }).append(
                $('<h3>', { class: 'fs-6 fw-semibold mb-0' }).append(
                    $('<a>', { class: 'text-reset text-decoration-none', href: detailsUrl, text: position.name }),
                ),
                appliedIds.has(position.id) ? $('<span>', { class: 'ems-chip', text: 'Applied' }) : statusBadge(position),
            ),
            $('<div>', { class: 'text-body-secondary', text: position.department }),
            $('<div>', { class: 'small text-body-secondary mb-2', text: facts.join(' · ') }),
            $('<div>', { class: 'fw-semibold mb-2' }).append(
                $('<span>', { class: 'visually-hidden', text: 'Base salary range: ' }),
                $('<span>', { class: 'tabular-nums', text: salaryText(position) }),
            ),
            position.summary
                ? $('<p>', { class: 'mb-2', text: position.summary })
                : $('<p>', { class: 'text-body-secondary mb-2', text: 'No description is available.' }),
            $('<div>', { class: 'mt-auto pt-2 d-flex flex-wrap gap-2' }).append(
                $('<a>', {
                    class: 'btn btn-sm btn-outline-primary',
                    href: detailsUrl,
                    text: 'View Details',
                    'aria-label': `View details for ${position.name}`,
                }),
                appliedIds.has(position.id)
                    ? $('<span>', { class: 'btn btn-sm disabled', text: 'Already applied' })
                    : $('<a>', {
                        class: 'btn btn-sm btn-primary',
                        href: applyHref($root, position.id, accountId),
                        text: 'Apply Now',
                        'aria-label': `Apply for ${position.name}`,
                        ...(accountId ? {} : { 'data-terms-continue': '' }),
                    }),
            ),
        ),
    );
}

function initPositionList($root) {
    // Declining the Terms returns here with ?position= so the chosen position and its Apply button stay in view.
    const selectedId = new URLSearchParams(window.location.search).get('position') ?? '';
    const $search = $root.find('[data-positions-search]');
    const $type = $root.find('[data-positions-type]');
    const $department = $root.find('[data-positions-department]');

    function render() {
        // Only open positions are listed; a closed one can still be opened from a saved link and says it is closed.
        const open = listPositions().filter((position) => position.isOpen);
        const departments = [...new Set(open.map((position) => position.department))].sort();
        const selectedDepartment = String($department.val() ?? '');
        const query = String($search.val() ?? '').trim().toLowerCase();
        const accountId = accountFor($root);
        const appliedIds = new Set(listApplicantApplications(accountId).map((application) => application.jobTitleId));

        $department.empty().append(
            $('<option>', { value: '', text: 'All colleges' }),
            ...departments.map((department) => $('<option>', { value: department, text: department })),
        );
        $department.val(departments.includes(selectedDepartment) ? selectedDepartment : '');

        const type = String($type.val() ?? '');
        const shown = open.filter((position) => (
            (!$department.val() || position.department === $department.val())
            && (!type || position.employmentType === type)
            && (!query || `${position.name} ${position.department} ${position.employmentType} ${position.location} ${position.summary} ${position.description}`.toLowerCase().includes(query))
        ));

        $root.find('[data-positions-open-count]').text(open.length);
        $root.find('[data-positions-open]').empty().append(...shown.map((position) => positionCard($root, position, appliedIds, accountId, position.id === selectedId)));
        $root.find('[data-positions-none]').toggleClass('d-none', open.length > 0);
        $root.find('[data-positions-no-match]').toggleClass('d-none', open.length === 0 || shown.length > 0);
        renderFeatured($root, open, appliedIds, accountId);
        renderHiringStats($root, open);
    }

    $search.on('input', render);
    $type.on('change', render);
    $department.on('change', render);
    render();
    document.getElementById(`position-${selectedId}`)?.scrollIntoView({ block: 'center' });
    onMockDataChange(render);
}

// PLP Careers' Featured Job Openings: the open positions with the most openings (then A to Z), up to three.
const featuredCount = 3;

function renderFeatured($root, open, appliedIds, accountId) {
    const $featured = $root.find('[data-positions-featured]');

    if (!$featured.length) {
        return;
    }

    const featured = [...open]
        .sort((first, second) => second.openings - first.openings || first.name.localeCompare(second.name))
        .slice(0, featuredCount);

    $featured.empty().append(...featured.map((position) => positionCard($root, position, appliedIds, accountId, false, { withId: false })));
    $root.find('[data-positions-featured-empty]').toggleClass('d-none', featured.length > 0);
}

// The hero's figures: how many positions are open, and how many openings in all.
function renderHiringStats($root, open) {
    $root.find('[data-careers-stat="positions"]').text(open.length);
    $root.find('[data-careers-stat="openings"]').text(open.reduce((total, position) => total + position.openings, 0));
}

function fillList($details, name, items) {
    $details.find(`[data-position-list="${name}"]`).empty().append(...items.map((item) => $('<li>', { text: item })));
    $details.find(`[data-position-list-empty="${name}"]`).toggleClass('d-none', items.length > 0);
}

/**
 * Apply Now, by who is looking: in the Applicant interface (or signed in on a public page) it opens the application
 * form for the position; on a public page without an account it shows the Terms and Conditions and, once accepted,
 * opens the registration form with the position kept. A closed position, or one the applicant already applied for, has no Apply Now.
 */
function renderActions($root, $details, position) {
    const accountId = accountFor($root);
    const ownApplication = listApplicantApplications(accountId).find((application) => application.jobTitleId === position.id);
    const block = accountId ? applicationBlockFor(accountId, position.id) : '';
    const canApply = position.isOpen && !ownApplication && !block;
    let notice = '';

    if (!position.isOpen) {
        notice = 'This position is closed and is not accepting applications.';
    } else if (ownApplication) {
        notice = `You applied for this position. Its status is ${ownApplication.status}.`;
    } else if (block) {
        notice = block;
    }

    const $apply = $details.find('[data-position-apply]');

    $apply.toggleClass('d-none', !canApply).attr('href', applyHref($root, position.id, accountId));
    setTermsGate($apply, !accountId, `${$root.attr('data-careers-url')}?position=${encodeURIComponent(position.id)}`);
    $details.find('[data-position-closed]').toggleClass('d-none', position.isOpen);
    $details.find('[data-position-view-application]')
        .toggleClass('d-none', !ownApplication)
        .attr('href', ownApplication ? `${$root.attr('data-application-url')}/${encodeURIComponent(ownApplication.id)}` : '#');
    $details.find('[data-position-notice]')
        .text(notice)
        .toggleClass('d-none', !notice)
        .toggleClass('alert-warning', !position.isOpen)
        .toggleClass('alert-secondary', position.isOpen);
}

function initPositionDetails($root) {
    const $details = $root.find('[data-position-details]');
    const id = $details.attr('data-position-id');

    function render() {
        const position = findPosition(id);

        $details.find('[data-position-missing]').toggleClass('d-none', Boolean(position));
        $details.find('[data-position-content]').toggleClass('d-none', !position);

        if (!position) {
            return;
        }

        const values = {
            name: position.name,
            department: position.department,
            employmentType: position.employmentType || notSpecified,
            location: position.location || notSpecified,
            salary: salaryText(position),
            openings: position.isOpen ? position.openings : 'None',
            description: position.description || 'No description is available.',
        };

        Object.entries(values).forEach(([field, value]) => {
            $details.find(`[data-position-field="${field}"]`).text(value);
        });
        $details.find('[data-position-status]').empty().append(statusBadge(position));
        fillList($details, 'responsibilities', position.responsibilities);
        fillList($details, 'qualifications', position.qualifications);
        fillList($details, 'requirements', position.requirements);
        $details.find('[data-position-benefits]').empty().append(
            ...(position.benefits.length
                ? position.benefits.map((benefit) => $('<span>', { class: 'ems-chip', text: benefit }))
                : [$('<span>', { class: 'text-body-secondary', text: 'No benefits are listed.' })]),
        );
        renderActions($root, $details, position);
    }

    render();
    onMockDataChange(render);
}

// The public header: Login and Create Account, or My Dashboard for an applicant already signed in this session. (Its
// light/dark toggle is wired in resources/js/theme.js.)
function initPublicHeader() {
    const isSignedInHere = Boolean(publicAccountId());
    const $header = $('.public-header');

    $header.find('[data-public-signed-out]').toggleClass('d-none', isSignedInHere);
    $header.find('[data-public-signed-in]').toggleClass('d-none', !isSignedInHere);
}

$(function () {
    const $list = $('[data-page="applicant-positions"]');
    const $details = $('[data-page="applicant-position"]');
    const $page = $list.length ? $list : $details;

    if (!$page.length) {
        return;
    }

    if ($page.attr('data-positions-context') === 'public') {
        initPublicHeader();
    } else if (!isSignedIn()) {
        return;
    }

    if ($list.length) {
        initPositionList($list);
    } else {
        initPositionDetails($details);
    }
});
