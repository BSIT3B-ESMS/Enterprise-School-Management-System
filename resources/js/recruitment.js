import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { initApplicationDetails } from './application-details.js';
import { formatDate, formatTime, initialsOf } from './format.js';
import {
    addApplication,
    applicationStatuses,
    canHrEditApplication,
    collegeChoiceError,
    deleteApplication,
    findApplication,
    getEmployeeFormOptions,
    isDuplicateApplication,
    listApplications,
    listJobTitles,
    onMockDataChange,
    updateApplication,
} from './mock-data.js';
import { renderPagination } from './pagination.js';
import { personNameRule } from './validation-rules.js';
import { renderPipelineBar } from './pipeline.js';
import { statusClass, workflowFor } from './recruitment-workflow.js';
import { showToast } from './toast.js';

const pageSize = 8;

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

// Digits, spaces, dashes, dots, and parentheses with an optional leading +; 7 to 15 digits in all.
const phonePattern = /^\+?[\d\s().-]+$/;

const sortValue = {
    name: (application) => application.name.toLowerCase(),
    jobTitle: (application) => application.jobTitle.toLowerCase(),
    appliedOn: (application) => application.appliedOn,
    status: (application) => applicationStatuses.indexOf(application.status),
};

// Table view state: search text, filters, sort column and direction (1 ascending, -1 descending), page.
const view = { query: '', status: '', departmentId: '', appliedOn: '', sortKey: 'appliedOn', sortDirection: -1, page: 1 };

// How long the applicant a link (for example, from the Calendar) pointed to stays highlighted in the list.
const highlightSeconds = 4;
let highlightedId = '';
let highlightTimer = null;

function plural(count, noun) {
    return `${count} ${noun}${count === 1 ? '' : 's'}`;
}

function todayIsoDate() {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

/**
 * Applications matching the search and filters, in the current sort order (ties fall back to the newest).
 */
function visibleApplications() {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];

    return listApplications()
        .filter((application) => !query || [application.name, application.email, application.phone, application.jobTitle, application.department]
            .join(' ').toLowerCase().includes(query))
        .filter((application) => !view.status || application.status === view.status)
        .filter((application) => !view.departmentId || application.departmentId === view.departmentId)
        .filter((application) => !view.appliedOn || application.appliedOn === view.appliedOn)
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);
            const order = firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0;

            return order * view.sortDirection || second.appliedOn.localeCompare(first.appliedOn);
        });
}

function statusOptions(placeholder = null) {
    return [
        ...(placeholder ? [$('<option>', { value: '', text: placeholder })] : []),
        ...applicationStatuses.map((status) => $('<option>', { value: status, text: status })),
    ];
}

function departmentOptions(placeholder) {
    return [
        $('<option>', { value: '', text: placeholder }),
        ...getEmployeeFormOptions().departments.map((department) => $('<option>', { value: department.id, text: department.name })),
    ];
}

function renderFilterOptions($root) {
    const departments = getEmployeeFormOptions().departments;

    // A department deleted elsewhere drops out of the options, so fall back to all departments.
    if (!departments.some((department) => department.id === view.departmentId)) {
        view.departmentId = '';
    }

    $root.find('[data-application-filter="status"]').empty().append(...statusOptions('All Statuses')).val(view.status);
    $root.find('[data-application-filter="departmentId"]').empty().append(...departmentOptions('All Colleges')).val(view.departmentId);
}

/**
 * The stacked bar and one filter button per status, each with its count across all applications.
 */
function renderPipeline($root) {
    const applications = listApplications();
    const stages = applicationStatuses.map((stage) => ({
        stage,
        count: applications.filter((application) => application.status === stage).length,
    }));

    $root.find('[data-application-total]').text(`${plural(applications.length, 'application')}, all stages`);
    renderPipelineBar($root.find('[data-application-pipeline]'), stages);

    $root.find('[data-application-stages]').empty().append(...stages.map(({ stage, count }) => (
        $('<div>', { class: 'col' }).append(
            $('<button>', {
                type: 'button',
                class: `pipeline-stage ${statusClass(stage)}`,
                'aria-pressed': String(view.status === stage),
                'data-application-stage': stage,
            }).append(
                $('<span>', { class: 'd-flex align-items-center gap-2' }).append(
                    $('<span>', { class: 'dashboard-swatch', 'aria-hidden': 'true' }),
                    document.createTextNode(stage),
                ),
                $('<strong>', { class: 'tabular-nums', text: count }),
            ),
        )
    )));
}

