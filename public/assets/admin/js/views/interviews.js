/* ==========================================================================
   View: Interviews (#/interviews), FR-17 · "Interview" in the Admission
   Personnel diagram. Programs flagged "requires interview" evaluate the
   applicants who passed the entrance exam, before the offer (D14).
   Admission personnel create schedules and assign applicants waiting for
   one; the program's College Dean records the evaluation (dean.js). The
   Admin, who runs the whole system, can record it here too.
   (Adapted from the interview-ui-validation branch.)
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;

  function isAdmin() { return ADM.STAFF.roleKey === "admission_head"; }
  function programName(code) {
    var p = ADM.interviews.programs.filter(function (x) { return x.code === code; })[0];
    return p ? p.name : code;
  }
  function when(s) { return AO.fmt(s.date, { weekday: "short", month: "short", day: "numeric", year: "numeric" }) + (s.time ? " · " + s.time : ""); }
  function reload() { return ADM.load().done(function () { ADM.refresh(); }); }

  function scheduleForm() {
    if (!ADM.interviews.programs.length) return "";
    return '<section class="card-soft" data-reveal><h3 class="card-title">New interview schedule</h3>' +
      '<form id="interview-schedule-form" class="row g-2 align-items-end" novalidate>' +
      '<div class="col-md-3"><label class="form-label" for="iv-program">Program</label><select class="form-select" id="iv-program" required>' +
      ADM.interviews.programs.map(function (p) { return '<option value="' + esc(p.code) + '">' + esc(p.code) + " · " + esc(p.name) + "</option>"; }).join("") +
      '</select><div class="invalid-feedback" id="iv-program-error"></div></div>' +
      '<div class="col-md-2"><label class="form-label" for="iv-date">Date</label><input class="form-control" type="date" id="iv-date" min="' + AO.toISO(AO.today()) + '" data-msg-range="Pick today or a later date." required><div class="invalid-feedback" id="iv-date-error"></div></div>' +
      '<div class="col-md-2"><label class="form-label" for="iv-time">Time</label><input class="form-control" id="iv-time" maxlength="40" placeholder="9:00 AM – 12:00 NN"><div class="invalid-feedback" id="iv-time-error"></div></div>' +
      '<div class="col-md-3"><label class="form-label" for="iv-venue">Venue</label><input class="form-control" id="iv-venue" maxlength="255" placeholder="e.g. Nursing Building · Room 105" required><div class="invalid-feedback" id="iv-venue-error"></div></div>' +
      '<div class="col-md-1"><label class="form-label" for="iv-capacity">Seats</label><input class="form-control" type="number" inputmode="numeric" id="iv-capacity" min="1" max="500" step="1" value="20" data-msg-range="Use 1 to 500 seats." required><div class="invalid-feedback" id="iv-capacity-error"></div></div>' +
      '<div class="col-md-1"><button class="btn btn-brand w-100" type="submit" id="iv-create">Add</button></div>' +
      "</form></section>";
  }

  function waitingTable() {
    var list = ADM.interviews.waiting;
    var body = list.length ? list.map(function (w) {
      var options = ADM.interviews.schedules.filter(function (s) { return s.program === w.program && s.applicants.length < s.capacity && AO.daysUntil(s.date) >= 0; });
      var pick = options.length
        ? '<div class="d-flex gap-2"><select class="form-select form-select-sm" id="iv-pick-' + esc(w.id) + '" aria-label="Interview schedule for ' + esc(w.name) + '">' +
          options.map(function (s) { return '<option value="' + s.id + '">' + esc(when(s)) + " · " + (s.capacity - s.applicants.length) + " seats left</option>"; }).join("") +
          '</select><button class="btn btn-brand btn-sm" type="button" data-iv-assign="' + esc(w.id) + '">Assign</button></div>'
        : '<small class="text-muted">No open ' + esc(w.program) + " schedule. Add one above.</small>";
      return "<tr><td><strong>" + esc(w.name) + '</strong><small class="d-block text-muted">' + esc(w.id) + '</small></td><td><span class="code-chip">' + esc(w.program) + "</span></td><td>" + pick + "</td></tr>";
    }).join("") : '<tr><td colspan="3">' + ADM.ui.empty("check", "Nobody is waiting", "Applicants appear here when they pass the exam for a program that requires an interview.") + "</td></tr>";
    return '<section class="table-card" data-reveal><div class="table-toolbar"><div><strong>Waiting for a schedule</strong><small class="d-block text-muted">Passed the entrance exam; first choice requires an interview</small></div></div>' +
      '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th style="width:55%">Schedule</th></tr></thead><tbody>' + body + "</tbody></table></div></section>";
  }

  function applicantRow(s, i) {
    var row = $.extend({ program: s.program, date: s.date, venue: s.venue }, i);
    var grade = i.grade != null ? '<small class="text-muted">' + icon("lock") + "Grade " + esc(i.grade) + " (internal)</small>" : "";
    return '<li class="d-flex justify-content-between align-items-center gap-3 py-2 border-top flex-wrap"><span><strong>' + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + "</small></span>" +
      '<span class="d-flex gap-2 align-items-center flex-wrap">' + ADM.evaluation.outcomeTag(i) + grade + (isAdmin() && !i.outcome ? ADM.evaluation.controls(row) : "") + "</span></li>";
  }

  function schedulesList() {
    var list = ADM.interviews.schedules.slice().sort(function (x, y) { return x.date < y.date ? -1 : x.date > y.date ? 1 : 0; });
    if (!list.length) return '<section class="card-soft" data-reveal>' + ADM.ui.empty("calendar", "No interview schedules yet", "Add one above, then assign the applicants waiting for it.") + "</section>";
    return list.map(function (s) {
      return '<section class="card-soft" data-reveal><div class="d-flex justify-content-between flex-wrap gap-2"><div><h3 class="card-title mb-1">' + esc(s.program) + " · " + esc(programName(s.program)) + "</h3>" +
        '<small class="text-muted">' + icon("calendar") + esc(when(s)) + " · " + esc(s.venue) + "</small></div>" +
        '<span class="text-muted small"><b>' + s.applicants.length + "/" + s.capacity + "</b> seats</span></div>" +
        (s.applicants.length ? '<ul class="list-unstyled mb-0 mt-2">' + s.applicants.map(function (i) { return applicantRow(s, i); }).join("") + "</ul>" : '<p class="text-muted small mt-2 mb-0">No applicants assigned yet.</p>') +
        "</section>";
    }).join("");
  }

  function render($el) {
    var iv = ADM.interviews;
    var assigned = [].concat.apply([], iv.schedules.map(function (s) { return s.applicants; }));
    var count = function (o) { return assigned.filter(function (i) { return i.outcome === o; }).length; };
    var kpis = [
      { icon: "clock", label: "Waiting for a schedule", value: iv.waiting.length },
      { icon: "calendar", label: "Awaiting evaluation", value: count(null) },
      { icon: "check", label: "Passed", value: count("passed") },
      { icon: "users", label: "Failed or referred", value: count("failed") + count("referred") }
    ];
    $el.html(
      '<div class="page-title" data-reveal><h1>Interviews</h1><p class="lede">Applicants who passed the exam for a program that requires an interview. ' +
      (isAdmin() ? "The college dean, or you as Admin, records the evaluation; Passed issues the offer." : "The college dean records the evaluation; Passed issues the offer.") + "</p></div>" +
      '<div class="kpi-grid kpi-grid-4 mb-3" data-reveal>' + kpis.map(ADM.ui.kpi).join("") + "</div>" +
      (!iv.programs.length ? '<section class="card-soft" data-reveal>' + ADM.ui.empty("users", "No programs require an interview", "Turn it on per program in Programs & requirements.") + "</section>" : "") +
      scheduleForm() + (iv.programs.length ? waitingTable() : "") +
      (iv.programs.length ? '<h2 class="group-title" data-reveal>Schedules<span>' + iv.schedules.length + "</span></h2>" + '<div class="d-flex flex-column gap-3">' + schedulesList() + "</div>" : "")
    );
  }

  $(document).on("submit", "#interview-schedule-form", function (e) {
    e.preventDefault();
    if (!AO.validateFields($(this))) return;
    var body = { program: $("#iv-program").val(), date: $("#iv-date").val(), time: $("#iv-time").val().trim() || null, venue: $("#iv-venue").val().trim(), capacity: +$("#iv-capacity").val() };
    AO.busy($("#iv-create"), AO.api("POST", ADM.API + "/interview-schedules", body).then(reload))
      .done(function () { AO.showToast("Interview schedule added."); })
      .fail(function (xhr) { if (xhr.status !== 401) AO.showToast(AO.apiError(xhr)); });
  });

  $(document).on("click", "[data-iv-assign]", function () {
    var id = $(this).data("iv-assign");
    var scheduleId = +$("#iv-pick-" + id).val();
    AO.busy($(this), AO.api("POST", ADM.API + "/applicants/" + encodeURIComponent(id) + "/interview", { scheduleId: scheduleId }).then(reload))
      .done(function () { AO.showToast("Interview scheduled. The applicant gets an email."); })
      .fail(function (xhr) { if (xhr.status !== 401) AO.showToast(AO.apiError(xhr)); });
  });

  ADM.views.interviews = { title: "Interviews", render: render };
})(jQuery);
