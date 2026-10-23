import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { formatDate, formatSalaryRange, initialsOf } from './format.js';
import {
    addJobTitle,
    collegeChoiceError,
    defaultLeaveCredits,
    deleteJobTitle,
    findJobTitle,
    getBenefitNames,
    getEmployeeFormOptions,
    isJobTitleNameTaken,
    listJobTitles,
    onMockDataChange,
    updateJobTitle,
} from './mock-data.js';
import { renderPagination } from './pagination.js';
import { leaveCreditsError, salaryRangeErrors } from './validation-rules.js';
import { showToast } from './toast.js';

const pageSize = 8;

// New job titles start with the statutory and standard benefits (the first five).
const defaultBenefitCount = 5;

const sortValue = {
    name: (jobTitle) => jobTitle.name.toLowerCase(),
    department: (jobTitle) => jobTitle.department.toLowerCase(),
    salary: (jobTitle) => Number(jobTitle.salaryMin),
    benefits: (jobTitle) => jobTitle.benefits.length,
};

// Numeric columns sort highest first when chosen; text columns sort A to Z.
const descendingByDefault = ['salary', 'benefits'];

// Table view state: search text, department filter, sort column and direction (1 ascending, -1 descending), page.
const view = { query: '', departmentId: '', sortKey: 'salary', sortDirection: -1, page: 1 };

function plural(count, noun) {
    return `${count} ${noun}${count === 1 ? '' : 's'}`;
}

/**
 * Job titles matching the search and filter, in the current sort order (ties fall back to the name).
 */
function visibleJobTitles() {
    const query = view.query.trim().toLowerCase();
    const valueOf = sortValue[view.sortKey];

    return listJobTitles()
        .filter((jobTitle) => !query || `${jobTitle.name} ${jobTitle.department}`.toLowerCase().includes(query))
        .filter((jobTitle) => !view.departmentId || jobTitle.departmentId === view.departmentId)
        .sort((first, second) => {
            const firstValue = valueOf(first);
            const secondValue = valueOf(second);
            const order = firstValue < secondValue ? -1 : firstValue > secondValue ? 1 : 0;

            return order * view.sortDirection || first.name.localeCompare(second.name);
        });
}

/**
 * Why a job title cannot be deleted yet, or '' when no employee or application uses it.
 */
function deleteBlockedMessage({ name, headcount, applicationCount = 0 }) {
    if (!headcount && !applicationCount) {
        return '';
    }

    const users = [
        headcount ? plural(headcount, 'employee') : '',
        applicationCount ? plural(applicationCount, 'application') : '',
    ].filter(Boolean).join(' and ');

    return `${name} is used by ${users}. Reassign them first.`;
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

    $root.find('[data-job-title-filter="departmentId"]').empty().append(...departmentOptions('All Colleges')).val(view.departmentId);
}

