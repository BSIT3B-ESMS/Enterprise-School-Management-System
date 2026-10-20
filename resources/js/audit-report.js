import $ from './jquery.js';
import { splitEntity } from './audit-logs.js';
import { localIsoDate } from './date-filter.js';
import { exportLogger } from './export-log.js';
import { formatDateTime } from './format.js';
import { activityActions, listActivity, onMockDataChange } from './mock-data.js';
import { initReportGenerator } from './report-generator.js';
import { initReportPeriod, periodForDay } from './report-period.js';

const localTime = (date) => `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

/**
 * Report fields, grouped and in display order (also the column order). `value` is what CSV and Excel export
 * ("2026-10-17 14:31"); `display` is what the preview and printed report show.
 *
 * @type {Array<{ group: string, fields: Array<{ key: string, label: string, value: Function, display?: Function }> }>}
 */
const fieldGroups = [
    {
        group: 'Activity',
        fields: [
            { key: 'at', label: 'Date and Time', value: (entry) => `${localIsoDate(entry.at)} ${localTime(entry.at)}`, display: (entry) => formatDateTime(entry.at) },
            { key: 'user', label: 'User', value: (entry) => entry.user },
            { key: 'action', label: 'Action', value: (entry) => entry.action },
        ],
    },
    {
        group: 'Affected Record',
        fields: [
            { key: 'recordType', label: 'Module', value: (entry) => splitEntity(entry.entity).recordType },
            { key: 'record', label: 'Affected Record', value: (entry) => splitEntity(entry.entity).record },
            { key: 'detail', label: 'Details', value: (entry) => entry.detail },
        ],
    },
];

// Selected when the page opens: the columns the Audit Logs table shows.
const defaultFieldKeys = ['at', 'user', 'action', 'recordType', 'record', 'detail'];

/**
 * The Audit Logs Generate Report page: the log entries matching the date range, user, action, and module chosen here
 * (starting from the filters the Audit Logs page had, carried in the query string), newest first.
 */
function initAuditReport($root) {
    const params = new URLSearchParams(window.location.search);
    const filters = { user: params.get('user') ?? '', action: params.get('action') ?? '', recordType: params.get('record') ?? '' };
    let period = null;

    function rows() {
        const range = period?.range();

        return listActivity()
            .filter((entry) => !filters.user || entry.user === filters.user)
            .filter((entry) => !filters.action || entry.action === filters.action)
            .filter((entry) => !filters.recordType || splitEntity(entry.entity).recordType === filters.recordType)
            // By calendar day in local time, so an entry at any time on the last day is included.
            .filter((entry) => !range || (localIsoDate(entry.at) >= range.from && localIsoDate(entry.at) <= range.to))
            .sort((first, second) => second.at - first.at);
    }

    function summary() {
        const lines = [];

        if (period?.describe()) {
            lines.push(`Date range: ${period.describe()}`);
        }

        if (filters.user) {
            lines.push(`User: ${filters.user}`);
        }

        if (filters.action) {
            lines.push(`Action: ${filters.action}`);
        }

        if (filters.recordType) {
            lines.push(`Module: ${filters.recordType}`);
        }

        return { filters: lines, sort: 'Sorted by Date and Time, newest first' };
    }

    const report = initReportGenerator($root, {
        beforeExport: exportLogger($root, 'audit'),
        title: 'Audit Log Report',
        fileStem: 'audit-logs-report',
        sheetName: 'Audit Logs',
        noun: ['entry', 'entries'],
        countText: (count) => `${count} ${count === 1 ? 'entry matches' : 'entries match'} the selected filters.`,
        noFiltersText: 'No filters applied: every entry',
        fieldGroups,
        defaultFieldKeys,
        rows,
        summary,
    });

    // Users and modules come from the log itself, so new ones appear as they are logged; actions are fixed. A choice that
    // is no longer in the log falls back to all.
    function renderFilterOptions() {
        const entries = listActivity();
        const fill = (key, placeholder, values) => {
            if (!values.includes(filters[key])) {
                filters[key] = '';
            }

            $root.find(`[data-report-filter="${key}"]`).empty().append(
                $('<option>', { value: '', text: placeholder }),
                ...values.map((value) => $('<option>', { value, text: value })),
            ).val(filters[key]);
        };

        fill('user', 'All Users', [...new Set(entries.map((entry) => entry.user))].sort());
        fill('action', 'All Actions', activityActions);
        fill('recordType', 'All Modules', [...new Set(entries.map((entry) => splitEntity(entry.entity).recordType))].sort());
    }

    period = initReportPeriod($root.find('[data-report-period]'), periodForDay(params.get('date')), () => {
        report.clearError();
        report.refresh();
    });

    $root.on('change', '[data-report-filter]', function () {
        filters[$(this).attr('data-report-filter')] = String($(this).val());
        report.clearError();
        report.refresh();
    });

    // Clears the date range and every filter on this report.
    $root.on('click', '[data-report-clear]', function () {
        Object.assign(filters, { user: '', action: '', recordType: '' });
        $root.find('[data-report-filter]').val('');
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
    const $report = $('[data-page="audit-report"]');

    if ($report.length) {
        initAuditReport($report);
    }
});
