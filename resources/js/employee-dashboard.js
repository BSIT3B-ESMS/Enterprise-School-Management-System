import $ from './jquery.js';
import { formatDate, greetingForHour } from './format.js';
import { getEmployeePortalData, onMockDataChange } from './mock-data.js';

const plural = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;

// The greeting uses the viewer's local time, which the server does not know.
function renderGreeting($root, employee) {
    $root.find('[data-portal-greeting]').text(`${greetingForHour(new Date().getHours())}, ${employee.firstName}`);
}

function renderKpis($root, { employee, leave }) {
    const kpis = [
        {
            label: 'Status',
            value: employee.status,
            caption: leave.onLeaveUntil ? `On approved leave until ${formatDate(leave.onLeaveUntil)}` : 'No approved leave today',
        },
        {
            label: 'Leave Taken',
            value: plural(leave.takenDays, 'day'),
            caption: `approved, so far in ${leave.year}`,
        },
        {
            label: 'Upcoming Leave',
            value: plural(leave.aheadDays, 'day'),
            caption: `across ${plural(leave.aheadCount, 'approved request')}`,
        },
        {
            label: 'Awaiting Approval',
            value: String(leave.awaitingCount),
            caption: leave.awaitingCount === 1 ? 'pending request' : 'pending requests',
        },
    ];

    $root.find('[data-portal-kpis]').empty().append(...kpis.map((kpi) => $('<div>', { class: 'col' }).append(
        $('<div>', { class: 'card dashboard-kpi' }).append(
            $('<span>', { class: 'dashboard-kpi-label', text: kpi.label }),
            $('<span>', { class: 'dashboard-kpi-value tabular-nums', text: kpi.value }),
            $('<span>', { class: 'dashboard-kpi-caption', text: kpi.caption }),
        ),
    )));
}

function whenText(request) {
    if (request.isUnderWay) {
        return 'Under way';
    }

    return request.startsInDays === 0 ? 'Starts today' : `In ${plural(request.startsInDays, 'day')}`;
}

function renderLeave($root, upcomingLeave) {
    $root.find('[data-portal-leave-empty]').toggleClass('d-none', upcomingLeave.length > 0);
    $root.find('[data-portal-leave]').empty().append(...upcomingLeave.map((request) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { class: 'fw-medium', text: request.type }),
                $('<small>', {
                    class: 'text-body-secondary',
                    text: `${request.startDate === request.endDate
                        ? formatDate(request.startDate)
                        : `${formatDate(request.startDate)} to ${formatDate(request.endDate)}`} · ${plural(request.days, 'day')}`,
                }),
            ),
            $('<span>', { class: 'ems-chip', text: whenText(request) }),
        )
    )));
}

function renderInformation($root, employee) {
    const values = {
        id: employee.id,
        status: employee.status,
        department: employee.department,
        jobTitle: employee.jobTitle,
        hiredOn: employee.hiredOn ? formatDate(employee.hiredOn) : '',
        phone: employee.phone,
        email: employee.email,
    };

    Object.entries(values).forEach(([field, value]) => {
        const $field = $root.find(`[data-portal-field="${field}"]`);

        if (value) {
            $field.text(value).removeClass('text-body-secondary');
        } else {
            $field.text('Not provided').addClass('text-body-secondary');
        }
    });
}

function renderBenefits($root, benefits) {
    $root.find('[data-portal-benefits]').empty().append(
        ...(benefits.length
            ? benefits.map((benefit) => $('<span>', { class: 'ems-chip', text: benefit }))
            : [$('<span>', { class: 'text-body-secondary', text: 'No benefits on file.' })]),
    );
}

function renderDashboard($root) {
    const data = getEmployeePortalData();

    // HR can delete the employee's record, for example in another tab.
    $root.find('[data-portal-missing]').toggleClass('d-none', Boolean(data.employee));
    $root.find('[data-portal-content]').toggleClass('d-none', !data.employee);

    if (!data.employee) {
        $root.find('[data-portal-greeting]').text('Welcome');

        return;
    }

    renderGreeting($root, data.employee);
    renderKpis($root, data);
    renderLeave($root, data.upcomingLeave);
    renderInformation($root, data.employee);
    renderBenefits($root, data.benefits);
}

$(function () {
    const $dashboard = $('[data-page="employee-dashboard"]');

    if (!$dashboard.length) {
        return;
    }

    renderDashboard($dashboard);
    onMockDataChange(() => renderDashboard($dashboard));
});
