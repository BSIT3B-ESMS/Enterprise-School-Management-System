/* ==========================================================================
   View: Medical clearance (#/medical) — the last admission step. Applicants
   who passed submit medical files after their result is released. The clinic
   nurse checks them in the nurse portal (nurse/index.html) and sends an
   approval. The office only sees where each applicant is: read-only here.
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;
  var f = { tab: "approved", q: "", page: 1 };

  var TABS = {
    approved: "Approved",
    nurse: "With the nurse",
    waiting: "Waiting on applicant"
  };

  function eligible() { return ADM.state.applicants.filter(function (a) { return ADM.medStatus(a) !== null; }); }

  function matchesQ(a) { return !f.q || (ADM.fullName(a) + " " + a.id).toLowerCase().indexOf(f.q.toLowerCase()) !== -1; }

  function inTab(tab, st) { return tab === "approved" ? st === "cleared" : tab === "nurse" ? st === "pending" : st === "none" || st === "resubmit"; }

  function applicantsIn(tab) {
    return eligible().filter(function (a) { return matchesQ(a) && inTab(tab, ADM.medStatus(a)); }).sort(function (x, y) {
      if (tab === "approved") return x.medical.approval.at < y.medical.approval.at ? 1 : -1;   // newest approvals first
      return x.lastName.localeCompare(y.lastName);
    });
  }

  function fileChips(a) {
    return ADM.MED_TYPES.map(function (m) {
      var file = ADM.fileOf(a, m.key);
      var st = file ? file.status : "missing";
      return '<span class="med-chip st-' + st + '" title="' + esc(m.title + ": " + ADM.DOC_STATUS[st].label) + '">' + (st === "verified" ? icon("check") : st === "resubmit" ? icon("flag") : "") + esc(m.title.split(" (")[0]) + "</span>";
    }).join("");
  }

  function statusCell(a) {
    var ui = ADM.ui, st = ADM.medStatus(a);
    var tag = '<span class="tag ' + ADM.MED_STATUS[st].tag + '">' + (st === "cleared" ? icon("check") : "") + esc(ADM.MED_STATUS[st].label) + "</span>";
    if (st === "cleared") return tag + '<small class="d-block text-muted mt-1">' + esc(a.medical.approval.by) + " · " + esc(ui.dt(a.medical.approval.at)) + "</small>";
    if (st === "none") return tag + '<small class="d-block text-muted mt-1">Result released ' + ui.relDays(a.result.releasedAt || a.result.recordedAt).toLowerCase() + "</small>";
    return tag;
  }

  function render($el) {
    var ui = ADM.ui;
    var all = eligible();
    var by = {}; all.forEach(function (a) { var st = ADM.medStatus(a); by[st] = (by[st] || 0) + 1; });
    var list = applicantsIn(f.tab);
    f.page = ui.clampPage(f.page, list.length);
    var rows = list.slice((f.page - 1) * ADM.PAGE_SIZE, f.page * ADM.PAGE_SIZE);
    var counts = { approved: applicantsIn("approved").length, nurse: applicantsIn("nurse").length, waiting: applicantsIn("waiting").length };

    var table = !rows.length
      ? ui.empty(f.tab === "approved" ? "shield" : f.tab === "nurse" ? "check" : "clock", f.tab === "approved" ? "No approvals from the nurse yet" : f.tab === "nurse" ? "Nothing with the nurse" : "No one is waiting", f.q ? "Clear the search to see everyone." : "")
      : '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Program</th><th>Requirements</th><th>Medical</th><th><span class="visually-hidden">Open</span></th></tr></thead><tbody>' +
        rows.map(function (a) {
          return '<tr class="row-link" data-href="#/applicants/' + esc(a.id) + '" data-open-tab="medical" tabindex="0"><td>' + ui.applicantCell(a) + "</td>" +
            '<td><span class="code-chip">' + esc(a.programs[0]) + "</span></td>" +
            '<td><div class="med-chips">' + fileChips(a) + "</div></td>" +
            "<td>" + statusCell(a) + "</td>" +
            '<td class="text-end">' + icon("arrowR") + "</td></tr>";
        }).join("") + "</tbody></table></div>";

    $el.html(
      '<div class="page-title" data-reveal><p class="eyebrow">Last admission step</p><h1>Medical clearance</h1>' +
      '<p class="lede">The university clinic nurse checks each applicant\'s medical files and sends an approval here. Approved applicants have completed their admission. Checking files is done by the clinic only.</p></div>' +
      '<div class="kpi-grid kpi-grid-4" data-reveal>' +
      ui.kpi({ label: "Approved", value: by.cleared || 0, sub: ui.pct(by.cleared || 0, all.length) + "% of " + all.length + " passers", icon: "shield" }) +
      ui.kpi({ label: "With the nurse", value: by.pending || 0, sub: "Files being checked", icon: "inbox" }) +
      ui.kpi({ label: "Resubmit", value: by.resubmit || 0, sub: "Fixing a file", icon: "flag", tone: by.resubmit ? "warn" : "" }) +
      ui.kpi({ label: "Not submitted", value: by.none || 0, sub: "Passed, no files yet", icon: "clock" }) + "</div>" +
      '<nav class="tabs" role="tablist" data-reveal>' + Object.keys(TABS).map(function (k) {
        return '<button type="button" role="tab" class="tab' + (f.tab === k ? " active" : "") + '" aria-selected="' + (f.tab === k) + '" data-mtab="' + k + '">' + TABS[k] + " <span>" + counts[k] + "</span></button>";
      }).join("") + "</nav>" +
      '<section class="table-card" data-reveal><div class="table-toolbar">' +
      '<div class="search-field">' + icon("search") + '<input type="search" class="form-control" id="med-q" placeholder="Search applicant or ID" aria-label="Search applicant" value="' + esc(f.q) + '"></div>' +
      '<span class="text-muted small ms-auto">' + icon("lock") + " Read-only · checked by the university clinic</span></div>" +
      table + (rows.length ? ui.pager(list.length, f.page) : "") + "</section>"
    );
  }

  var t;
  $(document).on("input", "#med-q", function () {
    var v = this.value; clearTimeout(t);
    t = setTimeout(function () { f.q = v.trim(); f.page = 1; ADM.refresh(); $("#med-q").trigger("focus")[0].setSelectionRange(v.length, v.length); }, 250);
  });
  $(document).on("click", "[data-mtab]", function () { f.tab = $(this).data("mtab"); f.page = 1; ADM.refresh(); });
  $(document).on("click", "#view-medical [data-page]", function () { f.page = +$(this).data("page"); ADM.refresh(); });

  ADM.views.medical = { title: "Medical clearance", render: render };
})(jQuery);
