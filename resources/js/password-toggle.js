// Adds a Show/Hide control to every password field. The button carries data-password-toggle and its aria-controls names
// the input; the label inside [data-password-toggle-label] changes between Show and Hide.
function initPasswordToggles(root = document) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const label = button.querySelector('[data-password-toggle-label]');

        if (!input || !label) {
            return;
        }

        button.addEventListener('click', () => {
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            label.textContent = isHidden ? 'Hide' : 'Show';
            button.setAttribute('aria-pressed', String(isHidden));
        });
    });
}

initPasswordToggles();
