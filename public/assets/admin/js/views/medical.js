/* ==========================================================================
   View: Medical clearance (#/medical) — the last admission step. Applicants
   who passed submit medical files after their result is released; each file
   is verified or sent back with a remark. All verified = medically cleared.
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;
  var f = { tab: "check", q: "", page: 1 };

  var TABS = {
    check: "To check",
    waiting: "Waiting on applicant",
    cleared: "Cleared"
  };

  function eligible() { return ADM.state.applicants.filter(function (a) { return ADM.medStatus(a) !== null; }); }

  function matchesQ(a) { return !f.q || (ADM.fullName(a) + " " + a.id).toLowerCase().indexOf(f.q.toLowerCase()) !== -1; }

  // Files waiting for the clinic, oldest first
  function fileQueue() {
    var rows = [];
    eligible().forEach(function (a) {
      if (!matchesQ(a)) return;
      ADM.MED_TYPES.forEach(function (m) {
        var file = ADM.fileOf(a, m.key);
        if (file && file.status === "pending") rows.push({ a: a, m: m, file: file });
      });
    });
    return rows.sort(function (x, y) { return x.file.uploadedAt < y.file.uploadedAt ? -1 : 1; });
  }

  function applicantsIn(tab) {
    return eligible().filter(function (a) {
      var st = ADM.medStatus(a);
      if (!matchesQ(a)) return false;
      return tab === "cleared" ? st === "cleared" : st === "none" || st === "resubmit";
    }).sort(function (x, y) { return x.lastName.localeCompare(y.lastName); });
  }

  function fileChips(a) {
    return ADM.MED_TYPES.map(function (m) {
      var file = ADM.fileOf(a, m.key);
      var st = file ? file.status : "missing";
      return '<span class="med-chip st-' + st + '" title="' + esc(m.title + ": " + ADM.DOC_STATUS[st].label) + '">' + (st === "verified" ? icon("check") : st === "resubmit" ? icon("flag") : "") + esc(m.title.split(" (")[0]) + "</span>";
    }).join("");
  }

  function render($el) {
    var ui = ADM.ui;
    var all = eligible();
    var by = {}; all.forEach(function (a) { var st = ADM.medStatus(a); by[st] = (by[st] || 0) + 1; });
    var queue = fileQueue();
    var list = f.tab === "check" ? queue : applicantsIn(f.tab);
    f.page = ui.clampPage(f.page, list.length);
    var rows = list.slice((f.page - 1) * ADM.PAGE_SIZE, f.page * ADM.PAGE_SIZE);
    var counts = { check: queue.length, waiting: applicantsIn("waiting").length, cleared: applicantsIn("cleared").length };

    var table;
    if (!rows.length) {
      table = ui.empty(f.tab === "check" ? "check" : "shield", f.tab === "check" ? "No medical files to check" : f.tab === "cleared" ? "No one cleared yet" : "No one is waiting", f.q ? "Clear the search to see everyone." : "");
    } else if (f.tab === "check") {
      table = '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Requirement</th><th>Uploaded</th><th>Program</th><th class="text-end">Decision</th></tr></thead><tbody>' +
        rows.map(function (r) {
          var ref = r.a.id + "|" + r.m.key;
          return '<tr><td><a href="#/applicants/' + esc(r.a.id) + '" class="plain-link">' + ui.applicantCell(r.a) + "</a></td>" +
            '<td><strong class="d-block">' + esc(r.m.title) + '</strong><small class="text-muted">' + esc(r.file.file) + " · " + AO.fileSize(r.file.size) + "</small></td>" +
            "<td>" + AO.fmt(r.file.uploadedAt) + '<small class="d-block text-muted">' + ui.relDays(r.file.uploadedAt) + "</small></td>" +
            '<td><span class="code-chip">' + esc(r.a.programs[0]) + "</span></td>" +
            '<td class="text-end"><div class="row-actions">' +
            '<button class="icon-button" type="button" data-m-view="' + ref + '" aria-label="View ' + esc(r.m.title) + '" data-bs-toggle="tooltip" data-bs-title="View">' + icon("eye") + "</button>" +
            '<button class="btn btn-warn btn-sm" type="button" data-m-resubmit="' + ref + '">' + icon("flag") + "Resubmit</button>" +
            '<button class="btn btn-ok btn-sm" type="button" data-m-verify="' + ref + '">' + icon("check") + "Verify</button></div></td></tr>";
        }).join("") + "</tbody></table></div>";
    } else {
      table = '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th>Requirements</th><th>Status</th><th><span class="visually-hidden">Open</span></th></tr></thead><tbody>' +
        rows.map(function (a) {
          var st = ADM.medStatus(a);
          return '<tr class="row-link" data-href="#/applicants/' + esc(a.id) + '" data-open-tab="medical" tabindex="0"><td>' + ui.applicantCell(a) + "</td>" +
            '<td><span class="code-chip">' + esc(a.programs[0]) + "</span></td>" +
            '<td><div class="med-chips">' + fileChips(a) + "</div></td>" +
            '<td><span class="tag ' + ADM.MED_STATUS[st].tag + '">' + (st === "cleared" ? icon("check") : "") + esc(ADM.MED_STATUS[st].label) + "</span>" +
            (st === "none" ? '<small class="d-block text-muted mt-1">Result released ' + ui.relDays(a.result.releasedAt || a.result.recordedAt).toLowerCase() + "</small>" : "") + "</td>" +
            '<td class="text-end">' + icon("arrowR") + "</td></tr>";
        }).join("") + "</tbody></table></div>";
    }

    $el.html(
      '<div class="page-title" data-reveal><p class="eyebrow">Last admission step</p><h1>Medical clearance</h1>' +
      '<p class="lede">Applicants who passed submit their medical requirements after the result is released. Verify each file or send it back with a remark. When every file is verified, the applicant is medically cleared and their admission is complete.</p></div>' +
      '<div class="kpi-grid kpi-grid-4" data-reveal>' +
      ui.kpi({ label: "For checking", value: by.pending || 0, sub: queue.length + " files waiting", icon: "inbox" }) +
      ui.kpi({ label: "Resubmit", value: by.resubmit || 0, sub: "Fixing a file", icon: "flag", tone: by.resubmit ? "warn" : "" }) +
      ui.kpi({ label: "Not submitted", value: by.none || 0, sub: "Passed, no files yet", icon: "clock" }) +
      ui.kpi({ label: "Medically cleared", value: by.cleared || 0, sub: ui.pct(by.cleared || 0, all.length) + "% of " + all.length + " passers", icon: "shield" }) + "</div>" +
      '<nav class="tabs" role="tablist" data-reveal>' + Object.keys(TABS).map(function (k) {
        return '<button type="button" role="tab" class="tab' + (f.tab === k ? " active" : "") + '" aria-selected="' + (f.tab === k) + '" data-mtab="' + k + '">' + TABS[k] + " <span>" + counts[k] + "</span></button>";
      }).join("") + "</nav>" +
      '<section class="table-card" data-reveal><div class="table-toolbar">' +
      '<div class="search-field">' + icon("search") + '<input type="search" class="form-control" id="med-q" placeholder="Search applicant or ID" aria-label="Search applicant" value="' + esc(f.q) + '"></div>' +
      '<span class="text-muted small ms-auto">' + esc(ADM.MED_TYPES.map(function (m) { return m.title; }).join(" · ")) + "</span></div>" +
      table + (rows.length ? ui.pager(list.length, f.page) : "") + "</section>"
    );
  }

  function parse(v) { var p = String(v).split("|"); return { a: ADM.applicant(p[0]), key: p[1] }; }

  function verify(a, key) {
    if (!ADM.actions.verifyMedical(a, key)) return;
    ADM.refresh();
    AO.showToast(ADM.medStatus(a) === "cleared" ? ADM.fullName(a) + " is medically cleared. Admission complete." : "Medical file verified.");
  }
  function resubmit(a, key) {
    ADM.ui.askRemark(a, key, function (remark) {
      if (ADM.actions.resubmitMedical(a, key, remark)) { ADM.refresh(); AO.showToast("Sent back to " + a.firstName + " with your remark."); }
    });
  }

  var t;
  $(document).on("input", "#med-q", function () {
    var v = this.value; clearTimeout(t);
    t = setTimeout(function () { f.q = v.trim(); f.page = 1; ADM.refresh(); $("#med-q").trigger("focus")[0].setSelectionRange(v.length, v.length); }, 250);
  });
  $(document).on("click", "[data-mtab]", function () { f.tab = $(this).data("mtab"); f.page = 1; ADM.refresh(); });
  $(document).on("click", "#view-medical [data-page]", function () { f.page = +$(this).data("page"); ADM.refresh(); });
  $(document).on("click", "[data-m-verify]", function () { var r = parse($(this).data("m-verify")); verify(r.a, r.key); });
  $(document).on("click", "[data-m-resubmit]", function () { var r = parse($(this).data("m-resubmit")); resubmit(r.a, r.key); });
  $(document).on("click", "[data-m-view]", function () {
    var v = $(this).data("m-view"), r = parse(v);
    ADM.ui.previewDoc(r.a, r.key, '<a class="btn btn-ghost me-auto" href="#/applicants/' + esc(r.a.id) + '" data-bs-dismiss="modal">Open record</a>' +
      '<button type="button" class="btn btn-warn" data-m-resubmit="' + v + '" data-bs-dismiss="modal">' + icon("flag") + "Resubmit</button>" +
      '<button type="button" class="btn btn-ok" data-m-verify="' + v + '" data-bs-dismiss="modal">' + icon("check") + "Verify</button>");
  });

  ADM.views.medical = { title: "Medical clearance", render: render, verify: verify, resubmit: resubmit };
})(jQuery);
