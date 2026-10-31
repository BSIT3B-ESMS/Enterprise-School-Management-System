# Dated official employee deduction rules

The `ph_2025` profile implements standard employed-member rules effective from 1 January 2025, with BIR’s 2023-onward semi-monthly table. Rates are versioned in config/statutory.php and copied into each payroll snapshot. This is a dated ruleset, not an automatic government-rate update service. HR must confirm coverage, exemptions and institution payroll policy before live release.

| Item | Implemented employee share | Primary reference |
|---|---|---|
| SSS | 5% of the applicable monthly salary credit; PHP 5,000–35,000 credit, PHP 500 brackets | SSS Circular 2024-006, effective January 2025: https://www.sss.gov.ph/sss-contribution-table/ |
| GSIS | Standard employee contribution: 9% of monthly basic salary | RA 8291, Section 5: https://lawphil.net/statutes/repacts/ra1997/ra_8291_1997.html ; confirm current GSIS membership assessment with HR |
| PhilHealth | 2.5% employee share (half of 5%); monthly basic salary floor PHP 10,000, ceiling PHP 100,000 | Advisory 2025-0002: https://www.philhealth.gov.ph/advisories/2025/PA2025-0002.pdf |
| Pag-IBIG | 1% for monthly salary up to PHP 1,500; otherwise 2%; maximum fund salary PHP 10,000 | HDMF Circular 460, effective February 2024; official circular register: https://www.pagibigfund.gov.ph/circulars_provident.html |
| BIR withholding | Actual cut-off taxable compensation using semi-monthly brackets | Annex E, RR 11-2018, table effective 2023 onward: https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf |

SSS and GSIS are alternatives for the ordinary employee calculation; both cannot be charged together. Contractor/self-employed arrangements and special GSIS membership assessments require confirmed treatment; do not assign ordinary employed-member coverage by guesswork. Employer shares are never deducted from employee pay. The Pag-IBIG register may require browser verification; retain the institution’s approved copy of Circular 460 when validating payroll.

Monthly contributions are split between the two cut-offs, with an odd cent allocated to the first half. Money uses integer centavos. SSS bracket boundaries use half-up salary-credit rounding. The monthly basic basis follows the effective salary and workday allocation used by payroll; reconcile salary-change months and unusual compensation against agency assessments and enter documented overrides when necessary.

Cut-off taxable compensation is gross pay less attendance deductions and employee mandatory contributions. Recurring loans do not reduce taxable compensation. BIR tax is calculated on this actual cut-off amount, not by dividing an estimated monthly tax in half. Additions are treated as taxable compensation. Tax-exempt allowance classification is not implemented: use confirmed treatment with payroll review instead of entering an exempt amount as a taxable addition. Annual tax reconciliation and employer remittance submissions are outside this implementation.

Effective-dated employee overrides are per cut-off: blank retains the automatic calculation; explicit zero overrides it. A reason and administrator are recorded. Manual mode uses confirmed amounts and does not claim automatic official calculation. Coverage changes inside a cut-off block automatic calculation for review. Periods before the dated profile require confirmed manual amounts.

Example: PHP 22,000 monthly salary, full first cut-off, SSS coverage, PhilHealth and Pag-IBIG enabled: gross PHP 11,000; SSS PHP 550, PhilHealth PHP 275, Pag-IBIG PHP 100; taxable PHP 10,075 and withholding PHP 0; net PHP 10,075 before other approved adjustments. With standard GSIS coverage instead, GSIS is PHP 990 and net is PHP 9,635.

Frozen released payslips retain their original calculation. Changes to rates, coverage or overrides require recomputing a draft; release guards reject stale calculations. Bulk release is transactional, so one invalid draft prevents a partial batch.
