import { Offcanvas } from 'bootstrap';
import $ from './jquery.js';
import { showQueuedToast } from './toast.js';

const desktopQuery = window.matchMedia('(min-width: 992px)');

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

$(function () {
    const $shell = $('[data-app-shell]');

    if (!$shell.length) {
        return;
    }

    initSidebarToggle($shell);
    showQueuedToast();
});
