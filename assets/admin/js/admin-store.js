/* ==========================================================================
   PLP Online Admission — Admission Office: configuration, demo data and state
   Needs assets/shared/js/core.js (window.AO). Exposes window.ADM.
   Everything is kept in localStorage for the prototype. Look for "BACKEND:"
   comments for the Laravel API calls that replace each piece.
   ========================================================================== */
(function ($) {
  "use strict";

  var STORAGE_KEY = "admitone.admin.v2";

  /* ==========================================================================
     CONFIG (edit to match the official admission period)
     ========================================================================== */
  // Who is signed in depends on the page: <body data-role="nurse"> is the
  // clinic portal (nurse/index.html); everything else is the admission office.
  // BACKEND: comes from the JWT role claim instead.
  var STAFF_BY_ROLE = {
    admin: { name: "Maria Santos", initials: "MS", role: "Admission Personnel", email: "admissions@plpasig.edu.ph" },
    nurse: { name: "L. Bautista, RN", initials: "LB", role: "University Clinic Nurse", email: "clinic@plpasig.edu.ph" }
  };
  var ROLE = document.body.getAttribute("data-role") === "nurse" ? "nurse" : "admin";
  var STAFF = STAFF_BY_ROLE[ROLE];
  var OFFICE = STAFF_BY_ROLE.admin, NURSE = STAFF_BY_ROLE.nurse;   // used by the demo data
  var REVIEW_SLA_DAYS = 3;        // working days to review a new application
  var PASSING_SCORE = 60;         // internal; scores are never shown to applicants
  var PAGE_SIZE = 12;

  var STATUSES = {
    draft: { label: "Draft", pill: "draft" },
    submitted: { label: "Submitted", pill: "review" },
    resubmit: { label: "Requires resubmission", pill: "resubmit" },
    for_exam: { label: "For exam", pill: "exam" },
    passed: { label: "Passed", pill: "admitted" },
    failed: { label: "Did not pass", pill: "failed" }
  };
  var STATUS_ORDER = ["draft", "submitted", "resubmit", "for_exam", "passed", "failed"];

  var DOC_STATUS = {
    missing: { label: "Not uploaded", tag: "tag-missing" },
    pending: { label: "For review", tag: "tag-review" },
    verified: { label: "Verified", tag: "tag-verified" },
    resubmit: { label: "Resubmit", tag: "tag-resubmit" }
  };

  // Freshmen documentary requirements. "when" decides who must upload it.
  var DOC_TYPES = [
    { key: "psa", title: "PSA Birth Certificate", when: "all", icon: "scroll" },
    { key: "parentId", title: "Parent's government-issued ID", when: "all", icon: "users" },
    { key: "affidavit", title: "Notarized affidavit of guardianship", when: "guardian", icon: "stamp", note: "Applicants with a guardian" },
    { key: "photo", title: "2 passport-size photos, white background with nameplate", when: "all", icon: "camera" },
    { key: "form137", title: "CTC of Form 137 — \"For Evaluation Purposes Only\"", when: "graduate", icon: "file", note: "SHS graduates" },
    { key: "form138", title: "CTC of Grade 11 Form 138", when: "grade12", icon: "file", note: "Ongoing Grade 12" },
    { key: "applicantId", title: "Applicant's government-issued or school ID", when: "all", icon: "idcard" }
  ];
  var PHYSICAL_ITEMS = ["Long brown envelope with plastic envelope"];

  // Medical requirements: the last step, only for applicants who passed and
  // have their result released. Placeholder list: replace with the clinic's.
  var MED_TYPES = [
    { key: "medCert", title: "Medical certificate", note: "Signed by a licensed physician", icon: "clipboard" },
    { key: "xray", title: "Chest X-ray result (PA view)", note: "With the radiologist's reading", icon: "file" },
    { key: "labs", title: "Laboratory results (CBC and urinalysis)", note: "Official laboratory copy", icon: "file" }
  ];
  var MED_STATUS = {
    none: { label: "Not submitted", tag: "tag-missing" },
    pending: { label: "For checking", tag: "tag-review" },
    resubmit: { label: "Resubmit", tag: "tag-resubmit" },
    cleared: { label: "Approved", tag: "tag-verified" }   // the clinic nurse sent an approval
  };
  var MED_REMARKS = [
    "File is blurry or unreadable. Upload a clearer copy.",
    "Result is older than 3 months. Submit a recent one.",
    "Missing the physician's signature and license number.",
    "Wrong file uploaded for this requirement."
  ];
  var EXAM_KIT = ["Water bottle", "Two (2) Mongol No. 2 pencils", "Glue, eraser, sharpener", "Two (2) black ballpens", "Two (2) passport-size photos with name tag", "Valid ID / school ID", "Printed exam permit and application form"];

  var REMARK_PRESETS = [
    "Image is blurry or unreadable. Upload a clearer scan.",
    "Wrong document uploaded.",
    "Document is incomplete. Include every page.",
    "Missing the remark \"For Evaluation Purposes Only\".",
    "Photo needs a white background and a nameplate.",
    "ID is expired. Upload a valid ID."
  ];

  var PROGRAMS_SEED = [
    { code: "BSIT", name: "BS Information Technology", slots: 120 },
    { code: "BSCS", name: "BS Computer Science", slots: 80 },
    { code: "BSIS", name: "BS Information Systems", slots: 60 },
    { code: "BSA", name: "BS Accountancy", slots: 80 },
    { code: "BSBA", name: "BS Business Administration", slots: 100 },
    { code: "BSHM", name: "BS Hospitality Management", slots: 80 },
    { code: "BSN", name: "BS Nursing", slots: 60 },
    { code: "BSPSY", name: "BS Psychology", slots: 60 },
    { code: "ABCOMM", name: "AB Communication", slots: 50 },
    { code: "BEED", name: "Bachelor of Elementary Education", slots: 60 },
    { code: "BSED", name: "Bachelor of Secondary Education", slots: 60 }
  ];

  // Strand → suggested programs (shown to applicants as guidance)
  var STRAND_SUGGEST = {
    "STEM": ["BSCS", "BSIT", "BSN", "BSPSY"],
    "ABM": ["BSA", "BSBA", "BSHM"],
    "HUMSS": ["ABCOMM", "BSPSY", "BSED", "BEED"],
    "GAS": ["BSBA", "BEED", "ABCOMM"],
    "TVL – ICT": ["BSIT", "BSIS", "BSCS"],
    "TVL – HE": ["BSHM", "BSBA"],
    "Arts and Design": ["ABCOMM", "BSIT"],
    "Sports": ["BSED", "BEED"]
  };
  var STRANDS = Object.keys(STRAND_SUGGEST);

  var BARANGAYS = {
    1: ["Bagong Ilog", "Bagong Katipunan", "Bambang", "Buting", "Caniogan", "Kalawaan", "Kapasigan", "Kapitolyo", "Malinao", "Oranbo", "Palatiw", "Pineda", "Sagad", "San Antonio", "San Joaquin", "San Jose", "San Nicolas", "Santa Cruz", "Santa Rosa", "Santo Tomas", "Sumilang", "Ugong"],
    2: ["Dela Paz", "Manggahan", "Maybunga", "Pinagbuhatan", "Rosario", "San Miguel", "Santa Lucia", "Santolan"]
  };

  var BATCHES = {
    A: { label: "Batch A", time: "8:00 AM – 12:00 NN" },
    B: { label: "Batch B", time: "1:00 PM – 5:00 PM" }
  };

  /* ==========================================================================
     DEMO DATA (deterministic, dates relative to today)
     ========================================================================== */
  function rng(seed) {
    return function () {
      seed |= 0; seed = seed + 0x6D2B79F5 | 0;
      var t = Math.imul(seed ^ seed >>> 15, 1 | seed);
      t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
      return ((t ^ t >>> 14) >>> 0) / 4294967296;
    };
  }

  function addDays(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function isoAt(d, h, m) { return AO.toISO(d) + "T" + String(h).padStart(2, "0") + ":" + String(m || 0).padStart(2, "0"); }

  function seed() {
    var r = rng(20262027);
    var pick = function (arr) { return arr[Math.floor(r() * arr.length)]; };
    var chance = function (p) { return r() < p; };
    var int = function (a, b) { return a + Math.floor(r() * (b - a + 1)); };

    var GIVEN_F = ["Andrea", "Bea", "Camille", "Danica", "Erika", "Faith", "Gianna", "Hannah", "Isabel", "Janine", "Kristine", "Leah", "Mikaela", "Nicole", "Patricia", "Rhea", "Samantha", "Trisha", "Venice", "Ysabel", "Angel", "Joanna", "Kyla", "Princess"];
    var GIVEN_M = ["Aaron", "Bryan", "Carlo", "Daniel", "Elijah", "Francis", "Gabriel", "Harvey", "Ivan", "Jerome", "Kenneth", "Lance", "Mark", "Nathaniel", "Paolo", "Rafael", "Sean", "Tristan", "Vince", "Zion", "John Paul", "Miguel", "Joshua", "Renz"];
    var SURNAMES = ["Abad", "Aquino", "Bautista", "Castillo", "Cruz", "Dela Cruz", "Del Rosario", "Domingo", "Espiritu", "Flores", "Garcia", "Gonzales", "Hernandez", "Ilagan", "Jimenez", "Lim", "Lopez", "Manalo", "Mendoza", "Navarro", "Ocampo", "Pascual", "Quinto", "Ramos", "Reyes", "Rivera", "Salazar", "Santos", "Soriano", "Tan", "Torres", "Valdez", "Villanueva", "Yap"];
    var SCHOOLS = [
      { name: "Rizal High School", type: "Public" }, { name: "Pasig City Science High School", type: "Public" },
      { name: "Santa Lucia High School", type: "Public" }, { name: "Manggahan High School", type: "Public" },
      { name: "Pinagbuhatan High School", type: "Public" }, { name: "Eusebio High School", type: "Public" },
      { name: "San Joaquin-Kalawaan High School", type: "Public" }, { name: "Pasig Catholic College", type: "Private" },
      { name: "Arellano University – Pasig", type: "Private" }, { name: "Saint Paul College Pasig", type: "Private" },
      { name: "STI College Ortigas-Cainta", type: "Private" }, { name: "AMA Computer College – Pasig", type: "Private" }
    ];
    var NON_RES = ["Cainta, Rizal", "Taytay, Rizal", "Marikina City", "Taguig City", "Quezon City", "Mandaluyong City"];
    var STREETS = ["Mabini St.", "Rizal Ave.", "C. Raymundo Ave.", "Dr. Sixto Antonio Ave.", "Ortigas Ave. Ext.", "M.H. del Pilar St.", "A. Luna St.", "Shaw Blvd.", "Elisco Rd.", "San Guillermo St."];
    var OTHER_STAFF = ["R. Dizon", "A. Cruz", OFFICE.name];

    var t = AO.today();
    var programs = PROGRAMS_SEED.map(function (p) { return $.extend({}, p); });
    var codes = programs.map(function (p) { return p.code; });

    // Exam sessions: two past days (results in), one today, two upcoming
    var rooms = ["Main Building · Room 301", "Main Building · Room 302", "Annex · AVR"];
    var sessions = [];
    [-13, -6, 0, 9, 16].forEach(function (offset, i) {
      ["A", "B"].forEach(function (b, j) {
        sessions.push({ id: "S" + (i * 2 + j + 1), date: AO.toISO(addDays(t, offset)), batch: b, venue: rooms[(i + j) % rooms.length], capacity: 30 });
      });
    });

    var applicants = [];
    for (var i = 0; i < 248; i++) {
      var female = chance(.56);
      var first = pick(female ? GIVEN_F : GIVEN_M);
      var last = pick(SURNAMES);
      var middle = pick(SURNAMES.filter(function (s) { return s !== last; }));
      var resident = chance(.82);
      var district = resident ? (chance(.6) ? 1 : 2) : null;
      var school = pick(SCHOOLS);
      var strand = pick(STRANDS);
      var suggested = STRAND_SUGGEST[strand];
      var p1 = chance(.7) ? pick(suggested) : pick(codes);
      var rest = codes.filter(function (c) { return c !== p1; });
      var p2 = pick(rest); rest = rest.filter(function (c) { return c !== p2; });
      var p3 = pick(rest);
      var graduate = chance(.3);
      var guardian = chance(.12);
      var id = "AD-2026-" + String(8000 + i * 3 + int(0, 2)).padStart(5, "0");

      var age = int(0, 44);                           // days since submission
      var draft = chance(.06);
      var submitted = draft ? null : addDays(t, -age);
      var created = addDays(submitted || addDays(t, -int(0, 10)), -int(0, 4));

      var a = {
        id: id, lrn: String(int(100000, 499999)) + String(int(100000, 999999)),
        lastName: last, firstName: first, middleName: middle, sex: female ? "Female" : "Male",
        email: (first + "." + last).toLowerCase().replace(/[^a-z.]/g, "") + int(1, 99) + "@gmail.com",
        phone: "+63 9" + int(10, 99) + " " + int(100, 999) + " " + int(1000, 9999),
        resident: resident, district: district,
        barangay: resident ? pick(BARANGAYS[district]) : "",
        address: int(1, 199) + " " + pick(STREETS) + (resident ? ", Brgy. " : ", ") + (resident ? "" : pick(NON_RES)),
        birthday: AO.toISO(new Date(2007 + int(0, 1), int(0, 11), int(1, 28))),
        schoolType: school.type, lastSchool: school.name, strand: strand,
        applicantType: graduate ? "graduate" : "grade12", hasGuardian: guardian,
        programs: [p1, p2, p3],
        createdAt: isoAt(created, int(7, 21), int(0, 59)),
        submittedAt: submitted ? isoAt(submitted, int(7, 22), int(0, 59)) : null,
        docs: {}, exam: null, result: null, history: []
      };
      if (resident) a.address = a.address + a.barangay + ", Pasig City";

      a.history.push({ at: a.createdAt, by: "Applicant", text: "Created an applicant account" });

      var required = requiredDocs(a);
      // Drafts: some uploads missing
      if (draft) {
        required.forEach(function (d) {
          a.docs[d.key] = chance(.5) ? { status: "pending", file: fileName(d.key), size: int(180, 2400) * 1024, uploadedAt: a.createdAt } : { status: "missing" };
        });
        applicants.push(a);
        continue;
      }

      required.forEach(function (d) {
        a.docs[d.key] = { status: "pending", file: fileName(d.key), size: int(180, 2400) * 1024, uploadedAt: a.submittedAt };
      });
      a.history.push({ at: a.submittedAt, by: "Applicant", text: "Submitted the application form and " + required.length + " documents" });

      // Recent submissions wait in the queue; older ones moved on
      var reviewedDay = addDays(submitted, int(1, 3));
      var stage = age < 3 ? "queue" : age < 8 && chance(.35) ? "queue" : chance(.16) ? "resubmit" : "verified";
      if (stage === "queue") { applicants.push(a); continue; }

      var reviewer = pick(OTHER_STAFF);
      var reviewAt = isoAt(reviewedDay, int(8, 16), int(0, 59));
      required.forEach(function (d) {
        a.docs[d.key].status = "verified";
        a.docs[d.key].reviewedBy = reviewer;
        a.docs[d.key].reviewedAt = reviewAt;
      });
      if (stage === "resubmit") {
        var bad = pick(required);
        a.docs[bad.key].status = "resubmit";
        a.docs[bad.key].remark = pick(REMARK_PRESETS);
        a.history.push({ at: reviewAt, by: reviewer, text: "Marked \"" + bad.title + "\" as requires resubmission" });
        a.history.push({ at: reviewAt, by: reviewer, text: "Verified " + (required.length - 1) + " documents" });
        applicants.push(a);
        continue;
      }
      a.history.push({ at: reviewAt, by: reviewer, text: "Verified all " + required.length + " documents" });
      if (age < 9 && chance(.45)) { applicants.push(a); continue; }   // verified, waiting for an exam slot

      // Assign the earliest session after review that still has seats
      var open = sessions.filter(function (s) {
        return AO.parseDate(s.date) > reviewedDay && countIn(applicants, s.id) < s.capacity;
      });
      var session = open[chance(.5) ? 0 : 1] || open[0];
      if (!session) { applicants.push(a); continue; }
      a.exam = { sessionId: session.id, permit: "EX-27-" + id.slice(-5), attendance: null, permitChecked: false, formChecked: false };
      a.history.push({ at: reviewAt.slice(0, 14) + "59", by: reviewer, text: "Approved for the entrance exam · " + sessionLabel(session) });

      var sDate = AO.parseDate(session.date);
      if (sDate < t) {
        var present = chance(.92);
        a.exam.attendance = present ? "present" : "absent";
        a.exam.permitChecked = a.exam.formChecked = present;
        a.history.push({ at: isoAt(sDate, session.batch === "A" ? 12 : 17, 10), by: "R. Dizon", text: present ? "Marked present at the exam venue (permit and form checked)" : "Marked absent from the entrance exam" });
        if (present) {
          var score = Math.min(98, Math.max(28, Math.round(66 + (r() + r() + r() - 1.5) * 26)));
          var resultDay = addDays(sDate, 2);
          var releasedSession = session.date === sessions[0].date;
          a.result = { score: score, decision: score >= PASSING_SCORE ? "passed" : "failed", recordedBy: "A. Cruz", recordedAt: isoAt(resultDay, 10, int(0, 59)), released: releasedSession };
          a.history.push({ at: a.result.recordedAt, by: "A. Cruz", text: "Recorded exam result: " + (a.result.decision === "passed" ? "Passed" : "Did not pass") });
          if (releasedSession) {
            var releasedAt = addDays(resultDay, 1);
            a.result.releasedAt = isoAt(releasedAt, 9, 0);
            a.history.push({ at: a.result.releasedAt, by: OFFICE.name, text: "Released the exam result to the applicant" });
            if (a.result.decision === "passed") seedMedical(a, releasedAt);
          }
        }
      }
      applicants.push(a);
    }

    // Passers submit medical files after their result is released
    function seedMedical(a, releasedAt) {
      a.medical = { files: {} };
      var roll = r();
      if (roll < .32) return;                                    // not submitted yet
      var upDay = addDays(releasedAt, int(1, 5));
      if (upDay > t) upDay = t;
      var upAt = isoAt(upDay, int(8, 21), int(0, 59));
      MED_TYPES.forEach(function (m) { a.medical.files[m.key] = { status: "pending", file: m.key + "_" + a.lastName.toLowerCase().replace(/[^a-z]/g, "") + ".pdf", size: int(200, 1800) * 1024, uploadedAt: upAt }; });
      a.history.push({ at: upAt, by: "Applicant", text: "Submitted " + MED_TYPES.length + " medical requirements" });
      if (roll < .68) return;                                    // waiting for the nurse
      var checkAt = isoAt(addDays(upDay, 1) > t ? t : addDays(upDay, 1), int(8, 16), int(0, 59));
      if (roll < .8) {
        var bad = pick(MED_TYPES);
        var fb = a.medical.files[bad.key];
        fb.status = "resubmit"; fb.remark = pick(MED_REMARKS); fb.reviewedBy = NURSE.name; fb.reviewedAt = checkAt;
        a.history.push({ at: checkAt, by: NURSE.name, text: "Asked to resubmit medical requirement \"" + bad.title + "\"" });
        return;
      }
      MED_TYPES.forEach(function (m) { var f = a.medical.files[m.key]; f.status = "verified"; f.reviewedBy = NURSE.name; f.reviewedAt = checkAt; });
      a.medical.approval = { by: NURSE.name, at: checkAt };
      a.history.push({ at: checkAt, by: NURSE.name, text: "Approved the medical requirements and sent the approval to the admission office" });
    }

    function countIn(list, sid) { return list.filter(function (x) { return x.exam && x.exam.sessionId === sid; }).length; }

    return {
      version: 2,
      programs: programs,
      sessions: sessions,
      applicants: applicants,
      settings: { deadline: AO.toISO(addDays(t, 43)), period: "AY 2027–2028 · 1st semester", inactiveDocs: [] },
      audit: [{ at: isoAt(addDays(t, -50), 9, 0), by: "System administrator", text: "Opened the AY 2027–2028 admission period" }]
    };
  }

  function fileName(key) {
    var names = { psa: "psa_birth_certificate.pdf", parentId: "parent_valid_id.jpg", affidavit: "affidavit_guardianship.pdf", photo: "passport_photo.jpg", form137: "form137_ctc.pdf", form138: "form138_grade11_ctc.pdf", applicantId: "school_id.jpg" };
    return names[key] || key + ".pdf";
  }

  /* ==========================================================================
     STATE
     ========================================================================== */
  var state = AO.readJSON("local", STORAGE_KEY);
  if (!state || state.version !== 2) { state = seed(); save(); }
  else migrate();

  // Records saved before the nurse approval existed: all-verified files count as approved
  function migrate() {
    state.applicants.forEach(function (a) {
      var files = a.medical && a.medical.files;
      if (!files || a.medical.approval) return;
      var all = MED_TYPES.map(function (m) { return files[m.key]; });
      if (all.every(function (f) { return f && f.status === "verified"; })) a.medical.approval = { by: all[0].reviewedBy || NURSE.name, at: all[0].reviewedAt || all[0].uploadedAt };
    });
  }

  function save() { AO.writeJSON("local", STORAGE_KEY, state); }
  function reset() { state = seed(); ADM.state = state; save(); }

  /* The office panel and the clinic portal share this record. When the other
     one saves (e.g. the nurse sends an approval), reload it and redraw the
     open view. BACKEND: replace with polling or a push channel. */
  window.addEventListener("storage", function (e) {
    if (e.key !== STORAGE_KEY || !e.newValue) return;
    try { state = JSON.parse(e.newValue); } catch (err) { return; }
    window.ADM.state = state;
    if (window.ADM.refresh && !$("#app").prop("hidden")) window.ADM.refresh();
  });

  function nowISO() { var d = new Date(); return AO.toISO(d) + "T" + String(d.getHours()).padStart(2, "0") + ":" + String(d.getMinutes()).padStart(2, "0"); }

  /* Append to an applicant's history (their audit trail) */
  function logApplicant(a, text) { a.history.push({ at: nowISO(), by: STAFF.name, text: text }); }
  /* Append to the office-wide audit log (not tied to one applicant) */
  function logOffice(text) { state.audit.push({ at: nowISO(), by: STAFF.name, text: text }); }

  /* ==========================================================================
     DERIVED VALUES
     ========================================================================== */
  function requiredDocs(a) {
    var inactive = (state && state.settings && state.settings.inactiveDocs) || [];
    return DOC_TYPES.filter(function (d) {
      if (inactive.indexOf(d.key) !== -1) return false;
      if (d.when === "guardian") return a.hasGuardian;
      if (d.when === "graduate") return a.applicantType === "graduate";
      if (d.when === "grade12") return a.applicantType === "grade12";
      return true;
    });
  }

  function docSummary(a) {
    var req = requiredDocs(a);
    var s = { total: req.length, verified: 0, pending: 0, resubmit: 0, missing: 0 };
    req.forEach(function (d) { var st = (a.docs[d.key] || { status: "missing" }).status; s[st] = (s[st] || 0) + 1; });
    return s;
  }

  function statusOf(a) {
    if (!a.submittedAt) return "draft";
    if (a.result) return a.result.decision;
    if (a.exam) return "for_exam";
    if (docSummary(a).resubmit) return "resubmit";
    return "submitted";
  }

  /* Working days between a date and today (Mon–Fri) */
  function workingDaysSince(iso) {
    var d = AO.parseDate(iso), t = AO.today(), n = 0;
    while (d < t) { d.setDate(d.getDate() + 1); var w = d.getDay(); if (w !== 0 && w !== 6) n++; }
    return n;
  }
  /* Medical clearance: null when it doesn't apply yet (not a released pass).
     "pending" = with the clinic nurse; "cleared" = the nurse sent an approval. */
  function medStatus(a) {
    if (!a.result || a.result.decision !== "passed" || !a.result.released) return null;
    if (a.medical && a.medical.approval) return "cleared";
    var files = (a.medical && a.medical.files) || {};
    var st = MED_TYPES.map(function (m) { return files[m.key] ? files[m.key].status : "missing"; });
    if (st.every(function (x) { return x === "missing"; })) return "none";
    if (st.indexOf("resubmit") !== -1) return "resubmit";
    return "pending";
  }
  /* The nurse can approve once every file is in and none is waiting on a resubmission */
  function canApproveMedical(a) {
    var files = (a.medical && a.medical.files) || {};
    return medStatus(a) === "pending" && MED_TYPES.every(function (m) { return files[m.key] && files[m.key].status !== "resubmit"; });
  }

  /* Find a requirement (document or medical) and the applicant's file for it */
  function findType(key) { return DOC_TYPES.concat(MED_TYPES).filter(function (d) { return d.key === key; })[0]; }
  function isMedical(key) { return MED_TYPES.some(function (m) { return m.key === key; }); }
  function fileOf(a, key) { return isMedical(key) ? (a.medical && a.medical.files[key]) : a.docs[key]; }

  function isOverdue(a) { return statusOf(a) === "submitted" && docSummary(a).pending > 0 && workingDaysSince(a.submittedAt) > REVIEW_SLA_DAYS; }
  function canApprove(a) { var s = docSummary(a); return statusOf(a) === "submitted" && s.verified === s.total; }

  function fullName(a, lastFirst) {
    return lastFirst ? a.lastName + ", " + a.firstName + (a.middleName ? " " + a.middleName.charAt(0) + "." : "") : [a.firstName, a.middleName, a.lastName].filter(Boolean).join(" ");
  }
  function initials(a) { return (a.firstName.charAt(0) + a.lastName.charAt(0)).toUpperCase(); }
  function residency(a) { return a.resident ? "Pasig · District " + a.district : "Non-resident"; }

  function program(code) { return state.programs.filter(function (p) { return p.code === code; })[0] || { code: code, name: code, slots: 0 }; }
  function session(id) { return state.sessions.filter(function (s) { return s.id === id; })[0]; }
  function sessionLabel(s) { return BATCHES[s.batch].label + " · " + AO.fmt(s.date, { weekday: "short", month: "short", day: "numeric" }); }
  function sessionRoster(id) { return state.applicants.filter(function (a) { return a.exam && a.exam.sessionId === id; }); }
  function sessionPast(s) { return AO.parseDate(s.date) <= AO.today(); }
  function applicant(id) { return state.applicants.filter(function (a) { return a.id === id; })[0]; }

  function sortedSessions() {
    return state.sessions.slice().sort(function (x, y) { return x.date === y.date ? (x.batch < y.batch ? -1 : 1) : (x.date < y.date ? -1 : 1); });
  }

  function countBy(fn) {
    var out = {};
    state.applicants.forEach(function (a) { var k = fn(a); out[k] = (out[k] || 0) + 1; });
    return out;
  }

  /* Office-wide audit: applicant histories + office entries, newest first */
  function auditTrail() {
    var rows = state.audit.map(function (e) { return $.extend({ target: "Admission office" }, e); });
    state.applicants.forEach(function (a) {
      a.history.forEach(function (h) { rows.push($.extend({ target: fullName(a) + " · " + a.id, applicantId: a.id }, h)); });
    });
    return rows.sort(function (x, y) { return x.at < y.at ? 1 : x.at > y.at ? -1 : 0; });
  }

  /* ---------- CSV export ---------- */
  function downloadCSV(name, header, rows) {
    var cell = function (v) { v = String(v == null ? "" : v); return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
    var csv = [header].concat(rows).map(function (r) { return r.map(cell).join(","); }).join("\r\n");
    var blob = new Blob(["﻿" + csv], { type: "text/csv;charset=utf-8" });
    var url = URL.createObjectURL(blob);
    $("<a>").attr({ href: url, download: name }).appendTo("body")[0].click();
    setTimeout(function () { URL.revokeObjectURL(url); $("a[download='" + name + "']").remove(); }, 500);
  }

  window.ADM = {
    STORAGE_KEY: STORAGE_KEY, ROLE: ROLE, STAFF: STAFF, REVIEW_SLA_DAYS: REVIEW_SLA_DAYS, PASSING_SCORE: PASSING_SCORE, PAGE_SIZE: PAGE_SIZE,
    STATUSES: STATUSES, STATUS_ORDER: STATUS_ORDER, DOC_STATUS: DOC_STATUS, DOC_TYPES: DOC_TYPES,
    PHYSICAL_ITEMS: PHYSICAL_ITEMS, EXAM_KIT: EXAM_KIT, REMARK_PRESETS: REMARK_PRESETS,
    MED_TYPES: MED_TYPES, MED_STATUS: MED_STATUS, MED_REMARKS: MED_REMARKS, medStatus: medStatus, canApproveMedical: canApproveMedical,
    findType: findType, isMedical: isMedical, fileOf: fileOf,
    STRAND_SUGGEST: STRAND_SUGGEST, STRANDS: STRANDS, BARANGAYS: BARANGAYS, BATCHES: BATCHES,
    state: state, save: save, reset: reset, nowISO: nowISO, logApplicant: logApplicant, logOffice: logOffice,
    requiredDocs: requiredDocs, docSummary: docSummary, statusOf: statusOf, workingDaysSince: workingDaysSince,
    isOverdue: isOverdue, canApprove: canApprove, fullName: fullName, initials: initials, residency: residency,
    program: program, session: session, sessionLabel: sessionLabel, sessionRoster: sessionRoster, sessionPast: sessionPast,
    sortedSessions: sortedSessions, applicant: applicant, countBy: countBy, auditTrail: auditTrail, downloadCSV: downloadCSV,
    views: {}
  };
})(jQuery);
