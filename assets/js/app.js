/* ==========================================================================
   ADMITONE — Applicant Portal (jQuery 3.7 + Bootstrap 5.3)
   Front-end demo: sign-in, form data, uploads and admission decisions are
   simulated in the browser. Look for "BACKEND:" comments for real API calls.
   ========================================================================== */
$(function () {
  "use strict";

  /* ==========================================================================
     CONFIG
     ========================================================================== */
  var STORAGE_KEY = "admitone.portal.v3";
  var SESSION_KEY = "admitone.session";
  var REMEMBER_KEY = "admitone.rememberEmail";
  var APP_ID = "AD-2026-08421";

  // My application: three sections only
  var SECTIONS = [
    { id: 1, title: "Personal information", short: "Personal", desc: "Tell us who you are and how to reach you." },
    { id: 2, title: "Academic background", short: "Academic", desc: "Your most recent school and grades." },
    { id: 3, title: "Program choice", short: "Program", desc: "Choose the programs and campus you're applying to." }
  ];

  // Overview journey: seven stages
  var JOURNEY = [
    { id: 1, short: "Personal", title: "Personal information", icon: "user" },
    { id: 2, short: "Academic", title: "Academic background", icon: "book" },
    { id: 3, short: "Program", title: "Program choice", icon: "star" },
    { id: 4, short: "Documents", title: "Required documents", icon: "folder" },
    { id: 5, short: "Review", title: "Admissions review", icon: "search" },
    { id: 6, short: "Exam", title: "Entrance exam", icon: "pencil" },
    { id: 7, short: "Result", title: "Admission result", icon: "award" }
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

  var PROGRAMS = [
    "Bachelor of Science in Information Technology",
    "Bachelor of Science in Computer Science",
    "Bachelor of Science in Information Systems",
    "Bachelor of Science in Business Administration",
    "Bachelor of Arts in Communication",
    "Bachelor of Science in Psychology",
    "Bachelor of Science in Nursing"
  ];

  // Placeholder dates for AY 2027–2028 — replace with the official calendar
  var KEY_DATES = [
    { title: "Application deadline", date: "2026-11-28" },
    { title: "Entrance exam days", date: "2026-12-05", end: "2026-12-13" },
    { title: "Release of results", date: "2027-02-13" },
    { title: "Reservation fee deadline", date: "2027-03-20" }
  ];
  var EXAM = { date: "2026-12-05", time: "8:00 AM – 12:00 NN", venue: "Main Campus · Assessment Center, Room 204", permit: "EX-27-08421" };
  var APP_FEE = "₱500";

  /* ==========================================================================
     ICONS (inline SVG, inherit currentColor)
     ========================================================================== */
  var ICONS = {
    home: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>',
    file: '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
    folder: '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H9l2 2.5h8.5A1.5 1.5 0 0 1 21 9v9.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
    users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.4 3.6-5 6.5-5s5.3 1.6 6.5 5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 15c1.9.6 3 2.3 3.5 5"/>',
    idcard: '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2.2"/><path d="M5.8 16c.7-1.4 1.8-2 3.2-2s2.5.6 3.2 2M14.5 10h4M14.5 13.5h3"/>',
    stamp: '<path d="M9 3h6l-1 6h-4z"/><path d="M4 13h16v4H4zM6 21h12"/>',
    shield: '<path d="M12 3l8 3v6c0 4.5-3.3 7.8-8 9-4.7-1.2-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
    scroll: '<path d="M7 3h11a2 2 0 0 1 2 2v2h-4"/><path d="M16 7v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2h10"/><path d="M7 3a2 2 0 0 0-2 2v12M9 8h4M9 12h4"/>',
    camera: '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
    wallet: '<path d="M4 7h15a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a2 2 0 0 1 2-2h11v3"/><path d="M15.5 13.5h.01"/>',
    book: '<path d="M4 5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2z"/><path d="M4 20a2 2 0 0 0 2 1h13"/>',
    search: '<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/>',
    pencil: '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
    award: '<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 21l5-3 5 3-1.5-7"/>',
    logout: '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
    menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
    check: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    arrowR: '<path d="M5 12h14M13 6l6 6-6 6"/>',
    arrowL: '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    upload: '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>',
    send: '<path d="M21 3 10 14"/><path d="M21 3l-7 18-4-7-7-4z"/>',
    refresh: '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 5v6h-6"/>',
    trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    eyeOff: '<path d="M3 3l18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7a9.6 9.6 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    pin: '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    lock: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
    help: '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.3-1 .9-1 1.7M12 17h.01"/>',
    ticket: '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v8a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2z"/><path d="M14 6v12" stroke-dasharray="2 2"/>'
  };
  var STAR = '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12 1.5c.7 5.6 3.4 8.6 10.5 10.5-7.1 1.9-9.8 4.9-10.5 10.5-.7-5.6-3.4-8.6-10.5-10.5C8.6 10.1 11.3 7.1 12 1.5z"/></svg>';

  function icon(name) {
    if (name === "star") return STAR;
    return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + (ICONS[name] || "") + "</svg>";
  }
  function paintIcons(root) {
    $(root || document).find("[data-icon]").each(function () {
      if (!this.firstElementChild) this.innerHTML = icon(this.getAttribute("data-icon"));
    });
  }

  /* ==========================================================================
     HELPERS
     ========================================================================== */
  function store(kind) { try { return kind === "session" ? window.sessionStorage : window.localStorage; } catch (e) { return null; } }
  function readJSON(kind, key) { try { var s = store(kind); var v = s && s.getItem(key); return v ? JSON.parse(v) : null; } catch (e) { return null; } }
  function writeJSON(kind, key, value) { try { var s = store(kind); if (s) s.setItem(key, JSON.stringify(value)); } catch (e) { /* storage blocked */ } }
  function removeKey(kind, key) { try { var s = store(kind); if (s) s.removeItem(key); } catch (e) { /* storage blocked */ } }

  function esc(str) { return String(str == null ? "" : str).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); }
  function parseDate(iso) { var p = iso.split("-"); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function today() { var d = new Date(); return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
  function toISO(d) { return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0"); }
  function fmt(iso, opts) { return parseDate(iso).toLocaleDateString("en-US", opts || { month: "short", day: "numeric", year: "numeric" }); }
  function daysUntil(iso) { return Math.round((parseDate(iso) - today()) / 86400000); }
  function fileSize(bytes) { return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + " MB" : Math.max(1, Math.round(bytes / 1024)) + " KB"; }
  function reducedMotion() { return window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches; }

  /* Count a number up or down inside an element */
  function animateCount($el, to) {
    var from = parseInt($el.text(), 10) || 0;
    if (from === to || reducedMotion()) { $el.text(to); return; }
    $({ v: from }).animate({ v: to }, { duration: 700, easing: "swing", step: function (now) { $el.text(Math.round(now)); }, complete: function () { $el.text(to); } });
  }

  /* Progress ring (r = 52 → circumference 326.7) */
  function setRing($ring, value, total) {
    var c = 326.7;
    $ring.find(".ring-fill").css("stroke-dashoffset", c * (1 - value / total));
    $ring.toggleClass("full", value === total);
  }

  /* ---------- Toast (Bootstrap) ---------- */
  var toast = bootstrap.Toast.getOrCreateInstance(document.getElementById("portal-toast"), { delay: 3200 });
  function showToast(message) { $("#toast-message").text(message); toast.show(); }

  /* ---------- Button ripple ---------- */
  $(document).on("pointerdown", ".btn", function (e) {
    if (reducedMotion()) return;
    var rect = this.getBoundingClientRect();
    var size = Math.max(rect.width, rect.height);
    $('<span class="ripple"></span>').css({ width: size, height: size, left: e.clientX - rect.left - size / 2, top: e.clientY - rect.top - size / 2 })
      .appendTo(this).on("animationend", function () { $(this).remove(); });
  });

  /* ---------- Confetti (canvas, palette colours) ---------- */
  var confettiCanvas = document.getElementById("confetti");
  var confettiCtx = confettiCanvas.getContext("2d");
  function confetti() {
    if (reducedMotion()) return;
    var colors = ["#7B75C9", "#80A4D4", "#9FE2EE", "#8fd3a8", "#f2c76e", "#5f58b8"];
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
      accepted: false
    };
  }
  var state = $.extend(true, freshState(), readJSON("local", STORAGE_KEY) || {});
  var previews = {};           // object URLs for image thumbnails (this session only)
  var editing = false;         // reopened the form after finishing it
  var selectedStage = null;    // stage shown in the overview detail panel
  var celebrated = false;      // confetti already played for the result this visit

  function save() { writeJSON("local", STORAGE_KEY, state); }
  function isDone(n) { return state.completed.indexOf(n) !== -1; }
  function sectionsDone() { return state.completed.length >= SECTIONS.length; }
  function firstOpenSection() { for (var i = 1; i <= SECTIONS.length; i++) if (!isDone(i)) return i; return SECTIONS.length; }
  function canVisitSection(n) { return n <= firstOpenSection() || isDone(n); }
  function docCount() { return DOCS.filter(function (d) { return state.docs[d.key]; }).length; }
  function locked() { return state.phase !== "draft"; }

  function journeyDone(n) {
    if (n <= 3) return isDone(n);
    if (n === 4) return state.phase !== "draft";
    if (n === 5) return state.phase === "exam" || state.phase === "result";
    if (n === 6) return state.phase === "result";
    return state.phase === "result" && state.accepted;
  }
  function journeyCurrent() { for (var i = 1; i <= 7; i++) if (!journeyDone(i)) return i; return 7; }
  function journeyPercent() { var n = 0; for (var i = 1; i <= 7; i++) if (journeyDone(i)) n++; return Math.round(n / 7 * 100); }

  /* ==========================================================================
     AUTH (simulated)
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
    removeKey("session", SESSION_KEY);
    $(".tooltip").remove();
    $("#app").prop("hidden", true);
    $("#auth-screen").prop("hidden", false);
    $("#signin-password").val("");
    showAuth("signin");
    window.scrollTo(0, 0);
  }

  function withLoading($btn, ms, done) {
    $btn.addClass("loading").prop("disabled", true);
    setTimeout(function () { $btn.removeClass("loading").prop("disabled", false); done(); }, ms);
  }

  $("#tab-signin").on("click", function () { showAuth("signin"); });
  $("#tab-signup").on("click", function () { showAuth("signup"); });
  $("#forgot-link").on("click", function () { $("#forgot-email").val($("#signin-email").val()); $("#forgot-note").text(""); showAuth("forgot"); });
  $(".back-to-signin").on("click", function () { showAuth("signin"); });

  $(document).on("click", ".pw-toggle", function () {
    var $input = $("#" + $(this).data("target"));
    var show = $input.attr("type") === "password";
    $input.attr("type", show ? "text" : "password");
    $(this).attr({ "aria-pressed": show, "aria-label": show ? "Hide password" : "Show password" }).html(icon(show ? "eyeOff" : "eye"));
  });

  $("#signin-form").on("submit", function (e) {
    e.preventDefault();
    if (!validateFields($(this))) return;
    var email = $("#signin-email").val().trim();
    if ($("#remember-me").is(":checked")) writeJSON("local", REMEMBER_KEY, email); else removeKey("local", REMEMBER_KEY);
    // BACKEND: replace the timeout with POST /api/auth/login { email, password }
    withLoading($("#signin-submit"), 800, function () {
      if (!readJSON("local", STORAGE_KEY)) state.data.email = email;
      writeJSON("session", SESSION_KEY, { email: email, at: Date.now() });
      save();
      enterApp();
    });
  });

  $("#signup-form").on("submit", function (e) {
    e.preventDefault();
    var ok = validateFields($(this));
    if ($("#signup-password").val() && $("#signup-confirm").val() !== $("#signup-password").val()) { setError($("#signup-confirm"), "Passwords don't match."); ok = false; }
    if (!ok) return;
    // BACKEND: replace with POST /api/auth/register
    withLoading($("#signup-submit"), 800, function () {
      state = freshState();
      state.data.firstName = $("#signup-first").val().trim();
      state.data.lastName = $("#signup-last").val().trim();
      state.data.email = $("#signup-email").val().trim();
      state.data.phone = $("#signup-phone").val().trim();
      save();
      writeJSON("session", SESSION_KEY, { email: state.data.email, at: Date.now() });
      enterApp();
      showToast("Account created. Let's start your application.");
    });
  });

  $("#forgot-form").on("submit", function (e) {
    e.preventDefault();
    if (!validateFields($(this))) return;
    // BACKEND: POST /api/auth/forgot-password
    $("#forgot-note").text("If an account exists for " + $("#forgot-email").val().trim() + ", a reset link is on its way.");
  });

  $(".logout-button").on("click", function () { leaveApp(); showToast("You've been signed out."); });

  /* ==========================================================================
     VALIDATION (Bootstrap .is-invalid + .invalid-feedback)
     ========================================================================== */
  function labelFor(el) {
    var txt = $("label[for='" + el.id + "']").first().clone().children().remove().end().text().trim();
    return txt || "This field";
  }
  function setError($input, msg) {
    var errId = $input.attr("id") + "-error";
    $input.addClass("is-invalid").attr({ "aria-invalid": "true", "aria-describedby": errId });
    $("#" + errId).text(msg).addClass("d-block");
  }
  function clearError($input) {
    $input.removeClass("is-invalid").removeAttr("aria-invalid");
    $("#" + $input.attr("id") + "-error").text("").removeClass("d-block");
  }
  function messageFor(el) {
    var v = el.validity;
    if (el.type === "checkbox" && v.valueMissing) return "Please tick this box to continue.";
    if (v.valueMissing) return labelFor(el) + " is required.";
    if (v.typeMismatch && el.type === "email") return "Enter a valid email, like name@email.com.";
    if (v.patternMismatch) return el.getAttribute("data-msg-pattern") || "Check the format of this field.";
    if (v.rangeUnderflow || v.rangeOverflow || v.stepMismatch) return el.getAttribute("data-msg-range") || "Enter a value in the allowed range.";
    return "Check this field.";
  }
  function validateFields($scope) {
    var ok = true;
    $scope.find("input, select").filter(":not([type=file]):not([readonly])").each(function () {
      if (!this.id) return;
      if (this.minLength > 0 && this.value && this.value.length < this.minLength) { setError($(this), "Use at least " + this.minLength + " characters."); ok = false; return; }
      if (!this.checkValidity()) { setError($(this), messageFor(this)); ok = false; } else clearError($(this));
    });
    if (!ok) $scope.find(".is-invalid").first().trigger("focus");
    return ok;
  }
  $(document).on("input change", ".is-invalid", function () { if (this.checkValidity()) clearError($(this)); });

  /* ==========================================================================
     NAVIGATION
     ========================================================================== */
  var PAGE_LABELS = { overview: "Overview", application: "My application", documents: "Documents" };

  function moveNavIndicator() {
    var $active = $(".sidebar-nav .nav-link.active");
    if ($active.length) $(".nav-indicator").css("transform", "translateY(" + $active[0].offsetTop + "px)");
  }

  function revealView($view) {
    $view.find("[data-reveal]").each(function (i) {
      $(this).removeClass("reveal-in");
      void this.offsetWidth;
      $(this).css("--d", (i * 80) + "ms").addClass("reveal-in");
    });
  }

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

  function closeSidebar() {
    $("#sidebar").removeClass("open");
    $("#sidebar-backdrop").removeClass("show");
    $(".menu-toggle").attr("aria-expanded", "false");
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
  $(".menu-toggle").on("click", function () {
    var open = !$("#sidebar").hasClass("open");
    $("#sidebar").toggleClass("open", open);
    $("#sidebar-backdrop").toggleClass("show", open);
    $(this).attr("aria-expanded", open ? "true" : "false");
  });
  $("#sidebar-backdrop").on("click", closeSidebar);
  $(window).on("resize", moveNavIndicator);

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

    var subs = { 5: "Under review", 6: "Scheduled", 7: "Released" };
    updateStepper($list, {
      done: journeyDone, current: current, selected: selectedStage,
      canVisit: function (n) { return n <= current; },
      titleFor: function (n) { return JOURNEY[n - 1].title; },
      subFor: function (n, status) { return status === "In progress" && subs[n] ? subs[n] : status; },
      caption: "Step " + selectedStage + " of 7 · " + JOURNEY[selectedStage - 1].title
    });

    var pct = journeyPercent();
    $("#journey-progress-bar").css("width", pct + "%");
    $("#journey-progress").attr("aria-valuenow", pct);
    animateCount($("#journey-percent"), pct);

    var titles = { draft: "Step " + current + " of 7 · " + JOURNEY[current - 1].title, review: "Your application is under review", exam: "You're scheduled for the entrance exam", result: state.accepted ? "Offer accepted · See you in August" : "Your admission result is in" };
    $("#journey-title").text(titles[state.phase]);
    var pill = { draft: ["draft", "Draft"], review: ["review", "Under review"], exam: ["exam", "Exam scheduled"], result: ["admitted", "Admitted"] }[state.phase];
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

    // Sections 1–3
    if (n <= 3) {
      var summaries = {
        1: [d.firstName && (d.firstName + " " + (d.lastName || "")), d.email, d.phone, d.city],
        2: [d.lastSchool, d.strand, d.gwa && ("Average " + d.gwa)],
        3: [d.program1, d.campus]
      };
      var chips = summaries[n].filter(Boolean).map(function (t) { return '<span class="chip-doc">' + esc(t) + "</span>"; }).join("");
      return '<div class="sp-row"><span class="sp-icon">' + icon(s.icon) + '</span><div class="sp-body">' +
        "<h3>" + esc(s.title) + (done ? " · complete" : "") + "</h3>" +
        "<p>" + (done ? "Saved in My application." : SECTIONS[n - 1].desc) + "</p>" +
        (chips ? '<div class="sp-chips">' + chips + "</div>" : "") +
        '</div><div class="sp-actions"><button class="btn ' + (done ? "btn-ghost" : "btn-brand") + '" type="button" data-goto="' + n + '">' + (done ? (locked() ? "View" : "Edit") : "Continue") + icon("arrowR") + "</button></div></div>";
    }

    // Documents
    if (n === 4) {
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
    if (n === 5) {
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
    if (n === 6) {
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

    // Result
    var first = esc(d.firstName || "applicant");
    var facts = '<div class="result-facts">' +
      '<div class="fact"><span>Program</span><strong>' + esc(d.program1 || PROGRAMS[0]) + "</strong></div>" +
      '<div class="fact"><span>Campus</span><strong>' + esc(d.campus || "Main Campus") + " · AY 2027–2028</strong></div>" +
      '<div class="fact"><span>Entrance exam</span><strong>Passed · 89th percentile</strong></div></div>';
    var steps = '<ol class="next-steps">' +
      '<li><span>1</span><strong>Accept your offer and pay the reservation fee</strong><time>by ' + fmt("2027-03-20") + "</time></li>" +
      '<li><span>2</span><strong>Enroll and choose your block section</strong><time>Jun 1 – 30, 2027</time></li>' +
      '<li><span>3</span><strong>Freshman orientation</strong><time>' + fmt("2027-08-02") + "</time></li></ol>";
    var actions = state.accepted
      ? '<div class="result-actions"><span class="accepted-banner">' + icon("check") + 'Offer accepted</span><button class="btn btn-ghost" type="button" id="view-letter">View offer letter</button></div>'
      : '<div class="result-actions"><button class="btn btn-brand" type="button" id="accept-offer">Accept offer' + icon("arrowR") + '</button><button class="btn btn-ghost" type="button" id="view-letter">View offer letter</button></div>';
    return '<div class="result-card"><div class="result-seal">' + STAR + "</div>" +
      '<p class="eyebrow">Admission result · AY 2027–2028</p>' +
      '<h3>Congratulations, <span class="shine">' + first + "!</span></h3>" +
      "<p>You've been admitted to the " + esc(d.program1 || PROGRAMS[0]) + " program. We're excited to welcome you to ADMITONE this August.</p>" +
      facts + steps + actions + "</div>";
  }

  function renderStagePanel(n) {
    var current = journeyCurrent();
    var html;
    if (n > current) {
      html = '<div class="locked-note"><span class="sp-icon">' + icon("lock") + "</span><p>" + esc(JOURNEY[n - 1].title) + " unlocks after " + esc(JOURNEY[n - 2].title.toLowerCase()) + ".</p></div>";
    } else {
      html = stagePanelHTML(n);
    }
    $("#stage-panel").html(html);
    if (n === 7 && state.phase === "result" && !celebrated) { celebrated = true; setTimeout(confetti, 250); }
  }

  $(document).on("click", "#accept-offer", function () {
    // BACKEND: POST /api/applications/:id/accept
    var $btn = $(this);
    withLoading($btn, 700, function () {
      state.accepted = true;
      save();
      confetti();
      renderJourney();
      showToast("Offer accepted. Welcome to ADMITONE!");
    });
  });

  $(document).on("click", "#view-letter", function () {
    var d = state.data;
    var name = [d.firstName, d.middleName, d.lastName].filter(Boolean).join(" ");
    $("#offer-letter").html(
      '<p class="letter-date">' + fmt("2027-02-13", { month: "long", day: "numeric", year: "numeric" }) + "</p>" +
      "<h2>Offer of Admission</h2>" +
      "<p>Dear " + esc(name || "Applicant") + ",</p>" +
      "<p>Congratulations! On behalf of the Office of Admissions, we are pleased to offer you admission as a first-year student for the first semester of Academic Year 2027–2028.</p>" +
      '<dl class="letter-box"><dt>Applicant</dt><dd>' + esc(name) + "</dd><dt>Application ID</dt><dd>" + APP_ID + "</dd><dt>Program</dt><dd>" + esc(d.program1 || PROGRAMS[0]) + "</dd><dt>Campus</dt><dd>" + esc(d.campus || "Main Campus") + "</dd><dt>Confirm by</dt><dd>" + fmt("2027-03-20", { month: "long", day: "numeric", year: "numeric" }) + "</dd></dl>" +
      "<p>To secure your slot, accept this offer in the applicant portal and pay the reservation fee on or before the date above. Enrollment runs from June 1 to 30, 2027. Please bring the original copies of all documents you uploaded.</p>" +
      "<p>We look forward to seeing you on campus.</p>" +
      '<div class="letter-sign"><strong>Office of Admissions</strong>ADMITONE University</div>');
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

  function initTooltips(root) {
    $(root).find('[data-bs-toggle="tooltip"]').each(function () { bootstrap.Tooltip.getOrCreateInstance(this); });
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

    // BACKEND: upload the file to storage here (e.g. POST /api/applications/:id/documents/:key)
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
    // BACKEND: POST /api/applications/:id/submit
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
    $("#sidebar-name").text(name);
    $("#sidebar-avatar, #topbar-avatar").text(ini);
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
    if (n <= 3) {
      $.each(SAMPLE[n], function (k, v) { if (!state.data[k]) state.data[k] = v; });
      if (!isDone(n)) state.completed.push(n);
      state.step = Math.min(n + 1, 3);
      editing = false;
    } else if (n === 4) {
      DOCS.forEach(function (doc) {
        if (!state.docs[doc.key]) state.docs[doc.key] = { name: doc.key + (doc.imageOnly ? ".jpg" : ".pdf"), size: 400000 + Math.round(Math.random() * 900000), type: doc.imageOnly ? "image/jpeg" : "application/pdf" };
      });
      state.phase = "review";
      state.submittedAt = toISO(today());
    } else if (n === 5) {
      state.phase = "exam";
    } else if (n === 6) {
      state.phase = "result";
    } else {
      showToast("You're at the last stage. Accept the offer to finish.");
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
  fillPrograms();
  initTooltips(document);
  var remembered = readJSON("local", REMEMBER_KEY);
  if (remembered) { $("#signin-email").val(remembered); $("#remember-me").prop("checked", true); }
  if (readJSON("session", SESSION_KEY)) enterApp(); else showAuth("signin");
});