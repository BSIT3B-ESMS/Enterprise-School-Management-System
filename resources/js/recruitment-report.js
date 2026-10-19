import $ from './jquery.js';
import { formatDate } from './format.js';
import { applicationStatuses, interviewTypeLabel, listApplications, listDepartments, listJobTitles, onMockDataChange } from './mock-data.js';
import { initReportGenerator } from './report-generator.js';
import { initReportPeriod, periodForDay } from './report-period.js';

const blankIfEmpty = (value) => value ?? '';

// The earliest demo interview, interview, or other activity still scheduled for the applicant, or undefined.
function nextScheduled(application) {
    return (application.activities ?? [])
        .filter((activity) => activity.status === 'Scheduled' && activity.date)
        .sort((first, second) => `${first.date} ${first.time ?? ''}`.localeCompare(`${second.date} ${second.time ?? ''}`))[0];
}

/**
 * Report fields, grouped and in display order (also the column order). `value` is what CSV and Excel export (ISO
 * dates); `display` is what the preview and printed report show.
 *
 * @type {Array<{ group: string, fields: Array<{ key: string, label: string, value: Function, display?: Function }> }>}
 */
const fieldGroups = [
    {
        group: 'Applicant',
        fields: [
            { key: 'id', label: 'Application ID', value: (application) => application.id.toUpperCase() },
            { key: 'name', label: 'Applicant Name', value: (application) => application.name },
            { key: 'email', label: 'Email', value: (application) => blankIfEmpty(application.email) },
            { key: 'phone', label: 'Phone', value: (application) => blankIfEmpty(application.phone) },
        ],
    },
    {
        group: 'Application',
        fields: [
            { key: 'jobTitle', label: 'Position Applied For', value: (application) => application.jobTitle },
            { key: 'department', label: 'Department', value: (application) => application.department },
            { key: 'appliedOn', label: 'Application Date', value: (application) => application.appliedOn, display: (application) => formatDate(application.appliedOn) },
            { key: 'status', label: 'Status', value: (application) => application.status },
        ],
    },
    {
        group: 'Schedule',
        fields: [
            {
                key: 'nextActivity',
                label: 'Next Scheduled Activity',
                value: (application) => {
                    const activity = nextScheduled(application);

                    return activity ? `${interviewTypeLabel(activity.type)} ${activity.date} ${activity.time ?? ''}`.trim() : '';
                },
                display: (application) => {
                    const activity = nextScheduled(application);

                    return activity ? `${interviewTypeLabel(activity.type)}, ${formatDate(activity.date)}${activity.time ? ` ${activity.time}` : ''}` : '';
                },
            },
        ],
    },
];

// Selected when the page opens: the columns the Recruitment table shows, with the department.
const defaultFieldKeys = ['name', 'jobTitle', 'department', 'appliedOn', 'status'];

/**
 * The Recruitment Generate Report page: the applications matching the date range, applicant name, job position,
 * department, and status chosen here (starting from the filters the Recruitment page had, carried in the query string),
 * newest application first.
 */
