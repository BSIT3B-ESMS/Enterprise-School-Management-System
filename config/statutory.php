<?php
// Dated employee-share rules. HR confirms each employee's actual legal coverage.
// No employer contribution is deducted from employee pay.
return ['ph_2025'=>[
 'label'=>'Philippine employee contributions (2025) / BIR 2023-onward semi-monthly withholding',
 'effective_from'=>'2025-01-01',
 'sss_min_cents'=>500000,'sss_max_cents'=>3500000,'sss_step_cents'=>50000,'sss_employee_basis_points'=>500,
 'gsis_employee_basis_points'=>900,
 'philhealth_min_cents'=>1000000,'philhealth_max_cents'=>10000000,'philhealth_employee_basis_points'=>250,
 'pagibig_max_cents'=>1000000,'pagibig_low_salary_cents'=>150000,'pagibig_low_basis_points'=>100,'pagibig_employee_basis_points'=>200,
 // BIR Annex E, effective 2023: threshold, fixed tax, marginal rate in basis points.
 'semi_monthly_tax_brackets'=>[[0,0,0],[1041700,0,1500],[1666700,93750,2000],[3333300,427070,2500],[8333300,1677070,3000],[33333300,9177070,3500]],
 'sources'=>[
  'SSS'=>'https://www.sss.gov.ph/sss-contribution-table/',
  'GSIS'=>'https://lawphil.net/statutes/repacts/ra1997/ra_8291_1997.html',
  'PhilHealth'=>'https://www.philhealth.gov.ph/advisories/2025/PA2025-0002.pdf',
  'Pag-IBIG'=>'https://www.pagibigfund.gov.ph/circulars_provident.html',
  'BIR'=>'https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf',
 ],
]];
