# Academic database design

The design separates master subjects, versioned curriculum requirements, semester offerings, scheduled classes and enrollment snapshots. Students belong to one curriculum version. Each offering is a capacity-controlled subject section; a student can take offerings across blocks. Pending proposals do not reserve seats. Capacity is rechecked under locks at finalization. If a class fills first, the Registrar must revise the proposal. Section capacity counts distinct finalized students across all offerings in the section. Each offering separately enforces its own capacity. Both checks run under the period locks at proposal/finalization; reopening an enrollment removes that student from finalized section counts.

```mermaid
erDiagram
 users ||--o| students : identity
 programs ||--o{ curriculums : versions
 curriculums ||--o{ curriculum_subjects : requirements
 subjects ||--o{ curriculum_subjects : referenced
 subjects ||--o{ prerequisites : requires
 students ||--o{ academic_records : history
 subjects ||--o{ offerings : offered
 periods ||--o{ offerings : period
 sections ||--o{ offerings : block
 offerings ||--o{ schedules : meetings
 professors ||--o{ schedules : teaches
 rooms ||--o{ schedules : hosts
 students ||--o{ enrollments : requests
 enrollments ||--o{ proposal_items : subjects
 offerings ||--o{ proposal_items : selected
 enrollments ||--o{ enrollment_events : decisions
 enrollments ||--o{ cor_versions : snapshots
 students ||--o{ special_cases : evaluation
```

Unique constraints: external admission and employee identifiers, student email via users, subject/program/room codes, period label, student plus period enrollment, proposal version plus offering, and schedule assignment uniqueness. Foreign keys preserve referenced records. Administrative removal is replaced by deactivation. Registrar-owned data entry uses validation and audit logs. Academic evaluations and proposal decisions are recorded independently.

Before production: validate this schema and per-offering capacity policy with PLP, test locking behavior on MariaDB, and confirm source contracts and grading policy. Development policy requires PASSED or CREDITED for prerequisites, with alternative prerequisite groups supported. No numeric passing-grade rule is assumed.
Additional normalized tables: buildings and floors; room_closures for period-specific recurring restrictions; academic_overrides for student/subject/period exceptions with the approving user; portal_notifications with per-user read status; personal_access_tokens with hashed tokens, abilities and expiration. Subjects include department and description; rooms support equipment and a dedicated subject; prerequisites support optional minimum grades. Periods explicitly record the academic year and semester.

## Evaluation and curriculum relationships

credit_evaluations belongs to special_cases and subjects, and optionally references the academic_records row awarded by that decision. The evaluation supplies the student, academic period, curriculum and reviewer. Subject decisions are retained, including rejections.

curriculum_assignments records student, previous curriculum, new curriculum, Registrar and reason. Previously assigned curricula remain immutable even after the last student changes to another version. special_cases.curriculum_id prevents an evaluation for an old curriculum from authorizing enrollment after reassignment.

offerings.allowed_days optionally narrows the master subject's permitted days. curriculums.academic_year records the applicable curriculum year without replacing the version name.

## Current workflow additions

The operational driver is MySQL against MariaDB. colleges groups programs; subjects record scope and verified weekly contact hours. student_sections uniquely assigns a student per academic period; schedule_locks uniquely finalizes a period/year. student_promotions is unique per student/academic year. subject_waiting_list retains requested/released subjects and the resulting evaluation. professors.user_id uniquely links a Professor login; academic_records retains submitter and submission time. periods includes start/end dates, current selection and immutable closure. Acceptance finalizes seats and the COR in one transaction; Accepted is also exposed as a pipeline milestone.
