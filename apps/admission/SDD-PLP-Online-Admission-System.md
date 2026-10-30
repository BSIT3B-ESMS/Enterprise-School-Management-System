# Solution Design Document Template — Enterprise School Management System

Oct 10, 2026 · @Joseph

## How to use this template

Use this template to produce one Solution Design Document (SDD) per client engagement for a School Management System (SMS). Each section has an **Instruction** (delete before issuing) and a worked **Example** for a fictional client, *Northbridge Schools Group* (12 K-12 schools, 9,500 students, 1,100 staff), which you replace with your own content.

- Copy this doc and rename it: SDD – <Client> – <Programme> – v0.1.

- Fill sections 1–5 with the client before designing anything. They are your contract on scope.

- Complete sections 6–12 as the design matures. Record every significant choice in the Decisions Log (section 13).

- Remove all *Instruction* lines and fictional examples before circulating.

- Version the document at every review gate: 0.x drafts, 1.0 on design sign-off, 1.x for approved changes.

**Writing rules for architects**

- State the decision first, then the reason. Reviewers skim.

- Trace every component to a requirement ID (FR-xx / NFR-xx). Anything untraced is scope creep or a gap.

- Quantify non-functional needs: "99.9% availability during term time", not "highly available".

- Treat student data as children's personal data throughout. Privacy is a design input, not a later review.

- Use diagrams for structure (context, components, flows); use tables for lists and comparisons.

## 1. Document control

*Instruction: Record every version and who must approve it. Design sign-off normally needs the client's IT lead, a data protection officer (DPO) and an education/business owner.*

**Document:** SDD – Pamantasan ng Lungsod ng Pasig – Online Admission System (OAS) – v0.1. Status: Draft for client review. Classification: Internal – contains no personal data.

**Version history**

| **Version** | **Date** | **Author** | **Change summary** |
| --- | --- | --- | --- |
| 0.1 | 2026-10-14 | Abella, Solution Architect | Initial draft: sections 1–5 (context, scope, personas, requirements), baselined from the OAS proposal "Collaboration Web Dev and SIA" |
| 0.2 | [date] | Abella, Solution Architect | Comments from Admissions Office and DPO incorporated; open decisions D-01 to D-06 (section 5.3) closed |
| 0.3 | [date] | Abella,Solution Architect | Current state, target architecture, data, integration and security design added (sections 6–12) |
| 1.0 | [date] | [Name], Solution Architect | Approved for build |

**Reviewers and approvers**

| **Name** | **Role** | **Organisation** | **Responsibility** |
| --- | --- | --- | --- |
| [name] | Director / Head, Admissions Office | PLP | Approver: business fit, admission rules, scope (business owner) |
| [name] | Head, MIS / ICT Office | PLP | Approver: technical, hosting, operations and support |
| [name] | Data Protection Officer | PLP | Approver: privacy and compliance (RA 10173), Privacy Impact Assessment |
| [name] | Vice President for Academic Affairs (or delegate) | PLP | Sponsor: budget and go-live date |
| [name] | University Registrar | PLP | Reviewer: hand-off of admitted applicants to enrolment |
| [name] | Lead Developer (Laravel) | Project team (Web Dev / SIA) | Reviewer: buildability and estimates |
| [name] | Security Lead | Project team (Web Dev / SIA) | Reviewer: authentication, RBAC, audit logging |
| Nicco [surname] | Adviser – document verification | Project team | Reviewer: automation option for document checks (D-02) |

**Related documents:** Online Admission System Proposal (Collaboration Web Dev and SIA), Business Requirements Document, Privacy Impact Assessment, Test Strategy, Admission-Period Go-Live and Cutover Plan.

## 2. Executive summary

*Instruction: Write this last, in under 250 words, for a non-technical executive. Answer four questions: what problem, what solution, what it costs **and** takes, what decision you need.*

**The problem. **Pamantasan ng Lungsod ng Pasig receives up to 20,000 freshman applications in a single admission period, processed through manual and fragmented steps. Applicants encode the same details more than once, documents arrive on paper or through scattered channels, applicants must contact the office to learn their status, and results are released through separate channels. The outcome is encoding errors, duplicate records, slow verification and no single, reliable record of each applicant.

**The solution. **We recommend one web-based Online Admission System that carries every applicant from registration to acceptance in a single record: online application with program choices and strand-based program suggestions, document upload and verification, exam scheduling with printable permits, result recording, and release of decisions and acceptance letters. It works on phones as well as computers. Staff work from review queues and built-in reports instead of spreadsheets. Access is limited by role, every change is logged, and applicant data, much of it belonging to minors, is protected under the Data Privacy Act of 2012.

