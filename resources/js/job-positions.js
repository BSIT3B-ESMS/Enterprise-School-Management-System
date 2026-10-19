import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { formatSalaryRange } from './format.js';
import {
    addJobTitle,
    deleteJobTitle,
    findJobTitle,
    getBenefitNames,
    getEmployeeFormOptions,
    employmentTypes,
    isJobTitleNameTaken,
    listJobTitles,
    onMockDataChange,
    setJobPostingStatus,
    updateJobTitle,
} from './mock-data.js';
import { renderPagination } from './pagination.js';
import { salaryRangeErrors } from './validation-rules.js';
import { showToast } from './toast.js';

const pageSize = 8;

// New postings start with the statutory and standard benefits (the first five), like new job titles.
const defaultBenefitCount = 5;

const notSpecified = 'Not specified';

const sortValue = {
    name: (posting) => posting.name.toLowerCase(),
    department: (posting) => posting.department.toLowerCase(),
    employmentType: (posting) => posting.employmentType.toLowerCase(),
    salary: (posting) => Number(posting.salaryMin),
    openings: (posting) => (posting.isOpen ? Number(posting.openings) : 0),
    status: (posting) => (posting.isOpen ? 0 : 1),
};

// Numbers sort highest first when chosen; text columns sort A to Z (Status puts Open first).
const descendingByDefault = ['salary', 'openings'];

// Table view state: search text, department, employment type and status filters, sort column and direction (1 ascending, -1 descending), page.
const view = { query: '', departmentId: '', employmentType: '', status: '', sortKey: 'status', sortDirection: 1, page: 1 };

const plural = (count, noun) => `${count} ${noun}${count === 1 ? '' : 's'}`;

function visiblePostings() {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];

    return listJobTitles()
        .filter((posting) => !query || `${posting.name} ${posting.department} ${posting.employmentType} ${posting.location}`.toLowerCase().includes(query))
        .filter((posting) => !view.departmentId || posting.departmentId === view.departmentId)
        .filter((posting) => !view.employmentType || posting.employmentType === view.employmentType)
        .filter((posting) => !view.status || (view.status === 'Open') === posting.isOpen)
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);
            const order = firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0;

            return order * view.sortDirection || first.name.localeCompare(second.name);
        });
}

function statusPill(posting) {
    return posting.isOpen
        ? $('<span>', { class: 'status-pill employment-active', text: 'Open' })
        : $('<span>', { class: 'ems-chip', text: 'Closed' });
}

/**
 * Why a posting cannot be removed, or '' when no employee or application uses it. Closing it is the way to take it down then.
 */
function removeBlockedMessage({ name, headcount, applicationCount }) {
    if (!headcount && !applicationCount) {
        return '';
    }

    const users = [
        headcount ? plural(headcount, 'employee') : '',
        applicationCount ? plural(applicationCount, 'application') : '',
    ].filter(Boolean).join(' and ');

    return `${name} is used by ${users}, so it cannot be removed. Close the posting instead.`;
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

    $root.find('[data-job-position-filter="departmentId"]').empty().append(...departmentOptions('All Departments')).val(view.departmentId);

    // The same two employment types the posting form offers.
    $root.find('[data-job-position-filter="employmentType"]').empty().append(
        $('<option>', { value: '', text: 'All Employment Types' }),
        ...employmentTypes.map((type) => $('<option>', { value: type, text: type })),
    ).val(view.employmentType);
}

