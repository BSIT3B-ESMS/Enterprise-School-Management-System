/* ==========================================================================
   View: Document review (#/documents) — one card per applicant with every
   document they sent, so a person's whole set is reviewed together.
   Oldest submissions first.
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;
  var f = { type: "", q: "", sort: "oldest", page: 1 };
  var PER_PAGE = 5;

  // Applicants reviewed during this visit stay on screen with their outcome
  // (ready to approve, or sent back) until you leave the page.
  var touched = {};

  function waiting(a) { return a.submittedAt && !a.exam && ADM.docSummary(a).pending > 0; }

  function queue() {
    return ADM.state.applicants.filter(function (a) {
      if (!waiting(a) && !(touched[a.id] && a.submittedAt && !a.exam)) return false;
      if (f.type && !(a.docs[f.type] && a.docs[f.type].status === "pending") && !touched[a.id]) return false;
      if (f.q && (ADM.fullName(a) + " " + a.id + " " + a.lrn).toLowerCase().indexOf(f.q.toLowerCase()) === -1) return false;
      return true;
    }).sort(function (x, y) {
      return f.sort === "newest" ? (x.submittedAt < y.submittedAt ? 1 : -1) : (x.submittedAt < y.submittedAt ? -1 : 1);
    });
  }

  function card(a) {
    var ui = ADM.ui;
    var s = ADM.docSummary(a);
    var wd = ADM.workingDaysSince(a.submittedAt);
    var late = s.pending && wd > ADM.REVIEW_SLA_DAYS;
    var outcome = "";
    if (!s.pending && ADM.canApprove(a)) {
      outcome = '<div class="rq-outcome ok">' + icon("check") + "<span><strong>All " + s.total + " documents verified.</strong> Schedule the entrance exam to issue the permit.</span>" +
        '<button class="btn btn-brand btn-sm" type="button" data-rq-approve="' + esc(a.id) + '">' + icon("calendar") + "Approve for exam</button></div>";
    } else if (!s.pending && s.resubmit) {
      outcome = '<div class="rq-outcome warn">' + icon("flag") + "<span><strong>Sent back for resubmission.</strong> " + s.resubmit + " document" + (s.resubmit === 1 ? "" : "s") + " to fix. It returns to this queue when the applicant re-uploads.</span></div>";
    }
    return '<article class="rq-card' + (s.pending ? "" : " is-done") + '">' +
      '<header class="rq-head">' + ui.avatar(a) +
      '<div class="rq-who"><a class="rq-name" href="#/applicants/' + esc(a.id) + '">' + esc(ADM.fullName(a, true)) + "</a>" +
      '<p class="rq-meta"><span class="mono">' + esc(a.id) + "</span><span>Submitted " + AO.fmt(a.submittedAt) + "</span>" +
      (s.pending ? '<span class="age' + (late ? " late" : "") + '">' + wd + " working day" + (wd === 1 ? "" : "s") + " waiting</span>" : "") +
      "<span>" + esc(ADM.residency(a)) + '</span><span class="code-chip">' + esc(a.programs[0]) + "</span><span>" + (a.applicantType === "graduate" ? "SHS graduate" : "Grade 12") + "</span></p></div>" +
      '<div class="rq-progress"><span class="doc-meter wide"><span style="--w:' + ui.pct(s.verified, s.total) + '%"></span></span><small>' + s.verified + " of " + s.total + " verified" + (s.pending ? " · " + s.pending + " to review" : "") + "</small></div>" +
      '<div class="rq-actions"><a class="btn btn-ghost btn-sm" href="#/applicants/' + esc(a.id) + '">' + icon("user") + "Open record</a>" +
      (s.pending ? '<button class="btn btn-ok btn-sm" type="button" data-rq-verify-all="' + esc(a.id) + '">' + icon("check") + "Verify all " + s.pending + "</button>" : "") + "</div></header>" +
      outcome +
      '<ul class="review-list">' + ui.docItems(a, true) + "</ul></article>";
  }

  function render($el) {
    var ui = ADM.ui;
    var all = ADM.state.applicants.filter(waiting);
    var files = all.reduce(function (n, a) { return n + ADM.docSummary(a).pending; }, 0);
    var late = all.filter(function (a) { return ADM.workingDaysSince(a.submittedAt) > ADM.REVIEW_SLA_DAYS; }).length;
    var list = queue();
    f.page = ui.clampPage(f.page, list.length, PER_PAGE);
    var page = list.slice((f.page - 1) * PER_PAGE, f.page * PER_PAGE);

    $el.html(
      '<div class="page-title" data-reveal><p class="eyebrow">Requirements verification</p><h1>Document review</h1>' +
      '<p class="lede">Each applicant\'s documents are grouped under their name, oldest submission first. Verify the whole set, or send a document back with a clear remark.</p></div>' +
      '<div class="kpi-grid kpi-grid-3" data-reveal>' +
      ui.kpi({ label: "Applicants waiting", value: all.length, sub: files + " documents to review", icon: "users" }) +
      ui.kpi({ label: "Past the " + ADM.REVIEW_SLA_DAYS + "-day target", value: late, sub: late ? "Start with the oldest" : "On track", icon: "clock", tone: late ? "warn" : "" }) +
      ui.kpi({ label: "Requires resubmission", value: ADM.state.applicants.filter(function (a) { return ADM.statusOf(a) === "resubmit"; }).length, sub: "Applicants fixing a document", icon: "flag", href: "#/applicants", status: "resubmit" }) +
      "</div>" +
      '<section class="table-card rq-toolbar" data-reveal><div class="table-toolbar">' +
      '<div class="search-field">' + icon("search") + '<input type="search" class="form-control" id="doc-q" placeholder="Search name, ID or LRN" aria-label="Search applicant" value="' + esc(f.q) + '"></div>' +
      '<select class="form-select" id="doc-type" aria-label="Only applicants with this document waiting"><option value="">Any document</option>' +
      ADM.DOC_TYPES.map(function (d) { return '<option value="' + d.key + '"' + (f.type === d.key ? " selected" : "") + ">" + esc(d.title) + "</option>"; }).join("") + "</select>" +
      '<select class="form-select" id="doc-sort" aria-label="Sort"><option value="oldest"' + (f.sort === "oldest" ? " selected" : "") + '>Oldest first</option><option value="newest"' + (f.sort === "newest" ? " selected" : "") + ">Newest first</option></select>" +
      "</div></section>" +
      (page.length ? '<div class="rq-list" data-reveal>' + page.map(card).join("") + "</div>" +
        '<section class="table-card">' + ui.pager(list.length, f.page, PER_PAGE).replace("of " + list.length + "<", "of " + list.length + " applicants<") + "</section>"
        : '<section class="table-card" data-reveal>' + ui.empty("check", all.length ? "Nothing matches" : "All caught up", all.length ? "Clear the search or pick another document." : "No applicant has documents waiting for review.") + "</section>")
    );
  }

  function afterChange(a, msg) {
    touched[a.id] = true;
    ADM.refresh();
    var s = ADM.docSummary(a);
    AO.showToast(!s.pending && ADM.canApprove(a) ? ADM.fullName(a) + " is fully verified. Approve them for the exam." : msg);
  }

  var t;
  $(document).on("input", "#doc-q", function () {
    var v = this.value; clearTimeout(t);
    t = setTimeout(function () { f.q = v.trim(); f.page = 1; ADM.refresh(); $("#doc-q").trigger("focus")[0].setSelectionRange(v.length, v.length); }, 250);
  });
  $(document).on("change", "#doc-type", function () { f.type = this.value; f.page = 1; ADM.refresh(); });
  $(document).on("change", "#doc-sort", function () { f.sort = this.value; f.page = 1; ADM.refresh(); });
  $(document).on("click", "#view-documents [data-page]", function () { f.page = +$(this).data("page"); ADM.refresh(); window.scrollTo({ top: 0, behavior: "smooth" }); });

  $(document).on("click", "#view-documents [data-doc-verify], #docModal [data-q-verify]", function () {
    var a = ADM.applicant($(this).data("id")), key = $(this).data("doc-verify") || $(this).data("q-verify");
    if (ADM.actions.verifyDoc(a, key)) afterChange(a, "Document verified.");
  });
  $(document).on("click", "#view-documents [data-doc-resubmit], #docModal [data-q-resubmit]", function () {
    var a = ADM.applicant($(this).data("id")), key = $(this).data("doc-resubmit") || $(this).data("q-resubmit");
    ADM.ui.askRemark(a, key, function (remark) {
      if (ADM.actions.resubmitDoc(a, key, remark)) afterChange(a, "Sent back to " + a.firstName + " with your remark.");
    });
  });
  $(document).on("click", "#view-documents [data-doc-view]", function () {
    var a = ADM.applicant($(this).data("id")), key = $(this).data("doc-view");
    var doc = a.docs[key];
    var id = ' data-id="' + esc(a.id) + '"';
    ADM.ui.previewDoc(a, key, '<button type="button" class="btn btn-ghost me-auto" data-bs-dismiss="modal">Close</button>' +
      (doc.status !== "resubmit" ? '<button type="button" class="btn btn-warn" data-q-resubmit="' + key + '"' + id + ' data-bs-dismiss="modal">' + icon("flag") + "Resubmit</button>" : "") +
      (doc.status !== "verified" ? '<button type="button" class="btn btn-ok" data-q-verify="' + key + '"' + id + ' data-bs-dismiss="modal">' + icon("check") + "Verify</button>" : ""));
  });
  $(document).on("click", "[data-rq-verify-all]", function () {
    var a = ADM.applicant($(this).data("rq-verify-all"));
    var n = ADM.actions.verifyAllPending(a);
    afterChange(a, n + " document" + (n === 1 ? "" : "s") + " verified.");
  });
  $(document).on("click", "[data-rq-approve]", function () {
    var a = ADM.applicant($(this).data("rq-approve"));
    ADM.ui.askSession(a, "approve", function (sid) {
      if (ADM.actions.approveForExam(a, sid)) {
        delete touched[a.id];
        ADM.refresh();
        AO.showToast(ADM.fullName(a) + " approved. Exam permit " + a.exam.permit + " issued.");
      } else AO.showToast("That session is full. Choose another.");
    });
  });

  // Leaving the page clears the "reviewed this visit" cards
  $(window).on("hashchange", function () { if (location.hash.indexOf("#/documents") !== 0) touched = {}; });

  ADM.views.documents = { title: "Document review", render: render };
})(jQuery);
