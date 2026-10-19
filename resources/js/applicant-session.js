// The applicant's frontend session: whether they accepted the Terms and Conditions and which mock account is signed
// in. It is kept in sessionStorage, so it lasts only for this browser tab and ends when the tab is closed. It is not
// a real login or a consent record: nothing is sent to a server or kept after the session.

const storageKey = 'ems-applicant-session';

// Used when sessionStorage is unavailable (private mode or blocked): the session then lasts for this page only.
let fallbackSession = {};

function readSession() {
    try {
        const saved = JSON.parse(sessionStorage.getItem(storageKey));

        return saved && typeof saved === 'object' ? saved : {};
    } catch {
        return { ...fallbackSession };
    }
}

function writeSession(session) {
    fallbackSession = { ...session };

    try {
        sessionStorage.setItem(storageKey, JSON.stringify(session));
    } catch {
        // Storage unavailable; the fallback above keeps the session for this page.
    }
}

export function hasAcceptedTerms() {
    return Boolean(readSession().termsAcceptedAt);
}

export function acceptTerms() {
    writeSession({ ...readSession(), termsAcceptedAt: Date.now() });
}

/**
 * The signed-in applicant's account ID, or '' when no one is signed in.
 */
export function currentAccountId() {
    return String(readSession().accountId ?? '');
}

/**
 * Signs the applicant in for this session. The Terms must have been accepted first.
 */
export function signIn(accountId) {
    writeSession({ ...readSession(), accountId });
}

/**
 * Ends the session: signs out and clears the Terms acceptance, so the next sign-in asks for it again.
 */
export function signOut() {
    writeSession({});
}
