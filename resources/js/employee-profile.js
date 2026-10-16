import $ from './jquery.js';
import { documentItem } from './employee-documents.js';
import { formatDate, formatDateTime, formatPeso, initialsOf } from './format.js';
import { findEmployee, getEmployeeHistory, getEmployeeLeave, onMockDataChange } from './mock-data.js';

const historyLimit = 10;

// Benefits that are government contributions; enrollment is read from the employee's benefits.
const governmentContributions = ['SSS', 'PhilHealth', 'Pag-IBIG'];

const statusClasses = { Active: 'employment-active', 'On Leave': 'employment-on-leave' };

function displayValues(employee) {
    return {
        initials: initialsOf(employee.name),
        name: employee.name,
        id: employee.id,
        role: `${employee.jobTitle} · ${employee.department}`,
        birthdate: employee.birthdate ? formatDate(employee.birthdate) : '',
        sex: employee.sex,
        civilStatus: employee.civilStatus,
        department: employee.department,
        jobTitle: employee.jobTitle,
        hiredOn: employee.hiredOn ? formatDate(employee.hiredOn) : '',
        status: employee.status,
        email: employee.email,
        phone: employee.phone,
        address: employee.address,
        sssNumber: employee.sssNumber,
        philhealthNumber: employee.philhealthNumber,
        pagibigNumber: employee.pagibigNumber,
        tin: employee.tin,
        birNumber: employee.birNumber,
        salary: employee.salary === '' || employee.salary === undefined ? '' : formatPeso(Number(employee.salary)),
        separationNote: `${employee.name} is currently ${employee.status === 'On Leave' ? 'on leave' : 'active'}; no separation is recorded.`,
    };
}

function statusItem(label, status, note) {
    return $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
        $('<div>', { class: 'min-w-0' }).append(
            $('<div>', { class: 'fw-medium', text: label }),
            $('<small>', { class: 'text-body-secondary', text: note }),
        ),
        $('<span>', { class: 'text-nowrap', text: status }),
    );
}

function renderGovernment($root, employee) {
    const benefits = employee.benefits ?? [];

    $root.find('[data-profile-government]').empty().append(
        ...governmentContributions.map((name) => statusItem(
            name,
            benefits.includes(name) ? 'Enrolled' : 'Not enrolled',
            `${name} contribution`,
        )),
    );
}

function renderDocuments($root, employee) {
    $root.find('[data-profile-documents]').each(function () {
        const key = $(this).attr('data-profile-documents');
        const files = employee.documents?.[key] ?? [];

        $(this).empty().append(...files.map((file) => documentItem(file)));
        $root.find(`[data-profile-documents-empty="${key}"]`).toggleClass('d-none', files.length > 0);
    });
}

function renderBenefits($root, employee) {
    const benefits = employee.benefits ?? [];

    $root.find('[data-profile-benefits]').empty().append(
        ...(benefits.length
            ? benefits.map((benefit) => $('<span>', { class: 'ems-chip', text: benefit }))
            : [$('<span>', { class: 'text-body-secondary', text: 'No benefits selected.' })]),
    );
}

function renderHistory($root, employee) {
    const current = $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
        $('<div>', { class: 'min-w-0' }).append(
            $('<div>', { class: 'fw-medium', text: `${employee.jobTitle} · ${employee.department}` }),
            $('<small>', { class: 'text-body-secondary', text: 'Current position' }),
        ),
        $('<small>', { class: 'text-body-secondary text-nowrap', text: employee.hiredOn ? `Since ${formatDate(employee.hiredOn)}` : '' }),
    );
    const changes = getEmployeeHistory(employee.id, historyLimit).map((entry) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { text: entry.detail }),
                $('<small>', { class: 'text-body-secondary', text: `by ${entry.user}` }),
            ),
            $('<small>', { class: 'text-body-secondary text-nowrap', text: formatDateTime(entry.at) }),
        )
    ));

    $root.find('[data-profile-history]').empty().append(current, ...changes);
}

function renderLeave($root, employee) {
    const leave = getEmployeeLeave(employee.id);

    $root.find('[data-profile-leave-empty]').toggleClass('d-none', leave.length > 0);
    $root.find('[data-profile-leave]').empty().append(...leave.map((request) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { class: 'fw-medium', text: request.type }),
                $('<small>', {
                    class: 'text-body-secondary',
                    text: request.startDate === request.endDate
                        ? formatDate(request.startDate)
                        : `${formatDate(request.startDate)} to ${formatDate(request.endDate)}`,
                }),
            ),
            $('<span>', { class: `status-pill calendar-status-${request.status.toLowerCase()}`, text: request.status }),
        )
    )));
}

function renderProfile($root) {
    const employee = findEmployee($root.attr('data-employee-id'));

    // Unknown or deleted (for example, in another tab) employees show the not-found state.
    if (!employee) {
        $root.find('[data-profile]').addClass('d-none');
        $root.find('[data-profile-missing]').removeClass('d-none');

        return;
    }

    $root.find('[data-profile-missing]').addClass('d-none');
    $root.find('[data-profile]').removeClass('d-none');
    document.title = document.title.replace(/^[^·]*/, `${employee.name} `);

    Object.entries(displayValues(employee)).forEach(([field, value]) => {
        const $fields = $root.find(`[data-profile-field="${field}"]`);

        if (value) {
            $fields.text(value).removeClass('text-body-secondary');
        } else {
            $fields.text('Not provided').addClass('text-body-secondary');
        }
    });

    $root.find('[data-profile-status-pill]')
        .attr('class', `status-pill ${statusClasses[employee.status] ?? ''}`.trim())
        .text(employee.status ?? '');

    renderGovernment($root, employee);
    renderDocuments($root, employee);
    renderBenefits($root, employee);
    renderHistory($root, employee);
    renderLeave($root, employee);
}

$(function () {
    const $profile = $('[data-page="employee-profile"]');

    if (!$profile.length) {
        return;
    }

    // Back to Employees restores the list's search, filters, sort, and page, which arrive in this page's query string.
    const $back = $profile.find('[data-employee-back-link]');

    $back.attr('href', `${$back.attr('data-employees-url')}${window.location.search}`);

    renderProfile($profile);
    onMockDataChange(() => renderProfile($profile));
});