/**
 * The status as a label (it follows the recruitment workflow, so it is not editable here), with the
 * upcoming demo interview or final interview underneath.
 */
function statusCellFor(application) {
    const { pendingActivity } = workflowFor(application);

    return $('<td>').append(
        $('<span>', { class: `status-pill ${statusClass(application.status)}`, text: application.status }),
        ...(pendingActivity
            ? [$('<small>', {
                class: 'd-block text-body-secondary mt-1',
                text: `${pendingActivity.type} ${formatDate(pendingActivity.date)}, ${formatTime(pendingActivity.time)}`,
            })]
            : []),
    );
}

function rowFor(application) {
    return $('<tr>', {
        class: `employee-row${application.id === highlightedId ? ' is-located' : ''}`,
        tabindex: 0,
        'aria-haspopup': 'dialog',
        title: `View ${application.name}`,
        'data-application-row': application.id,
    }).append(
        $('<td>').append(
            $('<div>', { class: 'd-flex align-items-center gap-2' }).append(
                $('<span>', { class: 'ems-avatar-sm', 'aria-hidden': 'true', text: initialsOf(application.name) }),
                $('<div>', { class: 'min-w-0' }).append(
                    $('<div>', { class: 'fw-medium', text: application.name }),
                    $('<small>', { class: 'text-body-secondary', text: application.email }),
                ),
            ),
        ),
        $('<td>').append(
            $('<div>', { text: application.jobTitle }),
            $('<small>', { class: 'text-body-secondary', text: application.department }),
        ),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatDate(application.appliedOn) }),
        statusCellFor(application),
    );
}

function renderSortHeaders($root) {
    $root.find('[data-sort-column]').each(function () {
        const isSorted = $(this).attr('data-sort-column') === view.sortKey;
        const direction = view.sortDirection > 0 ? 'ascending' : 'descending';

        $(this).attr('aria-sort', isSorted ? direction : 'none');
    });
}

