import { Modal } from 'bootstrap';
import $ from './jquery.js';
import { addEmployee, findEmployee, getEmployeeFormOptions, updateEmployee } from './mock-data.js';
import { queueToast, showToast } from './toast.js';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

// Each rule returns an error message, or '' when the value is valid.
const validationRules = {
    name: (value) => (value.trim() ? '' : 'Enter the employee’s full name.'),
    email: (value) => (emailPattern.test(value.trim()) ? '' : 'Enter a valid email address.'),
    departmentId: (value) => (value ? '' : 'Select a department.'),
    jobTitleId: (value) => (value ? '' : 'Select a job title.'),
    hiredOn: (value) => (value ? '' : 'Enter the date hired.'),
    salary: (value) => (value !== '' && Number(value) >= 0 ? '' : 'Enter a base salary of 0 or more.'),
};

function todayIsoDate() {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

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
        ['name', 'birthdate', 'sex', 'civilStatus', 'email', 'phone', 'address', 'departmentId', 'jobTitleId', 'hiredOn', 'salary']
            .forEach((name) => fieldFor($form, name).val(employee[name] ?? ''));
        $form.find('[name="benefits"]').each(function () {
            $(this).prop('checked', employee.benefits.includes($(this).val()));
        });
    }
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
        name: value('name'),
        birthdate: value('birthdate'),
        sex: value('sex'),
        civilStatus: value('civilStatus'),
        email: value('email'),
        phone: value('phone'),
        address: value('address'),
        departmentId: value('departmentId'),
        jobTitleId: value('jobTitleId'),
        hiredOn: value('hiredOn'),
        salary: Number(value('salary')),
        benefits: $form.find('[name="benefits"]:checked').map(function () {
            return $(this).val();
        }).get(),
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

// Edit modal, opened from the details modal's Edit button (data-employee-edit="EMP-…").
function initEditModal($modal) {
    const $form = $modal.find('[data-employee-form]');
    let editingId = null;

    $modal.on('show.bs.modal', function (event) {
        const employee = findEmployee($(event.relatedTarget).attr('data-employee-edit'));

        if (!employee) {
            event.preventDefault();

            return;
        }

        editingId = employee.id;
        prepareForm($form, employee);
    });
    $modal.on('shown.bs.modal', () => fieldFor($form, 'name').trigger('focus'));

    bindForm($form, (details) => {
        updateEmployee(editingId, details);
        Modal.getOrCreateInstance($modal[0]).hide();
        showToast('Changes saved.');
    });
}

$(function () {
    const $createForm = $('[data-page="employee-create"] [data-employee-form]');
    const $editModal = $('[data-employee-form-modal]');

    if ($createForm.length) {
        initCreatePage($createForm);
    }

    if ($editModal.length) {
        initEditModal($editModal);
    }
});
