/* ==========================================================================
   College Dean module (use-case diagram "College Dean Module"), own college only:
     #/college     Applicant list: applicants who chose one of the college's programs
     #/evaluation  Applicant evaluation: grade (internal) and outcome
     #/evaluated   Evaluation results / referral (the Admin sees every college)
   The dean accepts (passed) or rejects (failed) the applicant for the dean's
   own program. The offer goes to the highest accepting choice once every
   higher choice is decided. An outcome can't be changed once recorded.
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
    if (i.outcome === "passed") return '<span class="tag tag-verified">' + icon("check") + "Accepted</span>";
    if (i.outcome === "failed") return '<span class="tag tag-resubmit">Rejected</span>';
    return '<span class="tag tag-review">Awaiting evaluation</span>';
  }

  /* Accept / Reject for one assigned applicant and program (also used on the Admin's Interviews screen) */
  function controls(i) {
    var id = esc(i.id), prog = esc(i.program);
    return '<div class="d-flex gap-2 flex-wrap">' +
      '<button class="btn btn-brand btn-sm" type="button" data-ev-record="' + id + '" data-program="' + prog + '" data-outcome="passed">Accept</button>' +
      '<button class="btn btn-ghost btn-sm" type="button" data-ev-record="' + id + '" data-program="' + prog + '" data-outcome="failed">Reject</button></div>';
  }

  $(document).on("click", "[data-ev-record]", function () {
    var id = String($(this).data("ev-record")), program = $(this).data("program"), outcome = $(this).data("outcome");
    var row = assigned().filter(function (i) { return i.id === id && i.program === program; })[0] || { name: id };
    var accept = outcome === "passed";
    ADM.ui.confirm({
      title: (accept ? "Accept into " : "Reject from ") + program + "?",
      text: row.name + ". " + (accept ? "The applicant gets the offer for " + program + " once every higher choice is decided." : "The applicant moves on to their next choice, or the application ends if there is none.") + " It can't be changed afterwards.",
      ok: accept ? "Accept applicant" : "Reject applicant", icon: accept ? "check" : "users"
    }, function () {
      var url = isDean() ? "/api/dean/applicants/" + encodeURIComponent(id) + "/interview" : ADM.API + "/applicants/" + encodeURIComponent(id) + "/evaluation";
      AO.api("POST", url, { program: program, outcome: outcome }).then(reload)
        .done(function () { AO.showToast("Decision recorded."); })
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
      { icon: "clock", label: "Awaiting your decision", value: count(function (a) { return a.status === "for_interview"; }) },
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
            "<td>" + (a.interviews.length ? a.interviews.map(function (i) { return '<span class="d-block">' + esc(i.program) + " " + outcomeTag(i) + "</span>"; }).join("") : '<span class="text-muted small">—</span>') + "</td></tr>";
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
      '<div class="page-title" data-reveal><h1>Applicant evaluation</h1><p class="lede">Applicants who passed the entrance exam and chose a program of your college that requires an interview. ' +
      "Accept or reject each one after the interview. The offer goes to the highest accepting choice.</p></div>" +
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
      '<div class="page-title" data-reveal><h1>Evaluation results</h1><p class="lede">' + (isDean() ? "Your college's accept and reject decisions." : "Accept and reject decisions of every college.") + "</p></div>" +
      '<div class="kpi-grid kpi-grid-3 mb-3" data-reveal>' + [
        { icon: "check", label: "Accepted", value: count("passed") },
        { icon: "users", label: "Rejected", value: count("failed") },
        { icon: "award", label: "Total decided", value: done.length }
      ].map(ADM.ui.kpi).join("") + "</div>" +
      '<section class="table-card" data-reveal>' + (done.length ? '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th>Outcome</th><th>Grade <span class="text-muted fw-normal">(internal)</span></th><th>Recorded</th></tr></thead><tbody>' +
        done.map(function (i) {
          return "<tr><td><strong>" + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + '</small></td><td><span class="code-chip">' + esc(i.program) + "</span></td>" +
            "<td>" + outcomeTag(i) + (i.remark ? '<small class="d-block text-muted">' + esc(i.remark) + "</small>" : "") + '</td><td><span class="internal">' + esc(i.grade != null ? i.grade : "—") + "</span></td>" +
            "<td>" + (i.recordedAt ? esc(when(i.recordedAt.slice(0, 10))) : "") + "</td></tr>";
        }).join("") + "</tbody></table></div>" : ADM.ui.empty("award", "No evaluations recorded yet", "")) + "</section>"
    );
  }

  ADM.evaluation = { controls: controls, outcomeTag: outcomeTag };
  ADM.views.college = { title: "Applicant list", render: renderList };
  ADM.views.evaluation = { title: "Applicant evaluation", render: renderEvaluation };
  ADM.views.evaluated = { title: "Evaluation results", render: renderResults };
})(jQuery);
