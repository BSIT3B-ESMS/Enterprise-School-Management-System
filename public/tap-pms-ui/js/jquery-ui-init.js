/* jQuery UI is deliberately loaded after jQuery and before optional widgets. */
jQuery(function ($) {
  $('[data-pms-datepicker]').datepicker({ dateFormat: 'yy-mm-dd' });
  // Do not replace native type=date fields; their ISO values are used by payroll.
});
