import $ from './jquery.js';
import { bindDocumentFields, readDocuments, setDocuments, todayIsoDate } from './employee-documents.js';
import { addEmployee, findEmployee, getEmployeeFormOptions, updateEmployee } from './mock-data.js';
import { queueToast, showToast } from './toast.js';
import { idNumberRule } from './validation-rules.js';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

// Each rule returns an error message, or '' when the value is valid.
const validationRules = {
    name: (value) => (value.trim() ? '' : 'Enter the employee’s full name.'),
    email: (value) => (emailPattern.test(value.trim()) ? '' : 'Enter a valid email address.'),
    departmentId: (value) => (value ? '' : 'Select a department.'),
    jobTitleId: (value) => (value ? '' : 'Select a job title.'),
    hiredOn: (value) => (value ? '' : 'Enter the date hired.'),
    sssNumber: idNumberRule,
    philhealthNumber: idNumberRule,
    pagibigNumber: idNumberRule,
    tin: idNumberRule,
    birNumber: idNumberRule,
    salary: (value) => (value !== '' && Number(value) >= 0 ? '' : 'Enter a base salary of 0 or more.'),
};

// Text and select fields copied to and from the employee record as-is.
const recordFields = [
    'name', 'birthdate', 'sex', 'civilStatus', 'departmentId', 'jobTitleId', 'hiredOn', 'email', 'phone', 'address',
    'sssNumber', 'philhealthNumber', 'pagibigNumber', 'tin', 'birNumber',
];

function optionsFor(placeholder, items) {
    return [
        $('<option>', { value: '', text: placeholder }),
        ...items.map((item) => $('<option>', { value: item.id, text: item.name })),
    ];
}

function setFieldError($field, message) {
    $('#' + $field.attr('aria-describedby').split(' ').find((id) => id.endsWith('-error'))).text(message);

    if (message) {
        $field.addClass('is-invalid').attr('aria-invalid', 'true');
    } else {
        $field.removeClass('is-invalid').attr('aria-invalid', 'false');
    }
}

function fieldFor($form, name) {
    return $form.find(`[name="${name}"]`);
}

/**
 * Clears the form and loads current options, the employee ID, and defaults (or the employee's values).
 */
function prepareForm($form, employee) {
    const options = getEmployeeFormOptions();
    const isEditing = Boolean(employee);

    $form[0].reset();
    $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
    $form.find('[data-employee-id]').val(isEditing ? employee.id : options.nextEmployeeId);
    $form.find('[data-department-options]').empty().append(...optionsFor('Select department', options.departments));
    $form.find('[data-job-title-options]').empty().append(...optionsFor('Select job title', options.jobTitles));
    fieldFor($form, 'hiredOn').val(todayIsoDate());

    $form.find('[data-benefit-options]').empty().append(...options.benefits.map((benefit, index) => (
        $('<div>', { class: 'col' }).append(
            $('<div>', { class: 'form-check' }).append(
                $('<input>', {
                    class: 'form-check-input',
                    type: 'checkbox',
                    name: 'benefits',
                    value: benefit,
                    id: `employee-benefit-${index}`,
                }),
                $('<label>', { class: 'form-check-label', for: `employee-benefit-${index}`, text: benefit }),
            ),
        )
    )));

    if (isEditing) {
        [...recordFields, 'salary'].forEach((name) => fieldFor($form, name).val(employee[name] ?? ''));
        $form.find('[name="benefits"]').each(function () {
            $(this).prop('checked', employee.benefits.includes($(this).val()));
        });
    }

    setDocuments($form, employee?.documents);
}

function applyJobTitleDefaults($form, jobTitleId) {
    const jobTitle = getEmployeeFormOptions().jobTitles.find((title) => title.id === jobTitleId);

    if (!jobTitle) {
        return;
    }

    fieldFor($form, 'salary').val(jobTitle.salary);
    setFieldError(fieldFor($form, 'salary'), '');
    $form.find('[name="benefits"]').each(function () {
        $(this).prop('checked', jobTitle.benefits.includes($(this).val()));
    });
}