**Cost and timeline. **Delivery in three phases over [n] months, live before the [AY 2027–2028] admission period opens. One-off cost is [amount]; running cost (hosting, email/SMS, support) is [amount/year].

**Decision requested: **approve the scope and requirements in sections 3–5 as the design baseline, and resolve open decisions D-01 to D-06 (section 5.3), by [date].

## 3. Business context, objectives and scope

*Instruction: Describe the institution, the drivers for change and measurable objectives. Then draw a hard line between in-scope and out-of-scope. Most SMS overruns come from unstated scope such as timetabling or payroll.*

**3.1 ****Organisation**** profile**

| **Attribute** | **Value** |
| --- | --- |
| Institution | Pamantasan ng Lungsod ng Pasig (PLP), a local university and college (LUC) of the City Government of Pasig |
| Phase | Higher education (undergraduate). This program covers freshman admission only. |
| Students | ≈ 10,000 enrolled |
| Faculty | ≈ 500, including part-time |
| Applicants | Up to ≈ 20,000 per admission period (highest recorded). This, not the student or faculty count, sizes the system. |
| Admission staff | [n] — to be confirmed (D-06) |
| Location | 12-B Alcalde Jose, Pasig, 1600 Metro Manila |
| Admission cycle | One main admission period per academic year: registration → document verification → entrance exam (Batch A morning, Batch B afternoon) → results → acceptance (Taking the slot) → medical requirements |
| Regulatory jurisdiction | Philippines. CHED policies for higher education; Data Privacy Act of 2012 (RA 10173) and National Privacy Commission (NPC) issuances. Most applicants are Grade 12 students aged 17–18, so many are minors, and the system also holds parent/guardian IDs and guardianship affidavits. |

**3.2 Business drivers**

- **Repetitive registration:** applicants re-encode the same details at several steps, causing errors and duplicate records.

- **Difficult requirements verification:** paper or scattered submissions make it hard to confirm that an applicant is complete.

- **No status visibility:** applicants must call or visit the office to learn their status, which loads staff during the busiest weeks.

- **No central record:** applicant data is slow to retrieve and update, and reports are kept in separate spreadsheets.

- **Delayed and uncontrolled decisions:** results are communicated manually or through separate channels, with no single controlled release.

- **Limited program guidance:** applicants get little help choosing programs that fit their senior high school strand.

- **Volume:** up to 20,000 applicants and a two-batch exam day cannot be managed reliably on paper.

**3.3 Objectives and success measures**

*Targets are proposed by the architect and must be confirmed with the Admissions Office; baselines are measured from the last admission period.*

| **ID** | **Objective** | **Measure** | **Target** |
| --- | --- | --- | --- |
| OBJ-01 | Single source of applicant truth | Places where applicant data is encoded; duplicate applicant records | 1 (encode once, reuse everywhere); duplicates < 0.5% |
| OBJ-02 | Fully online application | Share of applications and documents received through the system | 100% (no paper application forms) |
| OBJ-03 | Faster document verification | Time from submission to first staff review | ≤ 3 working days for 95% of applications |
| OBJ-04 | Applicant self-service status | Status enquiries to the office by phone, walk-in or message | −70% vs. last period |
| OBJ-05 | Controlled exam day | Examinees checked in against the system roster and permit | 100%; no batch over room capacity |
| OBJ-06 | Timely, controlled decisions | Time from final result encoding to applicant visibility; share released through the system | ≤ 1 working day; 100% via system |
| OBJ-07 | Reporting without spreadsheets | Standard reports available on demand; parallel spreadsheets kept | 6 reports; 0 spreadsheets |
| OBJ-08 | Better program guidance | Applicants with a declared strand who are shown suggested programs | 100% |

**3.4 Scope**

| **In scope (Phase 1)** | **Out of scope** |
| --- | --- |
| Applicant account registration with email verification (one account per applicant) | Transferee, second-degree, cross-enrollee and graduate admission (freshmen only in Phase 1; Phase 2 option) |
| Freshman application form, including residency, district and barangay, school background and strand | Online, computer-based delivery of the entrance exam (pending decision D-01) |
| Program catalogue, 1st/2nd/3rd program choice and strand-based program suggestions | Exam item bank, test authoring and automated scoring (OMR); scores are encoded or imported |
| Document upload, staff verification, remarks and resubmission | Automated or AI document checking, e.g. OCR (pending decision D-02) |
| Exam scheduling (dates, Batch A/B, rooms, capacity), exam permit and printable application form | Enrolment, sectioning, subject registration, student records and grades (Registrar / SIS) |
| Exam-day check-in, attendance and rosters | Fees, payments and scholarships (no admission fee is collected) |
| Result recording (Passed / Did Not Pass; scores internal) and controlled release | Native iOS/Android app (the responsive web app covers mobile) |
| Acceptance letter and program placement against slots | Medical examination itself (only submission tracking is in scope) |
| Medical requirements submission tracking (final step) | Live validation of LRN against DepEd systems (format check only) |
| Status timeline and notifications (email and in-app; SMS optional) | Faculty and current-student functions |
| Staff review queues, applicant record with change history, dashboards and reports | Migration of past admission periods (historical data stays in current files) |
| Maintenance of admission periods, programs, slots, requirements and reference lists |  |
| User and role administration, audit logs and backups (System Administrator) |  |
| Export of admitted applicants to the Registrar for enrolment |  |

