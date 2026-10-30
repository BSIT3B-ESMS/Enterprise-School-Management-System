/* ==========================================================================
   Clinic module (clinic role): the university clinic nurse only sees
   applicants' medical requirements: the server sends a medical-only state
   (GET /api/clinic/state): no profile, exam, score or document data. They
   send a file back with a remark, or approve the applicant. The approval
   shows on the office's Medical page as "Approved".
     #/check, #/returned, #/approved   the three lists
     #/<list>/:id                       one applicant's medical files
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;

  var LISTS = {
    check: { title: "To check", lede: "Applicants who passed and sent their medical requirements. Open one to check the files, then approve or send a file back.", status: "pending", empty: ["check", "Nothing to check", "New medical files show up here as applicants send them."] },
    returned: { title: "Sent back", lede: "Applicants you asked to replace a file. They come back to To check once they send their files again.", status: "resubmit", empty: ["flag", "Nothing sent back", ""] },
    approved: { title: "Approved", lede: "Applicants you approved. The admission office sees these as Approved, and the applicant's admission is complete.", status: "cleared", empty: ["shield", "No approvals yet", ""] }
  };
  var f = { list: null, q: "", page: 1 };
  var reviewing = null;   // id on #/<list>/:id

  /* ==========================================================================
     ACTIONS (the nurse's only changes). The server saves them and writes the
     audit trail; its copy of the applicant replaces ours.
     ========================================================================== */
  function send(method, path, data, a) {
    return AO.api(method, "/api/clinic/applicants/" + encodeURIComponent(a.id) + "/medical/" + path, data)
      .done(function (res) { if (res && res.data) ADM.replaceApplicant(a, res.data); })
      .fail(function (xhr) { if (xhr.status !== 401) AO.showToast(AO.apiError(xhr)); });
  }
  var actions = {
    // PATCH /api/clinic/applicants/:id/medical/:key { status: "resubmit", remark }
    sendBack: function (a, key, remark) { return send("PATCH", encodeURIComponent(key), { status: "resubmit", remark: remark }, a); },
    // POST /api/clinic/applicants/:id/medical/approve → the office sees "Approved"
    approve: function (a) { return send("POST", "approve", {}, a); }
  };

  /* ==========================================================================
     DATA
     ========================================================================== */
  function inList(name) {
    return ADM.state.applicants.filter(function (a) {
      if (ADM.medStatus(a) !== LISTS[name].status) return false;
      return !f.q || (ADM.fullName(a) + " " + a.id).toLowerCase().indexOf(f.q.toLowerCase()) !== -1;
    });
  }
  function latest(a, field) {
    return ADM.MED_TYPES.reduce(function (m, t) { var file = ADM.fileOf(a, t.key); return file && file[field] && file[field] > m ? file[field] : m; }, "");
  }
  function sentAt(a) { return (a.medical && a.medical.submittedAt) || latest(a, "uploadedAt"); }
  function decidedAt(a) { return latest(a, "reviewedAt"); }
  function age(a) {
    if (!a.birthday) return null;
    var b = AO.parseDate(a.birthday), t = AO.today();
    return t.getFullYear() - b.getFullYear() - (t < new Date(t.getFullYear(), b.getMonth(), b.getDate()) ? 1 : 0);
  }
  function fileChips(a) {
    return ADM.MED_TYPES.map(function (m) {
      var file = ADM.fileOf(a, m.key);
      var st = file ? file.status : "missing";
      return '<span class="med-chip st-' + st + '" title="' + esc(m.title + ": " + ADM.DOC_STATUS[st].label) + '">' + (st === "verified" ? icon("check") : st === "resubmit" ? icon("flag") : "") + esc(m.title.split(" (")[0]) + "</span>";
    }).join("");
  }
  function listOf(a) { var st = ADM.medStatus(a); return st === "cleared" ? "approved" : st === "resubmit" ? "returned" : "check"; }

  /* ==========================================================================
     VIEWS
     ========================================================================== */
  function renderList($el, name) {
    var ui = ADM.ui, L = LISTS[name];
    if (f.list !== name) f = { list: name, q: "", page: 1 };
    var when = name === "check" ? sentAt : decidedAt;
    var list = inList(name).sort(function (x, y) {
      return name === "check" ? (when(x) < when(y) ? -1 : 1)   // oldest first, so no one waits too long
        : (when(x) < when(y) ? 1 : -1);                         // newest decisions first
    });
    f.page = ui.clampPage(f.page, list.length);
    var rows = list.slice((f.page - 1) * ADM.PAGE_SIZE, f.page * ADM.PAGE_SIZE);

    var table = !rows.length ? ui.empty(L.empty[0], L.empty[1], f.q ? "Clear the search to see everyone." : L.empty[2])
      : '<div class="table-responsive"><table class="table data-table"><thead><tr><th>Applicant</th><th>Medical files</th><th>' + (name === "check" ? "Sent" : name === "approved" ? "Approved" : "Sent back") + '</th><th><span class="visually-hidden">Open</span></th></tr></thead><tbody>' +
        rows.map(function (a) {
          var at = when(a);
          return '<tr class="row-link" data-href="#/' + name + "/" + esc(a.id) + '" tabindex="0"><td>' + ui.applicantCell(a) + "</td>" +
            '<td><div class="med-chips">' + fileChips(a) + "</div></td>" +
            "<td>" + (at ? AO.fmt(at) + '<small class="d-block text-muted">' + ui.relDays(at) + "</small>" : "—") + "</td>" +
            '<td class="text-end">' + (name === "check" ? '<span class="btn btn-ghost btn-sm">' + icon("eye") + "Check</span>" : icon("arrowR")) + "</td></tr>";
        }).join("") + "</tbody></table></div>";

    $el.html(
      '<div class="page-title" data-reveal><p class="eyebrow">Medical files</p><h1>' + esc(L.title) + '</h1><p class="lede">' + esc(L.lede) + "</p></div>" +
      '<section class="table-card" data-reveal><div class="table-toolbar">' +
      '<div class="search-field">' + icon("search") + '<input type="search" class="form-control" id="nurse-q" placeholder="Search applicant or ID" aria-label="Search applicant" value="' + esc(f.q) + '"></div>' +
      '<span class="text-muted small ms-auto">' + esc(ADM.MED_TYPES.map(function (m) { return m.title; }).join(" · ")) + "</span></div>" +
      table + (rows.length ? ui.pager(list.length, f.page) : "") + "</section>"
    );
  }

  function renderReview($el, id) {
    var ui = ADM.ui;
    var a = ADM.applicant(id);
    var st = a && ADM.medStatus(a);
    reviewing = id;
    if (!a || st === null || st === "none") {
      $el.html('<section class="card-soft">' + ui.empty("search", "No medical files for this ID", "The applicant hasn't sent medical requirements yet.") + '<div class="text-center pb-2"><a class="btn btn-ghost" href="#/check">Back to the list</a></div></section>');
      return;
    }
    var back = listOf(a);
    var approved = st === "cleared";
    var canApprove = ADM.canApproveMedical(a);
    var missing = ADM.MED_TYPES.filter(function (m) { return !ADM.fileOf(a, m.key); }).length;
    var years = age(a);

    var primary = approved ? '<span class="tag tag-verified">' + icon("check") + "Approved · sent to the admission office</span>"
      : '<button class="btn btn-ok" type="button" id="nurse-approve"' + (canApprove ? "" : " disabled") + ">" + icon("check") + "Approve</button>";
    var note = approved ? "Approved " + ui.dt(decidedAt(a)) + ". The admission office sees this applicant as Approved."
      : st === "resubmit" ? "A file was sent back. You can approve once the applicant replaces it and sends their files again."
        : missing ? missing + " file" + (missing === 1 ? " is" : "s are") + " still missing."
          : "Open each file. If everything is in order, approve. If not, send that file back with a remark.";

    $el.html(
      '<a class="back-link" href="#/' + back + '" data-reveal>' + icon("arrowL") + esc(LISTS[back].title) + "</a>" +
      '<section class="record-hero" data-reveal>' + ui.avatar(a, "avatar-xl") +
      '<div class="record-id"><p class="eyebrow">' + esc(a.id) + "</p><h1>" + esc(ADM.fullName(a)) + "</h1>" +
      '<p class="record-meta">' + (a.sex ? "<span>" + esc(a.sex) + "</span>" : "") + (years !== null ? "<span>" + years + " years old</span>" : "") +
      '<span class="tag ' + ADM.MED_STATUS[st].tag + '">' + (approved ? icon("check") : "") + esc(ADM.MED_STATUS[st].label) + "</span></p></div>" +
      '<div class="record-actions">' + primary + "</div></section>" +
      '<section class="table-card" data-reveal><div class="table-toolbar"><div><strong>Medical requirements</strong>' +
      '<small class="d-block text-muted">' + esc(note) + "</small></div></div>" +
      '<ul class="review-list">' + ADM.MED_TYPES.map(function (m) {
        var file = ADM.fileOf(a, m.key) || { status: "missing" };
        var has = file.status !== "missing";
        return '<li class="review-item status-' + file.status + '"><span class="doc-icon tone-3">' + icon(m.icon) + "</span>" +
          '<div class="review-main"><div class="doc-title-row"><h3>' + esc(m.title) + "</h3>" + ui.docTag(file.status) + '<span class="cond-note">' + esc(m.note) + "</span></div>" +
          (has ? '<p class="review-file">' + esc(file.file) + " · " + AO.fileSize(file.size) + " · sent " + ui.relDays(file.uploadedAt).toLowerCase() + "</p>" : '<p class="review-file">Not sent yet</p>') +
          (file.remark ? '<p class="review-remark">' + icon("flag") + esc(file.remark) + "</p>" : "") +
          (file.reviewedBy ? '<p class="review-by">' + (file.status === "verified" ? "Approved" : "Sent back") + " by " + esc(file.reviewedBy) + " · " + esc(ui.dt(file.reviewedAt)) + "</p>" : "") + "</div>" +
          '<div class="review-actions">' +
          (has ? '<button class="btn btn-ghost btn-sm" type="button" data-n-view="' + m.key + '">' + icon("eye") + "View</button>" : "") +
          (has && !approved && file.status === "pending" && st === "pending" ? '<button class="btn btn-warn btn-sm" type="button" data-n-back="' + m.key + '">' + icon("flag") + "Send back</button>" : "") +
          "</div></li>";
      }).join("") + "</ul></section>"
    );
  }

  /* ==========================================================================
     EVENTS (rows open through the shared .row-link handler in applicants.js)
     ========================================================================== */
  function reviewed() { return ADM.applicant(reviewing); }

  $(document).on("click", "#view-check [data-page], #view-returned [data-page], #view-approved [data-page]", function () { f.page = +$(this).data("page"); ADM.refresh(); });

  var t;
  $(document).on("input", "#nurse-q", function () {
    var v = this.value; clearTimeout(t);
    t = setTimeout(function () { f.q = v.trim(); f.page = 1; ADM.refresh(); $("#nurse-q").trigger("focus")[0].setSelectionRange(v.length, v.length); }, 250);
  });

  function sendBack(a, key) {
    ADM.ui.askRemark(a, key, function (remark) {
      actions.sendBack(a, key, remark).done(function () { ADM.refresh(); AO.showToast("Sent back to " + a.firstName + " with your remark."); });
    });
  }
  $(document).on("click", "[data-n-back]", function () { sendBack(reviewed(), $(this).data("n-back")); });
  $(document).on("click", "[data-n-view]", function () {
    var a = reviewed(), key = $(this).data("n-view");
    var file = ADM.fileOf(a, key);
    ADM.ui.previewDoc(a, key, '<button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Close</button>' +
      (file.status === "pending" && ADM.medStatus(a) === "pending" ? '<button type="button" class="btn btn-warn" data-n-back="' + key + '" data-bs-dismiss="modal">' + icon("flag") + "Send back</button>" : ""));
  });

  $(document).on("click", "#nurse-approve", function () {
    var a = reviewed();
    ADM.ui.confirm({ title: "Approve " + a.firstName + "'s medical requirements?", text: "Your approval goes to the admission office, where " + ADM.fullName(a) + " shows as Approved and their admission is complete. This can't be undone here.", ok: "Approve", icon: "check" }, function () {
      actions.approve(a).done(function () { ADM.refresh(); AO.showToast("Approved. The admission office can see it now."); });
    });
  });

  Object.keys(LISTS).forEach(function (name) {
    ADM.views[name] = { title: LISTS[name].title, render: function ($el) { renderList($el, name); } };
  });
  ADM.views.medreview = { title: function (id) { var a = ADM.applicant(id); return a ? ADM.fullName(a) : "Applicant"; }, render: renderReview };
})(jQuery);