function initRecruitmentReport($root) {
    const params = new URLSearchParams(window.location.search);
    const filters = {
        name: '',
        jobTitleId: params.get('jobTitle') ?? '',
        departmentId: params.get('department') ?? '',
        status: applicationStatuses.includes(params.get('status')) ? params.get('status') : '',
    };
    let period = null;

    function rows() {
        const range = period?.range();
        const name = filters.name.trim().toLowerCase();

        return listApplications()
            .filter((application) => !name || application.name.toLowerCase().includes(name))
            .filter((application) => !filters.jobTitleId || application.jobTitleId === filters.jobTitleId)
            .filter((application) => !filters.departmentId || application.departmentId === filters.departmentId)
            .filter((application) => !filters.status || application.status === filters.status)
            .filter((application) => !range || (application.appliedOn >= range.from && application.appliedOn <= range.to))
            .sort((first, second) => second.appliedOn.localeCompare(first.appliedOn) || first.name.localeCompare(second.name));
    }

    function summary() {
        const nameOf = (items, id) => items.find((item) => item.id === id)?.name ?? id;
        const lines = [];

        if (period?.describe()) {
            lines.push(`Application date: ${period.describe()}`);
        }

        if (filters.name.trim()) {
            lines.push(`Applicant name: “${filters.name.trim()}”`);
        }

        if (filters.jobTitleId) {
            lines.push(`Job position: ${nameOf(listJobTitles(), filters.jobTitleId)}`);
        }

        if (filters.departmentId) {
            lines.push(`Department: ${nameOf(listDepartments(), filters.departmentId)}`);
        }

        if (filters.status) {
            lines.push(`Status: ${filters.status}`);
        }

        return { filters: lines, sort: 'Sorted by Application Date, newest first' };
    }

    const report = initReportGenerator($root, {
        title: 'Recruitment Report',
        fileStem: 'recruitment-report',
        sheetName: 'Recruitment',
        noun: ['application', 'applications'],
        countText: (count) => `${count} ${count === 1 ? 'application matches' : 'applications match'} the selected filters.`,
        noFiltersText: 'No filters applied: every application',
        fieldGroups,
        defaultFieldKeys,
        rows,
        summary,
    });

    // Statuses are the recruitment stages; departments and job positions come from the records HR manages. Job positions
    // are narrowed to the chosen department. A choice that no longer exists (or is outside the department) falls back to all.
    function renderFilterOptions() {
        const departments = listDepartments().sort((first, second) => first.name.localeCompare(second.name));

        if (!departments.some((department) => department.id === filters.departmentId)) {
            filters.departmentId = '';
        }

        const jobTitles = listJobTitles()
            .filter((jobTitle) => !filters.departmentId || jobTitle.departmentId === filters.departmentId)
            .sort((first, second) => first.name.localeCompare(second.name) || first.department.localeCompare(second.department));

        if (!jobTitles.some((jobTitle) => jobTitle.id === filters.jobTitleId)) {
            filters.jobTitleId = '';
        }

        $root.find('[data-report-filter="departmentId"]').empty().append(
            $('<option>', { value: '', text: 'All Departments' }),
            ...departments.map((department) => $('<option>', { value: department.id, text: department.name })),
        ).val(filters.departmentId);
        $root.find('[data-report-filter="jobTitleId"]').empty().append(
            $('<option>', { value: '', text: 'All Job Positions' }),
            ...jobTitles.map((jobTitle) => $('<option>', {
                value: jobTitle.id,
                text: filters.departmentId ? jobTitle.name : `${jobTitle.name} (${jobTitle.department})`,
            })),
        ).val(filters.jobTitleId);
        $root.find('[data-report-filter="status"]').empty().append(
            $('<option>', { value: '', text: 'All Statuses' }),
            ...applicationStatuses.map((status) => $('<option>', { value: status, text: status })),
        ).val(filters.status);
    }

    period = initReportPeriod($root.find('[data-report-period]'), periodForDay(params.get('appliedOn')), () => {
        report.clearError();
        report.refresh();
    });

    $root.on('input', '[data-report-filter="name"]', function () {
        filters.name = String($(this).val());
        report.clearError();
        report.refresh();
    });

    $root.on('change', 'select[data-report-filter]', function () {
        filters[$(this).attr('data-report-filter')] = String($(this).val());

        if ($(this).attr('data-report-filter') === 'departmentId') {
            renderFilterOptions();
        }

        report.clearError();
        report.refresh();
    });

    // Clears the date range and every filter on this report.
    $root.on('click', '[data-report-clear]', function () {
        Object.assign(filters, { name: '', jobTitleId: '', departmentId: '', status: '' });
        $root.find('[data-report-filter]').val('');
        renderFilterOptions();
        $root.find('[data-period-clear]').trigger('click');
    });

    renderFilterOptions();
    report.refresh();

    onMockDataChange(() => {
        renderFilterOptions();
        report.refresh();
    });
}

$(function () {
    const $report = $('[data-page="recruitment-report"]');

    if ($report.length) {
        initRecruitmentReport($report);
    }
});