function renderTable($root) {
    const applications = visibleApplications();
    const pageCount = Math.max(1, Math.ceil(applications.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = applications.slice(start, start + pageSize);

    $root.find('[data-application-rows]').empty().append(...pageRows.map(rowFor));

    if (pageRows.length) {
        $root.find('[data-application-empty]').addClass('d-none');
    } else {
        $root.find('[data-application-empty]').removeClass('d-none');
        $root.find('[data-application-empty-hint]').text(
            view.query.trim() || view.status || view.departmentId || view.appliedOn
                ? 'Try a different search or clear the filters.'
                : 'Add an application to get started.',
        );
    }

    $root.find('[data-application-summary]').text(
        `Showing ${applications.length ? start + 1 : 0} to ${start + pageRows.length} of ${applications.length} applications`,
    );
    renderSortHeaders($root);
    renderPagination($root.find('[data-application-pagination]'), view.page, pageCount);
    syncReportLink($root);
}

/**
 * Generate Report starts from the status, department, and application date filtered here.
 */
function syncReportLink($root) {
    const $link = $root.find('[data-report-link]');
    const params = new URLSearchParams();

    [['status', view.status], ['department', view.departmentId], ['appliedOn', view.appliedOn]].forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    $link.attr('href', `${$link.attr('data-report-url')}${params.toString() ? `?${params}` : ''}`);
}

function renderPage($root) {
    renderPipeline($root);
    renderTable($root);
}

/**
 * Moves the table to the page that holds the application, if it matches the current search and filters.
 */
function showPageWith(applicationId) {
    const index = visibleApplications().findIndex((application) => application.id === applicationId);

    if (index !== -1) {
        view.page = Math.floor(index / pageSize) + 1;
    }
}

/**
 * Row clicks and Enter/Space open the details modal; clicks on controls inside a row are left alone. Rows are re-rendered after every change, so focus returns to the row
 * (found again by its ID) once no modal is open.
 */
function initRowDetails($root) {
    const $detailsModal = $('[data-application-details-modal]');
    const interactiveSelector = 'a, button, input, select, option, textarea, label, [data-bs-toggle]';
    let lastOpenedId = null;

    function openDetails(row) {
        // Ignore a repeat click or keypress while a modal is already open.
        if ($('.modal.show').length) {
            return;
        }

        lastOpenedId = $(row).attr('data-application-row');
        Modal.getOrCreateInstance($detailsModal[0]).show(row);
    }

    $root.on('click', 'tr[data-application-row]', function (event) {
        if ($(event.target).closest(interactiveSelector).length) {
            return;
        }

        openDetails(this);
    });

    $root.on('keydown', 'tr[data-application-row]', function (event) {
        if (event.target !== this || (event.key !== 'Enter' && event.key !== ' ')) {
            return;
        }

        event.preventDefault();
        openDetails(this);
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        if (!lastOpenedId || $('.modal.show').length) {
            return;
        }

        const $row = $root.find(`tr[data-application-row="${lastOpenedId}"]`);

        ($row.length ? $row : $root.find('[data-application-add]')).trigger('focus');
        lastOpenedId = null;
    });

    $root.on('click', '[data-application-add]', () => {
        lastOpenedId = null;
    });
}

function fieldFor($form, name) {
    return $form.find(`[name="${name}"]`);
}

function setFieldError($field, message) {
    const errorId = $field.attr('aria-describedby').split(' ').find((id) => id.endsWith('-error'));

    $(`#${errorId}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

/**
 * Each rule returns an error message, or '' when the value is valid. The email is checked against
 * other applications for the same position, so it reads the position field too.
 */
function validationRules($form, editingId) {
    return {
        lastName: personNameRule('Enter the applicant’s last name.'),
        firstName: personNameRule('Enter the applicant’s first name.'),
        middleName: (value) => (value.trim() ? personNameRule('')(value) : ''),
        email: (value) => {
            if (!emailPattern.test(value.trim())) {
                return 'Enter a valid email address.';
            }

            const jobTitleId = String(fieldFor($form, 'jobTitleId').val() ?? '');

            return jobTitleId && isDuplicateApplication(value, jobTitleId, editingId)
                ? 'This applicant already has an application for this position.'
                : '';
        },
        phone: (value) => {
            const digitCount = value.replace(/\D/g, '').length;

            if (!value.trim()) {
                return 'Enter a phone number.';
            }

            return phonePattern.test(value.trim()) && digitCount >= 7 && digitCount <= 15 ? '' : 'Enter a valid phone number.';
        },
        departmentId: (value) => collegeChoiceError(value),
        jobTitleId: (value) => (value ? '' : 'Select the position applied for.'),
        appliedOn: (value) => {
            if (!value) {
                return 'Enter the application date.';
            }

            return value > todayIsoDate() ? 'The application date cannot be in the future.' : '';
        },
    };
}

/**
 * Validates every field, marks errors, and returns the first invalid field (if any).
 */
function validateForm($form, editingId) {
    let $firstInvalid = null;

    Object.entries(validationRules($form, editingId)).forEach(([name, rule]) => {
        const $field = fieldFor($form, name);
        const message = rule(String($field.val() ?? ''));

        setFieldError($field, message);

        if (message && !$firstInvalid) {
            $firstInvalid = $field;
        }
    });

    return $firstInvalid;
}

/**
 * Lists the positions in the chosen department (every position, labeled with its department,
 * when none is chosen), keeping the current choice when it is still listed.
 */
function renderPositionOptions($form, selected = String(fieldFor($form, 'jobTitleId').val() ?? '')) {
    const departmentId = String(fieldFor($form, 'departmentId').val() ?? '');
    const $position = fieldFor($form, 'jobTitleId');
    const positions = listJobTitles()
        .filter((jobTitle) => !departmentId || jobTitle.departmentId === departmentId)
        .sort((first, second) => first.name.localeCompare(second.name));

    $position.empty().append(
        $('<option>', { value: '', text: positions.length || !departmentId ? 'Select position' : 'No positions in this college' }),
        ...positions.map((jobTitle) => $('<option>', {
            value: jobTitle.id,
            text: departmentId ? jobTitle.name : `${jobTitle.name} · ${jobTitle.department}`,
        })),
    );
    $position.val(positions.some((jobTitle) => jobTitle.id === selected) ? selected : '');
}

/**
 * Clears the form and loads current options and defaults (or the application's values).
 */
function prepareForm($form, application) {
    $form[0].reset();
    $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
    $form.find('.invalid-feedback').text('');
    $form.find('[data-application-department-options]').empty().append(...departmentOptions('Select college'));
    $form.find('[data-application-date]').attr('max', todayIsoDate());

    fieldFor($form, 'lastName').val(application?.lastName ?? '');
    fieldFor($form, 'firstName').val(application?.firstName ?? '');
    fieldFor($form, 'middleName').val(application?.middleName ?? '');
    fieldFor($form, 'email').val(application?.email ?? '');
    fieldFor($form, 'phone').val(application?.phone ?? '');
    fieldFor($form, 'departmentId').val(application?.departmentId ?? '');
    renderPositionOptions($form, application?.jobTitleId ?? '');
    fieldFor($form, 'appliedOn').val(application?.appliedOn ?? todayIsoDate());
}

function readApplication($form) {
    const value = (name) => String(fieldFor($form, name).val() ?? '').trim();

    return {
        lastName: value('lastName'),
        firstName: value('firstName'),
        middleName: value('middleName'),
        email: value('email'),
        phone: value('phone'),
        departmentId: value('departmentId'),
        jobTitleId: value('jobTitleId'),
        appliedOn: value('appliedOn'),
    };
}

function initFormModal($modal, $root) {
    const $form = $modal.find('[data-application-form]');
    let editingId = null;

    $modal.on('show.bs.modal', function (event) {
        const requestedId = $(event.relatedTarget).attr('data-application-edit');
        const application = requestedId ? findApplication(requestedId) : null;

        if (requestedId && !application) {
            event.preventDefault();
            showToast('That application no longer exists.', { isError: true });

            return;
        }

        // What an applicant submitted is theirs: HR can read it but not change it.
        if (application && !canHrEditApplication(application)) {
            event.preventDefault();
            showToast('This application was submitted by the applicant, so HR cannot edit it.', { isError: true });

            return;
        }

        editingId = application?.id ?? null;
        prepareForm($form, application);
        $modal.find('[data-application-form-title]').text(application ? 'Edit Application' : 'Add Application');
    });
    $modal.on('shown.bs.modal', () => fieldFor($form, 'lastName').trigger('focus'));

    // Department narrows the positions; choosing a position (with no department yet) fills it in.
    $form.on('change', '[name="departmentId"]', () => renderPositionOptions($form));
    $form.on('change', '[name="jobTitleId"]', function () {
        const jobTitle = listJobTitles().find((candidate) => candidate.id === $(this).val());

        if (jobTitle && !fieldFor($form, 'departmentId').val()) {
            fieldFor($form, 'departmentId').val(jobTitle.departmentId);
            renderPositionOptions($form, jobTitle.id);
        }
    });

    // Once a field has been flagged, re-check it as the user corrects it. The position affects the
    // duplicate check on the email, and the department affects the position list.
    $form.on('input change', '[name]', function () {
        const rules = validationRules($form, editingId);
        const related = { departmentId: ['jobTitleId'], jobTitleId: ['departmentId', 'email'] };
        const names = [$(this).attr('name'), ...(related[$(this).attr('name')] ?? [])];

        names.forEach((name) => {
            const $field = fieldFor($form, name);

            if ($field.hasClass('is-invalid')) {
                setFieldError($field, rules[name](String($field.val() ?? '')));
            }
        });
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        // A second Enter or click while the modal is closing must not save again.
        if (!$modal.hasClass('show')) {
            return;
        }

        const $firstInvalid = validateForm($form, editingId);

        if ($firstInvalid) {
            $firstInvalid.trigger('focus');

            return;
        }

        const details = readApplication($form);

        if (editingId) {
            if (!updateApplication(editingId, details)) {
                showToast('This application can no longer be edited.', { isError: true });
                Modal.getOrCreateInstance($modal[0]).hide();

                return;
            }

            showToast('Application updated.');
        } else {
            const application = addApplication(details);

            showPageWith(application.id);
            renderTable($root);
            showToast(`Application added: ${application.name}.`);
        }

        Modal.getOrCreateInstance($modal[0]).hide();
    });
}

function initDeleteModal($modal) {
    let deletingId = null;

    $modal.on('show.bs.modal', function (event) {
        const application = findApplication($(event.relatedTarget).attr('data-application-delete'));

        if (!application) {
            event.preventDefault();
            showToast('That application no longer exists.', { isError: true });

            return;
        }

        deletingId = application.id;
        $modal.find('[data-application-delete-message]')
            .text(`Delete the application from ${application.name} for ${application.jobTitle}? This cannot be undone.`);
    });

    $modal.on('click', '[data-application-delete-confirm]', function () {
        // A second click while the modal is closing must not delete again.
        if (!deletingId) {
            return;
        }

        const application = deleteApplication(deletingId);

        deletingId = null;
        Modal.getOrCreateInstance($modal[0]).hide();

        if (application) {
            showToast(`Application deleted: ${application.name}.`);
        } else {
            showToast('That application no longer exists.', { isError: true });
        }
    });
}

/**
 * Opens the list at one applicant (?applicant= is the application ID): any search or filter that would hide them is cleared,
 * the list moves to their page, scrolls to their row, and highlights it for a few seconds. Nothing is changed or opened. An ID
 * that matches no application only shows a notification, so no other record is shown in its place.
 */
function locateApplicant($root, applicantId) {
    // The link is used up, so a reload or Clear Filters starts from the plain list.
    window.history.replaceState(null, '', window.location.pathname);

    const application = findApplication(applicantId);

    if (!application) {
        showToast('That applicant could not be found in Recruitment. They may have been removed.', { isError: true });

        return;
    }

    if (!visibleApplications().some((candidate) => candidate.id === application.id)) {
        Object.assign(view, { query: '', status: '', departmentId: '', appliedOn: '' });
        $root.find('[data-application-search], [data-application-filter]').val('');
    }

    showPageWith(application.id);
    highlightedId = application.id;
    renderPage($root);

    const row = $root.find(`tr[data-application-row="${application.id}"]`)[0];
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    row.scrollIntoView({ block: 'center', behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    row.focus({ preventScroll: true });

    clearTimeout(highlightTimer);
    highlightTimer = setTimeout(() => {
        highlightedId = '';
        $root.find('tr.is-located').removeClass('is-located');
    }, highlightSeconds * 1000);
}

function setStatusFilter($root, status) {
    view.status = status;
    view.page = 1;
    $root.find('[data-application-filter="status"]').val(status);
    renderPage($root);
}

$(function () {
    const $recruitment = $('[data-page="recruitment"]');

    if (!$recruitment.length) {
        return;
    }

    // The header's pending-candidates link opens this page with ?status=Applied.
    const params = new URLSearchParams(window.location.search);
    const linkedStatus = params.get('status') ?? '';
    const linkedApplicant = params.get('applicant') ?? '';

    view.status = !linkedApplicant && applicationStatuses.includes(linkedStatus) ? linkedStatus : '';

    $recruitment.on('input', '[data-application-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderTable($recruitment);
    });

    $recruitment.on('change', '[data-application-filter]', function () {
        if ($(this).attr('data-application-filter') === 'status') {
            setStatusFilter($recruitment, String($(this).val()));

            return;
        }

        view[$(this).attr('data-application-filter')] = String($(this).val());
        view.page = 1;
        renderTable($recruitment);
    });

    // Clears the search, every filter (including the pipeline stage and any status linked from the
    // header), and returns to page 1.
    $recruitment.on('click', '[data-application-clear]', function () {
        Object.assign(view, { query: '', status: '', departmentId: '', appliedOn: '', page: 1 });
        $recruitment.find('[data-application-search], [data-application-filter]').val('');
        window.history.replaceState(null, '', window.location.pathname);
        renderPage($recruitment);
    });

    $recruitment.on('click', '[data-application-stage]', function () {
        const stage = $(this).attr('data-application-stage');

        setStatusFilter($recruitment, view.status === stage ? '' : stage);
        $recruitment.find(`[data-application-stage="${stage}"]`).trigger('focus');
    });

    $recruitment.on('click', '[data-sort-key]', function () {
        const key = $(this).attr('data-sort-key');

        if (view.sortKey === key) {
            view.sortDirection *= -1;
        } else {
            view.sortKey = key;
            view.sortDirection = key === 'appliedOn' ? -1 : 1;
        }

        view.page = 1;
        renderTable($recruitment);
    });

    $recruitment.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable($recruitment);
    });

    initRowDetails($recruitment);
    initApplicationDetails($('[data-application-details-modal]'));
    initFormModal($('[data-application-form-modal]'), $recruitment);
    initDeleteModal($('[data-application-delete-modal]'));

    renderFilterOptions($recruitment);
    renderPage($recruitment);

    if (linkedApplicant) {
        locateApplicant($recruitment, linkedApplicant);
    }

    onMockDataChange(() => {
        renderFilterOptions($recruitment);
        renderPage($recruitment);
    });
});
