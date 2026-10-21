/* ==========================================================================
   View: Interviews (#/interviews), FR-17
   Programs flagged "requires interview" (BS Nursing in the demo) interview
   applicants who passed the entrance exam, before the offer (D14).
   Admission personnel create schedules and assign applicants waiting for
   one. The College Dean of the program's college records the grade
   (internal, never shown to applicants) and Passed / Failed. Passed opens
   the offer; Failed is final.
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;

  function programName(code) {
    var p = ADM.interviews.programs.filter(function (x) { return x.code === code; })[0];
    return p ? p.name : code;
  }
  function when(s) { return AO.fmt(s.date, { weekday: "short", month: "short", day: "numeric", year: "numeric" }) + (s.time ? " · " + s.time : ""); }
  function outcomeTag(i) {
    if (i.outcome === "passed") return '<span class="tag tag-verified">' + icon("check") + "Passed</span>";
    if (i.outcome === "failed") return '<span class="tag tag-resubmit">Failed</span>';
    return '<span class="tag tag-review">Awaiting outcome</span>';
  }

  /* After a change the statuses move too (offer, interview failed): reload */
  function reload() {
    return (ADM.DEAN ? ADM.loadInterviews() : ADM.load()).done(function () { ADM.refresh(); });
  }

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
      return '<tr><td><strong>' + esc(w.name) + '</strong><small class="d-block text-muted">' + esc(w.id) + '</small></td><td><span class="code-chip">' + esc(w.program) + "</span></td><td>" + pick + "</td></tr>";
    }).join("") : '<tr><td colspan="3">' + ADM.ui.empty("check", "Nobody is waiting", "Applicants appear here when they pass the exam for a program that requires an interview.") + "</td></tr>";
    return '<section class="table-card" data-reveal><div class="table-toolbar"><div><strong>Waiting for a schedule</strong><small class="d-block text-muted">Passed the entrance exam; first choice requires an interview</small></div></div>' +
      '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th style="width:55%">Schedule</th></tr></thead><tbody>' + body + "</tbody></table></div></section>";
  }

  function applicantRow(i) {
    var grade = i.grade != null ? '<small class="text-muted">' + icon("lock") + "Grade " + i.grade + " (internal)</small>" : "";
    var action = "";
    if (ADM.DEAN && !i.outcome) {
      action = '<div class="d-flex gap-2 align-items-start flex-wrap">' +
        '<div><input class="form-control form-control-sm" style="width:90px" type="number" inputmode="numeric" min="0" max="100" step="1" id="iv-grade-' + esc(i.id) + '" placeholder="Grade" aria-label="Interview grade for ' + esc(i.name) + '" data-msg-range="Use a whole number from 0 to 100." required><div class="invalid-feedback" id="iv-grade-' + esc(i.id) + '-error"></div></div>' +
        '<button class="btn btn-brand btn-sm" type="button" data-iv-record="' + esc(i.id) + '" data-outcome="passed">Passed</button>' +
        '<button class="btn btn-ghost btn-sm" type="button" data-iv-record="' + esc(i.id) + '" data-outcome="failed">Failed</button></div>';
    }
    return '<li class="d-flex justify-content-between align-items-center gap-3 py-2 border-top flex-wrap"><span><strong>' + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + "</small></span>" +
      '<span class="d-flex gap-2 align-items-center flex-wrap">' + outcomeTag(i) + grade + action + "</span></li>";
  }

  function schedulesList() {
    var list = ADM.interviews.schedules.slice().sort(function (x, y) { return x.date < y.date ? -1 : x.date > y.date ? 1 : 0; });
    if (!list.length) return '<section class="card-soft" data-reveal>' + ADM.ui.empty("calendar", "No interview schedules yet", ADM.DEAN ? "The admission office creates them." : "Add one above, then assign the applicants waiting for it.") + "</section>";
    return list.map(function (s) {
      return '<section class="card-soft" data-reveal><div class="d-flex justify-content-between flex-wrap gap-2"><div><h3 class="card-title mb-1">' + esc(s.program) + " · " + esc(programName(s.program)) + "</h3>" +
        '<small class="text-muted">' + icon("calendar") + esc(when(s)) + " · " + icon("pin") + esc(s.venue) + "</small></div>" +
        '<span class="text-muted small"><b>' + s.applicants.length + "/" + s.capacity + "</b> seats</span></div>" +
        (s.applicants.length ? '<ul class="list-unstyled mb-0 mt-2">' + s.applicants.map(applicantRow).join("") + "</ul>" : '<p class="text-muted small mt-2 mb-0">No applicants assigned yet.</p>') +
        "</section>";
    }).join("");
  }

  function render($el) {
    var iv = ADM.interviews;
    var assigned = [].concat.apply([], iv.schedules.map(function (s) { return s.applicants; }));
    var count = function (o) { return assigned.filter(function (i) { return i.outcome === o; }).length; };
    var kpis = [
      ADM.DEAN ? null : { icon: "clock", label: "Waiting for a schedule", value: iv.waiting.length },
      { icon: "calendar", label: "Awaiting outcome", value: count(null) },
      { icon: "check", label: "Passed", value: count("passed") },
      { icon: "users", label: "Failed", value: count("failed") }
    ].filter(Boolean);
    $el.html(
      '<div class="page-title" data-reveal><h1>Interviews</h1><p class="lede">' +
      (ADM.DEAN ? "Record the grade and outcome for your college's applicants. The grade stays internal; applicants only see Passed or Failed. An outcome can't be changed once recorded."
        : "Applicants who passed the exam for a program that requires an interview. The college dean records the outcome; Passed issues the offer.") + "</p></div>" +
      '<div class="kpi-grid kpi-grid-' + kpis.length + ' mb-3" data-reveal>' + kpis.map(ADM.ui.kpi).join("") + "</div>" +
      (!iv.programs.length ? '<section class="card-soft" data-reveal>' + ADM.ui.empty("users", "No programs require an interview", ADM.DEAN ? "None of your college's programs require one." : "Programs are flagged in the database (requires_interview).") + "</section>" : "") +
      (ADM.DEAN ? "" : scheduleForm() + waitingTable()) +
      (iv.programs.length ? '<h2 class="group-title" data-reveal>Schedules<span>' + iv.schedules.length + "</span></h2>" + '<div class="d-flex flex-column gap-3">' + schedulesList() + "</div>" : "")
    );
  }

  $(document).on("submit", "#interview-schedule-form", function (e) {
    e.preventDefault();
    if (!AO.validateFields($(this))) return;
    var body = { program: $("#iv-program").val(), date: $("#iv-date").val(), time: $("#iv-time").val().trim() || null, venue: $("#iv-venue").val().trim(), capacity: +$("#iv-capacity").val() };
    AO.busy($("#iv-create"), AO.api("POST", ADM.API + "/interview-schedules", body).then(reload))
      .done(function () { AO.showToast("Interview schedule added."); })
      .fail(function (xhr) { AO.showToast(AO.apiError(xhr)); });
  });

  $(document).on("click", "[data-iv-assign]", function () {
    var id = $(this).data("iv-assign");
    var scheduleId = +$("#iv-pick-" + id).val();
    AO.busy($(this), AO.api("POST", ADM.API + "/applicants/" + encodeURIComponent(id) + "/interview", { scheduleId: scheduleId }).then(reload))
      .done(function () { AO.showToast("Interview scheduled. The applicant gets an email."); })
      .fail(function (xhr) { AO.showToast(AO.apiError(xhr)); });
  });

  $(document).on("click", "[data-iv-record]", function () {
    var id = $(this).data("iv-record"), outcome = $(this).data("outcome");
    var $grade = $("#iv-grade-" + id);
    if (!AO.validateFields($grade.parent())) return;
    var row = ADM.interviews.schedules.map(function (s) { return s.applicants; }).reduce(function (a, b) { return a.concat(b); }, []).filter(function (i) { return i.id === id; })[0];
    ADM.ui.confirm({
      title: "Record " + (outcome === "passed" ? "Passed" : "Failed") + "?",
      text: (row ? row.name : id) + ", grade " + $grade.val() + ". " + (outcome === "passed" ? "The applicant gets the offer letter." : "This ends the application.") + " It can't be changed afterwards.",
      ok: "Record outcome", icon: outcome === "passed" ? "check" : "users"
    }, function () {
      AO.api("POST", "/api/dean/applicants/" + encodeURIComponent(id) + "/interview", { grade: +$grade.val(), outcome: outcome })
        .then(reload)
        .done(function () { AO.showToast("Interview outcome recorded."); })
        .fail(function (xhr) { AO.showToast(AO.apiError(xhr)); });
    });
  });

  ADM.views.interviews = { title: "Interviews", render: render };
})(jQuery);
