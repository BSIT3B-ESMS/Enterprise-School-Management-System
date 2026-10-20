import { Offcanvas } from 'bootstrap';
import $ from './jquery.js';
import { formatDateTime } from './format.js';
import { getShellData, onMockDataChange, recordSignOut } from './mock-data.js';
import { showQueuedToast, showToast } from './toast.js';

const desktopQuery = window.matchMedia('(min-width: 992px)');

function renderHeader($shell, { currentUser, pendingCandidates, notifications }) {
    // HR pages print the signed-in user on the server; the other interfaces still fill it from the sample data.
    if (!$shell.is('[data-server-user]')) {
        $shell.find('[data-user-initials]').text(currentUser.initials);
        $shell.find('[data-user-name]').text(currentUser.name);
        $shell.find('[data-user-role]').text(currentUser.role);
        $shell.find('[data-user-email]').text(currentUser.email);
    }

    const $pendingIndicators = $shell.find('[data-notification-dot], [data-pending-candidates]');

    if (pendingCandidates > 0) {
        $pendingIndicators.removeClass('d-none');
    } else {
        $pendingIndicators.addClass('d-none');
    }

    $shell.find('[data-pending-count]').text(pendingCandidates);
    $shell.find('[data-pending-label]').text(
        `${pendingCandidates === 1 ? 'candidate' : 'candidates'} waiting for a demo interview`,
    );

    // With every kind turned off in Account Settings (or nothing logged yet), the menu says so.
    $shell.find('[data-notifications]').empty().append(...(notifications.length
        ? notifications.map((notification) => (
            $('<div>', { class: 'app-menu-note' }).append(
                $('<span>', { text: notification.entity }),
                $('<small>', { text: `${notification.detail} · ${formatDateTime(notification.at)}` }),
            )
        ))
        : [$('<div>', { class: 'app-menu-note text-body-secondary', text: 'No notifications to show.' })]));
}

function initSidebarToggle($shell) {
    const $toggle = $shell.find('[data-sidebar-toggle]');
    const $sidebar = $shell.find('#app-sidebar');

    function syncExpanded() {
        const isExpanded = desktopQuery.matches
            ? !$shell.hasClass('is-sidebar-collapsed')
            : $sidebar.hasClass('show');

        $toggle.attr('aria-expanded', String(isExpanded));
    }

    $toggle.on('click', function () {
        if (desktopQuery.matches) {
            $shell.toggleClass('is-sidebar-collapsed');
            syncExpanded();

            return;
        }

        Offcanvas.getOrCreateInstance($sidebar[0]).toggle();
    });

    $sidebar.on('shown.bs.offcanvas hidden.bs.offcanvas', syncExpanded);
    desktopQuery.addEventListener('change', syncExpanded);
    syncExpanded();
}

function initComingSoon($shell) {
    $shell.on('click', '[data-coming-soon]', function () {
        showToast(`${$(this).attr('data-coming-soon')} is not available yet.`);
    });
}

// The links still go to the login page; the sign-out is recorded in the audit log first.
function initLogout($shell) {
    $shell.on('click', '[data-logout]', () => recordSignOut());
}

$(function () {
    const $shell = $('[data-app-shell]');

    if (!$shell.length) {
        return;
    }

    renderHeader($shell, getShellData());
    onMockDataChange(() => renderHeader($shell, getShellData()));
    initSidebarToggle($shell);
    initComingSoon($shell);
    initLogout($shell);
    showQueuedToast();
});
