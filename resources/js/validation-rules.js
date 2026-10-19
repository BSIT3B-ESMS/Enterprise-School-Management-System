// Field rules shared by the forms that collect 201 file details and by the applicant account forms. A rule returns an
// error message, or '' when the value is valid. Only the format is checked, never a real record.

const idNumberPattern = /^[0-9][0-9 -]*$/;
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const mobilePattern = /^(09\d{9}|\+639\d{9})$/;
const idCharacters = /^[\d\s-]+$/;
const namePattern = /^\p{L}[\p{L}\s.'’-]*$/u;

export const minimumPasswordLength = 8;

// Government and tax ID numbers (the BIR number, and the employee form's numbers) are optional; when given they are
// digits with dashes or spaces.
export const idNumberRule = (value) => (!value.trim() || idNumberPattern.test(value.trim()) ? '' : 'Use numbers, dashes, and spaces only.');

export const emailRule = (value) => (emailPattern.test(value.trim()) ? '' : 'Enter a valid email address.');

/**
 * A required Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.
 *
 * @param {string} blankMessage What to say when the field is left empty.
 */
export const mobileNumberRule = (blankMessage) => (value) => {
    if (!value.trim()) {
        return blankMessage;
    }

    return mobilePattern.test(value.trim()) ? '' : 'Enter a valid mobile number: 09XXXXXXXXX or +639XXXXXXXXX.';
};

export const phoneRule = mobileNumberRule('Enter your phone number.');

/**
 * An optional government number made of dashes and a fixed count of digits (leading zeros count).
 *
 * @param {string} message What to say when the digit count is wrong.
 */
const digitCountRule = (counts, message) => (value) => {
    const trimmed = value.trim();

    if (!trimmed) {
        return '';
    }

    if (!idCharacters.test(trimmed)) {
        return 'Use numbers and dashes only.';
    }

    return counts.includes(trimmed.replace(/\D/g, '').length) ? '' : message;
};

export const sssRule = digitCountRule([10], 'SSS number must be 10 digits, like 00-1234567-8.');
export const philhealthRule = digitCountRule([12], 'PhilHealth number must be 12 digits, like 12-123456789-0.');
export const pagibigRule = digitCountRule([12], 'Pag-IBIG MID number must be 12 digits, like 1234-5678-9012.');
export const tinRule = digitCountRule([9, 12], 'TIN must be 9 digits, or 12 digits with a branch code, like 123-456-789 or 123-456-789-000.');

// A person's name: letters, spaces, and . ' - (at least two characters).
export const personNameRule = (blankMessage) => (value) => {
    const trimmed = value.trim();

    if (!trimmed) {
        return blankMessage;
    }

    return trimmed.length >= 2 && namePattern.test(trimmed) ? '' : 'Use letters, spaces, and . \' - only.';
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

/**
 * Field errors for a job's base salary range (empty when valid): both amounts are needed and 0 or more, and the maximum
 * cannot be lower than the minimum.
 *
 * @returns {{ salaryMin?: string, salaryMax?: string }}
 */
export function salaryRangeErrors(minimum, maximum) {
    const errors = {};
    const amountOf = (value) => (String(value).trim() === '' ? Number.NaN : Number(value));
    const low = amountOf(minimum);
    const high = amountOf(maximum);

    if (String(minimum).trim() === '') {
        errors.salaryMin = 'Enter a minimum base salary.';
    } else if (Number.isNaN(low) || low < 0) {
        errors.salaryMin = 'The minimum base salary must be 0 or more.';
    }

    if (String(maximum).trim() === '') {
        errors.salaryMax = 'Enter a maximum base salary.';
    } else if (Number.isNaN(high) || high < 0) {
        errors.salaryMax = 'The maximum base salary must be 0 or more.';
    } else if (!errors.salaryMin && high < low) {
        errors.salaryMax = 'The maximum cannot be lower than the minimum.';
    }

    return errors;
}
