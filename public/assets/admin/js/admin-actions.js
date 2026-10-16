/* ==========================================================================
   PLP Online Admission — Admission Office: actions
   Every change an admission officer makes goes through here, so each one is
   written to the applicant's history (the audit trail) and saved.
   Needs admin-store.js. Adds ADM.actions.
   ========================================================================== */
(function () {
  "use strict";

  var ADM = window.ADM;

  function docTitle(key) { return ADM.findType(key).title; }

  function stamp(doc) { doc.reviewedBy = ADM.STAFF.name; doc.reviewedAt = ADM.nowISO(); }

  ADM.actions = {
    // BACKEND: PATCH /api/applicants/:id/documents/:key { status: "verified" }
    verifyDoc: function (a, key) {
      var doc = a.docs[key];
      if (!doc || doc.status === "missing") return false;
      doc.status = "verified";
      delete doc.remark;
      stamp(doc);
      ADM.logApplicant(a, "Verified \"" + docTitle(key) + "\"");
      ADM.save();
      return true;
    },

    // BACKEND: PATCH /api/applicants/:id/documents/:key { status: "resubmit", remark }
    resubmitDoc: function (a, key, remark) {
      var doc = a.docs[key];
      if (!doc || doc.status === "missing") return false;
      doc.status = "resubmit";
      doc.remark = remark;
      stamp(doc);
      ADM.logApplicant(a, "Marked \"" + docTitle(key) + "\" as requires resubmission: " + remark);
      ADM.save();
      return true;
    },

    // Verify every document that is waiting for review
    verifyAllPending: function (a) {
      var n = 0;
      ADM.requiredDocs(a).forEach(function (d) {
        var doc = a.docs[d.key];
        if (doc && doc.status === "pending") { doc.status = "verified"; stamp(doc); n++; }
      });
      if (n) { ADM.logApplicant(a, "Verified " + n + " document" + (n === 1 ? "" : "s")); ADM.save(); }
      return n;
    },

    // BACKEND: POST /api/applicants/:id/approve { sessionId } → issues the exam permit
    approveForExam: function (a, sessionId) {
      if (!ADM.canApprove(a)) return false;
      var s = ADM.session(sessionId);
      if (!s || ADM.sessionRoster(s.id).length >= s.capacity) return false;
      a.exam = { sessionId: s.id, permit: "EX-27-" + a.id.slice(-5), attendance: null, permitChecked: false, formChecked: false };
      ADM.logApplicant(a, "Approved for the entrance exam · " + ADM.sessionLabel(s));
      ADM.save();
      return true;
    },

    // Move an absent examinee to another session
    reschedule: function (a, sessionId) {
      var s = ADM.session(sessionId);
      if (!a.exam || a.result || !s || ADM.sessionRoster(s.id).length >= s.capacity) return false;
      a.exam.sessionId = s.id;
      a.exam.attendance = null;
      a.exam.permitChecked = a.exam.formChecked = false;
      ADM.logApplicant(a, "Rescheduled the entrance exam to " + ADM.sessionLabel(s));
      ADM.save();
      return true;
    },

    // BACKEND: PATCH /api/exam-sessions/:id/roster/:applicantId
    setCheck: function (a, field, value) {
      if (!a.exam || a.result) return;
      a.exam[field] = value;
      ADM.save();
    },
    setAttendance: function (a, value) {
      if (!a.exam || a.result) return false;
      if (value === "present" && !(a.exam.permitChecked && a.exam.formChecked)) return false;
      a.exam.attendance = value;
      ADM.logApplicant(a, value === "present" ? "Marked present at the exam venue (permit and form checked)" : value === "absent" ? "Marked absent from the entrance exam" : "Cleared exam attendance");
      ADM.save();
      return true;
    },

    // BACKEND: POST /api/applicants/:id/result { score, decision } (score stays internal)
    recordResult: function (a, score, decision) {
      if (!a.exam || a.exam.attendance !== "present" || (a.result && a.result.released)) return false;
      var changed = !!a.result;
      a.result = { score: score, decision: decision, recordedBy: ADM.STAFF.name, recordedAt: ADM.nowISO(), released: false };
      ADM.logApplicant(a, (changed ? "Updated" : "Recorded") + " exam result: " + (decision === "passed" ? "Passed" : "Did not pass"));
      ADM.save();
      return true;
    },

    // BACKEND: POST /api/results/release { applicantIds } → notifies applicants
    releaseResults: function (list) {
      var n = 0;
      list.forEach(function (a) {
        if (a.result && !a.result.released) {
          a.result.released = true;
          a.result.releasedAt = ADM.nowISO();
          if (a.result.decision === "passed" && !a.medical) a.medical = { files: {} };
          ADM.logApplicant(a, "Released the exam result to the applicant");
          n++;
        }
      });
      if (n) { ADM.logOffice("Released " + n + " exam result" + (n === 1 ? "" : "s") + " through the system"); ADM.save(); }
      return n;
    },

    // BACKEND: PATCH /api/applicants/:id/medical/:key { status: "verified" }
    verifyMedical: function (a, key) {
      var f = ADM.fileOf(a, key);
      if (!f) return false;
      f.status = "verified";
      delete f.remark;
      stamp(f);
      ADM.logApplicant(a, "Verified medical requirement \"" + docTitle(key) + "\"");
      if (ADM.medStatus(a) === "cleared") ADM.logApplicant(a, "Medically cleared · admission complete");
      ADM.save();
      return true;
    },

    // BACKEND: PATCH /api/applicants/:id/medical/:key { status: "resubmit", remark }
    resubmitMedical: function (a, key, remark) {
      var f = ADM.fileOf(a, key);
      if (!f) return false;
      f.status = "resubmit";
      f.remark = remark;
      stamp(f);
      ADM.logApplicant(a, "Asked to resubmit medical requirement \"" + docTitle(key) + "\": " + remark);
      ADM.save();
      return true;
    },

    verifyAllMedical: function (a) {
      var n = 0;
      ADM.MED_TYPES.forEach(function (m) {
        var f = ADM.fileOf(a, m.key);
        if (f && f.status === "pending") { f.status = "verified"; stamp(f); n++; }
      });
      if (n) {
        ADM.logApplicant(a, "Verified " + n + " medical requirement" + (n === 1 ? "" : "s"));
        if (ADM.medStatus(a) === "cleared") ADM.logApplicant(a, "Medically cleared · admission complete");
        ADM.save();
      }
      return n;
    },

    // BACKEND: POST /api/exam-sessions
    createSession: function (date, batch, venue, capacity) {
      var dup = ADM.state.sessions.filter(function (s) { return s.date === date && s.batch === batch && s.venue.toLowerCase() === venue.toLowerCase(); })[0];
      if (dup) return null;
      var max = ADM.state.sessions.reduce(function (m, s) { return Math.max(m, +s.id.slice(1)); }, 0);
      var s = { id: "S" + (max + 1), date: date, batch: batch, venue: venue, capacity: capacity };
      ADM.state.sessions.push(s);
      ADM.logOffice("Created exam session " + ADM.sessionLabel(s) + " at " + venue + " (" + capacity + " seats)");
      ADM.save();
      return s;
    },

    // BACKEND: PATCH /api/programs/:code { slots }
    setSlots: function (code, slots) {
      var p = ADM.program(code);
      if (p.slots === slots) return;
      ADM.logOffice("Changed " + code + " slots from " + p.slots + " to " + slots);
      p.slots = slots;
      ADM.save();
    },
    addProgram: function (code, name, slots) {
      code = code.toUpperCase();
      if (ADM.state.programs.some(function (p) { return p.code === code; })) return false;
      ADM.state.programs.push({ code: code, name: name, slots: slots });
      ADM.logOffice("Added program " + code + " · " + name + " (" + slots + " slots)");
      ADM.save();
      return true;
    },

    // BACKEND: PATCH /api/admission-period { inactiveDocs, deadline }
    toggleRequirement: function (key, active) {
      var list = ADM.state.settings.inactiveDocs;
      var i = list.indexOf(key);
      if (active && i !== -1) list.splice(i, 1);
      if (!active && i === -1) list.push(key);
      ADM.logOffice((active ? "Required" : "Stopped requiring") + " \"" + docTitle(key) + "\"");
      ADM.save();
    },
    setDeadline: function (iso) {
      if (ADM.state.settings.deadline === iso) return;
      ADM.logOffice("Changed the application deadline to " + AO.fmt(iso, { month: "long", day: "numeric", year: "numeric" }));
      ADM.state.settings.deadline = iso;
      ADM.save();
    }
  };
})();
