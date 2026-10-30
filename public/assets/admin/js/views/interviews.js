/* ==========================================================================
   View: Interviews (#/interviews), FR-17 · "Interview" in the Admission
   Personnel diagram. Every choice (1st to 3rd) whose program is flagged
   "requires interview" interviews the applicants who passed the entrance
   exam, before the offer (D14), one choice at a time.

   Three levels, one click each:
     1. Colleges             → pick a college
     2. College              → its interview slots, plus the applicants waiting
                               for a slot ("Choose slot" and "Add slot" pop-ups)
     3. Slot                 → the applicants booked at that date and time
   The program's College Dean accepts or rejects (dean.js). The Admin, who
   runs the whole system, can record it here too.
   ========================================================================== */
(function ($) {
  "use strict";

  var ADM = window.ADM;
  var esc = AO.esc, icon = AO.icon;
  var nav = { college: null, slot: null };   // college code, slot id
  var pick = null;                            // { id, program } being scheduled in the pop-up

  function isAdmin() { return ADM.STAFF.roleKey === "admission_head" || ADM.STAFF.roleKey === "super_admin"; }
  function programName(code) {
    var p = ADM.interviews.programs.filter(function (x) { return x.code === code; })[0];
    return p ? p.name : code;
  }
  function day(s) { return AO.fmt(s.date, { weekday: "short", month: "short", day: "numeric", year: "numeric" }); }
  function when(s) { return day(s) + (s.time ? " · " + s.time : ""); }
  function reload() { return ADM.load().done(function () { ADM.refresh(); }); }
  function ordinal(n) { return ["1st", "2nd", "3rd"][n - 1] || n + "th"; }
  function open(s) { return s.applicants.length < s.capacity && AO.daysUntil(s.date) >= 0; }
  function modal(id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); }
  function clock(v) {   // "13:30" → "1:30 PM"
    if (!v) return "";
    var h = +v.slice(0, 2), m = v.slice(3, 5);
    return (h % 12 || 12) + ":" + m + " " + (h < 12 ? "AM" : "PM");
  }

  /* A colour per college, set as --c on its cards */
  var COLORS = { CCS: "#6b7280", CBA: "#eab308", CHM: "#dc2626", COE: "#ea580c", CAS: "#9b87d9", COED: "#2563eb", CON: "#ec4899" };
  ADM.tintStyle = function (college) { return ' style="--c:' + (COLORS[college] || "#1b6b45") + '"'; };   // also used by the dean screens
  function tint(college) { return ' style="--c:' + (COLORS[college] || "#1b6b45") + ';cursor:pointer"'; }

  /* ---------- grouping by college ---------- */
  function collegeOf(code) {
    var p = ADM.interviews.programs.filter(function (x) { return x.code === code; })[0];
    return (p && p.college) || "—";
  }
  function colleges() {
    var map = {};
    ADM.interviews.programs.forEach(function (p) {
      var c = p.college || "—";
      map[c] = map[c] || { code: c, name: p.collegeName || "Other programs", programs: [] };
      map[c].programs.push(p.code);
    });
    return Object.keys(map).sort().map(function (k) { return map[k]; });
  }
  function inCollege(c, program) { return c.programs.indexOf(program) !== -1; }
  function stats(c) {
    var slots = ADM.interviews.schedules.filter(function (s) { return inCollege(c, s.program); });
    return {
      slots: slots.length,
      booked: slots.reduce(function (n, s) { return n + s.applicants.length; }, 0),
      waiting: ADM.interviews.waiting.filter(function (w) { return inCollege(c, w.program); }).length
    };
  }
  function currentCollege() { return colleges().filter(function (c) { return c.code === nav.college; })[0]; }

  /* ---------- level 1: colleges ---------- */
  function renderColleges() {
    var list = colleges();
    return '<div class="row g-3">' + list.map(function (c) {
      var st = stats(c);
      return '<div class="col-12 col-md-6 col-xl-4"><button type="button" class="card-soft iv-card w-100 text-start" data-iv-college="' + esc(c.code) + '"' + tint(c.code) + '>' +
        '<div class="d-flex align-items-center gap-3"><span class="sp-icon">' + icon("users") + "</span><div><h3 class=\"card-title mb-0\">" + esc(c.code) + "</h3>" +
        '<small class="text-muted">' + esc(c.name) + "</small></div></div>" +
        '<p class="small text-muted mt-3 mb-2">' + esc(c.programs.join(" · ")) + "</p>" +
        '<div class="d-flex gap-3 flex-wrap small"><span><b>' + st.slots + "</b> slot" + (st.slots === 1 ? "" : "s") + "</span><span><b>" + st.booked + "</b> booked</span>" +
        (st.waiting ? '<span class="text-danger-soft"><b>' + st.waiting + "</b> waiting for a slot</span>" : "") + "</div></button></div>";
    }).join("") + "</div>";
  }

  /* ---------- level 2: one college's slots and waiting applicants ---------- */
  function waitingList(c) {
    var list = ADM.interviews.waiting.filter(function (w) { return inCollege(c, w.program); });
    if (!list.length) return "";
    return '<section class="card-soft iv-card mb-3"' + tint(c.code) + ' data-reveal><h3 class="card-title">Waiting for a slot <span class="text-muted fw-normal">(' + list.length + ")</span></h3>" +
      '<ul class="list-unstyled mb-0">' + list.map(function (w) {
        return '<li class="d-flex justify-content-between align-items-center gap-3 py-2 border-top flex-wrap">' +
          "<span><strong>" + esc(w.name) + '</strong><small class="d-block text-muted">' + esc(w.id) + " · " + ordinal(w.rank) + " choice · " + esc(programName(w.program)) + "</small></span>" +
          '<button class="btn btn-brand btn-sm" type="button" data-iv-pick="' + esc(w.id) + '" data-program="' + esc(w.program) + '">' + icon("calendar") + "Choose slot</button></li>";
      }).join("") + "</ul></section>";
  }

  function slotCards(c) {
    var list = ADM.interviews.schedules.filter(function (s) { return inCollege(c, s.program); })
      .sort(function (x, y) { return x.date < y.date ? -1 : x.date > y.date ? 1 : 0; });
    if (!list.length) return '<section class="card-soft" data-reveal>' + ADM.ui.empty("calendar", "No interview slots yet", "Click Add slot to set a date, time and venue.") + "</section>";
    return '<h2 class="group-title" data-reveal>Schedules<span>' + list.length + '</span></h2><div class="row g-3">' + list.map(function (s) {
      var past = AO.daysUntil(s.date) < 0;
      return '<div class="col-12 col-md-6"><button type="button" class="card-soft iv-card w-100 text-start" data-iv-slot="' + s.id + '"' + tint(c.code) + '>' +
        '<h3 class="card-title mb-1">' + icon("calendar") + " " + esc(when(s)) + "</h3>" +
        '<small class="text-muted d-block">' + icon("pin") + esc(s.venue) + "</small>" +
        '<div class="d-flex justify-content-between mt-2 small"><span class="code-chip">' + esc(s.program) + "</span><span><b>" + s.applicants.length + "/" + s.capacity + "</b> booked" + (past ? " · past" : "") + "</span></div></button></div>";
    }).join("") + "</div>";
  }

  function renderCollege(c) {
    return '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3" data-reveal>' +
      '<button class="btn btn-ghost btn-sm" type="button" data-iv-back="colleges">' + icon("arrowL") + "All colleges</button>" +
      '<button class="btn btn-brand" type="button" id="iv-add-slot">' + icon("plus") + "Add slot</button></div>" +
      waitingList(c) + slotCards(c);
  }

  /* ---------- level 3: who is booked at one slot ---------- */
  function applicantRow(s, i) {
    var row = $.extend({ program: s.program, date: s.date, time: s.time, venue: s.venue }, i);
    var grade = i.grade != null ? '<small class="text-muted">' + icon("lock") + "Grade " + esc(i.grade) + " (internal)</small>" : "";
    return '<li class="d-flex justify-content-between align-items-center gap-3 py-2 border-top flex-wrap"><span><strong>' + esc(i.name) + '</strong><small class="d-block text-muted">' + esc(i.id) + "</small></span>" +
      '<span class="d-flex gap-2 align-items-center flex-wrap">' + ADM.evaluation.outcomeTag(i) + grade + (isAdmin() && !i.outcome ? ADM.evaluation.controls(row) : "") + "</span></li>";
  }
  function renderSlot(c) {
    var s = ADM.interviews.schedules.filter(function (x) { return x.id === nav.slot; })[0];
    if (!s) { nav.slot = null; return renderCollege(c); }
    return '<div class="mb-3" data-reveal><button class="btn btn-ghost btn-sm" type="button" data-iv-back="college">' + icon("arrowL") + esc(c.code) + " schedules</button></div>" +
      '<section class="card-soft iv-card"' + tint(c.code) + ' data-reveal><div class="d-flex justify-content-between flex-wrap gap-2"><div>' +
      '<h3 class="card-title mb-1">' + icon("calendar") + " " + esc(when(s)) + "</h3>" +
      '<small class="text-muted">' + icon("pin") + esc(s.venue) + " · " + esc(s.program) + " · " + esc(programName(s.program)) + "</small></div>" +
      '<span class="text-muted small"><b>' + s.applicants.length + "/" + s.capacity + "</b> booked</span></div>" +
      (s.applicants.length ? '<ul class="list-unstyled mb-0 mt-2">' + s.applicants.map(function (i) { return applicantRow(s, i); }).join("") + "</ul>" : '<p class="text-muted small mt-3 mb-0">Nobody is booked at this time yet.</p>') +
      "</section>";
  }

  function render($el) {
    var iv = ADM.interviews, c = nav.college ? currentCollege() : null;
    if (nav.college && !c) nav.college = nav.slot = null;
    var title = !c ? "Interviews" : nav.slot ? "Interview slot" : c.code + " interviews";
    var lede = !c ? "Pick a college to see its interview schedules."
      : nav.slot ? "Everyone booked at this date and time."
      : "Add a slot (date, time, venue), choose a slot for each waiting applicant, then the college dean accepts or rejects" + (isAdmin() ? " (you can too as Admin)" : "") + ".";
    $el.html(
      '<div class="page-title" data-reveal><h1>' + esc(title) + '</h1><p class="lede">' + esc(c && !nav.slot ? c.name + ". " + lede : lede) + "</p></div>" +
      (!iv.programs.length ? '<section class="card-soft" data-reveal>' + ADM.ui.empty("users", "No programs require an interview", "Turn it on per program in Programs & requirements.") + "</section>"
        : !c ? renderColleges() : nav.slot ? renderSlot(c) : renderCollege(c))
    );
  }

  $(document).on("click", "[data-iv-college]", function () { nav = { college: String($(this).data("iv-college")), slot: null }; ADM.refresh(); });
  $(document).on("click", "[data-iv-slot]", function () { nav.slot = +$(this).data("iv-slot"); ADM.refresh(); });
  $(document).on("click", "[data-iv-back]", function () {
    if ($(this).data("iv-back") === "colleges") nav = { college: null, slot: null }; else nav.slot = null;
    ADM.refresh();
  });

  /* ---------- pop-up: add a slot (for the college being viewed) ---------- */
  function venueFor(code) {
    var c = collegeOf(code);
    return c !== "—" ? "3rd Floor, " + c + " Faculty" : "";
  }
  $(document).on("click", "#iv-add-slot", function () {
    var c = currentCollege();
    var programs = ADM.interviews.programs.filter(function (p) { return !c || inCollege(c, p.code); });
    $("#interview-schedule-form")[0].reset();
    $("#iv-program").html(programs.map(function (p) { return '<option value="' + esc(p.code) + '">' + esc(p.code) + " · " + esc(p.name) + "</option>"; }).join(""));
    $("#iv-date").attr("min", AO.toISO(AO.today()));
    $("#iv-venue").val(venueFor($("#iv-program").val()));
    modal("slotModal").show();
  });
  $(document).on("change", "#iv-program", function () { $("#iv-venue").val(venueFor(this.value)); });

  $(document).on("submit", "#interview-schedule-form", function (e) {
    e.preventDefault();
    if (!AO.validateFields($(this))) return;
    var from = clock($("#iv-from").val()), to = clock($("#iv-to").val());
    var body = { program: $("#iv-program").val(), date: $("#iv-date").val(), time: from ? from + (to ? " – " + to : "") : null, venue: $("#iv-venue").val().trim(), capacity: +$("#iv-capacity").val() };
    AO.busy($("#iv-create"), AO.api("POST", ADM.API + "/interview-schedules", body).then(reload))
      .done(function () { modal("slotModal").hide(); AO.showToast("Interview slot added."); })
      .fail(function (xhr) { if (xhr.status !== 401) AO.showToast(AO.apiError(xhr)); });
  });

  /* ---------- pop-up: choose a slot for one waiting applicant ---------- */
  $(document).on("click", "[data-iv-pick]", function () {
    pick = { id: String($(this).data("iv-pick")), program: $(this).data("program") };
    var w = ADM.interviews.waiting.filter(function (x) { return x.id === pick.id && x.program === pick.program; })[0] || { name: pick.id };
    var slots = ADM.interviews.schedules.filter(function (s) { return s.program === pick.program && open(s); });
    $("#pick-who").text(w.name + " · " + pick.program);
    $("#pick-list").html(slots.length ? slots.map(function (s, k) {
      return '<label class="d-flex align-items-center gap-3 p-3 mb-2 border rounded" style="cursor:pointer"><input type="radio" name="iv-slot" value="' + s.id + '"' + (k === 0 ? " checked" : "") + ">" +
        "<span><strong>" + esc(when(s)) + '</strong><small class="d-block text-muted">' + icon("pin") + esc(s.venue) + " · " + (s.capacity - s.applicants.length) + " seats left</small></span></label>";
    }).join("") : '<p class="text-muted mb-0">There is no open ' + esc(pick.program) + " slot. Add one first.</p>");
    $("#pick-assign").prop("hidden", !slots.length);
    $("#pick-add").prop("hidden", !!slots.length);
    modal("pickSlotModal").show();
  });
  $(document).on("click", "#pick-add", function () { modal("pickSlotModal").hide(); $("#iv-add-slot").trigger("click"); });
  $(document).on("click", "#pick-assign", function () {
    var scheduleId = +$("input[name=iv-slot]:checked").val();
    if (!pick || !scheduleId) return;
    AO.busy($(this), AO.api("POST", ADM.API + "/applicants/" + encodeURIComponent(pick.id) + "/interview", { scheduleId: scheduleId }).then(reload))
      .done(function () { modal("pickSlotModal").hide(); AO.showToast("Interview booked. The applicant gets an email with the date, time and venue."); })
      .fail(function (xhr) { if (xhr.status !== 401) AO.showToast(AO.apiError(xhr)); });
  });

  ADM.views.interviews = { title: "Interviews", render: render };
})(jQuery);
