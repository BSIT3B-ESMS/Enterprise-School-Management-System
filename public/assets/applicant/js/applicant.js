/* ==========================================================================
   PLP Online Admission — Applicant Portal (jQuery 3.7 + Bootstrap 5.3)
   Needs assets/shared/js/core.js loaded first (window.AO).
   Sign-in, the application form, the exam, the result, the offer and the
   medical requirements come from the Laravel API (/api/applicant/*).
   Still in the browser only, until the team settles how the 8 document
   cards map to the official requirements (PROGRESS.md, H1): the document
   cards and the "Submit" button.
   ========================================================================== */
$(function () {
  "use strict";

  /* ==========================================================================
     CONFIG
     ========================================================================== */
  var LOCAL_KEY = "admitone.portal.local.";   // + application number: document cards (H1)
  var REMEMBER_KEY = "admitone.rememberEmail";
  var APP_ID = "";                             // from the server

  // My application: three sections only
  var SECTIONS = [
    { id: 1, title: "Personal information", short: "Personal", desc: "Tell us who you are and how to reach you." },
    { id: 2, title: "Academic background", short: "Academic", desc: "Your most recent school and grades." },
    { id: 3, title: "Program choice", short: "Program", desc: "Choose the programs and campus you're applying to." }
  ];

  // Overview journey: six stages. Stage 1 covers all three form sections.
  var JOURNEY = [
    { id: 1, short: "Application", title: "Application form", icon: "file" },
    { id: 2, short: "Documents", title: "Required documents", icon: "folder" },
    { id: 3, short: "Review", title: "Admissions review", icon: "search" },
    { id: 4, short: "Exam", title: "Entrance exam", icon: "pencil" },
    { id: 5, short: "Result", title: "Admission result", icon: "award" },
    { id: 6, short: "Medical", title: "Medical requirements", icon: "shield" }
  ];
  var STAGES = JOURNEY.length;

  // Medical requirements, submitted after accepting the offer (last step).
  // Placeholder list: replace with the university clinic's official requirements.
  var MEDICAL = [
    { key: "medCert", title: "Medical certificate", desc: "Signed by a licensed physician, dated within the last 3 months." },
    { key: "xray", title: "Chest X-ray result (PA view)", desc: "Official result with the radiologist's reading." },
    { key: "labs", title: "Laboratory results", desc: "Complete blood count (CBC) and urinalysis." }
  ];

  // Required documents — edit titles and descriptions here
  var DOCS = [
    { key: "id", title: "Valid ID", desc: "Government-issued or school ID with photo. Put the front and back in one file.", icon: "idcard", tone: 1 },
    { key: "guardianId", title: "Parent / Guardian ID", desc: "Valid ID of your parent or legal guardian, showing their signature.", icon: "users", tone: 2 },
    { key: "ctcG11", title: "CTC Grade 11 Report Card", desc: "Certified true copy of your Grade 11 report card, signed by the school registrar.", icon: "stamp", tone: 3 },
    { key: "form138", title: "Form 138", desc: "Your Grade 12 report card showing the latest available grading period.", icon: "file", tone: 4 },
    { key: "goodMoral", title: "Certificate of Good Moral Character", desc: "Issued by your last school's guidance office, dated within the last 6 months.", icon: "shield", tone: 1 },
    { key: "psa", title: "PSA Birth Certificate", desc: "A clear copy of your birth certificate issued by the Philippine Statistics Authority.", icon: "scroll", tone: 2 },
    { key: "photo", title: "Passport-size photo with name tag", desc: "Recent photo on a white background with a name tag below.", icon: "camera", tone: 3, imageOnly: true, extra: "photo" },
    { key: "payment", title: "GCash / Dragonpay payment", desc: "Screenshot or receipt of your application fee payment with the reference number visible.", icon: "wallet", tone: 4, extra: "payment" }
  ];

  // Open programs, key dates and the exam schedule come from the server
  var PROGRAMS = [];
  var KEY_DATES = [];
  var EXAM = { date: AO.toISO(AO.today()), time: "", venue: "", permit: "" };
  function keyDate(title, fallback) { var k = KEY_DATES.filter(function (x) { return x.title === title; })[0]; return k ? k.date : fallback; }
  var APP_FEE = "₱500";

  /* ==========================================================================
     SHARED CORE (assets/shared/js/core.js)
     ========================================================================== */
  var icon = AO.icon, STAR = AO.STAR, paintIcons = AO.paintIcons;
  var readJSON = AO.readJSON, writeJSON = AO.writeJSON, removeKey = AO.removeKey;
  var esc = AO.esc, parseDate = AO.parseDate, today = AO.today, toISO = AO.toISO, fmt = AO.fmt, daysUntil = AO.daysUntil;
  var fileSize = AO.fileSize, reducedMotion = AO.reducedMotion, animateCount = AO.animateCount;
  var showToast = AO.showToast, withLoading = AO.withLoading;
  var setError = AO.setError, clearError = AO.clearError, validateFields = AO.validateFields;
  var moveNavIndicator = AO.moveNavIndicator, closeSidebar = AO.closeSidebar, revealView = AO.revealView, initTooltips = AO.initTooltips;

  /* Progress ring (r = 52 → circumference 326.7) */
  function setRing($ring, value, total) {
    var c = 326.7;
    $ring.find(".ring-fill").css("stroke-dashoffset", c * (1 - value / total));
    $ring.toggleClass("full", value === total);
  }

  /* ---------- Confetti (canvas, palette colours) ---------- */
  var confettiCanvas = document.getElementById("confetti");
  var confettiCtx = confettiCanvas.getContext("2d");
  function confetti() {
    if (reducedMotion()) return;
    var colors = ["#17803f", "#3fa45f", "#a9ddb8", "#8fd3a8", "#f2c76e", "#d9ae3a"];
    var w = confettiCanvas.width = window.innerWidth;
    var h = confettiCanvas.height = window.innerHeight;
    var pieces = [];
    for (var i = 0; i < 170; i++) {
      pieces.push({
        x: w / 2 + (Math.random() - .5) * w * .3, y: h * .35,
        vx: (Math.random() - .5) * 16, vy: -Math.random() * 15 - 5,
        r: Math.random() * 6 + 4, rot: Math.random() * 6, vr: (Math.random() - .5) * .3,
        c: colors[i % colors.length], shape: i % 3
      });
    }
    var start = performance.now();
    (function frame(now) {
      var t = now - start;
      confettiCtx.clearRect(0, 0, w, h);
      pieces.forEach(function (p) {
        p.vy += .38; p.vx *= .99; p.x += p.vx; p.y += p.vy; p.rot += p.vr;
        confettiCtx.save();
        confettiCtx.globalAlpha = Math.max(0, 1 - t / 3200);
        confettiCtx.translate(p.x, p.y); confettiCtx.rotate(p.rot);
        confettiCtx.fillStyle = p.c;
        if (p.shape === 0) { confettiCtx.fillRect(-p.r / 2, -p.r / 4, p.r, p.r / 2); }
        else if (p.shape === 1) { confettiCtx.beginPath(); confettiCtx.arc(0, 0, p.r / 2.4, 0, Math.PI * 2); confettiCtx.fill(); }
        else { confettiCtx.beginPath(); confettiCtx.moveTo(0, -p.r / 1.6); confettiCtx.quadraticCurveTo(p.r / 6, -p.r / 6, p.r / 1.6, 0); confettiCtx.quadraticCurveTo(p.r / 6, p.r / 6, 0, p.r / 1.6); confettiCtx.quadraticCurveTo(-p.r / 6, p.r / 6, -p.r / 1.6, 0); confettiCtx.quadraticCurveTo(-p.r / 6, -p.r / 6, 0, -p.r / 1.6); confettiCtx.fill(); }
        confettiCtx.restore();
      });
      if (t < 3200) requestAnimationFrame(frame); else confettiCtx.clearRect(0, 0, w, h);
    })(start);
  }

  /* ==========================================================================
     STATE
     ========================================================================== */
  function freshState() {
    return {
      step: 1,                 // section open in My application
      completed: [],           // finished sections (1–3)
      data: { firstName: "Jordan", lastName: "Mendoza", email: "jordan.mendoza@email.com", program1: PROGRAMS[0] },
      docs: {},                // { key: { name, size, type } }
      phase: "draft",          // draft → review → exam → result
      submittedAt: null,
      accepted: false,
      medical: { files: {}, status: "none" }   // none → submitted → cleared
    };
  }
  var state = freshState();
  var serverPhase = "draft";   // the phase the server reports; edits are only sent while it's "draft"
  var previews = {};           // object URLs for image thumbnails (this session only)
  var editing = false;         // reopened the form after finishing it
  var selectedStage = null;    // stage shown in the overview detail panel
  var celebrated = false;      // confetti already played for the result this visit

  /* ---------- Server state ---------- */
  function applyPortal(p) {
    APP_ID = p.applicationId;
    PROGRAMS = p.programs;
    KEY_DATES = p.keyDates;
    if (p.exam) EXAM = p.exam;
    serverPhase = p.phase;
    state.step = p.step || 1;
    state.completed = p.completed || [];
    state.data = $.extend({}, p.data);
    Object.keys(state.data).forEach(function (k) { if (state.data[k] == null) delete state.data[k]; });
    state.phase = p.phase;
    state.submittedAt = p.submittedAt;
    state.accepted = p.accepted;
    state.medical = { files: p.medical.files || {}, status: p.medical.status };
    // Document cards and their submit stay in this browser for now (H1)
    var local = readJSON("local", LOCAL_KEY + APP_ID) || {};
    state.docs = local.docs || {};
    if (p.phase === "draft" && local.phase === "review") { state.phase = "review"; state.submittedAt = local.submittedAt; }
  }
  function loadPortal() {
    return AO.api("GET", "/api/applicant/portal").done(function (res) { applyPortal(res.data); fillPrograms(); });
  }

  /* Save: the document cards locally (H1); the form and its progress to the server */
  var syncTimer;
  function save() {
    if (APP_ID) writeJSON("local", LOCAL_KEY + APP_ID, { docs: state.docs, phase: serverPhase === "draft" && state.phase === "review" ? "review" : null, submittedAt: state.submittedAt });
    if (serverPhase !== "draft") return;
    clearTimeout(syncTimer);
    syncTimer = setTimeout(function () {
      AO.api("PUT", "/api/applicant/application", $.extend({}, state.data, { step: state.step, completed: state.completed }))
        .done(function (res) {
          // A value the server refused although it looks right here (e.g. an LRN already in use)
          $.each(res.skipped || {}, function (name, msg) {
            var $input = $('#application-form [name="' + name + '"]');
            if ($input.length && $input[0].checkValidity() && $input.val()) setError($input, msg);
          });
        })
        .fail(function (xhr) { if (xhr.status !== 401) showToast(AO.apiError(xhr)); });
    }, 400);
  }
  function isDone(n) { return state.completed.indexOf(n) !== -1; }
  function sectionsDone() { return state.completed.length >= SECTIONS.length; }
  function firstOpenSection() { for (var i = 1; i <= SECTIONS.length; i++) if (!isDone(i)) return i; return SECTIONS.length; }
  function canVisitSection(n) { return n <= firstOpenSection() || isDone(n); }
  function docCount() { return DOCS.filter(function (d) { return state.docs[d.key]; }).length; }
  function locked() { return state.phase !== "draft"; }

  function medCount() { return MEDICAL.filter(function (m) { return state.medical.files[m.key]; }).length; }

  function journeyDone(n) {
    if (n === 1) return sectionsDone();
    if (n === 2) return state.phase !== "draft";
    if (n === 3) return state.phase === "exam" || state.phase === "result";
    if (n === 4) return state.phase === "result";
    if (n === 5) return state.phase === "result" && state.accepted;
    return state.medical.status === "cleared";
  }
  function journeyCurrent() { for (var i = 1; i <= STAGES; i++) if (!journeyDone(i)) return i; return STAGES; }
  function journeyPercent() { var n = 0; for (var i = 1; i <= STAGES; i++) if (journeyDone(i)) n++; return Math.round(n / STAGES * 100); }

  /* ==========================================================================
     AUTH (Laravel API, JWT)
     ========================================================================== */
  function showAuth(panel) {
    $("#signin-form").prop("hidden", panel !== "signin");
    $("#signup-form").prop("hidden", panel !== "signup");
    $("#forgot-form").prop("hidden", panel !== "forgot");
    $(".auth-tabs").toggleClass("signup", panel === "signup");
    $("#tab-signin").toggleClass("active", panel !== "signup").attr("aria-selected", panel !== "signup");
    $("#tab-signup").toggleClass("active", panel === "signup").attr("aria-selected", panel === "signup");
  }

  function enterApp() {
    $("#auth-screen").prop("hidden", true);
    $("#app").prop("hidden", false);
    refreshAll();
    openView("overview");
  }

  function leaveApp() {
    AO.setToken(null);
    clearTimeout(syncTimer);
    state = freshState(); APP_ID = ""; serverPhase = "draft";
    editing = false; selectedStage = null; celebrated = false;
    $(".tooltip").remove();
    $("#app").prop("hidden", true);
    $("#auth-screen").prop("hidden", false);
    $("#signin-password").val("");
    showAuth("signin");
    window.scrollTo(0, 0);
  }

  $("#tab-signin").on("click", function () { showAuth("signin"); });
  $("#tab-signup").on("click", function () { showAuth("signup"); });
  $("#forgot-link").on("click", function () { $("#forgot-email").val($("#signin-email").val()); $("#forgot-note").text(""); showAuth("forgot"); });
  $(".back-to-signin").on("click", function () { showAuth("signin"); });

  $("#signin-form").on("submit", function (e) {
    e.preventDefault();
    if (!validateFields($(this))) return;
    var email = $("#signin-email").val().trim();
    if ($("#remember-me").is(":checked")) writeJSON("local", REMEMBER_KEY, email); else removeKey("local", REMEMBER_KEY);
    var login = AO.api("POST", "/api/auth/login", { email: email, password: $("#signin-password").val(), portal: "applicant" })
      .then(function (res) {
        // UI pending: there is no 2FA code screen yet (see PROGRESS.md)
        if (res.data.two_factor_required) return $.Deferred().reject({ responseJSON: { message: "Two-factor sign-in is on for this account, but this screen can't ask for the code yet." } });
        AO.setToken(res.data.token);
        return loadPortal();
      });
    AO.busy($("#signin-submit"), login)
      .done(enterApp)
      .fail(function (xhr) { AO.setToken(null); setError($("#signin-email"), AO.apiError(xhr)); });
  });

  // Server field name → the input that shows its error
  var SIGNUP_FIELDS = { first_name: "#signup-first", last_name: "#signup-last", email: "#signup-email", phone: "#signup-phone", password: "#signup-password", terms: "#signup-terms" };

  $("#signup-form").on("submit", function (e) {
    e.preventDefault();
    var ok = validateFields($(this));
    if ($("#signup-password").val() && $("#signup-confirm").val() !== $("#signup-password").val()) { setError($("#signup-confirm"), "Passwords don't match."); ok = false; }
    if (!ok) return;
    var register = AO.api("POST", "/api/auth/register", {
      first_name: $("#signup-first").val().trim(), last_name: $("#signup-last").val().trim(),
      email: $("#signup-email").val().trim(), phone: $("#signup-phone").val().trim(),
      password: $("#signup-password").val(), password_confirmation: $("#signup-confirm").val(), terms: $("#signup-terms").is(":checked")
    }).then(function (res) {
      AO.setToken(res.data.token);
      return loadPortal();
    });
    AO.busy($("#signup-submit"), register)
      .done(function () {
        enterApp();
        showToast("Account created. Let's start your application.");
      })
      .fail(function (xhr) {
        var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
        var shown = false;
        Object.keys(errors).forEach(function (k) { if (SIGNUP_FIELDS[k]) { setError($(SIGNUP_FIELDS[k]), errors[k][0]); shown = true; } });
        if (!shown) showToast(AO.apiError(xhr));
      });
  });

  $("#forgot-form").on("submit", function (e) {
    e.preventDefault();
    if (!validateFields($(this))) return;
    AO.api("POST", "/api/auth/forgot-password", { email: $("#forgot-email").val().trim() })
      .done(function (res) { $("#forgot-note").text(res.message); })
      .fail(function (xhr) { $("#forgot-note").text(AO.apiError(xhr)); });
  });

  $(".logout-button").on("click", function () {
    AO.api("POST", "/api/auth/logout");
    leaveApp();
    showToast("You've been signed out.");
  });
  // Token expired or revoked
  $(document).on("ao:signedout", function () { if (!$("#app").prop("hidden")) { leaveApp(); showToast("Your session ended. Sign in again."); } });

  /* ==========================================================================
     NAVIGATION
     ========================================================================== */
  var PAGE_LABELS = { overview: "Overview", application: "My application", documents: "Documents" };

  function openView(name) {
    var $target = $("#view-" + name);
    if (!$target.length) return;
    $(".view-section").removeClass("active");
    $target.addClass("active");
    $(".sidebar-nav .nav-link").removeClass("active").filter('[data-view="' + name + '"]').addClass("active");
    moveNavIndicator();
    $("#page-breadcrumb").text(PAGE_LABELS[name]).css("animation", "none")[0].offsetHeight;
    $("#page-breadcrumb").css("animation", "");
    closeSidebar();
    if (name === "overview") { celebrated = false; renderJourney(); }
    if (name === "application") renderApplication(false);
    if (name === "documents") renderDocs();
    revealView($target);
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  $(document).on("click", "[data-view]", function (e) { e.preventDefault(); openView($(this).data("view")); });
  $(document).on("click", "[data-goto]", function (e) {
    e.preventDefault();
    var n = +$(this).data("goto");
    if (locked()) { openView("application"); return; }
    if (!canVisitSection(n)) { openView("application"); showToast("Finish the earlier sections first."); return; }
    editing = true;
    state.step = n;
    save();
    openView("application");
  });

  /* ==========================================================================
     STEPPERS (shared builder)
     ========================================================================== */
  function buildStepper($list, items) {
    if ($list.children().length) return;
    items.forEach(function (s) {
      $list.append('<li class="step" data-step="' + s.id + '"><button class="step-dot" type="button" data-step="' + s.id + '"></button>' +
        '<span class="step-text"><span class="step-label">' + esc(s.short) + '</span><span class="step-sub"></span></span></li>');
    });
    $list.after('<p class="stepper-caption" aria-hidden="true"></p>');
  }

  /* opts: { done(n), current, canVisit(n), subFor(n, status), selected, titleFor(n), caption } */
  function updateStepper($list, opts) {
    $list.children(".step").each(function () {
      var n = +$(this).data("step");
      var done = opts.done(n);
      var isCurrent = n === opts.current;
      var isLocked = !opts.canVisit(n);
      var wasDone = $(this).hasClass("done");
      var wasCurrent = $(this).hasClass("current");
      var $dot = $(this).find(".step-dot");

      $(this).toggleClass("done", done).toggleClass("current", isCurrent).toggleClass("locked", isLocked).toggleClass("selected", n === opts.selected);
      if (isCurrent) $(this).attr("aria-current", "step"); else $(this).removeAttr("aria-current");
      if ((done && !wasDone) || (isCurrent && !wasCurrent)) {
        var li = this;
        $(li).removeClass("popping"); void li.offsetWidth; $(li).addClass("popping");
        setTimeout(function () { $(li).removeClass("popping"); }, 500);
      }
      $dot.html(done ? icon("check") : String(n));
      var status = done ? "Done" : isCurrent ? "In progress" : isLocked ? "Locked" : "Ready";
      $(this).find(".step-sub").text(opts.subFor ? opts.subFor(n, status) : status);
      $dot.attr({ "aria-label": "Step " + n + ": " + opts.titleFor(n) + " — " + status, "aria-disabled": isLocked ? "true" : "false" });

      // Bootstrap tooltip on locked circles
      var tip = bootstrap.Tooltip.getInstance($dot[0]);
      if (isLocked && !tip) new bootstrap.Tooltip($dot[0], { title: "Complete the previous step first", placement: "top" });
      if (!isLocked && tip) tip.dispose();
    });
    $list.next(".stepper-caption").text(opts.caption || "");
  }

  /* ==========================================================================
     OVERVIEW JOURNEY
     ========================================================================== */
  function renderJourney() {
    var $list = $("#journey-stepper");
    var current = journeyCurrent();
    buildStepper($list, JOURNEY);
    if (selectedStage === null || selectedStage > current) selectedStage = current;

    var subs = { 3: "Under review", 4: "Scheduled", 5: "Released", 6: state.medical.status === "submitted" ? "Checking" : "To submit" };
    updateStepper($list, {
      done: journeyDone, current: current, selected: selectedStage,
      canVisit: function (n) { return n <= current; },
      titleFor: function (n) { return JOURNEY[n - 1].title; },
      subFor: function (n, status) { return status === "In progress" && subs[n] ? subs[n] : status; },
      caption: "Step " + selectedStage + " of " + STAGES + " · " + JOURNEY[selectedStage - 1].title
    });

    var pct = journeyPercent();
    $("#journey-progress-bar").css("width", pct + "%");
    $("#journey-progress").attr("aria-valuenow", pct);
    animateCount($("#journey-percent"), pct);

    var med = state.medical.status;
    var resultTitle = !state.accepted ? "Your admission result is in" : med === "cleared" ? "Admission complete · See you in August" : med === "submitted" ? "Your medical requirements are being checked" : "Last step · Submit your medical requirements";
    var titles = { draft: "Step " + current + " of " + STAGES + " · " + JOURNEY[current - 1].title, review: "Your application is under review", exam: "You're scheduled for the entrance exam", result: resultTitle };
    $("#journey-title").text(titles[state.phase]);
    var pill = { draft: ["draft", "Draft"], review: ["review", "Under review"], exam: ["exam", "Exam scheduled"], result: state.accepted && med !== "cleared" ? ["review", med === "submitted" ? "Medical check" : "Medical pending"] : ["admitted", "Admitted"] }[state.phase];
    $("#status-pill").attr("class", "status-pill " + pill[0]).text(pill[1]);

    renderStagePanel(selectedStage);
  }

  $(document).on("click", "#journey-stepper .step-dot", function () {
    var n = +$(this).data("step");
    if (n > journeyCurrent()) return;
    selectedStage = n;
    renderJourney();
  });

  // Arrow keys move between circles in either stepper
  $(document).on("keydown", ".stepper .step-dot", function (e) {
    if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
    e.preventDefault();
    var $li = $(this).closest(".step");
    var $next = e.key === "ArrowRight" ? $li.next(".step") : $li.prev(".step");
    $next.find(".step-dot").trigger("focus");
  });

  function stagePanelHTML(n) {
    var d = state.data;
    var s = JOURNEY[n - 1];
    var current = journeyCurrent();
    var done = journeyDone(n);

    // Application form: personal, academic and program sections in one stage
    if (n === 1) {
      var sectionChips = SECTIONS.map(function (sec) {
        var ok = isDone(sec.id);
        return '<span class="chip-doc' + (ok ? " ok" : "") + '">' + (ok ? icon("check") : "") + esc(sec.title) + "</span>";
      }).join("");
      var facts = [d.firstName && (d.firstName + " " + (d.lastName || "")), d.lastSchool, d.program1].filter(Boolean)
        .map(function (t) { return '<span class="chip-doc">' + esc(t) + "</span>"; }).join("");
      return '<div class="sp-row"><span class="sp-icon">' + icon(s.icon) + '</span><div class="sp-body">' +
        "<h3>" + (done ? "Application form complete" : state.completed.length + " of " + SECTIONS.length + " sections done") + "</h3>" +
        "<p>" + (done ? "Your personal, academic, and program details are saved in My application." : "Fill in your personal information, academic background, and program choices.") + "</p>" +
        '<div class="sp-chips">' + sectionChips + "</div>" + (done && facts ? '<div class="sp-chips">' + facts + "</div>" : "") +
        '</div><div class="sp-actions"><button class="btn ' + (done ? "btn-ghost" : "btn-brand") + '" type="button" data-goto="' + (done ? 1 : firstOpenSection()) + '">' + (done ? (locked() ? "View" : "Edit") : "Continue") + icon("arrowR") + "</button></div></div>";
    }

    // Documents
    if (n === 2) {
      var count = docCount();
      var docChips = DOCS.map(function (doc) {
        var ok = !!state.docs[doc.key];
        return '<span class="chip-doc' + (ok ? " ok" : "") + '">' + (ok ? icon("check") : "") + esc(doc.title.replace(" with name tag", "")) + "</span>";
      }).join("");
      return '<div class="sp-row"><span class="sp-icon">' + icon("folder") + '</span><div class="sp-body">' +
        "<h3>" + (done ? "All 8 documents submitted" : count + " of 8 documents uploaded") + "</h3>" +
        "<p>" + (done ? "Sent to admissions on " + fmt(state.submittedAt, { month: "long", day: "numeric", year: "numeric" }) + "." : "Upload every requirement, then submit them for review.") + "</p>" +
        '<div class="sp-chips">' + docChips + "</div></div>" +
        '<div class="sp-actions"><button class="btn ' + (done ? "btn-ghost" : "btn-brand") + '" type="button" data-view="documents">' + (done ? "View documents" : "Upload documents") + icon("arrowR") + "</button></div></div>";
    }

    // Review
    if (n === 3) {
      if (done) {
        return '<div class="sp-row"><span class="sp-icon">' + icon("shield") + '</span><div class="sp-body"><h3>Application approved for the entrance exam</h3><p>Your form and all 8 documents were verified by the admissions team.</p></div></div>';
      }
      return '<div class="sp-row"><span class="scan-doc" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span><div class="sp-body">' +
        "<h3>Admissions is reviewing your application</h3><p>This usually takes 3 to 5 working days. We'll email you once it's done.</p>" +
        '<ul class="review-steps"><li><span class="rs-mark">' + icon("check") + '</span>Application form received</li>' +
        '<li class="working"><span class="rs-mark"></span>Verifying your 8 documents</li>' +
        '<li class="waiting"><span class="rs-mark"></span>Exam eligibility</li></ul></div></div>';
    }

    // Exam
    if (n === 4) {
      var ed = parseDate(EXAM.date);
      var ticket = '<div class="exam-ticket"><div class="ticket-date"><span>' + ed.toLocaleDateString("en-US", { month: "short" }) + "</span><strong>" + ed.getDate() + "</strong></div>" +
        '<div class="ticket-info"><strong>' + ed.toLocaleDateString("en-US", { weekday: "long", month: "long", day: "numeric", year: "numeric" }) + "</strong>" +
        "<span>" + icon("clock") + EXAM.time + "</span><span>" + icon("pin") + esc(EXAM.venue) + "</span><span>" + icon("ticket") + "Exam permit " + EXAM.permit + "</span></div></div>";
      if (done) {
        return '<div class="sp-row"><span class="sp-icon">' + icon("pencil") + '</span><div class="sp-body"><h3>Entrance exam completed</h3><p>You took the exam on ' + fmt(EXAM.date, { month: "long", day: "numeric", year: "numeric" }) + ". Your result is ready.</p></div></div>";
      }
      return '<div class="sp-row"><span class="sp-icon">' + icon("pencil") + '</span><div class="sp-body"><h3>Your entrance exam schedule</h3>' +
        "<p>Arrive 30 minutes early. Bring the valid ID you uploaded, your exam permit, two pencils, and an eraser.</p>" + ticket + "</div></div>";
    }

    // Medical requirements (last step)
    if (n === 6) return medicalPanelHTML();

    // Result
    var first = esc(d.firstName || "applicant");
    var facts = '<div class="result-facts">' +
      '<div class="fact"><span>Program</span><strong>' + esc(d.program1 || PROGRAMS[0]) + "</strong></div>" +
      '<div class="fact"><span>Campus</span><strong>' + esc(d.campus || "Main Campus") + " · AY 2027–2028</strong></div>" +
      '<div class="fact"><span>Entrance exam</span><strong>Passed · 89th percentile</strong></div></div>';
    var steps = '<ol class="next-steps">' +
      '<li><span>1</span><strong>Accept your offer and pay the reservation fee</strong><time>by ' + fmt(keyDate("Reservation fee deadline", "2027-03-20")) + "</time></li>" +
      '<li><span>2</span><strong>Submit your medical requirements</strong><time>after accepting</time></li>' +
      '<li><span>3</span><strong>Enroll and choose your block section</strong><time>Jun 1 – 30, 2027</time></li></ol>';
    var actions = state.accepted
      ? '<div class="result-actions"><span class="accepted-banner">' + icon("check") + 'Offer accepted</span><button class="btn btn-brand" type="button" data-stage-go="6">Medical requirements' + icon("arrowR") + '</button><button class="btn btn-ghost" type="button" id="view-letter">View offer letter</button></div>'
      : '<div class="result-actions"><button class="btn btn-brand" type="button" id="accept-offer">Accept offer' + icon("arrowR") + '</button><button class="btn btn-ghost" type="button" id="view-letter">View offer letter</button></div>';
    return '<div class="result-card"><div class="result-seal">' + STAR + "</div>" +
      '<p class="eyebrow">Admission result · AY 2027–2028</p>' +
      '<h3>Congratulations, <span class="shine">' + first + "!</span></h3>" +
      "<p>You've been admitted to the " + esc(d.program1 || PROGRAMS[0]) + " program. We're excited to welcome you to Pamantasan ng Lungsod ng Pasig this August.</p>" +
      facts + steps + actions + "</div>";
  }

  function medicalPanelHTML() {
    var med = state.medical;
    if (med.status === "cleared") {
      return '<div class="sp-row"><span class="sp-icon">' + icon("shield") + '</span><div class="sp-body"><h3>Medically cleared · your admission is complete</h3>' +
        "<p>The university clinic verified your medical requirements. Watch for your enrollment schedule from June 1 to 30, 2027.</p></div></div>";
    }
    if (med.status === "submitted") {
      return '<div class="sp-row"><span class="scan-doc" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span><div class="sp-body">' +
        "<h3>The clinic is checking your medical files</h3><p>This usually takes 3 to 5 working days. We'll notify you once you're cleared, or if a file needs to be replaced.</p>" +
        '<div class="sp-chips">' + MEDICAL.map(function (m) { return '<span class="chip-doc ok">' + icon("check") + esc(m.title) + "</span>"; }).join("") + "</div></div></div>";
    }
    var count = medCount();
    return '<div class="sp-row sp-row-top"><span class="sp-icon">' + icon("shield") + '</span><div class="sp-body">' +
      "<h3>Submit your medical requirements</h3><p>This is the last step of your admission. Upload a clear PDF, JPG, or PNG of each one, up to 10 MB.</p>" +
      '<ul class="med-list">' + MEDICAL.map(function (m) {
        var f = med.files[m.key];
        return '<li class="' + (f ? "ok" : "") + '"><span class="mini-check">' + (f ? icon("check") : "") + "</span><div><strong>" + esc(m.title) + "</strong><small>" + esc(f ? f.name + " · " + fileSize(f.size) : m.desc) + "</small></div>" +
          '<label class="upload-btn" for="med-' + m.key + '">' + icon(f ? "refresh" : "upload") + (f ? "Replace" : "Upload") + "</label>" +
          '<input class="file-input med-input" type="file" id="med-' + m.key + '" data-key="' + m.key + '" accept=".pdf,.jpg,.jpeg,.png"></li>';
      }).join("") + "</ul>" +
      '<p class="upload-error" id="med-error" role="alert"></p></div>' +
      '<div class="sp-actions"><button class="btn btn-brand" type="button" id="submit-medical"' + (count < MEDICAL.length ? " disabled" : "") + ">" + icon("send") + "Submit " + count + "/" + MEDICAL.length + "</button></div></div>";
  }

  $(document).on("change", ".med-input", function () {
    var file = this.files[0], key = $(this).data("key");
    this.value = "";
    if (!file) return;
    if (["application/pdf", "image/jpeg", "image/png"].indexOf(file.type) === -1) { $("#med-error").text("Choose a PDF, JPG, or PNG file."); return; }
    if (file.size > 10 * 1024 * 1024) { $("#med-error").text("This file is over 10 MB. Compress it or choose a smaller file."); return; }
    var form = new FormData();
    form.append("file", file);
    AO.api("POST", "/api/applicant/medical/" + encodeURIComponent(key), form)
      .done(function (res) {
        applyPortal(res.data);
        renderJourney();
        showToast(MEDICAL.filter(function (m) { return m.key === key; })[0].title + " uploaded.");
      })
      .fail(function (xhr) { $("#med-error").text(AO.apiError(xhr)); });
  });
  $(document).on("click", "#submit-medical", function () {
    AO.busy($(this), AO.api("POST", "/api/applicant/medical/submit"))
      .done(function (res) {
        applyPortal(res.data);
        renderJourney();
        showToast("Medical requirements sent to the university clinic.");
      })
      .fail(function (xhr) { $("#med-error").text(AO.apiError(xhr)); });
  });
  $(document).on("click", "[data-stage-go]", function () { selectedStage = +$(this).data("stage-go"); renderJourney(); });

  function renderStagePanel(n) {
    var current = journeyCurrent();
    var html;
    if (n > current) {
      html = '<div class="locked-note"><span class="sp-icon">' + icon("lock") + "</span><p>" + esc(JOURNEY[n - 1].title) + " unlocks after " + esc(JOURNEY[n - 2].title.toLowerCase()) + ".</p></div>";
    } else {
      html = stagePanelHTML(n);
    }
    $("#stage-panel").html(html);
    if (n === 5 && state.phase === "result" && !celebrated) { celebrated = true; setTimeout(confetti, 250); }
  }

  $(document).on("click", "#accept-offer", function () {
    AO.busy($(this), AO.api("POST", "/api/applicant/offer/accept"))
      .done(function (res) {
        applyPortal(res.data);
        confetti();
        selectedStage = 6;
        renderJourney();
        showToast("Offer accepted. Last step: submit your medical requirements.");
      })
      .fail(function (xhr) { showToast(AO.apiError(xhr)); });
  });

  $(document).on("click", "#view-letter", function () {
    var d = state.data;
    var name = [d.firstName, d.middleName, d.lastName].filter(Boolean).join(" ");
    $("#offer-letter").html(
      '<p class="letter-date">' + fmt(keyDate("Release of results", "2027-02-13"), { month: "long", day: "numeric", year: "numeric" }) + "</p>" +
      "<h2>Offer of Admission</h2>" +
      "<p>Dear " + esc(name || "Applicant") + ",</p>" +
      "<p>Congratulations! On behalf of the Office of Admissions, we are pleased to offer you admission as a first-year student for the first semester of Academic Year 2027–2028.</p>" +
      '<dl class="letter-box"><dt>Applicant</dt><dd>' + esc(name) + "</dd><dt>Application ID</dt><dd>" + APP_ID + "</dd><dt>Program</dt><dd>" + esc(d.program1 || PROGRAMS[0]) + "</dd><dt>Campus</dt><dd>" + esc(d.campus || "Main Campus") + "</dd><dt>Confirm by</dt><dd>" + fmt(keyDate("Reservation fee deadline", "2027-03-20"), { month: "long", day: "numeric", year: "numeric" }) + "</dd></dl>" +
      "<p>To secure your slot, accept this offer in the applicant portal and pay the reservation fee on or before the date above. Enrollment runs from June 1 to 30, 2027. Please bring the original copies of all documents you uploaded.</p>" +
      "<p>We look forward to seeing you on campus.</p>" +
      '<div class="letter-sign"><strong>Office of Admissions</strong>Pamantasan ng Lungsod ng Pasig</div>');
    bootstrap.Modal.getOrCreateInstance(document.getElementById("offerModal")).show();
  });

  /* ---------- Overview side cards ---------- */
  function renderOverviewCards() {
    var count = docCount();
    animateCount($("#overview-doc-count"), count);
    setRing($("#overview-ring"), count, 8);
    $("#doc-mini-list").html(DOCS.map(function (doc) {
      var ok = !!state.docs[doc.key];
      return '<li class="' + (ok ? "ok" : "") + '"><span class="mini-check">' + (ok ? icon("check") : "") + "</span><span>" + esc(doc.title) + "</span></li>";
    }).join(""));
    $("#program-first").text(state.data.program1 || "Not chosen yet");
    $("#program-second").text(state.data.program2 ? "Second choice: " + state.data.program2 : "Second choice not set");
    $("#key-dates").html(KEY_DATES.map(function (k) {
      var left = daysUntil(k.date);
      var when = k.end ? fmt(k.date, { month: "short", day: "numeric" }) + " – " + parseDate(k.end).getDate() : fmt(k.date, { month: "short", day: "numeric" });
      var note = left > 1 ? "In " + left + " days" : left === 1 ? "Tomorrow" : left === 0 ? "Today" : "Passed";
      return "<li><span>" + esc(k.title) + "<small>" + note + '</small></span><time datetime="' + k.date + '">' + when + "</time></li>";
    }).join(""));
  }

  /* ==========================================================================
     MY APPLICATION (3 sections)
     ========================================================================== */
  function fillPrograms() {
    $(".program-select").each(function () {
      var $s = $(this);
      $s.find("option").slice(1).remove();   // keep "Select a program"
      PROGRAMS.forEach(function (p) { $s.append($("<option>").text(p).val(p)); });
    });
  }
  function restoreForm() {
    $("#application-form").find("[name]").each(function () {
      var v = state.data[this.name];
      $(this).val(v != null ? v : "");
    });
  }
  function collectForm() {
    $("#application-form").find("[name]").each(function () { state.data[this.name] = $(this).val(); });
  }

  var saveTimer;
  $("#application-form").on("input change", "[name]", function () {
    $("#save-state").addClass("saving").html(icon("refresh") + "Saving…");
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      collectForm();
      save();
      refreshIdentity();
      $("#save-state").removeClass("saving").html(icon("check") + "Saved on this device");
    }, 500);
  });

  function validateSection(n) {
    var ok = validateFields($('.stage[data-stage="' + n + '"]'));
    if (n === 1 && $("#birth-date").val()) {
      var age = (today() - parseDate($("#birth-date").val())) / 31557600000;
      if (age < 14 || age > 80) { setError($("#birth-date"), "Check your date of birth."); ok = false; }
    }
    if (n === 3 && $("#program1").val() && $("#program1").val() === $("#program2").val()) {
      setError($("#program2"), "Pick a different program from your first choice."); ok = false;
    }
    return ok;
  }

  function renderApplication(scroll) {
    var $list = $("#app-stepper");
    buildStepper($list, SECTIONS);
    var showComplete = sectionsDone() && !editing;
    var n = showComplete ? 0 : state.step;
    updateStepper($list, {
      done: isDone, current: n, selected: n,
      canVisit: function (i) { return !locked() && canVisitSection(i); },
      titleFor: function (i) { return SECTIONS[i - 1].title; },
      caption: showComplete ? "All 3 sections complete" : "Section " + n + " of 3 · " + SECTIONS[n - 1].title
    });

    $("#form-card").prop("hidden", showComplete);
    $("#complete-card").prop("hidden", !showComplete);
    if (showComplete) {
      $("#complete-name").text(state.data.firstName || "applicant");
      $("#edit-answers").prop("hidden", locked());
      $("#complete-card .lede").text(locked()
        ? "Your application has been sent to admissions. Follow its progress on the Overview."
        : "Your personal, academic, and program details are saved. Next, upload your 8 required documents so admissions can review your application.");
      $("#complete-card [data-view]").html(locked() ? "Go to overview" + icon("arrowR") : "Upload documents" + icon("arrowR")).attr("data-view", locked() ? "overview" : "documents").data("view", locked() ? "overview" : "documents");
      // replay the check animation
      var $svg = $("#complete-card .check-draw");
      $svg.replaceWith($svg.clone());
      return;
    }

    var s = SECTIONS[n - 1];
    $("#stage-count").text("Section " + n + " of 3");
    $("#stage-title").text(s.title);
    $("#stage-desc").text(s.desc);
    if (+$(".stage.active").data("stage") !== n) {
      $(".stage").removeClass("active");
      $('.stage[data-stage="' + n + '"]').addClass("active");
    }
    $("#btn-back").css("visibility", n === 1 ? "hidden" : "visible");
    $("#btn-next .btn-label").text(n === 3 ? "Save and finish" : "Save and continue");
    if (scroll) {
      var top = $("#app-stepper").offset().top - 90;
      if (window.scrollY > top) window.scrollTo({ top: top, behavior: "smooth" });
    }
  }

  $(document).on("click", "#app-stepper .step-dot", function () {
    var n = +$(this).data("step");
    if (locked() || !canVisitSection(n)) return;
    editing = sectionsDone();
    state.step = n;
    save();
    renderApplication(true);
  });

  $("#btn-back").on("click", function () {
    if (state.step > 1) { state.step--; save(); renderApplication(true); }
  });

  $("#edit-answers").on("click", function () { editing = true; state.step = 1; save(); renderApplication(true); });

  $("#application-form").on("submit", function (e) {
    e.preventDefault();
    var n = state.step;
    collectForm();
    if (!validateSection(n)) return;
    if (!isDone(n)) state.completed.push(n);
    save();
    refreshSummary();
    // Mark the circle done first so its connector fills, then move on
    updateStepper($("#app-stepper"), {
      done: isDone, current: n, selected: n,
      canVisit: function (i) { return canVisitSection(i); },
      titleFor: function (i) { return SECTIONS[i - 1].title; }
    });
    setTimeout(function () {
      if (n < 3) { state.step = n + 1; }
      else { editing = false; showToast("Application form saved. Next: upload your documents."); }
      save();
      renderApplication(true);
    }, 450);
  });

  /* ==========================================================================
     DOCUMENTS
     ========================================================================== */
  function docTag(doc) {
    if (state.phase === "review") return '<span class="tag tag-review">In review</span>';
    if (state.phase === "exam" || state.phase === "result") return '<span class="tag tag-verified">' + icon("check") + "Verified</span>";
    return state.docs[doc.key] ? '<span class="tag tag-uploaded">' + icon("check") + "Uploaded</span>" : '<span class="tag tag-required">Required</span>';
  }

  function docExtra(doc) {
    if (doc.extra === "photo") {
      var tag = ((state.data.lastName || "LAST NAME").toUpperCase()) + ", " + (state.data.firstName || "Given Name");
      return '<p class="doc-extra">Name tag: <span class="name-tag">' + esc(tag) + "</span></p>";
    }
    if (doc.extra === "payment") {
      return '<p class="doc-extra">Fee: <strong>' + APP_FEE + "</strong> via GCash or Dragonpay. Reference number must be readable.</p>";
    }
    return "";
  }

  function docCardHTML(doc, uploading) {
    var f = state.docs[doc.key];
    var body;
    if (f) {
      var thumb = previews[doc.key] ? '<img src="' + previews[doc.key] + '" alt="">' : icon(f.type === "application/pdf" ? "file" : doc.icon);
      var actions = locked() ? "" :
        '<div class="file-actions"><button class="icon-button doc-replace" type="button" data-key="' + doc.key + '" aria-label="Replace ' + esc(doc.title) + '" data-bs-toggle="tooltip" data-bs-title="Replace">' + icon("refresh") + "</button>" +
        '<button class="icon-button doc-remove" type="button" data-key="' + doc.key + '" aria-label="Remove ' + esc(doc.title) + '" data-bs-toggle="tooltip" data-bs-title="Remove">' + icon("trash") + "</button></div>";
      body = '<div class="file-row"><span class="file-thumb">' + thumb + '</span><span class="file-meta"><strong>' + esc(f.name) + "</strong><span>" +
        (uploading ? "Uploading…" : fileSize(f.size)) + "</span>" +
        (uploading ? '<span class="upload-progress"><span></span></span>' : "") + "</span>" + actions + "</div>";
    } else {
      body = '<label class="upload-btn" for="file-' + doc.key + '" title="' + (doc.imageOnly ? "JPG or PNG" : "PDF, JPG, or PNG") + ', 10 MB max">' + icon("upload") + "Upload</label>";
    }
    return '<article class="doc-card' + (f ? " has-file" : "") + '" data-key="' + doc.key + '">' +
      '<span class="doc-icon tone-' + doc.tone + '">' + icon(doc.icon) + "</span>" +
      '<div class="doc-main"><div class="doc-title-row"><h3>' + esc(doc.title) + "</h3>" + docTag(doc) + "</div>" +
      '<p class="doc-desc">' + esc(doc.desc) + "</p>" + docExtra(doc) +
      '<p class="upload-error" id="err-' + doc.key + '" role="alert"></p></div>' +
      '<div class="doc-action">' + body + "</div>" +
      '<input class="file-input" type="file" id="file-' + doc.key + '" data-key="' + doc.key + '" accept="' + (doc.imageOnly ? ".jpg,.jpeg,.png" : ".pdf,.jpg,.jpeg,.png") + '"' + (locked() ? " disabled" : "") + ">" +
      "</article>";
  }

  function renderDocs() {
    $(".tooltip").remove();
    $("#doc-grid").html(DOCS.map(function (doc, i) {
      return '<li class="reveal-in" style="--d:' + (120 + i * 40) + 'ms">' + docCardHTML(doc) + "</li>";
    }).join(""));
    initTooltips("#doc-grid");
    renderDocSummary();
  }

  function renderDocCard(key, uploading) {
    var doc = DOCS.filter(function (d) { return d.key === key; })[0];
    var $col = $('#doc-grid .doc-card[data-key="' + key + '"]').parent();
    $col.find('[data-bs-toggle="tooltip"]').each(function () { var t = bootstrap.Tooltip.getInstance(this); if (t) t.dispose(); });
    $col.removeClass("reveal-in").html(docCardHTML(doc, uploading));
    initTooltips($col);
  }

  function renderDocSummary() {
    var count = docCount();
    animateCount($("#docs-count"), count);
    setRing($("#docs-ring"), count, 8);
    var headline, sub;
    if (state.phase === "review") { headline = "Submitted for review"; sub = "Admissions is checking your documents. You'll be notified when they're verified."; }
    else if (locked()) { headline = "All documents verified"; sub = "Every requirement passed review. Keep the originals for enrollment."; }
    else if (count === 8) { headline = "Ready to submit"; sub = sectionsDone() ? "All 8 documents are in. Send them to admissions for review." : "All 8 documents are in. Finish My application before submitting."; }
    else { headline = (8 - count) + " document" + (8 - count === 1 ? "" : "s") + " to go"; sub = "Upload every requirement, then send them to admissions for review."; }
    $("#docs-headline").text(headline);
    $("#docs-subline").text(sub);
    $("#submit-docs").prop("disabled", locked() || count < 8 || !sectionsDone()).prop("hidden", locked());
    $("#nav-doc-count").text(count + "/8").toggleClass("full", count === 8);
  }

  function handleFile(file, key) {
    if (!file || locked()) return;
    var doc = DOCS.filter(function (d) { return d.key === key; })[0];
    var allowed = doc.imageOnly ? ["image/jpeg", "image/png"] : ["application/pdf", "image/jpeg", "image/png"];
    var $err = $("#err-" + key);
    if (allowed.indexOf(file.type) === -1) { $err.text(doc.imageOnly ? "Choose a JPG or PNG photo." : "Choose a PDF, JPG, or PNG file."); return; }
    if (file.size > 10 * 1024 * 1024) { $err.text("This file is over 10 MB. Compress it or choose a smaller file."); return; }

    // ON HOLD (PROGRESS.md, H1): POST /api/applicant/documents/{requirement key} once the cards are mapped
    if (previews[key]) URL.revokeObjectURL(previews[key]);
    if (file.type.indexOf("image/") === 0) previews[key] = URL.createObjectURL(file); else delete previews[key];
    state.docs[key] = { name: file.name, size: file.size, type: file.type };
    save();
    renderDocCard(key, true);
    setTimeout(function () {
      renderDocCard(key, false);
      renderDocSummary();
      refreshSummary();
      showToast(doc.title + " uploaded.");
    }, 850);
  }

  $(document).on("change", ".file-input", function () {
    handleFile(this.files[0], $(this).data("key"));
    this.value = "";
  });
  $(document).on("click", ".doc-replace", function () { $("#file-" + $(this).data("key")).trigger("click"); });
  $(document).on("click", ".doc-remove", function () {
    var key = $(this).data("key");
    var t = bootstrap.Tooltip.getInstance(this); if (t) t.dispose();
    delete state.docs[key];
    if (previews[key]) { URL.revokeObjectURL(previews[key]); delete previews[key]; }
    save();
    renderDocCard(key, false);
    renderDocSummary();
    refreshSummary();
  });
  $(document).on("dragover", "#doc-grid .doc-card", function (e) { if (locked()) return; e.preventDefault(); $(this).addClass("drag-over"); })
    .on("dragleave", "#doc-grid .doc-card", function () { $(this).removeClass("drag-over"); })
    .on("drop", "#doc-grid .doc-card", function (e) {
      e.preventDefault();
      $(this).removeClass("drag-over");
      var file = e.originalEvent.dataTransfer.files[0];
      handleFile(file, $(this).data("key"));
    });

  var submitModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("submitModal"));
  $("#submit-docs").on("click", function () { submitModal.show(); });
  $("#confirm-submit").on("click", function () {
    // ON HOLD (PROGRESS.md, H1): POST /api/applicant/submit once the cards upload to the server
    withLoading($(this), 800, function () {
      submitModal.hide();
      submitApplication();
      showToast("Sent to admissions. We'll let you know once it's reviewed.");
    });
  });

  function submitApplication() {
    state.phase = "review";
    state.submittedAt = toISO(today());
    save();
    renderDocs();
    refreshSummary();
  }

  /* ==========================================================================
     SHARED REFRESH
     ========================================================================== */
  function refreshIdentity() {
    var d = state.data;
    var name = [d.firstName, d.lastName].filter(Boolean).join(" ") || "Applicant";
    var ini = ((d.firstName || "").charAt(0) + (d.lastName || "").charAt(0)).toUpperCase() || "A";
    $("#sidebar-name, #topbar-name").text(name);
    $("#sidebar-avatar, #topbar-avatar").text(ini);
    // The application number from the server (the markup holds a sample one)
    if (APP_ID) {
      $(".id-chip").contents().last()[0].textContent = APP_ID;
      $(".application-id strong").text(APP_ID);
    }
    var h = new Date().getHours();
    $("#greeting").text((h < 12 ? "Good morning" : h < 18 ? "Good afternoon" : "Good evening") + ", " + (d.firstName || "there"));
  }

  function refreshSummary() {
    $("#nav-app-count").text(state.completed.length + "/3").toggleClass("full", sectionsDone());
    $("#nav-doc-count").text(docCount() + "/8").toggleClass("full", docCount() === 8);
    renderOverviewCards();
    if ($("#view-overview").hasClass("active")) renderJourney();
  }

  function refreshAll() {
    if (state.step > firstOpenSection() && !isDone(state.step)) state.step = firstOpenSection();
    restoreForm();
    refreshIdentity();
    refreshSummary();
    renderJourney();
    renderApplication(false);
    renderDocs();
    setTimeout(moveNavIndicator, 0);
  }

  /* ==========================================================================
     DEMO CONTROLS — delete this block and the .demo-strip markup for production
     ========================================================================== */
  var SAMPLE = {
    1: { middleName: "Reyes", birthDate: "2009-03-14", phone: "+63 917 123 4567", sex: "Male", civilStatus: "Single", address: "12 Mabini St., Brgy. San Isidro", city: "Quezon City", province: "Metro Manila", zip: "1100", guardianName: "Teresa Mendoza", guardianPhone: "+63 918 765 4321" },
    2: { lastSchool: "Rizal National High School", schoolType: "Public", strand: "STEM", gradYear: "2027 (expected)", gwa: "92.40" },
    3: { program2: "Bachelor of Science in Computer Science", campus: "Main Campus" }
  };
  $("#demo-advance").on("click", function () {
    var n = journeyCurrent();
    if (n === 1) {
      [1, 2, 3].forEach(function (sec) {
        $.each(SAMPLE[sec], function (k, v) { if (!state.data[k]) state.data[k] = v; });
        if (!isDone(sec)) state.completed.push(sec);
      });
      state.step = 3;
      editing = false;
    } else if (n === 2) {
      DOCS.forEach(function (doc) {
        if (!state.docs[doc.key]) state.docs[doc.key] = { name: doc.key + (doc.imageOnly ? ".jpg" : ".pdf"), size: 400000 + Math.round(Math.random() * 900000), type: doc.imageOnly ? "image/jpeg" : "application/pdf" };
      });
      state.phase = "review";
      state.submittedAt = toISO(today());
    } else if (n === 3) {
      state.phase = "exam";
    } else if (n === 4) {
      state.phase = "result";
    } else if (n === 5) {
      state.accepted = true;
    } else if (state.medical.status === "none") {
      MEDICAL.forEach(function (m) { if (!state.medical.files[m.key]) state.medical.files[m.key] = { name: m.key + ".pdf", size: 300000 + Math.round(Math.random() * 600000), type: "application/pdf" }; });
      state.medical.status = "submitted";
    } else if (state.medical.status === "submitted") {
      state.medical.status = "cleared";   // in production the clinic clears this from the admission office panel
    } else {
      showToast("Admission complete. There are no more stages.");
      return;
    }
    save();
    selectedStage = journeyCurrent();
    restoreForm();
    refreshSummary();
    renderDocs();
    renderApplication(false);
    renderJourney();
  });
  $("#demo-reset").on("click", function () {
    var keep = { firstName: state.data.firstName, lastName: state.data.lastName, email: state.data.email };
    Object.keys(previews).forEach(function (k) { URL.revokeObjectURL(previews[k]); });
    previews = {};
    state = freshState();
    $.extend(state.data, keep);
    editing = false; selectedStage = null; celebrated = false;
    save();
    $("#application-form .is-invalid").each(function () { clearError($(this)); });
    refreshAll();
    showToast("Demo reset. You're back at step 1.");
  });

  /* ==========================================================================
     BOOT
     ========================================================================== */
  paintIcons();
  initTooltips(document);
  var remembered = readJSON("local", REMEMBER_KEY);
  if (remembered) { $("#signin-email").val(remembered); $("#remember-me").prop("checked", true); }
  showAuth("signin");
  // Still signed in in this tab: reload the application and go straight in
  if (AO.getToken()) loadPortal().done(enterApp).fail(function () { AO.setToken(null); });
});