/**
 * Validates every rule-bound field, marks errors, and returns the first invalid field (if any).
 */
function validateForm($form) {
    let $firstInvalid = null;

    Object.entries(validationRules).forEach(([name, rule]) => {
        const $field = fieldFor($form, name);
        const message = rule(String($field.val() ?? ''));

        setFieldError($field, message);

        if (message && !$firstInvalid) {
            $firstInvalid = $field;
        }
    });

    return $firstInvalid;
}

function readEmployee($form) {
    const value = (name) => String(fieldFor($form, name).val() ?? '').trim();

    return {
        ...Object.fromEntries(recordFields.map((name) => [name, value(name)])),
        salary: Number(value('salary')),
        benefits: $form.find('[name="benefits"]:checked').map(function () {
            return $(this).val();
        }).get(),
        documents: readDocuments($form),
    };
}

/**
 * Field behavior shared by the New Employee page and the Edit modal. Calls onValid with the
 * employee details once every rule passes.
 */
function bindForm($form, onValid) {
    $form.on('change', '[name="jobTitleId"]', function () {
        applyJobTitleDefaults($form, $(this).val());
    });

    // Once a field has been flagged, re-check it as the user corrects it.
    $form.on('input change', '.is-invalid', function () {
        const name = $(this).attr('name');

        setFieldError($(this), validationRules[name](String($(this).val() ?? '')));
    });

    bindDocumentFields($form);

    $form.on('submit', function (event) {
        event.preventDefault();

        const $firstInvalid = validateForm($form);

        if ($firstInvalid) {
            $firstInvalid.trigger('focus');

            return;
        }

        onValid(readEmployee($form));
    });
}

// New Employee page: create, then return to the Employees list with a confirmation.
function initCreatePage($form) {
    prepareForm($form, null);

    bindForm($form, (details) => {
        const employee = addEmployee(details);

        queueToast(`Employee created: ${employee.name} (${employee.id}).`);
        window.location.assign($form.attr('data-employee-form-redirect'));
    });
}

// Employee profile: Edit swaps the 201 file tabs for this form, prefilled; Save Changes and Cancel both
// return to the tabs, and Cancel leaves the record untouched.
function initProfileEdit($page) {
    const $form = $page.find('[data-profile-edit-form]');
    const $view = $page.find('[data-profile-view]');
    const $actions = $page.find('[data-profile-actions]');
    const $editButton = $page.find('[data-profile-edit-start]');

    function setEditing(isEditing) {
        $form.toggleClass('d-none', !isEditing);
        $view.toggleClass('d-none', isEditing);
        $actions.toggleClass('d-none', isEditing);
    }

    function stopEditing() {
        setEditing(false);
        $editButton.trigger('focus');
    }

    $editButton.on('click', function () {
        const employee = findEmployee($page.attr('data-employee-id'));

        if (!employee) {
            showToast('That employee no longer exists.', { isError: true });

            return;
        }

        prepareForm($form, employee);
        setEditing(true);
        $form[0].scrollIntoView?.({ block: 'start' });
        fieldFor($form, 'name').trigger('focus');
    });

    $form.on('click', '[data-profile-edit-cancel]', stopEditing);

    bindForm($form, (details) => {
        if (!updateEmployee($page.attr('data-employee-id'), details)) {
            showToast('That employee no longer exists.', { isError: true });

            return;
        }

        stopEditing();
        showToast('Changes applied.');
    });
}

$(function () {
    const $createForm = $('[data-page="employee-create"] [data-employee-form]');
    const $profile = $('[data-page="employee-profile"]');

    if ($createForm.length) {
        initCreatePage($createForm);
    }

    if ($profile.length) {
        initProfileEdit($profile);
    }
});
