import { Toast } from 'bootstrap';
import $ from './jquery.js';

/**
 * Shows a message in the app shell's toast (see layouts/admin.blade.php).
 */
export function showToast(message, { isError = false } = {}) {
    const $toast = $('[data-app-toast]');

    if (!$toast.length) {
        return;
    }

    if (isError) {
        $toast.addClass('app-toast-error');
    } else {
        $toast.removeClass('app-toast-error');
    }

    $toast.find('[data-app-toast-message]').text(message);
    Toast.getOrCreateInstance($toast[0]).show();
}

const queuedToastKey = 'ems-queued-toast';

/**
 * Saves a message to show on the next page load (for actions that navigate away).
 */
export function queueToast(message) {
    try {
        sessionStorage.setItem(queuedToastKey, message);
    } catch {
        // Storage unavailable; the confirmation is skipped.
    }
}

export function showQueuedToast() {
    let message = null;

    try {
        message = sessionStorage.getItem(queuedToastKey);
        sessionStorage.removeItem(queuedToastKey);
    } catch {
        return;
    }

    if (message) {
        showToast(message);
    }
}