## 4. Stakeholders and user personas

*Instruction: List every group that uses or is affected by the system, with volumes and channel. Volumes drive licensing, capacity and support design. Peak usage in schools is predictable: morning registration, report-card release, admissions deadlines.*

| **Persona** | **Approx. users** | **Key tasks** | **Primary channel** | **Peak load** |
| --- | --- | --- | --- | --- |
| Applicant (freshman) | Up to 20,000 per period | Register, complete form, choose 3 programs, upload documents, re-upload when required, track status, print permit and form, view result, download acceptance letter, submit medical requirements | Web, mobile browser | Opening days of the period; last 48 h before deadline; results release day |
| Parent / guardian | ≈ 20,000 (data subjects, no own account) | Provide government ID or notarized affidavit of guardianship; give consent for minor applicants; often helps the applicant | Through applicant account | Same as applicant |
| Admission personnel (evaluator) | [10–20] (D-06) | Review applications within 3 working days, verify documents with remarks, schedule exams, encode results, generate reports, maintain programs and slots | Web (desktop) | Deadline-week backlog; result encoding |
| Admission head / director | 1–3 | Approve and release results, set program slots, monitor dashboards | Web | Before results release; weekly during period |
| Exam proctor / venue staff | [n] per batch (D-05) | Check permit, application form and ID; mark attendance; collect physical envelopes | Web on laptop/tablet, printed roster as fallback | Exam days: Batch A morning, Batch B afternoon |
| Medical / clinic staff | [n] (D-04) | Confirm medical requirements of passed applicants | Web | After results release |
| College deans / program chairs | [n] | View passed lists and slot fill for their programs (read-only) | Web | After results release |
| University Registrar | [n] | Receive admitted-applicant list for enrolment | File export (CSV/XLSX) | Before enrolment |
| System administrator (MIS/ICT) | 2–3 | Manage user accounts and roles, security settings, backups; view audit logs | Admin console | Before the period opens; ongoing |
| Data Protection Officer / auditor | 1–2 | Review audit logs, handle data-subject requests, oversee retention | Web (read-only), reports | On request |

**Sizing assumptions for design:** about 10% of applicants active in the peak hour of the deadline day gives ≈ 2,000 concurrent users; 20,000 applicants × ~8 files at ≤ 2 MB each gives ≈ 160,000 files and up to ≈ 300 GB per period; up to 10,000 examinees per batch if the exam runs on one day. The 500 faculty and 10,000 enrolled students are not users of this system in Phase 1.

**Role boundaries:** admission personnel cannot create, delete or change user accounts and roles, run backups, change security settings, or edit or delete audit log entries. Only the System Administrator manages accounts and roles, and no role can change the audit log.

## 5. Requirements

