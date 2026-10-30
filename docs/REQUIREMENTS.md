# Requirements baseline

Source: ENROLLMENT-MANAGEMENT-SYSTEM.docx supplied on October 30, 2026. The complete system, including the specified Laravel/PHP/MariaDB/Bootstrap/jQuery stack, is the target. No hosted Sites deployment is planned for this local Laravel checkout.

## Approval flows

Regular: approved Admission record -> Registrar proposal -> student acceptance or revision -> Registrar finalization -> official COR.

Irregular: back-subject request -> Department Head evaluation -> student acceptance of special assignments -> Registrar adds eligible subjects -> student acceptance of final proposal -> Registrar finalization -> official COR.

Transferee/returning: evaluation of prior records, credits, and applicable curriculum precedes the proposal flow. Confirm whether both student acceptance stages apply to these classifications.

## Boundaries

Admission owns approved student identity and provisioning input. No independent public student registration. EMS owns professor identity and employment facts. Integrate via authorized API or approved file exchange, never external database access. Academic grades must come from an authorized source yet to be confirmed.

Master subjects, versioned curriculum subjects, semester offerings, actual class meetings, academic evaluations and final enrollments are distinct entities. Preserve history through deactivation and revisions.

Server checks must enforce prerequisite completion, authorized overrides with reasons, offering availability, allowed days, active qualified professors, room compatibility, room and section capacity, and room/professor/section/student time conflicts. Finalization requires student acceptance of the current proposal version and database transactions protecting capacity under concurrency. Only finalized records generate official versioned CORs.

## Decisions required before dependent modules

- Actual Admission and EMS contracts, authentication, identifiers, field availability and failure behavior.
- Academic record source, grading/pass rules, credit equivalence, curriculum assignment, unit limits and authorized override policy.
- Department Head scope by department and direct schedule-assignment permissions.
- Reservation lifetime and capacity accounting for pending proposals; per-class membership for irregular students across blocks.
- Verified rooms, professor availability workflow, time grids and required COR template.

The default section size of 40 and sample campus room list are configurable examples, not hardcoded policy.