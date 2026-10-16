const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/**
 * ₱8,742,000 / ₱59,067.57 — whole amounts drop the ".00".
 */
export function formatPeso(amount) {
    const formatted = amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return `₱${formatted.replace(/\.00$/, '')}`;
}

/**
 * "2024-10-24" → "Oct 24, 2024" (parsed by parts so the time zone cannot shift the day).
 */
export function formatDate(isoDate) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return `${monthNames[month - 1]} ${day}, ${year}`;
}

/**
 * Date → "Oct 16, 2026, 2:31 PM" in the viewer's local time.
 */
export function formatDateTime(date) {
    const hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${monthNames[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}, ${hours % 12 || 12}:${minutes} ${hours < 12 ? 'AM' : 'PM'}`;
}

/**
 * "Maria Clara Santos" → "MS" (first and last name initials).
 */
export function initialsOf(name) {
    const parts = String(name).trim().split(/\s+/);

    return `${parts[0]?.[0] ?? ''}${parts.length > 1 ? parts[parts.length - 1][0] : ''}`.toUpperCase();
}

/**
 * 2048 → "2 KB", 1572864 → "1.5 MB".
 */
export function formatFileSize(bytes) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let size = bytes / 1024;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit += 1;
    }

    return `${Number(size.toFixed(1))} ${units[unit]}`;
}

/**
 * "14:30" → "2:30 PM" (the value of a time input).
 */
export function formatTime(time) {
    const [hours, minutes] = time.split(':').map(Number);

    return `${hours % 12 || 12}:${String(minutes).padStart(2, '0')} ${hours < 12 ? 'AM' : 'PM'}`;
}