*Instruction: **Summarise** requirements here and link to the full Business Requirements Document. Give every requirement an ID and a **MoSCoW** priority (Must, Should, Could, Won't). Every NFR needs a number someone can test.*

*Full detail: Business Requirements Document v0.1 [link]. Each requirement traces to an objective in 3.3.*

**5.1 Functional requirements**

| **ID** | **Module** | **Requirement** | **Priority** | **Traces to** |
| --- | --- | --- | --- | --- |
| FR-01 | Accounts | Applicant registers with email and password and verifies the email by one-time link. One account per applicant, enforced by unique email and unique LRN. | Must | OBJ-01 |
| FR-02 | Accounts | Password reset by email; account locks for 15 minutes after 5 failed logins. | Must | OBJ-02 |
| FR-03 | Application | Freshman form captures LRN (12 digits), sex, surname, given and middle name, birthday, contact number, address, residency status, district (1 or 2) and barangay filtered by district, school type (public/private), last school attended and SHS strand. | Must | OBJ-01, OBJ-02 |
| FR-04 | Application | Form saves as Draft and can be resumed. Submission locks it; it reopens only when staff set Requires Resubmission. | Must | OBJ-01 |
| FR-05 | Programs | Applicant selects distinct 1st, 2nd and 3rd program choices from programs open in the current period. | Must | OBJ-02 |
| FR-06 | Programs | System shows suggested programs based on the applicant's strand, using a strand-to-program table maintained by staff. | Should | OBJ-08 |
| FR-07 | Documents | System builds each applicant's checklist: all applicants – PSA birth certificate, parent's government ID, applicant's government or school ID, passport photo (white background, nameplate); SHS graduate – CTC of Form 137 "For Evaluation Purposes Only"; Grade 12 in progress – CTC of Grade 11 Form 138; with guardian – notarized affidavit of guardianship. | Must | OBJ-03 |
| FR-08 | Documents | Upload PDF, JPG or PNG up to 5 MB per file; file type checked by content and malware-scanned; file replaceable while Pending or Requires Resubmission. | Must | OBJ-02, OBJ-03 |
| FR-09 | Verification | Staff mark each document Verified, Rejected or Requires Resubmission; a remark is mandatory unless Verified; applicant is notified. | Must | OBJ-03 |
| FR-10 | Verification | Application moves to For Exam only when every mandatory document is Verified. | Must | OBJ-03 |
| FR-11 | Status | Applicant sees a dated status timeline: Draft, Submitted, Requires Resubmission, For Exam, Passed or Did Not Pass, plus each document's status and remark. | Must | OBJ-04 |
| FR-12 | Notifications | Email and in-app notification on every status change, document remark, exam schedule and result release. | Must | OBJ-04 |
| FR-13 | Notifications | SMS for exam schedule and result release. | Could | OBJ-04 |
| FR-14 | Exam scheduling | Staff define exam dates, Batch A (morning) and Batch B (afternoon), rooms and capacities; system assigns For Exam applicants to slots without exceeding capacity. | Must | OBJ-05 |
| FR-15 | Exam permit | Applicant prints an exam permit (photo, name, application number, date, batch, room, QR code, what-to-bring list) and a copy of the submitted application form. | Must | OBJ-05 |
| FR-16 | Exam day | Staff print rosters per room and batch, check in examinees by application number, and mark Present or Absent. | Must | OBJ-05 |
| FR-17 | Exam day | Check-in by scanning the permit QR code; record receipt of the physical long brown envelope. | Should | OBJ-05 |
| FR-18 | Exam | Applicants take the entrance exam online in the system. | Won't (Phase 1, see D-01) | OBJ-05 |
| FR-19 | Results | Staff record score and outcome (Passed / Did Not Pass) one by one or by CSV import. Scores are never shown to applicants in any screen, file or API response. | Must | OBJ-06 |
| FR-20 | Results | Results stay hidden until the Admission Head releases them; release is logged and triggers notifications. | Must | OBJ-06 |
| FR-21 | Acceptance | Staff place each passed applicant into one of their three choices within program slots; system issues a PDF acceptance letter with program, next steps and deadlines. | Must | OBJ-06 |
| FR-22 | Medical | Passed applicants submit medical requirements; authorised staff mark them complete as the final step. | Should | OBJ-06 |
| FR-23 | Staff workspace | Review queues with counts by status; search and filter by name, LRN, program, status and date. | Must | OBJ-03 |
| FR-24 | Staff workspace | Complete applicant record with the history of every change. | Must | OBJ-01 |
| FR-25 | Duplicates | Flag possible duplicates (same LRN, or same name and birthday) for staff review. | Must | OBJ-01 |
| FR-26 | Configuration | Staff maintain admission periods, programs, slots, requirements, strand mapping, barangay list and exam venues without code changes. | Must | OBJ-07 |
| FR-27 | Reporting | Reports: applicants per program (by choice), status breakdown, document completion, exam attendance, pass rate per program, daily submissions; export to CSV, XLSX and PDF. | Must | OBJ-07 |
| FR-28 | Reporting | Dashboard for the Admission Head with live counts and trends. | Should | OBJ-07 |
| FR-29 | Integration | Export admitted applicants to the Registrar in an agreed CSV/XLSX layout. | Should | OBJ-01 |
| FR-30 | Admin | Only the System Administrator creates and changes users and roles (Applicant, Admission Staff, Admission Head, Proctor, Medical, Read-only, System Admin). | Must | OBJ-01 |
| FR-31 | Privacy | Privacy notice and consent at registration, including parent or guardian consent for applicants under 18. | Must | OBJ-02 |

**5.2 ****Non-functional**** requirements**

| **ID** | **Category** | **Requirement** | **Priority** |
| --- | --- | --- | --- |
| NFR-01 | Availability | 99.5% monthly during an open admission period; 99.9% from 06:00 to 22:00 PHT on deadline days, exam days and results release day. Planned maintenance only 00:00–05:00 or outside the admission period. | Must |
| NFR-02 | Performance | At 2,000 concurrent users: pages load in < 2 s at p95; form save < 1 s at p95; a 5 MB upload completes in < 15 s at p95 on a 5 Mbps mobile connection. | Must |
| NFR-03 | Scalability | 20,000 applications and 160,000 files (≈ 300 GB) per period, with 3 periods kept online; up to 30,000 applicants (1.5×) without redesign; 20,000 logins within 2 hours of results release. | Must |
| NFR-04 | Security – authentication | HTTPS only (TLS 1.2+). Passwords hashed with bcrypt or Argon2, minimum 8 characters. JWT access tokens expire in 15 minutes; refresh tokens rotate and can be revoked. MFA for all staff and admin accounts. | Must |
| NFR-05 | Security – authorisation | RBAC enforced on the server for every endpoint; applicants can reach only their own records. API keys are for system-to-system calls only (e.g. Registrar export, email/SMS gateway), never in browser code, stored hashed and rotated every 90 days. | Must |
| NFR-06 | Security – application | Meets OWASP ASVS Level 2. Rate limit of 5 attempts per minute on login and registration, plus CAPTCHA on registration. Uploads stored outside the web root and served only through signed links that expire in 5 minutes. Penetration test before go-live with no open high or critical findings. | Must |
| NFR-07 | Privacy | Complies with RA 10173 and NPC circulars: Privacy Impact Assessment signed off before go-live; only the fields listed in FR-03 and FR-07 are collected; database and files encrypted at rest; breach reported to the NPC within 72 hours; data hosted in [the Philippines / approved provider]. | Must |
| NFR-08 | Retention | Documents of applicants not admitted or withdrawn are deleted [12] months after the period closes; admitted records pass to the Registrar; audit logs are kept [5] years. | Must |
| NFR-09 | Auditability | Every create, update, delete, login, result release and role change is logged with user, time, IP address and before/after values. The log is append-only, and no role can edit or delete it. | Must |
| NFR-10 | Recovery | RPO 15 minutes (MySQL binary logs); RTO 4 hours; daily full backup with an off-site copy; restore tested before every admission period. | Must |
| NFR-11 | Usability and accessibility | WCAG 2.1 AA; responsive down to 360 px width; a first-time applicant completes the form on a phone in ≤ 20 minutes in usability tests. | Must |
| NFR-12 | Compatibility | Latest two versions of Chrome, Edge, Firefox and Safari, and Chrome on Android 9+. Permit and form print correctly on A4 and Letter. | Must |
| NFR-13 | Notification timeliness | 95% of emails sent within 5 minutes of the triggering event; failed sends retried and reported. | Should |
| NFR-14 | Maintainability | Supported versions of Laravel, PHP and MySQL 8; automated tests cover ≥ 70% of business rules; configuration changes (FR-26) need no code deployment. | Should |
| NFR-15 | Monitoring | Uptime and error-rate monitoring alerts the MIS team within 5 minutes of an outage. | Should |
| NFR-16 | Localisation | English user interface; Filipino help text. | Could |

**5.3 Open decisions and assumptions**

*These items change scope or design and must be closed before v1.0. They will be carried into the Decisions Log (section 13).*

| **ID** | **Question** | **Architect's recommendation** | **Owner** |
| --- | --- | --- | --- |
| D-01 | The proposal's objectives describe an online entrance exam, but the admission process describes a paper exam at a venue (pencils, permits, Batch A/B). Which one applies? | Phase 1: keep the paper exam and manage it in the system (FR-14 to FR-20). Treat online exam delivery as a separate Phase 2 decision because it needs proctoring, an item bank and much higher load. | Admissions Head |
| D-02 | Should document verification be partly automated? | Phase 1: manual verification with mandatory remarks. Assess OCR or auto-checks after one period of real data. | Nicco / Admissions |
| D-03 | Does Pasig residency or district affect admission priority or slots? | Confirm the rule; if yes, add it as a configurable placement rule under FR-21. | Admissions Head |
| D-04 | Who verifies medical requirements: the clinic or the Admissions Office? | Use a separate Medical role so health data is seen only by the people who need it. | Admissions / DPO |
| D-05 | How many exam days, rooms and proctors? | Needed to size FR-14 and the check-in load. | Admissions Office |
| D-06 | How many admission staff, and what are the hosting options (on-premise or cloud)? | Needed for licensing, capacity and NFR-01. | MIS / ICT Office |

## 6. Current state and gap analysis

*Instruction: Inventory every existing system, what it does, who owns it and its fate (retain, replace, retire, integrate). The gap column justifies the investment.*

| Current system | Function | Data held | Pain point / gap | Disposition |
| --- | --- | --- | --- | --- |
| Legacy SIS (on-prem, per school) | Student records, attendance | 12 separate databases | No group view; unsupported after 2027 | Replace |
| Spreadsheets | Grading, report cards | Grades | Manual, error-prone, no audit | Replace |
| Accounting package | Fees and finance | Invoices, ledgers | Manual fee reconciliation | Integrate (keep GL) |
| Moodle | Learning, homework | Courses, submissions | Rosters created by hand | Integrate |
| Microsoft 365 | Email, identity | Staff and student accounts | Not linked to SIS roles | Integrate (identity provider) |
| Bulk SMS service | Parent alerts | Phone numbers | Contact lists out of date | Retire (replaced by comms module) |

*Instruction: Add a short note on technical debt, contracts ending, and any data-quality issues found during discovery (duplicates, missing guardian links, inconsistent year-group codes).*

## 7. Target solution architecture

*Instruction: Start with the principles that guided the design, then show the logical architecture as a diagram, then describe each component and the requirement it satisfies. Add a system context diagram (C4 level 1) and container diagram (C4 level 2) if the audience is technical.*

**7.1 Architecture principles (example)**

- Single source of truth: each data entity has exactly one system of record.

- Buy for commodity, build for differentiation.

- Standards-first integration (OneRoster, SCIM, OIDC).

- Privacy by design: least-privilege access, minimised data sharing.

- Cloud-native and mobile-first for parents and students.

**7.2 Logical architecture (example)**

logical architecture · 4 layers, 8 core modules

All users enter through one identity and gateway layer; the core holds one tenant per school, and external systems connect only through the gateway.

**7.3 Component responsibilities**

| Component | Responsibility | Traces to |
| --- | --- | --- |
| Identity and API gateway | SSO, MFA, token validation, rate limiting, routing | NFR-04, INT-01 |
| Admissions | Online applications, offers, waiting lists | FR-01 |
| Student info | Master student and guardian records, transfers | FR-02 |
| Attendance | Lesson registers, absence alerts | FR-03 |
| Timetabling | Timetable build, calendar sync | FR-04 |
| Assessment | Grading schemes, report cards | FR-05 |
| Fees, payments | Invoices, instalments, payment reconciliation | FR-06, INT-05 |
| Messaging | Multi-channel targeted communications | FR-07, INT-07 |
| Safeguarding | Restricted concern logging and case notes | FR-09 |
| Data warehouse | Group dashboards on pseudonymised data | FR-08, INT-09 |

**7.4 Build vs buy:** state which components come from a commercial SIS, which are configured and which are custom-built. Record the choice in the Decisions Log (AD-01).

## 8. Data architecture and data protection

*Instruction: Define the core entities, which system **masters** each one, how data is classified and how long it is kept. In school systems, the student–guardian relationship and custody restrictions are the most error-prone data; design them explicitly.*

**8.1 Core entities and system of record**

| Entity | Key attributes | System of record | Consumers |
| --- | --- | --- | --- |
| Student | ID, name, DOB, year group, school, SEN/medical flags | SMS | LMS, analytics, portals |
| Guardian | ID, contact details, relationship, contact permissions, custody restrictions | SMS | Comms, fees, portal |
| Staff | ID, role, schools, qualifications | HRIS | SMS, identity provider |
| Enrolment | Student, school, class, start/end dates | SMS | LMS, timetabling |
| Attendance mark | Student, session, code, recorded by | SMS | Analytics, comms |
| Assessment result | Student, subject, assessment, grade | SMS | Report cards, analytics |
| Invoice / payment | Account, amount, status | SMS fees module | Finance GL |

**8.2 Data classification**

| Class | Examples | Controls |
| --- | --- | --- |
| Restricted | Safeguarding notes, medical, SEN, custody orders | Named-role access only, field-level encryption, access logged and reviewed |
| Confidential | Grades, attendance, contact details, fees | Role- and school-scoped access, encrypted at rest |
| Internal | Timetables, class lists | Staff access |
| Public | School calendar, prospectus | No restriction |

**8.3 Retention and privacy**

- Retention schedule per entity, aligned to [jurisdiction] education records rules (e.g. student file kept until age 25).

- Data Protection Impact Assessment (DPIA) completed before go-live; reference: [DPIA ID].

- Data residency: all production data and backups stored in [region].

- Subject access and deletion requests handled through an admin workflow with an audit log.

- Analytics uses a pseudonymised data warehouse; no restricted data leaves the SMS.

## 9. Integration architecture

*Instruction: List every interface with direction, pattern, frequency and owner. Prefer education data standards over custom formats; they cut integration cost and vendor lock-in. Route all integrations through one API gateway or integration platform rather than point-to-point.*

**Standards to consider:** OneRoster (rostering to LMS and edtech apps), LTI 1.3 (tool launch), Ed-Fi or SIF (data exchange in some regions), SAML 2.0 / OpenID Connect (SSO), SCIM (user provisioning), and your national statutory return format.

| ID | Interface | Direction | Pattern | Frequency | Data | Standard |
| --- | --- | --- | --- | --- | --- | --- |
| INT-01 | Identity provider (Entra ID) | SMS → IdP | API (SCIM) | Near real-time | Staff and student accounts, groups | SCIM 2.0, OIDC |
| INT-02 | LMS (Moodle) | SMS → LMS | API | Hourly | Classes, enrolments, teachers | OneRoster 1.2 |
| INT-03 | LMS grades | LMS → SMS | API | Daily | Assessment scores | OneRoster 1.2 |
| INT-04 | Finance GL | SMS → Finance | File / API | Daily | Fee journals | Vendor API |
| INT-05 | Payment gateway | Bi-directional | API + webhooks | Real-time | Payments, refunds | PCI-DSS hosted pages |
| INT-06 | HRIS | HRIS → SMS | API | Daily | Staff records | Vendor API |
| INT-07 | SMS / email / push provider | SMS → Provider | API | Real-time | Messages | Provider API |
| INT-08 | Ministry / regulator | SMS → Regulator | File | Termly | Statutory returns | National format |
| INT-09 | Data warehouse | SMS → DW | CDC / batch | Nightly | Pseudonymised facts | Internal |

*Instruction: For each interface, also specify error handling, retries, idempotency and who is alerted **on** failure.*

## 10. Security, identity and compliance

*Instruction: Show how each security control maps to a requirement or regulation. Schools are frequent ransomware targets, and the data concerns minors, so reviewers will **scrutinise** this section most.*

**10.1 Identity and access**

- Staff and students sign in via SSO (OIDC/SAML) against [Entra ID / Google Workspace]; MFA enforced for all staff.

- Parents use a separate customer identity (B2C) tenant with email or phone verification; one account links to all their children across schools.

- Role-based access control scoped by role **and** school **and** class (a teacher sees only their classes).

- Break-glass admin accounts stored in a vault, monitored, reviewed quarterly.

**10.2 Role matrix (sample)**

| Role | Student records | Grades | Safeguarding | Fees | Group reports |
| --- | --- | --- | --- | --- | --- |
| Teacher | Own classes, read | Own classes, write | Log concern only | None | None |
| School admin | Own school, write | Read | None | Read | None |
| Safeguarding lead | Own school, read | Read | Full | None | None |
| Finance officer | Contact only | None | None | Full | Finance only |
| Principal | Own school, read | Read | Read | Read | Own school |
| Group executive | Aggregated only | Aggregated only | None | Aggregated | All |
| Parent | Own children | Own children, read | None | Own account | None |

**10.3 Security controls**

- Encryption: TLS 1.2+ in transit; AES-256 at rest; field-level encryption for restricted data.

- Network: WAF and DDoS protection in front of all public endpoints; private endpoints for databases.

- Application: OWASP ASVS Level 2; annual penetration test plus test before go-live.

- Monitoring: central SIEM, alerting on bulk exports and unusual access to restricted records.

- Backups: immutable, geo-redundant, restore tested each term (ransomware resilience).

**10.4 Compliance checklist**

- ☐ Data protection law: [GDPR / FERPA / COPPA / PDPA / other]

- ☐ Children's code / age-appropriate design requirements

- ☐ PCI-DSS (use hosted payment pages to stay in SAQ-A scope)

- ☐ Accessibility: WCAG 2.2 AA

- ☐ Vendor certifications: ISO 27001, SOC 2 Type II

- ☐ Statutory reporting obligations for [jurisdiction]

## 11. Infrastructure, deployment and operations

*Instruction: Describe hosting, environments, scaling, resilience and the support model. Size for school-day peaks, not averages, and avoid deployments during exam and report periods.*

**11.1 Hosting and environments**

| Environment | Purpose | Data | Hosting |
| --- | --- | --- | --- |
| Dev | Build and unit test | Synthetic | [Cloud], shared |
| Test / SIT | Integration testing | Synthetic | [Cloud] |
| UAT / Training | Business acceptance, staff training | Anonymised copy | [Cloud] |
| Production | Live service | Real | [Cloud], [region], zone-redundant |
| DR | Recovery | Replicated | [Cloud], paired region |

**11.2 Platform choices (example)**

- Compute: containers on managed Kubernetes or App Service, auto-scaling on CPU and request rate.

- Data: managed relational database (e.g. Azure SQL / PostgreSQL) with zone redundancy; object storage for documents.

- Tenancy: one logical tenant per school within a shared platform, with group-level reporting across tenants.

- Delivery: infrastructure as code (Terraform/Bicep), CI/CD with automated tests and security scanning.

**11.3 Operations and support**

- Monitoring: application performance, synthetic checks on login and register at 08:00 each school day.

- Change freeze windows: exam weeks, report-card release, term start (list dates per academic calendar).

- Support tiers: L1 school office champions → L2 group IT service desk → L3 vendor/partner.

- SLAs: P1 (system down in school hours) response 15 min, restore 4 h; P2 response 1 h.

- Capacity review each term; cost reporting per school for chargeback.

## 12. Migration, implementation roadmap and testing

*Instruction: Plan go-lives around the academic calendar. The safest windows are the summer break and the start of a new term; never cut over during exams or report periods. Show phases, gates and their exit criteria.*

**12.1 Roadmap (example)**

implementation roadmap · 4 phases, 3 gates

No school moves to the new system until the pilot gate is passed; each later wave reuses the pilot's migration scripts and training plan.

**12.2 Data migration approach**

- Profile legacy data in every school; report duplicates, orphan records and code mismatches.

- Agree a canonical mapping (year groups, attendance codes, grade scales) with school data owners.

- Migrate current-year students, guardians, staff and enrolments in full; archive history older than [n] years read-only.

- Run at least two rehearsal migrations with reconciliation reports signed off by each school.

- Cut over over a weekend or holiday; freeze legacy for writes; keep it read-only for [n] months.

**12.3 Testing strategy**

| Test type | Scope | Owner | Exit criteria |
| --- | --- | --- | --- |
| Unit and integration | Modules and interfaces INT-01 to INT-09 | Delivery team | All critical paths pass |
| Data migration | Record counts, field checks, reconciliation | Data lead | 100% of active records reconciled |
| Performance | 08:30 registration peak, report-card release | Delivery team | NFR-02 met at 1.5× peak |
| Security | Penetration test, role-access checks | Security architect | No open critical or high findings |
| Accessibility | Parent and student portals | QA | WCAG 2.2 AA |
| User acceptance | End-to-end school scenarios | School super-users | Sign-off by each pilot school |

**12.4 Training and change management:** champions in every school, role-based training before each wave, quick-reference guides for teachers, and parent onboarding campaigns for the app.

## 13. Risks, assumptions, decisions and appendices

*Instruction: Keep these live through delivery. Each risk needs an owner and a mitigation; each decision records the options rejected so it is not reopened later.*

**13.1 Risks**

| ID | Risk | Likelihood | Impact | Mitigation | Owner |
| --- | --- | --- | --- | --- | --- |
| R-01 | Legacy data quality delays migration | High | High | Early data profiling; cleansing sprint; school sign-off of reconciled data | Data lead |
| R-02 | Go-live slips past term start | Medium | High | Fixed pilot date; fallback to run legacy one more term | Programme manager |
| R-03 | Low teacher adoption | Medium | High | Champions per school; training in INSET days; simple register UX | Change lead |
| R-04 | Vendor product gap for local statutory returns | Medium | Medium | Confirm in vendor demo; budget custom report | Solution architect |
| R-05 | Data breach involving minors | Low | Very high | Section 10 controls; DPIA; incident playbook | DPO |

**13.2 Assumptions and constraints**

- Client provides an identity provider and licences for all staff and students.

- Each school nominates a data owner and a super-user.

- Budget and data residency constraints: [state them].

**13.3 Architecture decisions log**

| ID | Decision | Options considered | Rationale | Date |
| --- | --- | --- | --- | --- |
| AD-01 | Buy SIS core, build group extensions | Full build; full buy; buy + extend | Faster time to value; vendor carries statutory updates; extensions cover group needs | [date] |
| AD-02 | Cloud SaaS / PaaS hosting | On-prem; IaaS; SaaS/PaaS | Removes server estate in 12 schools; elastic for peaks | [date] |
| AD-03 | OneRoster for LMS rostering | Custom CSV; vendor API; OneRoster | Standard, supported by Moodle and most edtech | [date] |
| AD-04 | Separate parent identity tenant | Same tenant as staff; separate B2C | Isolates external users; self-service sign-up | [date] |

**13.4 Appendices**

- A. Glossary (SIS, LMS, SEN, RPO/RTO, OneRoster, DPIA).

- B. Full requirements traceability matrix (requirement → component → test case).

- C. Detailed interface specifications.

- D. Vendor evaluation scorecard.

- E. Cost model: one-off and 5-year total cost of ownership.