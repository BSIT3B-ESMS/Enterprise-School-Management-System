// Recruitment workflow rules. An application's status is not picked by hand: it moves forward only
// when a milestone is reached, and the milestones are recorded as they happen.
//
//   Applied ──complete demo interview──▶ Demo Interview ──complete final interview──▶ Final Interview
//   Final Interview ──HR issues offer──▶ Offered ──applicant accepts + pre-employment information + HR completes hiring──▶ Hired
//   Offered ──applicant declines──▶ Rejected
//   Any open stage ──HR rejects──▶ Rejected
//
// Scheduling a demo interview or final interview only puts it on the calendar; the status changes when that
// activity is marked completed. Once the final interview is completed nothing more is scheduled. Only the applicant
// answers an offer. Hired and Rejected are final. These functions only read plain application objects, so they hold
// no state and can move to a backend unchanged.

export const stageOrder = ['Applied', 'Demo Interview', 'Final Interview', 'Offered', 'Hired'];

export const finalStatuses = ['Hired', 'Rejected'];

// The CSS class that colors a status (see the .stage-* rules in _dashboard.scss): "Demo Interview" is stage-demo-interview.
export function statusClass(status) {
    return `stage-${status.toLowerCase().replace(/\s+/g, '-')}`;
}

export const milestoneLabels = {
    applied: 'Application received',
    demoInterview: 'Demo Interview completed',
    interview: 'Final Interview completed',
    offered: 'Offer issued',
    accepted: 'Offer accepted by the applicant',
    declined: 'Offer declined by the applicant',
    preEmployment: 'Pre-employment information submitted',
    hired: 'Hired',
    rejected: 'Rejected',
};

// The status each activity type moves an application to once it is completed.
export const statusAfterCompleting = { 'Demo Interview': 'Demo Interview', 'Final Interview': 'Final Interview' };

// The milestone recorded when an activity of each type is completed.
export const milestoneKeyFor = { 'Demo Interview': 'demoInterview', 'Final Interview': 'interview' };

/**
 * What can be done next for an application, from its status, offer response, pre-employment information, and
 * activities. `canRespondToOffer` and `canSubmitPreEmployment` are the applicant's own steps; HR has no way to take them.
 *
 * @param {{ status: string, offerResponse?: string, preEmploymentSubmittedAt?: number, activities?: Array<{ type: string, status: string }> }} application
 * @returns {{
 *   isFinal: boolean,
 *   pendingActivity: object|null,
 *   scheduleType: 'Demo Interview'|'Final Interview'|null,
 *   canIssueOffer: boolean,
 *   canUpdateOffer: boolean,
 *   canRespondToOffer: boolean,
 *   canSubmitPreEmployment: boolean,
 *   hasPreEmployment: boolean,
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

    // A completed final interview (status Final Interview) leaves nothing more to schedule: the next step is the offer.
    if (isFree && status === 'Applied') {
        scheduleType = 'Demo Interview';
    } else if (isFree && status === 'Demo Interview') {
        scheduleType = 'Final Interview';
    }

    const isAwaitingResponse = status === 'Offered' && application.offerResponse === 'Pending';
    const hasAccepted = status === 'Offered' && application.offerResponse === 'Accepted';
    const hasPreEmployment = Boolean(application.preEmploymentSubmittedAt);

    return {
        isFinal,
        pendingActivity,
        scheduleType,
        canIssueOffer: isFree && status === 'Final Interview',
        canUpdateOffer: isAwaitingResponse,
        canRespondToOffer: isAwaitingResponse,
        canSubmitPreEmployment: hasAccepted,
        hasPreEmployment,
        canCompleteHiring: hasAccepted && hasPreEmployment && !pendingActivity,
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
        return application.offerResponse === 'Declined'
            ? 'The applicant declined the offer. The application cannot move forward.'
            : 'This application was rejected and cannot move forward.';
    }

    if (workflow.pendingActivity) {
        return `Complete or reschedule the ${workflow.pendingActivity.type.toLowerCase()} in Demo and Final Interviews to continue.`;
    }

    if (workflow.canRespondToOffer) {
        return 'Waiting for the applicant to accept or decline the offer. Only the applicant can respond.';
    }

    if (workflow.canSubmitPreEmployment && !workflow.hasPreEmployment) {
        return 'The applicant accepted the offer. Waiting for their pre-employment information before hiring can be completed.';
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
    const reached = { Applied: true, 'Demo Interview': reachedKeys.has('demoInterview'), 'Final Interview': reachedKeys.has('interview'), Offered: reachedKeys.has('offered'), Hired: reachedKeys.has('hired') };

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
        activities.push({ id: 'a2', type: 'Final Interview', date: on(5), time: '14:00', status: 'Completed', completedOn: on(5) });
    }

    if (reached >= 4) {
        milestones.push({ key: 'offered', at: on(8) });
    }

    if (status === 'Hired') {
        milestones.push({ key: 'accepted', at: on(10) }, { key: 'preEmployment', at: on(11) }, { key: 'hired', at: on(12) });
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
