import $ from './jquery.js';
import { getHrSettings, onMockDataChange, updateHrSettings } from './mock-data.js';
import { getThemeChoice, setThemeChoice } from './theme.js';

// Account Settings: the theme radios use the header toggle's theme choice, and the notification switches
// decide what the header's notifications menu shows. Each change applies as soon as it is made.
function renderTheme($root) {
    $root.find('[data-settings-theme]').each(function () {
        $(this).prop('checked', $(this).val() === getThemeChoice());
    });
}

function renderNotifications($root) {
    const { notifications } = getHrSettings();

    $root.find('[data-settings-notification]').each(function () {
        $(this).prop('checked', Boolean(notifications[$(this).attr('data-settings-notification')]));
    });
}

$(function () {
    const $settings = $('[data-page="account-settings"]');

    if (!$settings.length) {
        return;
    }

    $settings.on('change', '[data-settings-theme]', function () {
        setThemeChoice(String($(this).val()));
    });

    $settings.on('change', '[data-settings-notification]', function () {
        updateHrSettings({ notifications: { [$(this).attr('data-settings-notification')]: $(this).prop('checked') } });
    });

    renderTheme($settings);
    renderNotifications($settings);

    // The header's theme toggle changes the same choice; keep the radios in step. Notification changes made in
    // another tab arrive through the shared mock data.
    document.addEventListener('ems:theme-change', () => renderTheme($settings));
    onMockDataChange(() => renderNotifications($settings));
});
