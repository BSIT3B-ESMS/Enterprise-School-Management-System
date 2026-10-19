import $ from './jquery.js';
import { describeHiredRange, hiredRange, monthBounds, validateHiredRange } from './employee-list.js';

const monthPattern = /^\d{4}-(0[1-9]|1[0-2])$/;

/**
 * The Date Range picker on the Recruitment and Audit Logs reports (resources/views/components/report-period.blade.php):
 * the same Month, From Date, and To Date choice, checks, and Apply Filter / Clear Filter as the Employees Date Hired
 * filter. A month sets the period; the dates narrow it inside that month. Nothing chosen means every date.
 *
 * @param {JQuery} $scope The picker's fieldset.
 * @param {{ month: string, from: string, to: string }} initial A period carried over from the module page (used only if valid).
 * @param {() => void} onChange Called after the period is applied or cleared.
 * @returns {{ range: () => ({ from: string, to: string }|null), describe: () => string }}
 */
export function initReportPeriod($scope, initial, onChange) {
    const period = Object.keys(validateHiredRange(initial)).length ? { month: '', from: '', to: '' } : { ...initial };
    const asView = () => ({ month: period.month, hiredFrom: period.from, hiredTo: period.to });

    function readInputs() {
        return {
            month: String($scope.find('[data-period-month]').val() ?? ''),
            from: String($scope.find('[data-period-from]').val() ?? ''),
            to: String($scope.find('[data-period-to]').val() ?? ''),
        };
    }

    // The date fields only offer days of the chosen month.
    function limitDates() {
        const { month } = readInputs();
        const bounds = monthPattern.test(month) ? monthBounds(month) : null;

        $scope.find('[data-period-from], [data-period-to]').each(function () {
            if (bounds) {
                $(this).attr({ min: bounds.first, max: bounds.last });
            } else {
                $(this).removeAttr('min').removeAttr('max');
            }
        });
    }

    function showErrors(errors) {
        ['month', 'from', 'to'].forEach((key) => {
            const message = errors[key] ?? '';

            $scope.find(`[data-period-error="${key}"]`).text(message);
            $scope.find(`[data-period-${key}]`).toggleClass('is-invalid', Boolean(message)).attr('aria-invalid', String(Boolean(message)));
        });
    }

    function renderSummary() {
        const label = describeHiredRange(asView());

        $scope.find('[data-period-summary]').text(label ? `Applied: ${label}` : 'Applied: every date').toggleClass('text-body-secondary', !label);
    }

    function fillInputs() {
        $scope.find('[data-period-month]').val(period.month);
        $scope.find('[data-period-from]').val(period.from);
        $scope.find('[data-period-to]').val(period.to);
        limitDates();
        showErrors({});
        renderSummary();
    }

    // Choosing a month limits the dates to that month (and drops dates from another month).
    $scope.on('input change', '[data-period-month]', function () {
        limitDates();
        $scope.find('[data-period-from], [data-period-to]').each(function () {
            const first = $(this).attr('min') ?? '';
            const last = $(this).attr('max') ?? '';
            const value = String($(this).val() ?? '');

            if (!first || value < first || value > last) {
                $(this).val('');
            }
        });
        showErrors({});
    });

    $scope.on('input change', '[data-period-from], [data-period-to]', () => showErrors({}));

    // Apply Filter checks the period first: nothing changes while it is invalid.
    $scope.on('click', '[data-period-apply]', function () {
        const typed = readInputs();
        const errors = validateHiredRange(typed);

        showErrors(errors);

        if (Object.keys(errors).length) {
            $scope.find(`[data-period-${Object.keys(errors)[0]}]`).trigger('focus');

            return;
        }

        Object.assign(period, typed);
        renderSummary();
        onChange();
    });

    $scope.on('keydown', 'input', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            $scope.find('[data-period-apply]').trigger('click');
        }
    });

    $scope.on('click', '[data-period-clear]', function () {
        Object.assign(period, { month: '', from: '', to: '' });
        fillInputs();
        onChange();
    });

    fillInputs();

    return {
        range: () => hiredRange(asView()),
        describe: () => describeHiredRange(asView()),
    };
}

/**
 * A single day (as the module pages' date filter picks) as a report period: that day within its month.
 *
 * @returns {{ month: string, from: string, to: string }}
 */
export function periodForDay(isoDate) {
    return /^\d{4}-\d{2}-\d{2}$/.test(isoDate ?? '') ? { month: isoDate.slice(0, 7), from: isoDate, to: isoDate } : { month: '', from: '', to: '' };
}
