import $ from './jquery.js';
import { formatDate, formatDateTime, greetingForHour } from './format.js';
import { getDashboardData, onMockDataChange } from './mock-data.js';
import { renderPipelineBar } from './pipeline.js';

function sum(numbers) {
    return numbers.reduce((total, value) => total + value, 0);
}

function percentOf(value, total) {
    return `${Math.round((value / total) * 10000) / 100}%`;
}

// "92%" — a whole-number share, or "0%" when there is nothing to divide by.
function sharePercent(value, total) {
    return `${total ? Math.round((value / total) * 100) : 0}%`;
}

function countForStage(candidateStages, stage) {
    return candidateStages.find((entry) => entry.stage === stage).count;
}

// The greeting uses the viewer's local time, which the server does not know.
function renderGreeting($root, currentUser) {
    $root.find('[data-greeting]').text(`${greetingForHour(new Date().getHours())}, ${currentUser.firstName}`);
}

function renderKpis($root, data) {
    const { total: totalEmployees, active, onLeave } = data.employeeCounts;
    const totalCandidates = sum(data.candidateStages.map((entry) => entry.count));
    const openApplications = totalCandidates
        - countForStage(data.candidateStages, 'Hired')
        - countForStage(data.candidateStages, 'Rejected');
    const kpis = [
        {
            label: 'Active',
            value: active.toLocaleString('en-PH'),
            caption: `${sharePercent(active, totalEmployees)} of ${totalEmployees.toLocaleString('en-PH')} employees`,
        },
        {
            label: 'On-Leave',
            value: onLeave.toLocaleString('en-PH'),
            caption: `${sharePercent(onLeave, totalEmployees)} of employees`,
        },
        {
            label: 'Open Positions',
            value: data.openPositions.total.toLocaleString('en-PH'),
            caption: `across ${data.openPositions.jobTitleCount} ${data.openPositions.jobTitleCount === 1 ? 'job title' : 'job titles'}`,
        },
        {
            label: 'Applicants',
            value: String(openApplications),
            caption: `${countForStage(data.candidateStages, 'Applied')} not yet screened`,
        },
    ];

    $root.find('[data-dashboard-kpis]').empty().append(...kpis.map((kpi) => $('<div>', { class: 'col' }).append(
        $('<div>', { class: 'card dashboard-kpi' }).append(
            $('<span>', { class: 'dashboard-kpi-label', text: kpi.label }),
            $('<span>', { class: 'dashboard-kpi-value tabular-nums', text: kpi.value }),
            $('<span>', { class: 'dashboard-kpi-caption', text: kpi.caption }),
        ),
    )));
}

function renderHeadcounts($root, departmentHeadcounts) {
    const largestHeadcount = Math.max(...departmentHeadcounts.map((department) => department.headcount));

    $root.find('[data-dashboard-headcounts]').empty().append(...departmentHeadcounts.map(({ name, headcount }) => (
        $('<div>', { class: 'dashboard-bar-row' }).append(
            $('<span>', { class: 'text-truncate', title: name, text: name }),
            $('<div>', {
                class: 'progress dashboard-progress',
                role: 'progressbar',
                'aria-label': `${name} headcount`,
                'aria-valuenow': headcount,
                'aria-valuemin': 0,
                'aria-valuemax': largestHeadcount,
            }).append($('<div>', { class: 'progress-bar' }).css('width', percentOf(headcount, largestHeadcount))),
            $('<strong>', { class: 'tabular-nums', text: headcount }),
        )
    )));
}

function renderPipeline($root, candidateStages) {
    const totalCandidates = sum(candidateStages.map((entry) => entry.count));

    $root.find('[data-dashboard-candidate-total]').text(`${totalCandidates} candidates, all stages`);

    renderPipelineBar($root.find('[data-dashboard-pipeline]'), candidateStages);

    $root.find('[data-dashboard-pipeline-legend]').empty().append(...candidateStages.map(({ stage, count }) => (
        $('<li>', { class: 'd-flex justify-content-between gap-2' }).append(
            $('<span>', { class: 'd-flex align-items-center gap-2' }).append(
                $('<span>', { class: `dashboard-swatch stage-${stage.toLowerCase()}` }),
                document.createTextNode(stage),
            ),
            $('<strong>', { class: 'tabular-nums', text: count }),
        )
    )));
}