function rowFor(posting) {
    return $('<tr>', {
        class: 'employee-row',
        tabindex: 0,
        'aria-haspopup': 'dialog',
        title: `View ${posting.name}`,
        'data-job-position-row': posting.id,
    }).append(
        $('<td>').append(
            $('<div>', { class: 'fw-medium', text: posting.name }),
            $('<small>', { class: 'text-body-secondary', text: posting.location || notSpecified }),
        ),
        $('<td>', { text: posting.department }),
        $('<td>', { text: posting.employmentType || notSpecified }),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatSalaryRange(posting.salaryMin, posting.salaryMax) }),
        $('<td>', { class: 'tabular-nums', text: posting.isOpen ? posting.openings : '—' }),
        $('<td>').append(statusPill(posting)),
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
    const postings = visiblePostings();
    const pageCount = Math.max(1, Math.ceil(postings.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = postings.slice(start, start + pageSize);

    $root.find('[data-job-position-rows]').empty().append(...pageRows.map(rowFor));
    $root.find('[data-job-position-empty]').toggleClass('d-none', pageRows.length > 0);
    $root.find('[data-job-position-empty-hint]').text(
        view.query.trim() || view.departmentId || view.employmentType || view.status ? 'Try a different search or clear the filters.' : 'Add a job posting to get started.',
    );
    $root.find('[data-job-position-summary]').text(
        `Showing ${postings.length ? start + 1 : 0} to ${start + pageRows.length} of ${plural(postings.length, 'job posting')}`,
    );
    renderSortHeaders($root);
    renderPagination($root.find('[data-job-position-pagination]'), view.page, pageCount);
}

/**
 * Moves the table to the page that holds the posting, if it matches the current search and filters.
 */
function showPageWith(postingId) {
    const index = visiblePostings().findIndex((posting) => posting.id === postingId);

    if (index !== -1) {
        view.page = Math.floor(index / pageSize) + 1;
    }
}

/**
 * A click on a row, or Enter or Space on a focused row, opens that posting's details. Rows are re-rendered after every
 * change, so focus goes back to the row (found again by its ID) once no modal is open.
 */
function initRowDetails($root, $detailsModal) {
    let lastOpenedId = null;

    function openDetails(row) {
        // Ignore a repeat click or keypress while a modal is already open.
        if ($('.modal.show').length) {
            return;
        }

        lastOpenedId = $(row).attr('data-job-position-row');
        Modal.getOrCreateInstance($detailsModal[0]).show(row);
    }

    $root.on('click', 'tr[data-job-position-row]', function () {
        openDetails(this);
    });

    $root.on('keydown', 'tr[data-job-position-row]', function (event) {
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

        const $row = $root.find(`tr[data-job-position-row="${lastOpenedId}"]`);

        ($row.length ? $row : $root.find('[data-job-position-add]')).trigger('focus');
        lastOpenedId = null;
    });

    $root.on('click', '[data-job-position-add]', () => {
        lastOpenedId = null;
    });
}

function fillList($modal, name, items) {
    $modal.find(`[data-job-position-list="${name}"]`).empty().append(
        ...(items.length ? items.map((item) => $('<li>', { text: item })) : [$('<li>', { class: 'list-unstyled text-body-secondary', text: 'None listed.' })]),
    );
}

function initDetailsModal($modal, careersUrl) {
    $modal.on('show.bs.modal', function (event) {
        const posting = findJobTitle($(event.relatedTarget).attr('data-job-position-row'));

        if (!posting) {
            event.preventDefault();
            showToast('That job posting no longer exists.', { isError: true });

            return;
        }

        const values = {
            name: posting.name,
            department: posting.department,
            employmentType: posting.employmentType || notSpecified,
            location: posting.location || notSpecified,
            salary: formatSalaryRange(posting.salaryMin, posting.salaryMax),
            openings: posting.isOpen ? String(posting.openings) : 'None (closed)',
            applicationCount: String(posting.applicationCount),
            headcount: String(posting.headcount),
            summary: posting.summary || notSpecified,
            description: posting.description || notSpecified,
        };

        Object.entries(values).forEach(([field, value]) => {
            $modal.find(`[data-job-position-field="${field}"]`).text(value);
        });
        $modal.find('[data-job-position-status]').empty().append(statusPill(posting));
        fillList($modal, 'responsibilities', posting.responsibilities);
        fillList($modal, 'qualifications', posting.qualifications);
        fillList($modal, 'requirements', posting.requirements);
        $modal.find('[data-job-position-benefits]').empty().append(
            ...(posting.benefits.length
                ? posting.benefits.map((benefit) => $('<span>', { class: 'ems-chip', text: benefit }))
                : [$('<span>', { class: 'text-body-secondary', text: 'No benefits selected.' })]),
        );
        $modal.find('[data-job-position-public-link]')
            .attr('href', `${careersUrl}/${encodeURIComponent(posting.id)}`)
            .toggleClass('d-none', !posting.isOpen);
        $modal.find('[data-job-position-edit]').attr('data-job-position-edit', posting.id);
        $modal.find('[data-job-position-remove]').attr('data-job-position-remove', posting.id);
        $modal.find('[data-job-position-toggle]')
            .attr('data-job-position-toggle', posting.id)
            .text(posting.isOpen ? 'Close Posting' : 'Open Posting');
    });
}

function fieldFor($form, name) {
    return $form.find(`[name="${name}"]`);
}

function setFieldError($field, message) {
    const errorId = String($field.attr('aria-describedby') ?? '').split(' ').find((id) => id.endsWith('-error'));

    if (errorId) {
        $(`#${errorId}`).text(message);
    }

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

const linesOf = (value) => String(value).split('\n').map((line) => line.trim()).filter(Boolean);

/**
 * Each rule returns an error message, or '' when the value is valid. The title must be unique within its department,
 * so it reads the department field too.
 */
function validationRules($form, editingId) {
    const requiredText = (message) => (value) => (value.trim() ? '' : message);

    return {
        name: (value) => {
            if (!value.trim()) {
                return 'Enter a job title.';
            }

            const departmentId = String(fieldFor($form, 'departmentId').val() ?? '');

            return isJobTitleNameTaken(value, departmentId, editingId) ? 'This title already exists in that department.' : '';
        },
        departmentId: (value) => (value ? '' : 'Select a department.'),
        employmentType: (value) => (value ? '' : 'Select an employment type.'),
        location: requiredText('Enter the location.'),
        // The minimum and maximum are checked together: both needed, 0 or more, the maximum not lower than the minimum.
        salaryMin: () => salaryRangeErrors(fieldFor($form, 'salaryMin').val(), fieldFor($form, 'salaryMax').val()).salaryMin ?? '',
        salaryMax: () => salaryRangeErrors(fieldFor($form, 'salaryMin').val(), fieldFor($form, 'salaryMax').val()).salaryMax ?? '',
        openings: (value) => (Number.isInteger(Number(value)) && Number(value) >= 1 && value !== '' ? '' : 'Enter at least 1 opening.'),
        summary: requiredText('Enter a short description.'),
        description: requiredText('Enter the full description.'),
        responsibilities: (value) => (linesOf(value).length ? '' : 'Enter at least one responsibility.'),
        qualifications: (value) => (linesOf(value).length ? '' : 'Enter at least one qualification.'),
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

function renderBenefitOptions($form, selected) {
    $form.find('[data-job-position-benefit-options]').empty().append(...getBenefitNames().map((benefit, index) => {
        const id = `job-position-benefit-${index}`;

        return $('<div>', { class: 'col' }).append(
            $('<div>', { class: 'form-check' }).append(
                $('<input>', { class: 'form-check-input', type: 'checkbox', id, name: 'benefits', value: benefit, checked: selected.includes(benefit) }),
                $('<label>', { class: 'form-check-label', for: id, text: benefit }),
            ),
        );
    }));
}

function initFormModal($modal, $root) {
    const $form = $modal.find('[data-job-position-form]');
    let editingId = null;

    $form.find('[data-job-position-department-options]').append(...departmentOptions('Select department'));
    fieldFor($form, 'employmentType').append(...employmentTypes.map((type) => $('<option>', { value: type, text: type })));

    $modal.on('show.bs.modal', function (event) {
        const posting = findJobTitle($(event.relatedTarget).attr('data-job-position-edit') ?? '');

        editingId = posting ? posting.id : null;
        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $form.find('.invalid-feedback').text('');
        $form.find('[data-job-position-department-options]').empty().append(...departmentOptions('Select department'));
        $modal.find('[data-job-position-form-title]').text(posting ? 'Edit Job Posting' : 'Add Job Posting');

        const values = posting
            ? {
                name: posting.name,
                departmentId: posting.departmentId,
                employmentType: posting.employmentType,
                location: posting.location,
                salaryMin: posting.salaryMin,
                salaryMax: posting.salaryMax,
                status: posting.isOpen ? 'Open' : 'Closed',
                openings: posting.isOpen ? posting.openings : posting.lastOpenings || 1,
                summary: posting.summary,
                description: posting.description,
                responsibilities: posting.responsibilities.join('\n'),
                qualifications: posting.qualifications.join('\n'),
                requirements: posting.requirements.join('\n'),
            }
            : {
                name: '', departmentId: '', employmentType: '', location: '', salaryMin: '', salaryMax: '', status: 'Open', openings: 1,
                summary: '', description: '', responsibilities: '', qualifications: '', requirements: '',
            };

        Object.entries(values).forEach(([name, value]) => fieldFor($form, name).val(value));
        renderBenefitOptions($form, posting ? posting.benefits : getBenefitNames().slice(0, defaultBenefitCount));
    });

    $modal.on('shown.bs.modal', () => fieldFor($form, 'name').trigger('focus'));

    // Once a field has been flagged, re-check it as the user corrects it. The title also depends on the department.
    $form.on('input change', '.is-invalid', function () {
        const rules = validationRules($form, editingId);
        const name = $(this).attr('name');

        if (rules[name]) {
            setFieldError($(this), rules[name](String($(this).val() ?? '')));
        }
    });

    // The salary range is one check: changing either end re-checks both once it has been flagged.
    $form.on('input change', '[name="salaryMin"], [name="salaryMax"]', function () {
        if (fieldFor($form, 'salaryMin').hasClass('is-invalid') || fieldFor($form, 'salaryMax').hasClass('is-invalid')) {
            const errors = salaryRangeErrors(fieldFor($form, 'salaryMin').val(), fieldFor($form, 'salaryMax').val());

            setFieldError(fieldFor($form, 'salaryMin'), errors.salaryMin ?? '');
            setFieldError(fieldFor($form, 'salaryMax'), errors.salaryMax ?? '');
        }
    });

    $form.on('change', '[name="departmentId"]', function () {
        const $name = fieldFor($form, 'name');

        if ($name.hasClass('is-invalid')) {
            setFieldError($name, validationRules($form, editingId).name(String($name.val() ?? '')));
        }
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        const $firstInvalid = validateForm($form, editingId);

        if ($firstInvalid) {
            $firstInvalid.trigger('focus');

            return;
        }

        const isOpen = String(fieldFor($form, 'status').val()) === 'Open';
        const openings = Number(fieldFor($form, 'openings').val());
        const details = {
            name: String(fieldFor($form, 'name').val()),
            departmentId: String(fieldFor($form, 'departmentId').val()),
            salaryMin: Number(fieldFor($form, 'salaryMin').val()),
            salaryMax: Number(fieldFor($form, 'salaryMax').val()),
            benefits: $form.find('[name="benefits"]:checked').map(function () {
                return String($(this).val());
            }).get(),
            employmentType: String(fieldFor($form, 'employmentType').val()),
            location: String(fieldFor($form, 'location').val()).trim(),
            summary: String(fieldFor($form, 'summary').val()).trim(),
            description: String(fieldFor($form, 'description').val()).trim(),
            responsibilities: linesOf(fieldFor($form, 'responsibilities').val()),
            qualifications: linesOf(fieldFor($form, 'qualifications').val()),
            requirements: linesOf(fieldFor($form, 'requirements').val()),
            // A closed posting keeps the number of openings to come back with when it is opened again.
            openings: isOpen ? openings : 0,
            lastOpenings: openings,
        };
        const posting = editingId ? updateJobTitle(editingId, details) : addJobTitle(details);

        if (!posting) {
            showToast('That job posting no longer exists.', { isError: true });

            return;
        }

        showPageWith(posting.id);
        renderTable($root);
        Modal.getOrCreateInstance($modal[0]).hide();
        showToast(editingId ? `Job posting updated: ${posting.name}.` : `Job posting added: ${posting.name}.`);
    });
}

function initRemoveModal($modal) {
    let removingId = null;

    $modal.on('show.bs.modal', function (event) {
        const posting = findJobTitle($(event.relatedTarget).attr('data-job-position-remove'));

        if (!posting) {
            event.preventDefault();

            return;
        }

        removingId = posting.id;
        $modal.find('[data-job-position-remove-message]').text(
            `Remove ${posting.name} from the job postings? It is taken off the Careers website and its job title is deleted. This cannot be undone.`,
        );
    });

    $modal.on('click', '[data-job-position-remove-confirm]', function () {
        const result = deleteJobTitle(removingId);

        Modal.getOrCreateInstance($modal[0]).hide();

        if (!result.jobTitle) {
            showToast(removeBlockedMessage({ name: 'That posting', ...result }) || 'That job posting no longer exists.', { isError: true });

            return;
        }

        showToast(`Job posting removed: ${result.jobTitle.name}.`);
    });
}

/**
 * A Remove button opens the confirmation only when nothing uses the posting; otherwise it explains why not. It opens the
 * modal itself (not through data-bs-toggle, which Bootstrap handles before any handler here could stop it), and first
 * closes the details modal when the button is in it.
 */
function initRemoveTrigger($scope, removeModal) {
    $scope.on('click', '[data-job-position-remove]', function () {
        const trigger = this;
        const posting = findJobTitle($(trigger).attr('data-job-position-remove'));
        const message = posting ? removeBlockedMessage(posting) : '';

        if (message) {
            showToast(message, { isError: true });

            return;
        }

        const showConfirmation = () => Modal.getOrCreateInstance(removeModal).show(trigger);
        const open = document.querySelector('.modal.show');

        if (open) {
            $(open).one('hidden.bs.modal', showConfirmation);
            Modal.getOrCreateInstance(open).hide();
        } else {
            showConfirmation();
        }
    });
}

/**
 * Open and Close change only the posting's status (its openings); applications already received are not touched.
 */
function toggleStatus(postingId, modal) {
    const posting = findJobTitle(postingId);
    const result = posting ? setJobPostingStatus(postingId, posting.isOpen ? 'Closed' : 'Open') : { ok: false, error: 'That job posting no longer exists.' };

    if (modal) {
        Modal.getOrCreateInstance(modal).hide();
    }

    if (!result.ok) {
        showToast(result.error, { isError: true });

        return;
    }

    showToast(result.jobTitle.isOpen
        ? `${result.jobTitle.name} is open with ${plural(result.jobTitle.openings, 'opening')} and is listed on the Careers website.`
        : `${result.jobTitle.name} is closed and no longer listed on the Careers website.`);
}

$(function () {
    const $page = $('[data-page="job-positions"]');

    if (!$page.length) {
        return;
    }

    const $details = $('[data-job-position-details-modal]');

    $page.on('input', '[data-job-position-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderTable($page);
    });

    $page.on('change', '[data-job-position-filter]', function () {
        view[$(this).attr('data-job-position-filter')] = String($(this).val());
        view.page = 1;
        renderTable($page);
    });

    // Clear Filters shows every posting again (the sort order is kept).
    $page.on('click', '[data-job-position-clear]', function () {
        Object.assign(view, { query: '', departmentId: '', employmentType: '', status: '', page: 1 });
        $page.find('[data-job-position-search], [data-job-position-filter]').val('');
        renderTable($page);
    });

    $page.on('click', '[data-sort-key]', function () {
        const key = $(this).attr('data-sort-key');

        if (view.sortKey === key) {
            view.sortDirection *= -1;
        } else {
            view.sortKey = key;
            view.sortDirection = descendingByDefault.includes(key) ? -1 : 1;
        }

        view.page = 1;
        renderTable($page);
    });

    $page.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable($page);
    });

    initRowDetails($page, $details);

    // Open or Close, from the details view.
    $details.on('click', '[data-job-position-toggle]', function () {
        toggleStatus($(this).attr('data-job-position-toggle'), $details[0]);
    });

    initRemoveTrigger($details, $('[data-job-position-remove-modal]')[0]);
    initDetailsModal($details, $page.attr('data-careers-url'));
    initFormModal($('[data-job-position-form-modal]'), $page);
    initRemoveModal($('[data-job-position-remove-modal]'));

    renderFilterOptions($page);
    renderTable($page);
    onMockDataChange(() => {
        renderFilterOptions($page);
        renderTable($page);
    });
});
