/**
 * A Date → "2026-10-17" in the viewer's local time, matching how timestamps are displayed. Comparing
 * this (not the timestamp) against a picked date keeps every entry from that whole day.
 */
export function localIsoDate(date) {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}
