// Calendar events, filtering, and the month grid. Pure functions over plain data: events are built
// from the same leave requests and recruitment activities the Employees and Recruitment pages use, so
// any change there shows here on the next render.

export const eventTypes = {
    leave: 'Employee Leave',
    screening: 'Applicant Screening',
    interview: 'Applicant Interview',
};

// Leave requests are Filed (submitted), Pending (under review), or Approved; screenings and interviews
// are Scheduled until they are marked Completed. A past scheduled activity is still Scheduled.
export const eventStatuses = ['Filed', 'Pending', 'Approved', 'Scheduled', 'Completed'];

// Statuses drawn solid on the calendar; the others are drawn dashed (not final yet).
export const confirmedStatuses = ['Approved', 'Completed'];

function addDays(isoDate, days) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return isoDateOf(new Date(year, month - 1, day + days));
}

export function isoDateOf(date) {
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/**
 * Every calendar event. Leave spans startDate to endDate; screenings and interviews fall on one date
 * at a time.
 *
 * @param {{ leaveRequests: object[], applications: object[] }} data
 * @returns {Array<{ id: string, type: string, name: string, startDate: string, endDate: string, time: string, status: string, detail: object }>}
 */
export function buildCalendarEvents({ leaveRequests, applications }) {
    const leaveEvents = leaveRequests.map((leave) => ({
        id: `leave-${leave.id}`,
        type: 'leave',
        name: leave.employeeName,
        startDate: leave.startDate,
        endDate: leave.endDate,
        time: '',
        status: leave.status,
        detail: leave,
    }));
    const activityEvents = applications.flatMap((application) => application.activities.map((activity) => ({
        id: `activity-${application.id}-${activity.id}`,
        type: activity.type === 'Screening' ? 'screening' : 'interview',
        name: application.name,
        startDate: activity.date,
        endDate: activity.date,
        time: activity.time,
        status: activity.status,
        detail: { application, activity },
    })));

    return [...leaveEvents, ...activityEvents];
}

/**
 * Events whose type and status are both selected (AND across the two; any of the chosen values within each).
 *
 * @param {{ types: string[], statuses: string[] }} filters
 */
export function filterEvents(events, { types, statuses }) {
    return events.filter((event) => types.includes(event.type) && statuses.includes(event.status));
}

export function occursOn(event, isoDate) {
    return event.startDate <= isoDate && isoDate <= event.endDate;
}

/**
 * Events on one day, with timed ones (screenings and interviews) after leave and in time order.
 */
export function eventsOn(events, isoDate) {
    return events
        .filter((event) => occursOn(event, isoDate))
        .sort((first, second) => (first.time || '').localeCompare(second.time || '') || first.name.localeCompare(second.name));
}

/**
 * Events that touch any day of the month (0-based month).
 */
export function eventsInMonth(events, year, month) {
    const first = isoDateOf(new Date(year, month, 1));
    const last = isoDateOf(new Date(year, month + 1, 0));

    return events.filter((event) => event.startDate <= last && event.endDate >= first);
}

/**
 * The month as weeks of seven ISO dates, Sunday first, padded with days from the next and previous months.
 *
 * @returns {Array<Array<{ date: string, inMonth: boolean }>>}
 */
export function monthGrid(year, month) {
    const first = new Date(year, month, 1);
    const start = addDays(isoDateOf(first), -first.getDay());
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const weekCount = Math.ceil((first.getDay() + daysInMonth) / 7);
    const weeks = [];

    for (let week = 0; week < weekCount; week++) {
        weeks.push(Array.from({ length: 7 }, (_, day) => {
            const date = addDays(start, week * 7 + day);

            return { date, inMonth: Number(date.slice(5, 7)) === month + 1 };
        }));
    }

    return weeks;
}