function rowFor(jobTitle) {
    return $('<tr>', {
        class: 'employee-row',
        tabindex: 0,
        'aria-haspopup': 'dialog',
        title: `View ${jobTitle.name}`,
        'data-job-title-row': jobTitle.id,
    }).append(
        $('<td>', { class: 'fw-medium', text: jobTitle.name }),
        $('<td>', { text: jobTitle.department }),
        $('<td>', { class: 'tabular-nums text-nowrap', text: formatSalaryRange(jobTitle.salaryMin, jobTitle.salaryMax) }),
        $('<td>').append($('<span>', { class: 'ems-chip', text: plural(jobTitle.benefits.length, 'benefit') })),
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
    const jobTitles = visibleJobTitles();
    const pageCount = Math.max(1, Math.ceil(jobTitles.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = jobTitles.slice(start, start + pageSize);

    $root.find('[data-job-title-rows]').empty().append(...pageRows.map(rowFor));

    if (pageRows.length) {
        $root.find('[data-job-title-empty]').addClass('d-none');
    } else {
        $root.find('[data-job-title-empty]').removeClass('d-none');
        $root.find('[data-job-title-empty-hint]').text(
            view.query.trim() || view.departmentId ? 'Try a different search or clear the filter.' : 'Add a job title to get started.',
        );
    }

    $root.find('[data-job-title-summary]').text(
        `Showing ${jobTitles.length ? start + 1 : 0} to ${start + pageRows.length} of ${jobTitles.length} job titles`,
    );
    renderSortHeaders($root);
    renderPagination($root.find('[data-job-title-pagination]'), view.page, pageCount);
}

/**
 * Moves the table to the page that holds the job title, if it matches the current search and filter.
 */
function showPageWith(jobTitleId) {
    const index = visibleJobTitles().findIndex((jobTitle) => jobTitle.id === jobTitleId);

    if (index !== -1) {
        view.page = Math.floor(index / pageSize) + 1;
    }
}

/**
 * Row clicks and Enter/Space open the details modal; clicks on controls inside a row are left alone.
 * Rows are re-rendered after every change, so focus returns to the row (found again by its ID) once
 * no modal is open.
 */
function initRowDetails($root) {
    const $detailsModal = $('[data-job-title-details-modal]');
    const interactiveSelector = 'a, button, input, select, textarea, label, [data-bs-toggle]';
    let lastOpenedId = null;

    function openDetails(row) {
        // Ignore a repeat click or keypress while a modal is already open.
        if ($('.modal.show').length) {
            return;
        }

        lastOpenedId = $(row).attr('data-job-title-row');
        Modal.getOrCreateInstance($detailsModal[0]).show(row);
    }

    $root.on('click', 'tr[data-job-title-row]', function (event) {
        if ($(event.target).closest(interactiveSelector).length) {
            return;
        }

        openDetails(this);
    });

    $root.on('keydown', 'tr[data-job-title-row]', function (event) {
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

        const $row = $root.find(`tr[data-job-title-row="${lastOpenedId}"]`);

        ($row.length ? $row : $root.find('[data-job-title-add]')).trigger('focus');
        lastOpenedId = null;
    });

    $root.on('click', '[data-job-title-add]', () => {
        lastOpenedId = null;
    });
}

/**
 * The details modal's Delete button stops here, before Bootstrap closes the details and opens the
 * confirmation, while employees or applications still use the job title.
 */
function guardDeleteTrigger($modal) {
    $modal.on('click', '[data-job-title-delete]', function (event) {
        const jobTitle = findJobTitle($(this).attr('data-job-title-delete'));
        const message = jobTitle ? deleteBlockedMessage(jobTitle) : '';

        if (message) {
            event.preventDefault();
            event.stopPropagation();
            showToast(message, { isError: true });
        }
    });
}

function initDetailsModal($modal, employeesUrl) {
    $modal.on('show.bs.modal', function (event) {
        const jobTitle = findJobTitle($(event.relatedTarget).attr('data-job-title-row'));

        if (!jobTitle) {
            event.preventDefault();
            showToast('That job title no longer exists.', { isError: true });

            return;
        }

        const values = {
            name: jobTitle.name,
            department: jobTitle.department,
            salary: formatSalaryRange(jobTitle.salaryMin, jobTitle.salaryMax),
            leaveCredits: `${jobTitle.leaveCredits ?? 0} ${Number(jobTitle.leaveCredits) === 1 ? 'day' : 'days'} per year`,
            headcount: String(jobTitle.headcount),
            benefitCount: String(jobTitle.benefits.length),
        };

        Object.entries(values).forEach(([field, value]) => {
            $modal.find(`[data-job-title-field="${field}"]`).text(value);
        });

        $modal.find('[data-job-title-benefits]').empty().append(
            ...(jobTitle.benefits.length
                ? jobTitle.benefits.map((benefit) => $('<span>', { class: 'ems-chip', text: benefit }))
                : [$('<span>', { class: 'text-body-secondary', text: 'No benefits selected.' })]),
        );

        $modal.find('[data-job-title-employees]').empty().append(
            ...(jobTitle.recentEmployees.length
                ? jobTitle.recentEmployees.map((employee) => (
                    $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
                        $('<div>', { class: 'min-w-0' }).append(
                            $('<div>', { class: 'd-flex align-items-center gap-2' }).append(
                                $('<span>', { class: 'ems-avatar-sm', 'aria-hidden': 'true', text: initialsOf(employee.name) }),
                                document.createTextNode(employee.name),
                            ),
                            $('<small>', { class: 'text-body-secondary', text: employee.department }),
                        ),
                        $('<small>', { class: 'text-body-secondary text-nowrap tabular-nums', text: `Hired ${formatDate(employee.hiredOn)}` }),
                    )
                ))
                : [$('<li>', { class: 'list-group-item text-body-secondary', text: 'No employees hold this job title yet.' })]),
        );

        const $employeesLink = $modal.find('[data-job-title-employees-link]')
            .attr('href', `${employeesUrl}?jobTitle=${encodeURIComponent(jobTitle.id)}`)
            .text(`View all ${plural(jobTitle.headcount, 'employee')} in Employees`);

        if (jobTitle.headcount) {
            $employeesLink.removeClass('d-none');
        } else {
            $employeesLink.addClass('d-none');
        }

        $modal.find('[data-job-title-edit]').attr('data-job-title-edit', jobTitle.id);
        $modal.find('[data-job-title-delete]').attr('data-job-title-delete', jobTitle.id);
    });

    guardDeleteTrigger($modal);
}

function fieldFor($form, name) {
    return $form.find(`[name="${name}"]`);
}

function setFieldError($field, message) {
    $(`#${$field.attr('aria-describedby')}`).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

/**
 * Each rule returns an error message, or '' when the value is valid. The name must be unique
 * within its department, so it reads the department field too.
 */
function validationRules($form, editingId) {
    return {
        name: (value) => {
            if (!value.trim()) {
                return 'Enter a job title.';
            }

            const departmentId = String(fieldFor($form, 'departmentId').val() ?? '');

            return isJobTitleNameTaken(value, departmentId, editingId) ? 'This title already exists in that college.' : '';
        },
        departmentId: (value) => collegeChoiceError(value),
        // The minimum and maximum are checked together: both needed, 0 or more, the maximum not lower than the minimum.
        salaryMin: () => salaryRangeErrors(fieldFor($form, 'salaryMin').val(), fieldFor($form, 'salaryMax').val()).salaryMin ?? '',
        salaryMax: () => salaryRangeErrors(fieldFor($form, 'salaryMin').val(), fieldFor($form, 'salaryMax').val()).salaryMax ?? '',
        leaveCredits: (value) => leaveCreditsError(value),
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
 * Clears the form and loads current department options and benefits (or the job title's values).
 */
function prepareForm($form, jobTitle) {
    const benefitNames = getBenefitNames();
    const selectedBenefits = jobTitle ? jobTitle.benefits : benefitNames.slice(0, defaultBenefitCount);

    $form[0].reset();
    $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
    $form.find('.invalid-feedback').text('');
    $form.find('[data-job-title-department-options]').empty().append(...departmentOptions('Select college'));

    $form.find('[data-job-title-benefit-options]').empty().append(...benefitNames.map((benefit, index) => (
        $('<div>', { class: 'col' }).append(
            $('<div>', { class: 'form-check' }).append(
                $('<input>', {
                    class: 'form-check-input',
                    type: 'checkbox',
                    name: 'benefits',
                    value: benefit,
                    id: `job-title-benefit-${index}`,
                    checked: selectedBenefits.includes(benefit),
                }),
                $('<label>', { class: 'form-check-label', for: `job-title-benefit-${index}`, text: benefit }),
            ),
        )
    )));

    fieldFor($form, 'name').val(jobTitle?.name ?? '');
    fieldFor($form, 'departmentId').val(jobTitle?.departmentId ?? '');
    fieldFor($form, 'salaryMin').val(jobTitle?.salaryMin ?? '');
    fieldFor($form, 'salaryMax').val(jobTitle?.salaryMax ?? '');
    fieldFor($form, 'leaveCredits').val(jobTitle?.leaveCredits ?? defaultLeaveCredits);
}

function readJobTitle($form) {
    const value = (name) => String(fieldFor($form, name).val() ?? '').trim();

    return {
        name: value('name'),
        departmentId: value('departmentId'),
        salaryMin: Number(value('salaryMin')),
        salaryMax: Number(value('salaryMax')),
        leaveCredits: Number(value('leaveCredits')),
        benefits: $form.find('[name="benefits"]:checked').map(function () {
            return $(this).val();
        }).get(),
    };
}

function initFormModal($modal, $root) {
    const $form = $modal.find('[data-job-title-form]');
    let editingId = null;

    $modal.on('show.bs.modal', function (event) {
        const requestedId = $(event.relatedTarget).attr('data-job-title-edit');
        const jobTitle = requestedId ? findJobTitle(requestedId) : null;

        if (requestedId && !jobTitle) {
            event.preventDefault();
            showToast('That job title no longer exists.', { isError: true });

            return;
        }

        editingId = jobTitle?.id ?? null;
        prepareForm($form, jobTitle);
        $modal.find('[data-job-title-form-title]').text(jobTitle ? 'Edit Job Title' : 'Add Job Title');
    });
    $modal.on('shown.bs.modal', () => fieldFor($form, 'name').trigger('focus'));

    // Once a field has been flagged, re-check it as the user corrects it. Changing the department
    // can resolve (or cause) a duplicate name, so the name is re-checked with it.
    $form.on('input change', '.is-invalid, [name="departmentId"], [name="salaryMin"], [name="salaryMax"]', function () {
        const rules = validationRules($form, editingId);
        const changed = $(this).attr('name');
        const salaryFlagged = fieldFor($form, 'salaryMin').hasClass('is-invalid') || fieldFor($form, 'salaryMax').hasClass('is-invalid');

        // The salary range is one check: changing either end re-checks both once it has been flagged.
        if (['salaryMin', 'salaryMax'].includes(changed) && !salaryFlagged) {
            return;
        }

        const names = (changed === 'departmentId' && ['departmentId', 'name'])
            || (['salaryMin', 'salaryMax'].includes(changed) && ['salaryMin', 'salaryMax'])
            || [changed];

        names.forEach((name) => {
            const $field = fieldFor($form, name);

            if ($field.hasClass('is-invalid') || name === $(this).attr('name')) {
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

        const details = readJobTitle($form);

        if (editingId) {
            updateJobTitle(editingId, details);
            showToast('Job title updated.');
        } else {
            const jobTitle = addJobTitle(details);

            showPageWith(jobTitle.id);
            renderTable($root);
            showToast(`Job title added: ${jobTitle.name}.`);
        }

        Modal.getOrCreateInstance($modal[0]).hide();
    });
}

function initDeleteModal($modal) {
    let deletingId = null;

    $modal.on('show.bs.modal', function (event) {
        const jobTitle = findJobTitle($(event.relatedTarget).attr('data-job-title-delete'));

        if (!jobTitle || deleteBlockedMessage(jobTitle)) {
            event.preventDefault();

            return;
        }

        deletingId = jobTitle.id;
        $modal.find('[data-job-title-delete-message]').text(`Delete ${jobTitle.name}? This cannot be undone.`);
    });

    $modal.on('click', '[data-job-title-delete-confirm]', function () {
        // A second click while the modal is closing must not delete again.
        if (!deletingId) {
            return;
        }

        const { jobTitle, ...usage } = deleteJobTitle(deletingId);

        deletingId = null;

        Modal.getOrCreateInstance($modal[0]).hide();

        if (jobTitle) {
            showToast(`Job title deleted: ${jobTitle.name}.`);
        } else {
            showToast(deleteBlockedMessage({ name: 'This job title', ...usage }) || 'That job title no longer exists.', {
                isError: true,
            });
        }
    });
}

$(function () {
    const $jobTitles = $('[data-page="job-titles"]');

    if (!$jobTitles.length) {
        return;
    }

    $jobTitles.on('input', '[data-job-title-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderTable($jobTitles);
    });

    $jobTitles.on('change', '[data-job-title-filter]', function () {
        view[$(this).attr('data-job-title-filter')] = String($(this).val());
        view.page = 1;
        renderTable($jobTitles);
    });

    $jobTitles.on('click', '[data-sort-key]', function () {
        const key = $(this).attr('data-sort-key');

        if (view.sortKey === key) {
            view.sortDirection *= -1;
        } else {
            view.sortKey = key;
            view.sortDirection = descendingByDefault.includes(key) ? -1 : 1;
        }

        view.page = 1;
        renderTable($jobTitles);
    });

    $jobTitles.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderTable($jobTitles);
    });

    initRowDetails($jobTitles);
    initDetailsModal($('[data-job-title-details-modal]'), $jobTitles.attr('data-employees-url'));
    initFormModal($('[data-job-title-form-modal]'), $jobTitles);
    initDeleteModal($('[data-job-title-delete-modal]'));

    renderFilterOptions($jobTitles);
    renderTable($jobTitles);
    onMockDataChange(() => {
        renderFilterOptions($jobTitles);
        renderTable($jobTitles);
    });
});
