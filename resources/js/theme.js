import $ from './jquery.js';

// Dark is the default (set on <html> by the layouts); a saved choice overrides it.
const storageKey = 'ems-theme';
const themes = ['dark', 'light'];
const $html = $(document.documentElement);

function readSavedTheme() {
    try {
        return localStorage.getItem(storageKey);
    } catch {
        return null;
    }
}

function saveTheme(theme) {
    try {
        localStorage.setItem(storageKey, theme);
    } catch {
        // Storage unavailable (private mode or blocked); the choice lasts for this page only.
    }
}

function applyTheme(theme) {
    $html.attr('data-bs-theme', theme);
}

const savedTheme = readSavedTheme();

// Applied immediately (not on DOM ready) so every page, including Login, starts in the saved mode.
if (themes.includes(savedTheme)) {
    applyTheme(savedTheme);
}

$(function () {
    $('[data-app-shell]').on('click', '[data-theme-toggle]', function () {
        const nextTheme = $html.attr('data-bs-theme') === 'dark' ? 'light' : 'dark';

        applyTheme(nextTheme);
        saveTheme(nextTheme);
    });
});