function renderHiresChart($root, hiresByYear) {
    const width = 560;
    const height = 230;
    const marginLeft = 34;
    const marginRight = 6;
    const marginTop = 10;
    const marginBottom = 26;
    const plotWidth = width - marginLeft - marginRight;
    const plotHeight = height - marginTop - marginBottom;
    const plotBottom = marginTop + plotHeight;
    const round = (value) => Math.round(value * 100) / 100;

    const peak = hiresByYear.reduce((best, entry) => (entry.count > best.count ? entry : best));
    const scaleMax = [4, 8, 12, 16, 20, 24, 32, 40, 60, 80, 100].find((step) => peak.count <= step)
        ?? Math.ceil(peak.count / 20) * 20;
    const slotWidth = plotWidth / hiresByYear.length;
    const barWidth = round(slotWidth * 0.62);

    let svg = `<svg viewBox="0 0 ${width} ${height}" width="100%" role="img" aria-label="Hires per year, peak ${Number(peak.year)} with ${Number(peak.count)}">`;

    for (let tick = 0; tick <= 4; tick++) {
        const tickValue = (scaleMax / 4) * tick;
        const tickY = round(plotBottom - (tickValue / scaleMax) * plotHeight);

        svg += `<line class="chart-grid" x1="${marginLeft}" x2="${width - marginRight}" y1="${tickY}" y2="${tickY}"/>`;
        svg += `<text class="chart-axis" x="${marginLeft - 6}" y="${tickY + 4}" text-anchor="end">${tickValue}</text>`;
    }

    hiresByYear.forEach(({ year, count }, index) => {
        const barHeight = round((count / scaleMax) * plotHeight);
        const barX = round(marginLeft + index * slotWidth + slotWidth * 0.19);
        const barCenter = round(barX + barWidth / 2);
        const isPeak = year === peak.year;

        svg += `<rect class="${isPeak ? 'chart-bar-peak' : 'chart-bar'}" x="${barX}" y="${round(plotBottom - barHeight)}" width="${barWidth}" height="${barHeight}" rx="3"/>`;
        svg += `<text class="chart-axis" x="${barCenter}" y="${height - 8}" text-anchor="middle">${Number(year)}</text>`;

        if (isPeak) {
            svg += `<text class="chart-peak-label" x="${barCenter}" y="${round(plotBottom - barHeight - 5)}" text-anchor="middle">${Number(count)}</text>`;
        }
    });

    $root.find('[data-dashboard-hires-chart]').html(`${svg}</svg>`);
}

function renderRecentHires($root, recentHires) {
    $root.find('[data-dashboard-recent-hires]').empty().append(...recentHires.map((hire) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<a>', {
                    class: 'btn btn-link p-0 text-start text-truncate mw-100',
                    href: `${$root.attr('data-employees-url')}/${encodeURIComponent(hire.id)}`,
                    'aria-label': `View ${hire.name}`,
                    text: hire.name,
                }),
                $('<small>', { class: 'd-block text-body-secondary', text: `${hire.title} · ${hire.department}` }),
            ),
            $('<span>', { class: 'ems-chip tabular-nums', text: formatDate(hire.hiredOn) }),
        )
    )));
}

function renderRecentActivity($root, recentActivity) {
    $root.find('[data-dashboard-activity]').empty().append(...recentActivity.map((entry) => (
        $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-3' }).append(
            $('<div>', { class: 'min-w-0' }).append(
                $('<div>', { class: 'text-truncate', text: entry.entity }),
                $('<small>', { class: 'text-body-secondary', text: entry.detail }),
            ),
            $('<small>', { class: 'text-body-secondary text-end text-nowrap', text: formatDateTime(entry.at) }),
        )
    )));
}

function renderDashboard($dashboard) {
    const data = getDashboardData();

    renderGreeting($dashboard, data.currentUser);
    renderKpis($dashboard, data);
    renderHeadcounts($dashboard, data.departmentHeadcounts);
    renderPipeline($dashboard, data.candidateStages);
    renderHiresChart($dashboard, data.hiresByYear);
    renderRecentHires($dashboard, data.recentHires);
    renderRecentActivity($dashboard, data.recentActivity);
}

$(function () {
    const $dashboard = $('[data-page="dashboard"]');

    if (!$dashboard.length) {
        return;
    }

    renderDashboard($dashboard);
    onMockDataChange(() => renderDashboard($dashboard));
});
