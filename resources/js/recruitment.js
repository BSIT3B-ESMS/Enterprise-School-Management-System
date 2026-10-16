import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { isInDateRange, syncDateRange } from './date-filter.js';
import { formatDate, formatDateTime, initialsOf } from './format.js';
import {
    addApplication,
    applicationStatuses,
    deleteApplication,
    findApplication,
    getApplicationHistory,
    getEmployeeFormOptions,
    isDuplicateApplication,
    listApplications,
    listJobTitles,
    onMockDataChange,
    setApplicationStatus,
    updateApplication,
} from './mock-data.js';
import { renderPagination } from './pagination.js';
import { renderPipelineBar } from './pipeline.js';
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
const view = { query: '', status: '', departmentId: '', startDate: '', endDate: '', sortKey: 'appliedOn', sortDirection: -1, page: 1 };

function plural(count, noun) {
    return `${count} ${noun}${count === 1 ? '' : 's'}`;
}

function todayIsoDate() {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

function statusClass(status) {
    return `stage-${status.toLowerCase()}`;
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
        .filter((application) => isInDateRange(application.appliedOn, view.startDate, view.endDate))
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
    $root.find('[data-application-filter="departmentId"]').empty().append(...departmentOptions('All Departments')).val(view.departmentId);
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

function statusSelectFor(application) {
    return $('<select>', {
        class: `form-select form-select-sm application-status-select ${statusClass(application.status)}`,
        'aria-label': `Status for ${application.name}`,
        'data-application-status': application.id,
    }).append(...statusOptions()).val(application.status);
}

function rowFor(application) {
    return $('<tr>', {
        class: 'employee-row',
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
        $('<td>').append(statusSelectFor(application)),
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
            view.query.trim() || view.status || view.departmentId || view.startDate || view.endDate
                ? 'Try a different search or clear the filters.'
                : 'Add an application to get started.',
        );
    }

    $root.find('[data-application-summary]').text(
        `Showing ${applications.length ? start + 1 : 0} to ${start + pageRows.length} of ${applications.length} applications`,
    );
    renderSortHeaders($root);
    renderPagination($root.find('[data-application-pagination]'), view.page, pageCount);
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
 * Row clicks and Enter/Space open the details modal; clicks on controls inside a row (the status
 * dropdown) are left alone. Rows are re-rendered after every change, so focus returns to the row
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

/**
 * The row's status dropdown saves on change. The table re-renders (the row may leave the current
 * filter), so focus moves back to the same dropdown when its row is still shown.
 */
function initInlineStatus($root) {
    $root.on('change', '[data-application-status]', function () {
        const id = $(this).attr('data-application-status');
        const status = String($(this).val());
        const application = setApplicationStatus(id, status);

        if (!application) {
            showToast('That application no longer exists.', { isError: true });
            renderPage($root);

            return;
        }

        showToast(`${application.name} moved to ${status}.`);
        $root.find(`[data-application-status="${id}"]`).trigger('focus');
    });
}

function renderHistory($modal, history) {
    const $section = $modal.find('[data-application-history-section]');

    if (!history.length) {
        $section.addClass('d-none');

        return;
    }

    $section.removeClass('d-none');
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

function initDetailsModal($modal) {
    $modal.on('show.bs.modal', function (event) {
        const application = findApplication($(event.relatedTarget).attr('data-application-row'));

        if (!application) {
            event.preventDefault();
            showToast('That application no longer exists.', { isError: true });

            return;
        }

        const values = {
            name: application.name,
            email: application.email,
            phone: application.phone,
            jobTitle: application.jobTitle,
            department: application.department,
            appliedOn: formatDate(application.appliedOn),
            status: application.status,
        };

        Object.entries(values).forEach(([field, value]) => {
            $modal.find(`[data-application-field="${field}"]`).text(value);
        });

        $modal.find('[data-application-status-pill]')
            .attr('class', `status-pill ${statusClass(application.status)}`)
            .text(application.status);
        $modal.find('[data-application-edit]').attr('data-application-edit', application.id);
        $modal.find('[data-application-delete]').attr('data-application-delete', application.id);
        renderHistory($modal, getApplicationHistory(application.id));
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
        name: (value) => (value.trim() ? '' : 'Enter the applicant’s full name.'),
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
        departmentId: (value) => (value ? '' : 'Select a department.'),
        jobTitleId: (value) => (value ? '' : 'Select the position applied for.'),
        appliedOn: (value) => {
            if (!value) {
                return 'Enter the application date.';
            }

            return value > todayIsoDate() ? 'The application date cannot be in the future.' : '';
        },
        status: (value) => (applicationStatuses.includes(value) ? '' : 'Select a status.'),
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
        $('<option>', { value: '', text: positions.length || !departmentId ? 'Select position' : 'No positions in this department' }),
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
    $form.find('[data-application-department-options]').empty().append(...departmentOptions('Select department'));
    $form.find('[data-application-status-options]').empty().append(...statusOptions());
    $form.find('[data-application-date]').attr('max', todayIsoDate());

    fieldFor($form, 'name').val(application?.name ?? '');
    fieldFor($form, 'email').val(application?.email ?? '');
    fieldFor($form, 'phone').val(application?.phone ?? '');
    fieldFor($form, 'departmentId').val(application?.departmentId ?? '');
    renderPositionOptions($form, application?.jobTitleId ?? '');
    fieldFor($form, 'appliedOn').val(application?.appliedOn ?? todayIsoDate());
    fieldFor($form, 'status').val(application?.status ?? 'Applied');
}

function readApplication($form) {
    const value = (name) => String(fieldFor($form, name).val() ?? '').trim();

    return {
        name: value('name'),
        email: value('email'),
        phone: value('phone'),
        departmentId: value('departmentId'),
        jobTitleId: value('jobTitleId'),
        appliedOn: value('appliedOn'),
        status: value('status'),
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

        editingId = application?.id ?? null;
        prepareForm($form, application);
        $modal.find('[data-application-form-title]').text(application ? 'Edit Application' : 'Add Application');
    });
    $modal.on('shown.bs.modal', () => fieldFor($form, 'name').trigger('focus'));

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
            updateApplication(editingId, details);
            showToast('Application saved.');
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
    const linkedStatus = new URLSearchParams(window.location.search).get('status') ?? '';

    view.status = applicationStatuses.includes(linkedStatus) ? linkedStatus : '';

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
        syncDateRange($recruitment);
        renderTable($recruitment);
    });

    // Clears the search, every filter (including the pipeline stage and any status linked from the
    // header), and returns to page 1.
    $recruitment.on('click', '[data-application-clear]', function () {
        Object.assign(view, { query: '', status: '', departmentId: '', startDate: '', endDate: '', page: 1 });
        $recruitment.find('[data-application-search], [data-application-filter]').val('');
        window.history.replaceState(null, '', window.location.pathname);
        syncDateRange($recruitment);
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
    initInlineStatus($recruitment);
    initDetailsModal($('[data-application-details-modal]'));
    initFormModal($('[data-application-form-modal]'), $recruitment);
    initDeleteModal($('[data-application-delete-modal]'));

    renderFilterOptions($recruitment);
    renderPage($recruitment);
    onMockDataChange(() => {
        renderFilterOptions($recruitment);
        renderPage($recruitment);
    });
});
