import $ from './jquery.js';

// Each area has its own default (set on <html> by the layouts) and its own saved choice, so changing one never changes
// the other: the PLP Careers landing and job details pages (data-theme-scope="public") open in Light, and Login,
// account creation, and everything after it open in Dark. Parts of the system shown on a Careers page (the Terms and
// Conditions, marked data-theme-follow="system") keep the system's theme. A saved choice overrides the default. The choice is Light, Dark, or System (follow the device's light
// or dark setting); the header toggle and Account Settings both use it.
const isPublicScope = document.documentElement.dataset.themeScope === 'public';
const storageKey = isPublicScope ? 'ems-theme-public' : 'ems-theme';
const defaultChoice = isPublicScope ? 'light' : 'dark';
const systemStorageKey = 'ems-theme';
const choices = ['light', 'dark', 'system'];
const $html = $(document.documentElement);
const systemQuery = typeof window.matchMedia === 'function' ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function readSavedChoice(key = storageKey) {
    try {
        return localStorage.getItem(key);
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

let currentChoice = choices.includes(readSavedChoice()) ? readSavedChoice() : defaultChoice;

function applyChoice(choice) {
    currentChoice = choice;
    $html.attr('data-bs-theme', modeFor(choice));
}

/**
 * The chosen theme: 'light', 'dark', or 'system' (the area's default until another is chosen).
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
    if (isPublicScope) {
        const systemChoice = readSavedChoice(systemStorageKey);
        $('[data-theme-follow="system"]').attr('data-bs-theme', modeFor(choices.includes(systemChoice) ? systemChoice : 'dark'));
    }

    // A toggle (in the app header, the PLP Careers header, or on Login and Create Account) flips the mode you are
    // looking at, which makes it an explicit Light or Dark choice for this area.
    $('[data-app-shell], .public-header, [data-guest-theme]').on('click', '[data-theme-toggle]', function () {
        setThemeChoice($html.attr('data-bs-theme') === 'dark' ? 'light' : 'dark');
    });
});
