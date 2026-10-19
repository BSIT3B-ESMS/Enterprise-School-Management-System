// Recruitment workflow rules. An application's status is not picked by hand: it moves forward only
// when a milestone is reached, and the milestones are recorded as they happen.
//
//   Applied ──complete demo interview──▶ Demo Interview ──complete interview──▶ Interview
//   Interview ──issue offer──▶ Offered ──offer accepted + complete hiring──▶ Hired
//   Any open stage ──reject──▶ Rejected
//
// Scheduling a demo interview or interview only puts it on the calendar; the status changes when that
// activity is marked completed. Hired and Rejected are final. These functions only read plain
// application objects, so they hold no state and can move to a backend unchanged.

export const stageOrder = ['Applied', 'Demo Interview', 'Interview', 'Offered', 'Hired'];

export const finalStatuses = ['Hired', 'Rejected'];

// The CSS class that colors a status (see the .stage-* rules in _dashboard.scss): "Demo Interview" is stage-demo-interview.
export function statusClass(status) {
    return `stage-${status.toLowerCase().replace(/\s+/g, '-')}`;
}

export const milestoneLabels = {
    applied: 'Application received',
    demoInterview: 'Demo Interview completed',
    interview: 'Interview completed',
    offered: 'Offer issued',
    accepted: 'Offer accepted',
    hired: 'Hired',
    rejected: 'Rejected',
};

// The status each activity type moves an application to once it is completed.
export const statusAfterCompleting = { 'Demo Interview': 'Demo Interview', Interview: 'Interview' };

// The milestone recorded when an activity of each type is completed.
export const milestoneKeyFor = { 'Demo Interview': 'demoInterview', Interview: 'interview' };

/**
 * What can be done next for an application, from its status, offer response, and activities.
 *
 * @param {{ status: string, offerResponse?: string, activities?: Array<{ type: string, status: string }> }} application
 * @returns {{
 *   isFinal: boolean,
 *   pendingActivity: object|null,
 *   scheduleType: 'Demo Interview'|'Interview'|null,
 *   canIssueOffer: boolean,
 *   canRecordAcceptance: boolean,
 *   canCompleteHiring: boolean,
 *   canReject: boolean,
 * }}
 */
export function workflowFor(application) {
    const { status } = application;
    const isFinal = finalStatuses.includes(status);
    // Only the recruitment steps count; an "Other" schedule (set on the HR Calendar) never holds up the workflow.
    const pendingActivity = (application.activities ?? []).find((activity) => activity.status === 'Scheduled' && activity.type !== 'Other') ?? null;
    const isFree = !isFinal && !pendingActivity;
    let scheduleType = null;

    if (isFree && status === 'Applied') {
        scheduleType = 'Demo Interview';
    } else if (isFree && (status === 'Demo Interview' || status === 'Interview')) {
        scheduleType = 'Interview';
    }

    return {
        isFinal,
        pendingActivity,
        scheduleType,
        canIssueOffer: isFree && status === 'Interview',
        canRecordAcceptance: status === 'Offered' && application.offerResponse === 'Pending',
        canCompleteHiring: status === 'Offered' && application.offerResponse === 'Accepted',
        canReject: !isFinal,
    };
}

/**
 * Why the next step is not available yet, for empty states: a sentence, or '' when something can be done.
 */
export function waitingMessage(application) {
    const workflow = workflowFor(application);

    if (application.status === 'Hired') {
        return 'This applicant has been hired. There are no further recruitment steps.';
    }

    if (application.status === 'Rejected') {
        return 'This application was rejected and cannot move forward.';
    }

    if (workflow.pendingActivity) {
        return `Complete or reschedule the ${workflow.pendingActivity.type.toLowerCase()} below to continue.`;
    }

    if (application.status === 'Offered' && application.offerResponse === 'Pending') {
        return 'Waiting for the applicant to respond to the offer.';
    }

    return '';
}

/**
 * Progress through the five stages for the tracker: each is 'done', 'current', or 'pending'.
 * A rejected application shows the stages it reached as done and nothing as current.
 *
 * @returns {Array<{ stage: string, state: 'done'|'current'|'pending' }>}
 */
export function progressSteps(application) {
    const reachedKeys = new Set((application.milestones ?? []).map((milestone) => milestone.key));
    const reached = { Applied: true, 'Demo Interview': reachedKeys.has('demoInterview'), Interview: reachedKeys.has('interview'), Offered: reachedKeys.has('offered'), Hired: reachedKeys.has('hired') };

    return stageOrder.map((stage) => {
        if (application.status === stage) {
            return { stage, state: stage === 'Hired' ? 'done' : 'current' };
        }

        return { stage, state: reached[stage] ? 'done' : 'pending' };
    });
}

function addDays(isoDate, days) {
    const [year, month, day] = isoDate.split('-').map(Number);
    const date = new Date(year, month - 1, day + days);
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/**
 * Milestones, completed activities, and offer response for an application that only has a status
 * (the sample data and applications saved before the workflow existed), so its history agrees with
 * where it stands. Dates run forward from the application date, never past today.
 */
export function progressForStatus(application, today) {
    const status = stageOrder.includes(application.status) || application.status === 'Rejected' ? application.status : 'Applied';
    const reached = status === 'Rejected' ? 1 : stageOrder.indexOf(status) + 1;
    const on = (days) => {
        const date = addDays(application.appliedOn, days);

        return date > today ? today : date;
    };
    const milestones = [{ key: 'applied', at: application.appliedOn }];
    const activities = [];

    if (reached >= 2) {
        milestones.push({ key: 'demoInterview', at: on(2) });
        activities.push({ id: 'a1', type: 'Demo Interview', date: on(2), time: '10:00', status: 'Completed', completedOn: on(2) });
    }

    if (reached >= 3) {
        milestones.push({ key: 'interview', at: on(5) });
        activities.push({ id: 'a2', type: 'Interview', date: on(5), time: '14:00', status: 'Completed', completedOn: on(5) });
    }

    if (reached >= 4) {
        milestones.push({ key: 'offered', at: on(8) });
    }

    if (status === 'Hired') {
        milestones.push({ key: 'accepted', at: on(10) }, { key: 'hired', at: on(12) });
    }

    if (status === 'Rejected') {
        milestones.push({ key: 'rejected', at: on(3) });
    }

    return {
        ...application,
        status,
        milestones,
        activities,
        offerResponse: status === 'Offered' ? 'Pending' : status === 'Hired' ? 'Accepted' : '',
        employeeId: application.employeeId ?? '',
    };
}
