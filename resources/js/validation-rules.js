// Field rules shared by the forms that collect 201 file details (the employee form and the job application form).
// A rule returns an error message, or '' when the value is valid.

const idNumberPattern = /^[0-9][0-9 -]*$/;

// Government and tax ID numbers (SSS, PhilHealth, Pag-IBIG, TIN, BIR) are optional; when given they are digits
// with dashes or spaces.
export const idNumberRule = (value) => (!value.trim() || idNumberPattern.test(value.trim()) ? '' : 'Use numbers, dashes, and spaces only.');
