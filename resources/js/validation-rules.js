// Field rules shared by the forms that collect 201 file details (the employee form and the job application form)
// and the applicant account forms. A rule returns an error message, or '' when the value is valid.

const idNumberPattern = /^[0-9][0-9 -]*$/;
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const phonePattern = /^\+?[\d\s().-]+$/;

export const minimumPasswordLength = 8;

// Government and tax ID numbers (SSS, PhilHealth, Pag-IBIG, TIN, BIR) are optional; when given they are digits
// with dashes or spaces.
export const idNumberRule = (value) => (!value.trim() || idNumberPattern.test(value.trim()) ? '' : 'Use numbers, dashes, and spaces only.');

export const emailRule = (value) => (emailPattern.test(value.trim()) ? '' : 'Enter a valid email address.');

// A required phone number: digits with an optional leading +, spaces, dashes, dots, or parentheses (7 to 15 digits).
export const phoneRule = (value) => {
    const digitCount = value.replace(/\D/g, '').length;

    if (!value.trim()) {
        return 'Enter your phone number.';
    }

    return phonePattern.test(value.trim()) && digitCount >= 7 && digitCount <= 15 ? '' : 'Enter a valid phone number.';
};

// A new password (not trimmed): at least eight characters with an uppercase letter, a lowercase letter, and a number.
export const newPasswordRule = (value) => {
    if (!value) {
        return 'Enter a password.';
    }

    if (value.length < minimumPasswordLength) {
        return `Password must be at least ${minimumPasswordLength} characters.`;
    }

    if (!/[A-Z]/.test(value)) {
        return 'Include at least one uppercase letter.';
    }

    if (!/[a-z]/.test(value)) {
        return 'Include at least one lowercase letter.';
    }

    return /[0-9]/.test(value) ? '' : 'Include at least one number.';
};
