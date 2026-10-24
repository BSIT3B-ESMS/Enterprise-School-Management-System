/* ==========================================================================
   View: Interview (#/interview) — interview schedules and who is waiting,
   for programs that need an interview. Read-only here; the college dean
   records the outcome.
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;
  var data = null;

  function load() {
    AO.api("GET", "/api/admin/interviews").done(function (res) { data = res.data; ADM.refresh(); });
  }

  function render($el) {
    if (!data) { $el.html('<div class="page-title"><h1>Interview</h1></div><p class="text-muted">Loading…</p>'); load(); return; }
    var ui = ADM.ui;
    $el.html(
      '<div class="page-title" data-reveal><h1>Interview</h1><p>Programs that need an interview: schedules, assigned applicants and those still waiting.</p></div>' +
      '<section class="table-card mb-3" data-reveal><div class="table-toolbar"><strong>Schedules</strong></div>' +
      (data.schedules.length ? '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Program</th><th>Date</th><th>Time</th><th>Venue</th><th>Assigned</th></tr></thead><tbody>' +
        data.schedules.map(function (s) {
          return '<tr><td><span class="code-chip">' + esc(s.program) + "</span></td><td>" + esc(s.date) + "</td><td>" + esc(s.time || "") + "</td><td>" + esc(s.venue) + "</td><td>" + s.applicants.length + " of " + s.capacity + "</td></tr>";
        }).join("") + "</tbody></table></div>" : ui.empty("calendar", "No interview schedules yet")) + "</section>" +
      '<section class="table-card" data-reveal><div class="table-toolbar"><strong>Waiting for an interview</strong></div>' +
      (data.waiting.length ? '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th></tr></thead><tbody>' +
        data.waiting.map(function (w) {
          return '<tr class="row-link" data-href="#/applicants/' + esc(w.id) + '" tabindex="0"><td>' + esc(w.name) + " · " + esc(w.id) + '</td><td><span class="code-chip">' + esc(w.program) + "</span></td></tr>";
        }).join("") + "</tbody></table></div>" : ui.empty("check", "No one is waiting")) + "</section>"
    );
  }

  ADM.views.interview = { title: "Interview", render: render };
})(jQuery);
