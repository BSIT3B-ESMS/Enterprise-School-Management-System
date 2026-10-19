// Input masks for the phone and government number fields on the applicant forms. A field opts in with
// data-mask="phone|sss|philhealth|pagibig|tin". The masks only shape what is typed (digits, dashes, a length limit);
// whether a number is acceptable is the job of the rules in validation-rules.js. Values stay text, so leading zeros
// are kept.

// Digits per dash-separated group: SSS 00-1234567-8, PhilHealth 12-123456789-0, Pag-IBIG MID 1234-5678-9012, and TIN
// 123-456-789 (with a branch code, 123-456-789-000).
const groupSizes = {
    sss: [2, 7, 1],
    philhealth: [2, 9, 1],
    pagibig: [4, 4, 4],
    tin: [3, 3, 3, 3],
};

const digitsOnly = (value) => String(value).replace(/\D/g, '');

function groupDigits(digits, sizes) {
    const groups = [];
    let start = 0;

    for (const size of sizes) {
        if (start >= digits.length) {
            break;
        }

        groups.push(digits.slice(start, start + size));
        start += size;
    }

    return groups.join('-');
}

/**
 * The value as the mask shows it: a Philippine mobile number as 09XXXXXXXXX or +639XXXXXXXXX (no spaces), a government
 * number with its dashes. Anything past the number's length is dropped.
 *
 * @param {'phone'|'sss'|'philhealth'|'pagibig'|'tin'} type
 */
export function maskValue(type, value) {
    const digits = digitsOnly(value);

    if (type === 'phone') {
        return String(value).trim().startsWith('+') ? `+${digits.slice(0, 12)}` : digits.slice(0, 11);
    }

    const sizes = groupSizes[type];

    return groupDigits(digits.slice(0, sizes.reduce((total, size) => total + size, 0)), sizes);
}

/**
 * A saved value for a masked field, in the mask's format; a value that does not fit the mask (for example, too many
 * digits) is shown as it was saved rather than cut short.
 */
export function maskedForDisplay(type, value) {
    const masked = type ? maskValue(type, value) : String(value);

    return !type || digitsOnly(masked).length === digitsOnly(value).length ? masked : String(value);
}

function applyMask(input, type) {
    const typed = input.value;
    const digitsBeforeCaret = digitsOnly(typed.slice(0, input.selectionStart ?? typed.length)).length;
    const masked = maskValue(type, typed);

    if (masked === typed) {
        return;
    }

    input.value = masked;

    // Keep the caret after the same digit it was after, instead of jumping to the end.
    let position = 0;
    let seen = 0;

    while (position < masked.length && seen < digitsBeforeCaret) {
        seen += /\d/.test(masked[position]) ? 1 : 0;
        position += 1;
    }

    input.setSelectionRange(position, position);
}

/**
 * Masks every [data-mask] field in the root as it is typed or pasted. Bind this before the form's own input handlers,
 * so they see the masked value.
 */
export function bindMasks($root) {
    $root.on('input', '[data-mask]', function () {
        applyMask(this, this.getAttribute('data-mask'));
    });
}
