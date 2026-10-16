/* ==========================================================================
   View: Dashboard — review queues, today's workload, upcoming exams
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM, ui = null;
  var esc = AO.esc, icon = AO.icon;

  function render($el) {
    ui = ADM.ui;
    var apps = ADM.state.applicants;
    var by = ADM.countBy(ADM.statusOf);
    var overdue = apps.filter(ADM.isOverdue);
    var ready = apps.filter(ADM.canApprove);
    var decided = apps.filter(function (a) { return a.result; });
    var passed = decided.filter(function (a) { return a.result.decision === "passed"; }).length;
    var unreleased = decided.filter(function (a) { return !a.result.released; }).length;
    var weekAgo = AO.toISO(new Date(AO.today().getTime() - 6 * 86400000));
    var thisWeek = apps.filter(function (a) { return a.submittedAt && a.submittedAt.slice(0, 10) >= weekAgo; }).length;
    var upcoming = ADM.sortedSessions().filter(function (s) { return !ADM.sessionPast(s); });
    var h = new Date().getHours();
    var left = AO.daysUntil(ADM.state.settings.deadline);

    // Daily submissions, last 14 days
    var days = [];
    for (var i = 13; i >= 0; i--) {
      var d = new Date(AO.today().getTime() - i * 86400000), iso = AO.toISO(d);
      var n = apps.filter(function (a) { return a.submittedAt && a.submittedAt.slice(0, 10) === iso; }).length;
      days.push({ label: d.toLocaleDateString("en-US", { month: "short", day: "numeric" }), value: n, tick: i % 3 === 0 || i === 13, title: AO.fmt(iso, { weekday: "short", month: "short", day: "numeric" }) + ": " + n + " submitted" });
    }

    // Oldest applications still waiting for document review
    var awaiting = apps.filter(function (a) { return ADM.statusOf(a) === "submitted" && ADM.docSummary(a).pending; })
      .sort(function (x, y) { return x.submittedAt < y.submittedAt ? -1 : 1; });
    var queue = awaiting.slice(0, 6);

    $el.html(
      '<div class="welcome-row" data-reveal><div><p class="eyebrow">' + esc(ADM.state.settings.period) + "</p>" +
      "<h1>" + (h < 12 ? "Good morning" : h < 18 ? "Good afternoon" : "Good evening") + ", " + esc(ADM.STAFF.name.split(" ")[0]) + "</h1>" +
      '<p class="lede">Here is where the admission queue stands today.</p></div>' +
      '<div class="deadline-chip"><span data-icon="clock"></span><span>Application deadline<strong>' + AO.fmt(ADM.state.settings.deadline, { month: "long", day: "numeric" }) +
      " · " + (left > 0 ? left + " days left" : left === 0 ? "today" : "closed") + "</strong></span></div></div>" +

      '<div class="kpi-grid" data-reveal>' +
      ui.kpi({ label: "Total applicants", value: apps.length, sub: "+" + thisWeek + " submitted this week", icon: "users", href: "#/applicants", status: "all" }) +
      ui.kpi({ label: "Awaiting review", value: awaiting.length, sub: overdue.length + " past the " + ADM.REVIEW_SLA_DAYS + "-day target", icon: "inbox", tone: overdue.length ? "warn" : "", href: "#/documents" }) +
      ui.kpi({ label: "Requires resubmission", value: by.resubmit || 0, sub: "Waiting on applicants", icon: "flag", href: "#/applicants", status: "resubmit" }) +
      ui.kpi({ label: "Ready to schedule", value: ready.length, sub: "All documents verified", icon: "calendar", href: "#/applicants", status: "ready" }) +
      ui.kpi({ label: "For exam", value: by.for_exam || 0, sub: upcoming.length + " upcoming sessions", icon: "pencil", href: "#/exams" }) +
      ui.kpi({ label: "Pass rate", value: ui.pct(passed, decided.length) + "%", sub: unreleased + " results not released", icon: "award", href: "#/results" }) +
      "</div>" +

      '<div class="row g-3">' +
      '<div class="col-xl-8" data-reveal><section class="card-soft h-100"><div class="panel-heading"><div><h2>Daily submissions</h2><p>Applications submitted in the last 14 days</p></div>' +
      '<a class="text-link" href="#/reports">Reports' + icon("arrowR") + "</a></div>" + ui.columns(days, "Daily submissions for the last 14 days") + "</section></div>" +
      '<div class="col-xl-4" data-reveal><section class="card-soft h-100"><div class="panel-heading"><div><h2>Review queues</h2><p>Applicants by status</p></div></div>' +
      '<ul class="queue-list">' + ADM.STATUS_ORDER.map(function (st) {
        return '<li><a href="#/applicants" data-status-link="' + st + '">' + ui.pill(st) + "<strong>" + (by[st] || 0) + "</strong>" + icon("arrowR") + "</a></li>";
      }).join("") + "</ul></section></div>" +

      '<div class="col-xl-7" data-reveal><section class="card-soft h-100"><div class="panel-heading"><div><h2>Oldest in the queue</h2><p>Review new applications within ' + ADM.REVIEW_SLA_DAYS + " working days</p></div>" +
      '<a class="text-link" href="#/documents">Document review' + icon("arrowR") + "</a></div>" +
      (queue.length ? '<ul class="mini-rows">' + queue.map(function (a) {
        var wd = ADM.workingDaysSince(a.submittedAt);
        return '<li><a href="#/applicants/' + esc(a.id) + '">' + ui.applicantCell(a) +
          '<span class="mini-meta">' + ADM.docSummary(a).pending + " to verify</span>" +
          '<span class="age' + (wd > ADM.REVIEW_SLA_DAYS ? " late" : "") + '">' + wd + " working day" + (wd === 1 ? "" : "s") + "</span>" + icon("arrowR") + "</a></li>";
      }).join("") + "</ul>" : ui.empty("check", "Queue is clear", "Every submitted application has been reviewed.")) + "</section></div>" +

      '<div class="col-xl-5" data-reveal><section class="card-soft h-100"><div class="panel-heading"><div><h2>Upcoming exam sessions</h2><p>Seats filled per batch</p></div>' +
      '<a class="text-link" href="#/exams">All sessions' + icon("arrowR") + "</a></div>" +
      (upcoming.length ? ui.hbars(upcoming.slice(0, 5).map(function (s) {
        var used = ADM.sessionRoster(s.id).length;
        return { label: ADM.sessionLabel(s), sub: s.venue, value: used, display: used + "/" + s.capacity, title: used + " of " + s.capacity + " seats filled" };
      }), Math.max.apply(null, upcoming.map(function (s) { return s.capacity; }))) : ui.empty("calendar", "No upcoming sessions", "Create one under Entrance exams.")) +
      "</section></div></div>"
    );
  }

  $(document).on("click", "[data-status-link]", function () { ADM.views.applicants.filterStatus($(this).data("status-link")); });

  ADM.views.dashboard = { title: "Dashboard", render: render };
})(jQuery);
