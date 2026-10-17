import $ from './jquery.js';
import { getEmployeePortalData, onMockDataChange } from './mock-data.js';

// Fills the Employee interface's header (avatar, name, email, job title) for whoever is signed in.
function renderHeader($shell) {
    const { employee } = getEmployeePortalData();

    $shell.find('[data-portal-initials]').text(employee?.initials ?? '');
    $shell.find('[data-portal-name]').text(employee?.name ?? 'Employee');
    $shell.find('[data-portal-email]').text(employee?.email ?? '');
    $shell.find('[data-portal-role]').text(employee?.jobTitle ?? '');
}

$(function () {
    const $shell = $('[data-app-shell]');

    if (!$shell.find('[data-portal-name]').length) {
        return;
    }

    renderHeader($shell);
    onMockDataChange(() => renderHeader($shell));
});
