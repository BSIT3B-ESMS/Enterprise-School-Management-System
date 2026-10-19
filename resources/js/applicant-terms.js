import { Modal, Toast } from 'bootstrap';
import $ from './jquery.js';
import { acceptTerms, currentAccountId, hasAcceptedTerms } from './applicant-session.js';

// What to do once the open Terms modal is answered: { onAccept, onDecline }. The answer runs after the modal has
// finished closing, so focus and navigation are not cut off mid-animation.
let pendingRequest = null;
let answer = null;

function termsModal() {
    return $('[data-terms-modal]');
}

function showDeclinedToast() {
    const $toast = $('[data-terms-declined-toast]');

    if ($toast.length) {
        Toast.getOrCreateInstance($toast[0]).show();
    }
}

/**
 * Asks for the Terms and Conditions before continuing. When they were already accepted this session, `onAccept`
 * runs at once (unless `force` is set, which always shows the modal); otherwise the modal opens and `onAccept` runs
 * only after Accept and Continue. Decline runs `onDecline` (by default, a notice that the terms are required) and
 * does not continue.
 */
export function requestTerms({ onAccept, onDecline = showDeclinedToast, force = false }) {
    if (!force && hasAcceptedTerms()) {
        onAccept();

        return;
    }

    const $modal = termsModal();

    if (!$modal.length) {
        return;
    }

    pendingRequest = { onAccept, onDecline };
    answer = null;
    $modal.find('[data-terms-agree]').prop('checked', false);
    $modal.find('[data-terms-accept]').prop('disabled', true);
    Modal.getOrCreateInstance($modal[0]).show();
}

function initTermsModal($modal) {
    const modal = Modal.getOrCreateInstance($modal[0]);

    $modal.on('change', '[data-terms-agree]', function () {
        $modal.find('[data-terms-accept]').prop('disabled', !this.checked);
    });

    $modal.on('click', '[data-terms-accept]', function () {
        if (!$modal.find('[data-terms-agree]').prop('checked')) {
            return;
        }

        acceptTerms();
        answer = 'accept';
        modal.hide();
    });

    $modal.on('click', '[data-terms-decline]', function () {
        answer = 'decline';
        modal.hide();
    });

    $modal.on('hidden.bs.modal', function () {
        const request = pendingRequest;

        pendingRequest = null;

        if (request && answer === 'accept') {
            request.onAccept();
        } else if (request) {
            request.onDecline();
        }
    });
}

$(function () {
    const $modal = termsModal();

    if (!$modal.length) {
        return;
    }

    initTermsModal($modal);

    // Links into registration (Create Account and Apply) show the terms every time, even when they were accepted earlier
    // in this tab, and continue to the link only once accepted. Someone already signed in has an account, so their links
    // go straight through.
    $(document).on('click', 'a[data-terms-continue]', function (event) {
        if (currentAccountId()) {
            return;
        }

        event.preventDefault();

        const href = $(this).attr('href');
        const declineUrl = $(this).attr('data-terms-decline-url');

        // Declining closes the modal and stays on this page, or goes to the link's own decline address (a position's details
        // return to Open Positions with that position selected).
        requestTerms({
            force: true,
            onAccept: () => window.location.assign(href),
            ...(declineUrl ? { onDecline: () => window.location.assign(declineUrl) } : {}),
        });
    });
});
