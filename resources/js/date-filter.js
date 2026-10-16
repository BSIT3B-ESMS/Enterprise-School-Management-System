const rangeErrorMessage = 'Start date must be on or before the end date.';

/**
 * "2026-10-17" → whether it falls on or between the start and end dates. Both ends are inclusive and
 * either may be empty (no limit on that side). ISO dates compare correctly as plain strings.
 */
export function isInDateRange(isoDate, startDate, endDate) {
    return (!startDate || isoDate >= startDate) && (!endDate || isoDate <= endDate);
}

/**
 * A Date → "2026-10-17" in the viewer's local time, matching how timestamps are displayed. Comparing
 * this (not the timestamp) against the end date keeps every entry from the whole end day.
 */
export function localIsoDate(date) {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

/**
 * Keeps a page's Start and End Date inputs (data-date-filter="start" / "end") in step: each limits
 * the other's picker, and a start after the end is flagged (the list is then empty, as nothing can match).
 */
export function syncDateRange($root) {
    const $start = $root.find('[data-date-filter="start"]');
    const $end = $root.find('[data-date-filter="end"]');
    const startDate = String($start.val() ?? '');
    const endDate = String($end.val() ?? '');
    const isReversed = Boolean(startDate && endDate && startDate > endDate);

    if (startDate) {
        $end.attr('min', startDate);
    } else {
        $end.removeAttr('min');
    }

    if (endDate) {
        $start.attr('max', endDate);
    } else {
        $start.removeAttr('max');
    }

    $start.add($end).attr('aria-invalid', String(isReversed)).toggleClass('is-invalid', isReversed);
    $root.find('[data-date-range-error]').text(isReversed ? rangeErrorMessage : '').toggleClass('d-none', !isReversed);
}
