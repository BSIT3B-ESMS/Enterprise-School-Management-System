import $ from './jquery.js';
import { getEmployeeBenefitsData, onMockDataChange } from './mock-data.js';

function eligibilityText(benefit) {
    if (benefit.isForAllJobTitles) {
        return 'Eligible: all job titles';
    }

    return benefit.eligibleJobTitles.length
        ? `Eligible job titles: ${benefit.eligibleJobTitles.join(', ')}`
        : 'Not offered with any job title yet';
}

function benefitCard(benefit) {
    const $status = benefit.isIncluded
        ? $('<span>', { class: 'status-pill employment-active', text: 'Included' })
        : $('<span>', { class: 'ems-chip', text: 'Not included' });

    return $('<div>', { class: 'col' }).append(
        $('<section>', { class: 'card dashboard-panel' }).append(
            $('<div>', { class: 'd-flex justify-content-between align-items-start gap-3 mb-2' }).append(
                $('<h3>', { class: 'fs-6 fw-semibold mb-0', text: benefit.name }),
                $status,
            ),
            $('<p>', { class: 'mb-2', text: benefit.description }),
            $('<small>', { class: 'text-body-secondary mt-auto', text: eligibilityText(benefit) }),
        ),
    );
}

function renderBenefits($root) {
    const { employee, benefits } = getEmployeeBenefitsData();

    // HR can delete the employee's record, for example in another tab.
    $root.find('[data-portal-missing]').toggleClass('d-none', Boolean(employee));
    $root.find('[data-benefits-none]').toggleClass('d-none', !employee || benefits.length > 0);
    $root.find('[data-portal-content]').toggleClass('d-none', !employee || benefits.length === 0);

    if (!employee || !benefits.length) {
        return;
    }

    const yours = benefits.filter((benefit) => benefit.isIncluded);
    const others = benefits.filter((benefit) => !benefit.isIncluded);

    $root.find('[data-benefits-yours-count]').text(yours.length);
    $root.find('[data-benefits-yours]').empty().append(...yours.map(benefitCard));
    $root.find('[data-benefits-yours-empty]').toggleClass('d-none', yours.length > 0);
    $root.find('[data-benefits-other]').empty().append(...others.map(benefitCard));
    $root.find('[data-benefits-other-section]').toggleClass('d-none', others.length === 0);
}

$(function () {
    const $benefits = $('[data-page="portal-benefits"]');

    if (!$benefits.length) {
        return;
    }

    renderBenefits($benefits);
    onMockDataChange(() => renderBenefits($benefits));
});
