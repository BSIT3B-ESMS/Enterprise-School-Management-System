import $ from './jquery.js';

// Dark is the default (set on <html> by the layouts); a saved choice overrides it. The choice is Light, Dark,
// or System (follow the device's light or dark setting); the header toggle and Account Settings both use it.
const storageKey = 'ems-theme';
const choices = ['light', 'dark', 'system'];
const $html = $(document.documentElement);
const systemQuery = typeof window.matchMedia === 'function' ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function readSavedChoice() {
    try {
        return localStorage.getItem(storageKey);
    } catch {
        return null;
    }
}

function saveChoice(choice) {
    try {
        localStorage.setItem(storageKey, choice);
    } catch {
        // Storage unavailable (private mode or blocked); the choice lasts for this page only.
    }
}

// The mode a choice looks like right now: System is whatever the device is set to.
function modeFor(choice) {
    if (choice === 'system') {
        return systemQuery?.matches ? 'dark' : 'light';
    }

    return choice;
}

let currentChoice = choices.includes(readSavedChoice()) ? readSavedChoice() : 'dark';

function applyChoice(choice) {
    currentChoice = choice;
    $html.attr('data-bs-theme', modeFor(choice));
}

/**
 * The chosen theme: 'light', 'dark', or 'system' (dark until another is chosen).
 */
export function getThemeChoice() {
    return currentChoice;
}

/**
 * Applies and remembers a theme choice, and tells the page (so Account Settings stays in step with the header toggle).
 */
export function setThemeChoice(choice) {
    if (!choices.includes(choice)) {
        return;
    }

    applyChoice(choice);
    saveChoice(choice);
    document.dispatchEvent(new CustomEvent('ems:theme-change'));
}

// Applied immediately (not on DOM ready) so every page, including Login, starts in the saved mode.
applyChoice(currentChoice);

// With System chosen, follow the device when it switches between light and dark.
systemQuery?.addEventListener?.('change', () => {
    if (currentChoice === 'system') {
        applyChoice('system');
    }
});

$(function () {
    // The header's toggle flips the mode you are looking at, which makes it an explicit Light or Dark choice.
    $('[data-app-shell]').on('click', '[data-theme-toggle]', function () {
        setThemeChoice($html.attr('data-bs-theme') === 'dark' ? 'light' : 'dark');
    });
});
