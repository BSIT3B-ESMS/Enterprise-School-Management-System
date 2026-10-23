/* ==========================================================================
   College Dean module (use-case diagram "College Dean Module"), own college only:
     #/college     Applicant list: applicants who chose one of the college's programs
     #/evaluation  Applicant evaluation: grade (internal) and outcome
     #/evaluated   Evaluation results / referral (the Admin sees every college)
   Outcomes: Passed → the offer for that program; Failed → final; Referred →
   the offer for another of the applicant's choices (one without its own
   interview). An outcome can't be changed once recorded.
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;
  var f = { program: "", status: "", q: "" };

  function isDean() { return ADM.STAFF.roleKey === "college_dean"; }
  function assigned() { return [].concat.apply([], ADM.interviews.schedules.map(function (s) { return s.applicants.map(function (i) { return $.extend({ program: s.program, date: s.date, venue: s.venue }, i); }); })); }
  function when(d) { return AO.fmt(d, { month: "short", day: "numeric", year: "numeric" }); }
  function reload() { return ADM.load().done(function () { ADM.refresh(); }); }

  function outcomeTag(i) {
    if (i.outcome === "passed") return '<span class="tag tag-verified">' + icon("check") + "Passed</span>";
    if (i.outcome === "failed") return '<span class="tag tag-resubmit">Failed</span>';
    if (i.outcome === "referred") return '<span class="tag tag-review">Referred to ' + esc(i.referredTo || "") + "</span>";
    return '<span class="tag tag-review">Awaiting evaluation</span>';
  }

  /* Grade + Passed / Failed / Refer, for one assigned applicant (also used on the Admin's Interviews screen) */
  function controls(i) {
    var others = (i.choices || []).filter(function (c) { return c !== i.program; });
    var id = esc(i.id);
    return '<div class="d-flex gap-2 align-items-start flex-wrap">' +
      '<div><input class="form-control form-control-sm" style="width:90px" type="number" inputmode="numeric" min="0" max="100" step="1" id="ev-grade-' + id + '" placeholder="Grade" aria-label="Evaluation grade for ' + esc(i.name) + '" data-msg-range="Use a whole number from 0 to 100." required><div class="invalid-feedback" id="ev-grade-' + id + '-error"></div></div>' +
      '<button class="btn btn-brand btn-sm" type="button" data-ev-record="' + id + '" data-outcome="passed">Passed</button>' +
      '<button class="btn btn-ghost btn-sm" type="button" data-ev-record="' + id + '" data-outcome="failed">Failed</button>' +
      (others.length ? '<span class="d-flex gap-1"><select class="form-select form-select-sm" id="ev-refer-' + id + '" aria-label="Refer ' + esc(i.name) + ' to">' +
        others.map(function (c) { return '<option value="' + esc(c) + '">' + esc(c) + "</option>"; }).join("") +
        '</select><button class="btn btn-ghost btn-sm" type="button" data-ev-record="' + id + '" data-outcome="referred">Refer</button></span>' : "") +
      "</div>";
  }

  $(document).on("click", "[data-ev-record]", function () {
    var id = $(this).data("ev-record"), outcome = $(this).data("outcome");
    var $grade = $("#ev-grade-" + id);
    if (!AO.validateFields($grade.parent())) return;
    var row = assigned().filter(function (i) { return i.id === id; })[0] || { name: id };
    var body = { grade: +$grade.val(), outcome: outcome };
    if (outcome === "referred") body.program = $("#ev-refer-" + id).val();
    var label = outcome === "passed" ? "Passed" : outcome === "failed" ? "Failed" : "Referred to " + body.program;
    ADM.ui.confirm({
      title: "Record " + label + "?",
      text: row.name + ", grade " + body.grade + ". " + (outcome === "failed" ? "This ends the application." : "The applicant gets the offer letter" + (outcome === "referred" ? " for " + body.program : "") + ".") + " It can't be changed afterwards.",
      ok: "Record evaluation", icon: outcome === "failed" ? "users" : "check"
    }, function () {
      var url = isDean() ? "/api/dean/applicants/" + encodeURIComponent(id) + "/interview" : ADM.API + "/applicants/" + encodeURIComponent(id) + "/evaluation";
      AO.api("POST", url, body).then(reload)
        .done(function () { AO.showToast("Evaluation recorded."); })
        .fail(function (xhr) { if (xhr.status !== 401) AO.showToast(AO.apiError(xhr)); });
    });
  });

  /* ---------- #/college: Applicant list ---------- */
  function renderList($el) {
    var d = ADM.dean, ui = ADM.ui;
    var q = f.q.toLowerCase();
    var list = d.applicants.filter(function (a) {
      if (f.program && !a.choices.some(function (c) { return c.code === f.program; })) return false;
      if (f.status && a.status !== f.status) return false;
      return !q || a.name.toLowerCase().indexOf(q) !== -1 || a.id.toLowerCase().indexOf(q) !== -1;
    });
    var statuses = d.applicants.reduce(function (m, a) { m[a.status] = a.statusLabel; return m; }, {});
    var count = function (fn) { return d.applicants.filter(fn).length; };
    var kpis = [
      { icon: "users", label: "Applicants", value: d.applicants.length },
      { icon: "flag", label: "1st choice in your college", value: count(function (a) { return a.choices[0] && a.choices[0].inCollege; }) },
      { icon: "clock", label: "Awaiting evaluation", value: count(function (a) { return a.status === "for_interview" && !(a.interview && a.interview.outcome); }) },
      { icon: "award", label: "Offered or admitted here", value: count(function (a) { return a.offeredProgram && d.programs.some(function (p) { return p.code === a.offeredProgram; }); }) }
    ];
    $el.html(
      '<div class="page-title" data-reveal><h1>Applicant list</h1><p class="lede">' + esc(d.college ? d.college.name : "No college assigned to your account") +
      " · applicants who chose " + esc(d.programs.map(function (p) { return p.code; }).join(", ") || "one of your programs") + ". Read-only.</p></div>" +
      '<div class="kpi-grid kpi-grid-4 mb-3" data-reveal>' + kpis.map(ui.kpi).join("") + "</div>" +
      '<section class="table-card" data-reveal><div class="table-toolbar">' +
      '<input class="form-control" type="search" id="dean-q" placeholder="Name or application number" value="' + esc(f.q) + '" aria-label="Search applicants">' +
      '<select class="form-select" id="dean-program" aria-label="Filter by program"><option value="">All your programs</option>' +
      d.programs.map(function (p) { return '<option value="' + esc(p.code) + '"' + (f.program === p.code ? " selected" : "") + ">" + esc(p.code) + "</option>"; }).join("") + "</select>" +
      '<select class="form-select" id="dean-status" aria-label="Filter by status"><option value="">All statuses</option>' +
      Object.keys(statuses).map(function (k) { return '<option value="' + esc(k) + '"' + (f.status === k ? " selected" : "") + ">" + esc(statuses[k]) + "</option>"; }).join("") + "</select></div>" +
      (list.length ? '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program choices</th><th>Strand · school</th><th>Status</th><th>Evaluation</th></tr></thead><tbody>' +
        list.map(function (a) {
          return "<tr><td><strong>" + esc(a.name) + '</strong><small class="d-block text-muted">' + esc(a.id) + (a.submittedAt ? " · submitted " + esc(when(a.submittedAt)) : "") + "</small></td>" +
            "<td>" + a.choices.map(function (c) { return '<span class="code-chip' + (c.inCollege ? "" : " opacity-50") + '" title="Choice ' + c.rank + '">' + c.rank + ". " + esc(c.code) + "</span>"; }).join(" ") + "</td>" +
            "<td>" + esc(a.strand || "—") + '<small class="d-block text-muted">' + esc(a.school || "") + "</small></td>" +
            "<td>" + esc(a.statusLabel) + (a.offeredProgram ? '<small class="d-block text-muted">Offered ' + esc(a.offeredProgram) + "</small>" : "") + "</td>" +
            "<td>" + (a.interview ? outcomeTag(a.interview) : '<span class="text-muted small">—</span>') + "</td></tr>";
        }).join("") + "</tbody></table></div>"
        : ui.empty("users", "No applicants", "Applicants appear here once they submit an application that includes one of your college's programs.")) +
      "</section>"
    );
  }
  $(document).on("input", "#dean-q", function () { f.q = this.value.trim(); var v = this.value; ADM.refresh(); $("#dean-q").trigger("focus")[0].setSelectionRange(v.length, v.length); });
  $(document).on("change", "#dean-program", function () { f.program = this.value; ADM.refresh(); });
  $(document).on("change", "#dean-status", function () { f.status = this.value; ADM.refresh(); });

  /* ---------- #/evaluation: Applicant evaluation ---------- */
  function renderEvaluation($el) {
    var iv = ADM.interviews, ui = ADM.ui;
    var pending = assigned().filter(function (i) { return !i.outcome; }).sort(function (x, y) { return x.date < y.date ? -1 : 1; });
    var waiting = iv.waiting.length;
    $el.html(
      '<div class="page-title" data-reveal><h1>Applicant evaluation</h1><p class="lede">Applicants who passed the entrance exam for a program of your college that requires an evaluation. ' +
      "Record the grade (internal: applicants never see it) and the outcome. Passed issues the offer; Refer offers another of the applicant's choices.</p></div>" +
      (!iv.programs.length ? '<section class="card-soft" data-reveal>' + ui.empty("users", "None of your programs require an evaluation",
        "The Admin or admission personnel turn it on per program (Programs & requirements → Interview).") + "</section>" : "") +
      (waiting ? '<div class="info-note mb-3" data-reveal>' + icon("clock") + "<p>" + waiting + " applicant" + (waiting === 1 ? " is" : "s are") + " waiting for admission personnel to schedule the evaluation.</p></div>" : "") +
      (pending.length ? '<section class="table-card" data-reveal><div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th>Schedule</th><th>Evaluation</th></tr></thead><tbody>' +
        pending.map(function (i) {
          return "<tr><td><strong>" + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + '</small></td><td><span class="code-chip">' + esc(i.program) + "</span></td>" +
            "<td>" + esc(when(i.date)) + '<small class="d-block text-muted">' + esc(i.venue) + "</small></td><td>" + controls(i) + "</td></tr>";
        }).join("") + "</tbody></table></div></section>"
        : iv.programs.length ? '<section class="card-soft" data-reveal>' + ui.empty("check", "Nothing to evaluate", "Scheduled applicants appear here.") + "</section>" : "")
    );
  }

  /* ---------- #/evaluated: Evaluation results / referral ---------- */
  function renderResults($el) {
    var done = assigned().filter(function (i) { return i.outcome; }).sort(function (x, y) { return (y.recordedAt || "").localeCompare(x.recordedAt || ""); });
    var count = function (o) { return done.filter(function (i) { return i.outcome === o; }).length; };
    $el.html(
      '<div class="page-title" data-reveal><h1>Evaluation results</h1><p class="lede">' + (isDean() ? "Your college's recorded evaluations, with referrals." : "Recorded evaluations of every college, with referrals.") + "</p></div>" +
      '<div class="kpi-grid kpi-grid-3 mb-3" data-reveal>' + [
        { icon: "check", label: "Passed", value: count("passed") },
        { icon: "send", label: "Referred", value: count("referred") },
        { icon: "users", label: "Failed", value: count("failed") }
      ].map(ADM.ui.kpi).join("") + "</div>" +
      '<section class="table-card" data-reveal>' + (done.length ? '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th>Outcome</th><th>Grade <span class="text-muted fw-normal">(internal)</span></th><th>Recorded</th></tr></thead><tbody>' +
        done.map(function (i) {
          return "<tr><td><strong>" + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + '</small></td><td><span class="code-chip">' + esc(i.program) + "</span></td>" +
            "<td>" + outcomeTag(i) + (i.remark ? '<small class="d-block text-muted">' + esc(i.remark) + "</small>" : "") + '</td><td><span class="internal">' + esc(i.grade) + "</span></td>" +
            "<td>" + (i.recordedAt ? esc(when(i.recordedAt.slice(0, 10))) : "") + "</td></tr>";
        }).join("") + "</tbody></table></div>" : ADM.ui.empty("award", "No evaluations recorded yet", "")) + "</section>"
    );
  }

  ADM.evaluation = { controls: controls, outcomeTag: outcomeTag };
  ADM.views.college = { title: "Applicant list", render: renderList };
  ADM.views.evaluation = { title: "Applicant evaluation", render: renderEvaluation };
  ADM.views.evaluated = { title: "Evaluation results", render: renderResults };
})(jQuery);
