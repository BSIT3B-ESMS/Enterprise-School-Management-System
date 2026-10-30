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
  function assigned() { return [].concat.apply([], ADM.interviews.schedules.map(function (s) { return s.applicants.map(function (i) { return $.extend({ program: s.program, date: s.date, time: s.time, venue: s.venue }, i); }); })); }
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
      '<section class="table-card iv-card"' + ADM.tintStyle() + ' data-reveal><div class="table-toolbar">' +
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
    var pending = assigned().filter(function (i) { return !i.outcome; });
    var waiting = iv.waiting.length;
    // One card per schedule (date and time), earliest first, so the list reads like the day's plan
    var groups = {};
    pending.forEach(function (i) { (groups[i.scheduleId] = groups[i.scheduleId] || []).push(i); });
    var ids = Object.keys(groups).sort(function (x, y) {
      var a = groups[x][0], b = groups[y][0];
      return (a.date + (a.time || "")) < (b.date + (b.time || "")) ? -1 : 1;
    });
    $el.html(
      '<div class="page-title d-flex justify-content-between align-items-start flex-wrap gap-3" data-reveal><div><h1>Applicant evaluation</h1><p class="lede">Applicants who passed the entrance exam and chose a program of your college that requires an interview. ' +
      "Accept or reject each one after the interview. The offer goes to the highest accepting choice.</p></div>" +
      "</div>" +
      (!iv.programs.length ? '<section class="card-soft iv-card"' + ADM.tintStyle() + ' data-reveal>' + ui.empty("users", "None of your programs require an evaluation",
        "The Admin or admission personnel turn it on per program (Programs & requirements → Interview).") + "</section>" : "") +
      (waiting ? '<div class="info-note mb-3" data-reveal>' + icon("clock") + "<p>" + waiting + " applicant" + (waiting === 1 ? " is" : "s are") + " waiting for admission personnel to schedule the evaluation.</p></div>" : "") +
      (ids.length ? '<div class="d-flex flex-column gap-3">' + ids.map(function (id) {
        var rows = groups[id].sort(function (x, y) { return x.name < y.name ? -1 : 1; }), s0 = rows[0];
        return '<section class="card-soft iv-card"' + ADM.tintStyle() + ' data-reveal><div class="d-flex justify-content-between align-items-center flex-wrap gap-2">' +
          '<div><h3 class="card-title mb-1">' + icon("calendar") + " " + esc(when(s0.date)) + (s0.time ? " · " + esc(s0.time) : "") + "</h3>" +
          '<small class="text-muted">' + icon("pin") + esc(s0.venue) + ' · <span class="code-chip">' + esc(s0.program) + "</span> · " + rows.length + " applicant" + (rows.length === 1 ? "" : "s") + "</small></div>" +
          '<button class="btn btn-ghost btn-sm" type="button" data-print-slot="' + esc(id) + '">' + icon("printer") + "Print all (" + rows.length + ")</button></div>" +
          '<ul class="list-unstyled mb-0 mt-2">' + rows.map(function (i) {
            return '<li class="d-flex justify-content-between align-items-center gap-3 py-2 border-top flex-wrap"><span><strong>' + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + "</small></span>" +
              '<span class="d-flex gap-2 align-items-center flex-wrap">' + controls(i) +
              '<button class="btn btn-ghost btn-sm" type="button" data-q-print="' + esc(i.id) + '" data-program="' + esc(i.program) + '" title="Print form" aria-label="Print form for ' + esc(i.name) + '">' + icon("printer") + "</button></span></li>";
          }).join("") + "</ul></section>";
      }).join("") + "</div>"
        : iv.programs.length ? '<section class="card-soft iv-card"' + ADM.tintStyle() + ' data-reveal>' + ui.empty("check", "Nothing to evaluate", "Scheduled applicants appear here.") + "</section>" : "")
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
      '<section class="table-card iv-card"' + ADM.tintStyle() + ' data-reveal>' + (done.length ? '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th>Outcome</th><th>Grade <span class="text-muted fw-normal">(internal)</span></th><th>Recorded</th></tr></thead><tbody>' +
        done.map(function (i) {
          return "<tr><td><strong>" + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + '</small></td><td><span class="code-chip">' + esc(i.program) + "</span></td>" +
            "<td>" + outcomeTag(i) + (i.remark ? '<small class="d-block text-muted">' + esc(i.remark) + "</small>" : "") + '</td><td><span class="internal">' + esc(i.grade != null ? i.grade : "—") + "</span></td>" +
            "<td>" + (i.recordedAt ? esc(when(i.recordedAt.slice(0, 10))) : "") + "</td></tr>";
        }).join("") + "</tbody></table></div>" : ADM.ui.empty("award", "No evaluations recorded yet", "")) + "</section>"
    );
  }

  /* ---------- #/questions: Interview questions (the dean edits their college's list) ---------- */
  function questionRow(q, i) {
    return '<div class="d-flex gap-2 align-items-start mb-2" data-q-row><span class="pt-2 fw-bold">' + (i + 1) + '.</span>' +
      '<textarea class="form-control" rows="2" maxlength="300" data-q-input aria-label="Question ' + (i + 1) + '">' + esc(q) + "</textarea>" +
      '<button class="btn btn-ghost btn-sm" type="button" data-q-remove aria-label="Remove question ' + (i + 1) + '">' + icon("trash") + "</button></div>";
  }
  function renumberQuestions() {
    $("[data-q-row]").each(function (i) { $(this).children("span").text((i + 1) + "."); });
  }
  function renderQuestions($el) {
    var qs = (ADM.dean.college && ADM.dean.college.questions) || [];
    $el.html(
      '<div class="page-title" data-reveal><h1>Interview questions</h1><p class="lede">Edit the questions you ask every applicant. Then accept or reject in Applicant evaluation.</p></div>' +
      '<section class="card-soft iv-card"' + ADM.tintStyle() + ' data-reveal><div id="q-list">' + qs.map(questionRow).join("") + "</div>" +
      '<p class="text-danger small" id="q-error" role="alert"></p>' +
      '<div class="d-flex gap-2 flex-wrap"><button class="btn btn-ghost" type="button" id="q-add">' + icon("plus") + 'Add question</button>' +
      '<button class="btn btn-brand" type="button" id="q-save">Save questions</button>' +
      '<button class="btn btn-ghost" type="button" data-q-print="">' + icon("download") + "Print blank form</button></div></section>"
    );
  }
  $(document).on("click", "#q-add", function () {
    if ($("[data-q-row]").length >= 10) { $("#q-error").text("Ten questions at most."); return; }
    $("#q-list").append(questionRow("", $("[data-q-row]").length));
    $("#q-list [data-q-input]").last().trigger("focus");
  });
  $(document).on("click", "[data-q-remove]", function () { $(this).closest("[data-q-row]").remove(); renumberQuestions(); });
  $(document).on("click", "#q-save", function () {
    var qs = $("[data-q-input]").map(function () { return $.trim(this.value); }).get().filter(Boolean);
    if (!qs.length) { $("#q-error").text("Keep at least one question."); return; }
    $("#q-error").text("");
    AO.busy($(this), AO.api("PUT", "/api/dean/interview-questions", { questions: qs }))
      .done(function (res) { ADM.dean.college.questions = res.data.questions; ADM.refresh(); AO.showToast("Interview questions saved."); })
      .fail(function (xhr) { if (xhr.status !== 401) $("#q-error").text(AO.apiError(xhr)); });
  });

  /* ---------- Printable interview form: "<COLLEGE> APPLICATION", applicant info, questions with a Likert scale, summary lines ---------- */
  var LIKERT = [[5, "Excellent"], [4, "Very good"], [3, "Good"], [2, "Fair"], [1, "Poor"]];
  /* items: [{ id, program }, ...] one page each; [null] prints one blank form */
  function printForms(items) {
    var college = ADM.dean.college || { code: "", name: "" };
    var qs = $("[data-q-input]").length ? $("[data-q-input]").map(function () { return $.trim(this.value); }).get().filter(Boolean) : (college.questions || []);
    var logo = location.origin + "/assets/shared/img/plp-logo.png";
    var blank = "<span class='fill'></span>";
    function field(label, v) { return "<tr><th>" + label + "</th><td>" + (v ? esc(v) : blank) + "</td></tr>"; }
    var scale = "<div class='scale'>" + LIKERT.map(function (l) { return "<span><b>" + l[0] + "</b> " + l[1] + "</span>"; }).join("") + "</div>";
    var questions = qs.map(function (q, i) {
      return "<div class='q'><p><b>" + (i + 1) + ".</b> " + esc(q) + "</p><div class='likert'>" +
        LIKERT.slice().reverse().map(function (l) { return "<span>" + l[0] + "</span>"; }).join("") + "</div></div>";
    }).join("");

    function page(it) {
      var row = it ? assigned().filter(function (i) { return i.id === it.id && i.program === it.program; })[0] : null;
      var a = it ? ADM.dean.applicants.filter(function (x) { return x.id === it.id; })[0] : null;
      var choices = a ? a.choices.map(function (c) { return c.rank + ". " + c.code; }).join("   ") : "";
      var info = "<table class='info'>" +
        field("Applicant name", a && a.name) + field("Application no.", row ? row.id : "") + field("Program interviewed for", row ? row.program : "") +
        field("Program choices", choices) + field("Strand", a && a.strand) + field("Last school", a && a.school) + "</table>";
      return "<div class='page'><div class='mark'></div>" +
        "<header><img src='" + logo + "' alt=''><div><small>Pamantasan ng Lungsod ng Pasig · Office of the Dean</small><h1>" + esc(college.code) + " APPLICATION</h1><small>" + esc(college.name) + " · Interview and evaluation form · AY 2027–2028</small></div></header><div class='stripe'></div>" +
        "<div class='body'><h2>Applicant information</h2>" + info +
        "<h2>Interview questions</h2>" + scale + questions +
        "<p class='total'>Total score: ________ / " + (qs.length * 5) + "</p>" +
        "<h2>Summary of the applicant evaluation</h2><div class='lines'><div></div><div></div><div></div><div></div><div></div></div>" +
        "<p class='rec'>Recommendation:<span class='box'></span>Accept<span class='box'></span>Reject</p>" +
        "<div class='row'><div class='sig'>Interviewer (printed name and signature)</div><div class='sig'>Date</div></div>" +
        "<footer>Online Admission · AY 2027–2028</footer></div></div>";
    }

    var css = "@page{size:A4;margin:12mm}*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}" +
      ":root{--g:#1b6b45;--g2:#2f8f63;--tint:#eef6f1;--ink:#16221b;--line:#c9d8ce}" +
      "body{font:13px/1.5 'Segoe UI',Arial,Helvetica,sans-serif;color:var(--ink);margin:0}" +
      ".page{break-after:page;position:relative;border:2px solid var(--g);border-radius:14px;padding:0 0 16px;overflow:hidden}" +
      ".page:before{content:'';position:absolute;inset:8px;border:1px solid var(--line);border-radius:8px;pointer-events:none}" +
      ".mark{position:absolute;left:50%;top:52%;width:380px;height:380px;margin:-190px 0 0 -190px;opacity:.06;background:url(" + logo + ") center/contain no-repeat}" +
      "header{position:relative;display:flex;align-items:center;gap:16px;padding:18px 26px;color:#fff;background:linear-gradient(110deg,var(--g),var(--g2))}" +
      "header img{width:62px;height:62px;border-radius:50%;background:#fff;padding:3px}" +
      "header small{display:block;letter-spacing:.14em;font-size:10.5px;opacity:.92;text-transform:uppercase}" +
      "header h1{margin:2px 0 3px;font-size:26px;letter-spacing:.1em;font-weight:800}" +
      ".stripe{height:5px;background:repeating-linear-gradient(90deg,#e8b93a 0 28px,var(--g) 28px 56px)}" +
      ".body{position:relative;padding:4px 28px 0}" +
      "h2{display:flex;align-items:center;gap:8px;margin:16px 0 8px;font-size:12.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--g)}" +
      "h2:before{content:'';width:5px;height:16px;border-radius:3px;background:#e8b93a}h2:after{content:'';flex:1;height:1px;background:var(--line)}" +
      "table.info{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--line);border-radius:8px;overflow:hidden}" +
      "table.info th,table.info td{padding:6px 10px;text-align:left;border-bottom:1px solid var(--line)}table.info tr:last-child th,table.info tr:last-child td{border-bottom:0}" +
      "table.info th{width:30%;background:var(--tint);font-weight:600;color:var(--g)}.fill{display:inline-block;width:100%;height:14px}" +
      ".scale{display:flex;gap:8px;flex-wrap:wrap;font-size:11.5px;margin-bottom:8px}.scale span{background:var(--tint);border:1px solid var(--line);border-radius:999px;padding:2px 10px}.scale b{color:var(--g)}" +
      ".q{margin:0 0 9px;padding:8px 12px;border:1px solid var(--line);border-left:5px solid var(--g);border-radius:8px;background:#fff;break-inside:avoid}.q p{margin:0 0 6px;font-weight:600}.q p b{color:var(--g)}" +
      ".likert{display:flex;gap:10px}.likert span{display:flex;align-items:center;justify-content:center;width:42px;height:26px;border:1.5px solid var(--g);border-radius:999px;font-weight:700;color:var(--g)}" +
      ".total{margin:2px 0 0;text-align:right;font-weight:600}.lines div{border-bottom:1px solid var(--ink);opacity:.55;height:27px}" +
      ".rec{margin:16px 0 0;display:flex;align-items:center;gap:8px;font-weight:600}.box{display:inline-block;width:15px;height:15px;border:1.5px solid var(--g);border-radius:4px;margin:0 2px 0 14px}" +
      ".row{display:flex;gap:40px;margin-top:34px}.sig{flex:1;border-top:1px solid var(--ink);padding-top:3px;text-align:center;font-size:11.5px}.sig+.sig{flex:.5}" +
      ".page:last-child{break-after:auto}footer{text-align:center;font-size:10px;letter-spacing:.1em;color:var(--g);margin-top:14px;text-transform:uppercase}";
    var html = "<!doctype html><html><head><meta charset='utf-8'><title>" + esc(college.code) + " Application" + (items.length === 1 && items[0] ? "" : " forms") + "</title><style>" + css + "</style></head><body>" +
      items.map(page).join("") +
      "<script>window.onload=function(){setTimeout(function(){window.print()},300)}<\/script></body></html>";
    var w = window.open("", "_blank");
    if (!w) { AO.showToast("Allow pop-ups to print the form."); return; }
    w.document.open(); w.document.write(html); w.document.close();
  }
  function itemsOf(rows) { return rows.map(function (i) { return { id: i.id, program: i.program }; }); }
  $(document).on("click", "[data-q-print]", function () {
    var id = String($(this).data("q-print") || "");
    printForms([id ? { id: id, program: $(this).data("program") } : null]);
  });
  // Every applicant of one schedule (data-print-slot = schedule id), or of all schedules (data-print-all)
  $(document).on("click", "[data-print-slot], [data-print-all]", function () {
    var slot = $(this).data("print-slot");
    var rows = assigned().filter(function (i) { return !i.outcome && (slot === undefined || i.scheduleId === +slot); })
      .sort(function (x, y) { return (x.date + (x.time || "") + x.scheduleId + x.name) < (y.date + (y.time || "") + y.scheduleId + y.name) ? -1 : 1; });
    if (rows.length) printForms(itemsOf(rows));
  });

  ADM.evaluation = { controls: controls, outcomeTag: outcomeTag };
  ADM.views.college = { title: "Applicant list", render: renderList };
  ADM.views.evaluation = { title: "Applicant evaluation", render: renderEvaluation };
  ADM.views.questions = { title: "Interview questions", render: renderQuestions };
  ADM.views.evaluated = { title: "Evaluation results", render: renderResults };
})(jQuery);
