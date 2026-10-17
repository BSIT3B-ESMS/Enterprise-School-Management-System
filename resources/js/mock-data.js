// Frontend-only sample data. Employees, departments, job titles, applications, leave requests, and
// activity are saved in localStorage so changes carry across pages; everything else is fixed for now. In a future phase, replace this
// module with backend calls that return the same shapes.

import { formatDate, formatTime, initialsOf } from './format.js';
import { progressForStatus, statusAfterCompleting, workflowFor } from './recruitment-workflow.js';

const storageKey = 'ems-mock-data';
const storageVersion = 11;

const benefitNames = [
    'SSS',
    'PhilHealth',
    'Pag-IBIG',
    '13th Month Pay',
    'Paid Leave Credits',
    'Health Insurance (HMO)',
    'Transportation Allowance',
    'Research Allowance',
];

// What each benefit is, as the Employee interface describes it. Who is eligible comes from the job titles
// (the benefits each one includes), so it always matches what HR sets.
const benefitDescriptions = {
    SSS: 'Social Security System contributions that cover retirement, disability, sickness, and death benefits.',
    PhilHealth: 'National health insurance contributions that help cover hospital and medical expenses.',
    'Pag-IBIG': 'Pag-IBIG Fund contributions for savings, with access to housing and short-term loans.',
    '13th Month Pay': 'A yearly pay equal to one-twelfth of the basic salary earned during the year.',
    'Paid Leave Credits': 'Paid time off for vacation, sick, and emergency leave.',
    'Health Insurance (HMO)': 'Health maintenance organization coverage for medical consultations and hospitalization.',
    'Transportation Allowance': 'A regular allowance to help with the cost of commuting to work.',
    'Research Allowance': 'Funding that supports research work and related expenses.',
};

// The signed-in HR user's account. Kept apart from the employee and applicant records; `state.hrProfile`
// starts from this and holds the user's edits.
const seedHrProfile = {
    name: 'Maria Santos',
    email: 'maria.santos@company.com',
    role: 'Admin',
};

const seedDepartments = [
    { id: 'd1', name: 'Science & Mathematics' },
    { id: 'd2', name: 'Computer Science' },
    { id: 'd3', name: 'Humanities & Social Sciences' },
    { id: 'd4', name: 'Education' },
    { id: 'd5', name: 'Business Administration' },
    { id: 'd6', name: 'Engineering' },
    { id: 'd7', name: 'Allied Health Sciences' },
    { id: 'd8', name: 'Languages & Literature' },
];

// `openings` is the number of open vacancies for the position.
const seedJobTitles = [
    { id: 't1', name: 'College Dean', departmentId: 'd5', salary: 110000, benefits: benefitNames.slice(0, 8), openings: 0 },
    { id: 't2', name: 'Department Chair', departmentId: 'd2', salary: 92000, benefits: benefitNames.slice(0, 8), openings: 1 },
    { id: 't3', name: 'Professor', departmentId: 'd6', salary: 88000, benefits: benefitNames.slice(0, 7), openings: 3 },
    { id: 't4', name: 'Associate Professor', departmentId: 'd1', salary: 72000, benefits: benefitNames.slice(0, 7), openings: 2 },
    { id: 't5', name: 'Assistant Professor', departmentId: 'd1', salary: 58000, benefits: benefitNames.slice(0, 6), openings: 4 },
    { id: 't6', name: 'Senior Lecturer', departmentId: 'd3', salary: 48000, benefits: benefitNames.slice(0, 6), openings: 1 },
    { id: 't7', name: 'Instructor', departmentId: 'd4', salary: 36000, benefits: benefitNames.slice(0, 5), openings: 2 },
    { id: 't8', name: 'Visiting Professor', departmentId: 'd7', salary: 65000, benefits: benefitNames.slice(0, 5), openings: 0 },
    { id: 't9', name: 'Lecturer', departmentId: 'd8', salary: 40000, benefits: benefitNames.slice(0, 5), openings: 1 },
    { id: 't10', name: 'Research Associate', departmentId: 'd2', salary: 45000, benefits: benefitNames.slice(0, 6), openings: 2 },
];

// Every action an activity (audit log) entry can record.
export const activityActions = ['Login', 'Logout', 'Created', 'Updated', 'Deleted', 'Hired'];

// Recruitment pipeline order; every application is in exactly one of these.
export const applicationStatuses = ['Applied', 'Screening', 'Interview', 'Offered', 'Hired', 'Rejected'];

// The reference's ten applicants: name, job title, status, and days before today they applied.
const seedApplications = [
    ['Juan Dela Cruz', 't5', 'Applied', 2],
    ['Patricia Lumbao', 't4', 'Screening', 5],
    ['Rafael Ignacio', 't10', 'Interview', 9],
    ['Isabel Hernandez', 't2', 'Offered', 14],
    ['Daniel Perez', 't7', 'Applied', 1],
    ['Liza Maglaya', 't9', 'Rejected', 21],
    ['Gabriel Ocampo', 't3', 'Interview', 11],
    ['Teresa Villar', 't8', 'Screening', 6],
    ['Eduardo Panganiban', 't6', 'Hired', 30],
    ['Bianca Aguilar', 't5', 'Applied', 0],
];

// The newest employees, with full records (salary and benefits default from the job title).
const featuredEmployees = [
    {
        name: 'Maria Clara Santos', departmentId: 'd2', jobTitleId: 't4', hiredOn: '2024-10-24', salary: 95000,
        birthdate: '1992-05-14', sex: 'Female', civilStatus: 'Single', email: 'mariaclara.santos@company.com',
        phone: '+63 917 123 4567', address: '123 Ayala Avenue, Makati City, Metro Manila',
    },
    {
        name: 'Juan Carlos Reyes', departmentId: 'd3', jobTitleId: 't5', hiredOn: '2024-10-22',
        birthdate: '1988-11-03', sex: 'Male', civilStatus: 'Married', email: 'juancarlos.reyes@company.com',
        phone: '+63 918 234 5678', address: '45 Katipunan Avenue, Quezon City',
    },
    {
        name: 'Angelica De la Cruz', departmentId: 'd5', jobTitleId: 't3', hiredOn: '2024-10-18',
        birthdate: '1979-02-21', sex: 'Female', civilStatus: 'Married', email: 'angelica.delacruz@company.com',
        phone: '+63 919 345 6789', address: '210 Ortigas Avenue, Pasig City',
    },
    {
        name: 'Mark Anthony Bautista', departmentId: 'd1', jobTitleId: 't2', hiredOn: '2024-10-15',
        birthdate: '1975-07-09', sex: 'Male', civilStatus: 'Married', email: 'markanthony.bautista@company.com',
        phone: '+63 920 456 7890', address: '88 España Boulevard, Manila',
    },
    {
        name: 'Kristine Joy Mendoza', departmentId: 'd4', jobTitleId: 't9', hiredOn: '2024-10-10',
        birthdate: '1995-09-30', sex: 'Female', civilStatus: 'Single', email: 'kristinejoy.mendoza@company.com',
        phone: '+63 921 567 8901', address: '17 Shaw Boulevard, Mandaluyong City',
    },
    {
        name: 'Paolo Luis Villanueva', departmentId: 'd6', jobTitleId: 't4', hiredOn: '2024-09-28',
        birthdate: '1986-04-17', sex: 'Male', civilStatus: 'Married', email: 'paololuis.villanueva@company.com',
        phone: '+63 922 678 9012', address: '302 Marcos Highway, Antipolo, Rizal',
    },
    {
        name: 'Catherine Rose Ramos', departmentId: 'd1', jobTitleId: 't7', hiredOn: '2024-09-15',
        birthdate: '1997-12-05', sex: 'Female', civilStatus: 'Single', email: 'catherinerose.ramos@company.com',
        phone: '+63 923 789 0123', address: '64 Roxas Boulevard, Pasay City',
    },
    {
        name: 'Jose Mari Alcantara', departmentId: 'd3', jobTitleId: 't5', hiredOn: '2024-08-30',
        birthdate: '1990-06-26', sex: 'Male', civilStatus: 'Single', email: 'josemari.alcantara@company.com',
        phone: '+63 924 890 1234', address: '155 Commonwealth Avenue, Quezon City',
    },
];

const firstNames = ['Maria', 'Juan', 'Angelica', 'Mark', 'Kristine', 'Paolo', 'Catherine', 'Jose', 'Ana', 'Miguel', 'Patricia', 'Rafael', 'Isabel', 'Carlo', 'Teresa', 'Daniel', 'Liza', 'Gabriel', 'Rosario', 'Francis', 'Nicole', 'Ramon', 'Jasmine', 'Eduardo', 'Bianca', 'Vincent', 'Cecilia', 'Arnel', 'Michelle', 'Joseph'];
const middleNames = ['Clara', 'Carlos', 'Luis', 'Rose', 'Joy', 'Mari', 'Anne', 'Grace', 'Marie', 'Paul', 'Jean', 'Dela', 'Mae', 'Santos', 'Louise', 'Ann'];
const lastNames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Mendoza', 'Villanueva', 'Ramos', 'Alcantara', 'Garcia', 'Torres', 'Flores', 'Aquino', 'Castillo', 'Navarro', 'Domingo', 'Morales', 'Salazar', 'Valdez', 'Pascual', 'Soriano', 'Lim', 'Tan', 'Dizon', 'Mercado', 'Rivera', 'Fernandez', 'Lopez', 'Gonzales', 'Ocampo', 'Manalo'];
const streets = ['Katipunan Avenue, Quezon City', 'España Boulevard, Manila', 'Ortigas Avenue, Pasig City', 'Shaw Boulevard, Mandaluyong', 'Roxas Boulevard, Pasay City', 'Commonwealth Avenue, Quezon City', 'Aguinaldo Highway, Imus, Cavite', 'Marcos Highway, Antipolo, Rizal'];

function isoDateOf(date) {
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

// Every twelfth sample employee is on leave today (they get an approved leave that covers today).
function employmentStatusFor(index) {
    return index % 12 === 5 ? 'On Leave' : 'Active';
}

function findById(items, id) {
    return items.find((item) => item.id === id);
}

function seededRandom(seed) {
    let value = seed;

    return () => {
        value |= 0;
        value = (value + 0x6d2b79f5) | 0;
        let mixed = Math.imul(value ^ (value >>> 15), 1 | value);
        mixed = (mixed + Math.imul(mixed ^ (mixed >>> 7), 61 | mixed)) ^ mixed;

        return ((mixed ^ (mixed >>> 14)) >>> 0) / 4294967296;
    };
}

/**
 * The reference's 148-person roster: the featured employees plus a seeded, repeatable remainder.
 */
function generateRoster() {
    const random = seededRandom(20241024);
    const pick = (items) => items[Math.floor(random() * items.length)];
    const pad = (number) => String(number).padStart(2, '0');
    const isoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const remainingHeadcounts = { d1: 22, d2: 17, d3: 19, d4: 13, d5: 15, d6: 25, d7: 19, d8: 10 };
    const departmentBag = Object.entries(remainingHeadcounts).flatMap(([id, count]) => Array(count).fill(id));
    const jobTitleBag = ['t3', 't4', 't4', 't5', 't5', 't5', 't6', 't6', 't7', 't7', 't9', 't9', 't8', 't10', 't2', 't1'];

    for (let index = departmentBag.length - 1; index > 0; index--) {
        const swapIndex = Math.floor(random() * (index + 1));

        [departmentBag[index], departmentBag[swapIndex]] = [departmentBag[swapIndex], departmentBag[index]];
    }

    let hiredDate = new Date(2024, 7, 25);
    const roster = [...featuredEmployees];

    departmentBag.forEach((departmentId) => {
        hiredDate = new Date(hiredDate.getTime() - (10 + Math.floor(random() * 30)) * 86400000);

        const first = pick(firstNames);
        const middle = pick(middleNames);
        const last = pick(lastNames);

        roster.push({
            name: `${first} ${middle} ${last}`,
            departmentId,
            jobTitleId: pick(jobTitleBag),
            hiredOn: isoDate(hiredDate),
            sex: random() < 0.55 ? 'Female' : 'Male',
            civilStatus: pick(['Single', 'Married', 'Single', 'Married', 'Widowed']),
            birthdate: `${1962 + Math.floor(random() * 38)}-${pad(1 + Math.floor(random() * 12))}-${pad(1 + Math.floor(random() * 28))}`,
            email: `${`${first}.${last}`.toLowerCase().replace(/[^a-z.]/g, '')}${Math.floor(random() * 90 + 10)}@company.com`,
            phone: `+63 9${pad(Math.floor(random() * 100))} ${Math.floor(random() * 900 + 100)} ${Math.floor(random() * 9000 + 1000)}`,
            address: `${Math.floor(random() * 400 + 10)} ${pick(streets)}`,
        });
    });

    return roster.map((employee, index) => {
        const jobTitle = findById(seedJobTitles, employee.jobTitleId);

        const id = `EMP-${1001 + index}`;

        return {
            id,
            ...employee,
            salary: employee.salary ?? jobTitle.salary,
            benefits: [...jobTitle.benefits],
            ...(id === portalEmployeeId ? { documents: portalDocuments() } : {}),
        };
    });
}

function seedJobTitleState() {
    return {
        jobTitles: seedJobTitles.map((jobTitle) => ({ ...jobTitle, benefits: [...jobTitle.benefits] })),
        nextJobTitleNumber: seedJobTitles.length + 1,
    };
}

function seedDepartmentState() {
    return {
        departments: seedDepartments.map((department) => ({ ...department })),
        nextDepartmentNumber: seedDepartments.length + 1,
    };
}

function seedApplicationState() {
    const today = isoDateOf(new Date());
    const applications = seedApplications.map(([name, jobTitleId, status, daysAgo], index) => {
        const appliedOn = new Date();
        const nameParts = name.toLowerCase().split(' ');

        appliedOn.setDate(appliedOn.getDate() - daysAgo);

        return progressForStatus({
            id: `c${index + 1}`,
            name,
            email: `${nameParts[0]}.${nameParts[nameParts.length - 1]}@mail.com`,
            phone: `+63 9${17 + index} 555 ${1000 + index * 37}`,
            jobTitleId,
            departmentId: findById(seedJobTitles, jobTitleId).departmentId,
            appliedOn: isoDateOf(appliedOn),
            status,
        }, today);
    });

    // Upcoming activities (scheduled, not completed, so the statuses above stay as they are).
    [['c1', 'Screening', 2, '10:00'], ['c10', 'Screening', 1, '09:00'], ['c2', 'Interview', 3, '14:00'], ['c8', 'Interview', 6, '10:30']]
        .forEach(([id, type, days, time]) => {
            const application = findById(applications, id);

            application.activities.push({ id: `a${application.activities.length + 1}`, type, date: addDaysIso(today, days), time, status: 'Scheduled' });
        });

    return { applications, nextApplicationNumber: applications.length + 1 };
}

/**
 * Gives entries saved before activity had IDs (version 4 and earlier) one, numbering from the
 * oldest so existing IDs never change.
 */
function withActivityIds({ activity, nextActivityNumber = 1 }) {
    let next = Math.max(nextActivityNumber, ...activity.map((entry) => Number(String(entry.id ?? '').slice(1)) + 1 || 1));
    const numbered = [...activity].reverse().map((entry) => (entry.id ? entry : { ...entry, id: `l${next++}` })).reverse();

    return { activity: numbered, nextActivityNumber: next };
}

const leaveTypes = ['Vacation Leave', 'Sick Leave', 'Emergency Leave', 'Vacation Leave'];

// Other sample leave, by order: days from today it starts, how many days, and its status. Only
// future requests are still Filed or Pending.
const otherLeavePlan = [
    [-20, 2, 'Approved'], [-9, 3, 'Approved'], [4, 2, 'Filed'], [9, 3, 'Pending'], [13, 1, 'Approved'],
    [17, 2, 'Filed'], [22, 3, 'Pending'], [27, 2, 'Approved'], [-3, 2, 'Approved'],
];

// The employee the Employee interface is signed in as (Juan Carlos Reyes). The leave below is his: days
// from today it starts, how many days, type, and status. HR sees it too, on the Calendar and his profile.
const portalEmployeeId = 'EMP-1002';
const portalLeavePlan = [
    [-35, 2, 'Sick Leave', 'Approved'],
    [10, 3, 'Vacation Leave', 'Approved'],
    [32, 2, 'Vacation Leave', 'Approved'],
    [18, 1, 'Emergency Leave', 'Pending'],
    [50, 2, 'Vacation Leave', 'Filed'],
];

// The 201 file document categories, in the order the HR employee form lists them: [key, label].
const documentCategories = [
    ['hiringRequirements', 'Hiring Requirements'],
    ['birForm2316', 'BIR Form 2316'],
    ['education', 'Education Transcripts or Diplomas'],
    ['workHistory', 'Work History'],
    ['performance', 'Performance Assessments'],
    ['correctiveActions', 'Corrective Actions'],
    ['clearances', 'Clearances'],
    ['postEmployment', 'Post-Employment Information'],
];

// Sample 201 file records for the signed-in employee. Like every document record here they hold only the
// file's name, size, and the date it was added; no file content is kept.
function portalDocuments() {
    return {
        hiringRequirements: [
            { name: 'Employment Contract.pdf', size: 184320, addedOn: '2024-10-22' },
            { name: 'NBI Clearance.pdf', size: 96256, addedOn: '2024-10-21' },
            { name: 'Medical Certificate.pdf', size: 142336, addedOn: '2024-10-21' },
        ],
        birForm2316: [{ name: 'BIR Form 2316 - 2025.pdf', size: 71680, addedOn: '2026-02-03' }],
        education: [
            { name: 'Diploma - Master of Arts in Education.pdf', size: 1258291, addedOn: '2024-10-18' },
            { name: 'Transcript of Records.pdf', size: 2097152, addedOn: '2024-10-18' },
        ],
        workHistory: [{ name: 'Certificate of Employment - Previous School.pdf', size: 88064, addedOn: '2024-10-18' }],
        performance: [],
        correctiveActions: [],
        clearances: [],
        postEmployment: [],
    };
}

function portalLeaveRequests(firstNumber, today) {
    return portalLeavePlan.map(([startOffset, days, type, status], index) => {
        const startDate = addDaysIso(today, startOffset);

        return {
            id: `lv${firstNumber + index}`,
            employeeId: portalEmployeeId,
            type,
            startDate,
            endDate: addDaysIso(startDate, days - 1),
            status,
            filedOn: addDaysIso(today, Math.min(startOffset, 0) - 7),
        };
    });
}

/**
 * Sample leave requests: the approved leave of everyone on leave today, plus filed, pending, upcoming
 * approved (scheduled), and past leave for a few others, and the signed-in employee's own leave. Dates
 * are relative to today.
 */
function seedLeaveState(employees, today) {
    const leaveRequests = [];
    const add = (employee, type, startOffset, days, status) => {
        const startDate = addDaysIso(today, startOffset);

        leaveRequests.push({
            id: `lv${leaveRequests.length + 1}`,
            employeeId: employee.id,
            type,
            startDate,
            endDate: addDaysIso(startDate, days - 1),
            status,
            filedOn: addDaysIso(today, Math.min(startOffset, 0) - 7),
        });
    };
    let onLeaveCount = 0;
    let otherCount = 0;

    employees.forEach((employee, index) => {
        if ((employee.status ?? employmentStatusFor(index)) === 'On Leave') {
            add(employee, leaveTypes[onLeaveCount % leaveTypes.length], -(onLeaveCount % 4), 3 + (onLeaveCount % 5) + (onLeaveCount % 4), 'Approved');
            onLeaveCount += 1;
        } else if (index % 17 === 3) {
            const [startOffset, days, status] = otherLeavePlan[otherCount % otherLeavePlan.length];

            add(employee, leaveTypes[(otherCount + 1) % leaveTypes.length], startOffset, days, status);
            otherCount += 1;
        }
    });

    if (employees.some((employee) => employee.id === portalEmployeeId)) {
        leaveRequests.push(...portalLeaveRequests(leaveRequests.length + 1, today));
    }

    return { leaveRequests, nextLeaveNumber: leaveRequests.length + 1 };
}

function addDaysIso(isoDate, days) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return isoDateOf(new Date(year, month - 1, day + days));
}

function createInitialState() {
    const now = Date.now();
    const hoursAgo = (hours) => Math.round(now - hours * 3600000);
    const entry = (action, entity, detail, hours) => ({ action, entity, detail, user: seedHrProfile.name, at: hoursAgo(hours) });
    const employees = generateRoster();
    const today = isoDateOf(new Date());

    return {
        version: storageVersion,
        hrProfile: { ...seedHrProfile },
        employees,
        nextEmployeeNumber: 1001 + employees.length,
        ...seedDepartmentState(),
        ...seedJobTitleState(),
        ...seedApplicationState(),
        ...seedLeaveState(employees, today),
        // Newest first; `at` is a timestamp in milliseconds.
        ...withActivityIds({ activity: [
            entry('Login', 'Session', 'Signed in', 0.2),
            entry('Created', 'Candidate · Bianca Aguilar', 'Added to Recruitment as Applied', 3),
            entry('Created', 'Candidate · Juan Dela Cruz', 'Added to Recruitment as Applied', 27),
            entry('Updated', 'Candidate · Isabel Hernandez', 'Stage changed from Interview to Offered', 31),
            entry('Updated', 'Job Title · Department Chair', 'Base salary updated to ₱92,000.00', 52),
            entry('Created', 'Department · Allied Health Sciences', 'Department created', 75),
            entry('Created', 'Employee · Maria Clara Santos (EMP-1001)', 'Employee record created', 96),
            entry('Updated', 'Employee · Juan Carlos Reyes (EMP-1002)', 'Job title changed to Assistant Professor', 120),
            entry('Hired', 'Candidate · Eduardo Panganiban', 'Hired and added to Employees', 170),
            entry('Deleted', 'Job Title · Teaching Assistant', 'Job title removed', 190),
            entry('Updated', 'Department · Education', 'Department renamed', 215),
            entry('Login', 'Session', 'Signed in', 240),
        ] }),
    };
}

function saveState() {
    try {
        localStorage.setItem(storageKey, JSON.stringify(state));
    } catch {
        // Storage unavailable (private mode, blocked, or full); changes last until the page reloads.
    }
}

function loadState() {
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey));

        if (!Array.isArray(saved?.employees) || !Array.isArray(saved.activity)) {
            return null;
        }

        if (!(saved.version >= 1 && saved.version <= storageVersion)) {
            return null;
        }

        // Earlier versions kept departments (1), job titles (2), and applications (3) fixed, and
        // activity had no IDs (4); carry the saved records over and fill in what is missing.
        const migrated = {
            ...saved,
            ...(Array.isArray(saved.departments) ? {} : seedDepartmentState()),
            ...(Array.isArray(saved.jobTitles) ? {} : seedJobTitleState()),
            ...(Array.isArray(saved.applications) ? {} : seedApplicationState()),
            ...withActivityIds(saved),
            // The HR profile (8 and earlier) starts from the sample account.
            hrProfile: saved.hrProfile?.name ? saved.hrProfile : { ...seedHrProfile },
            version: storageVersion,
        };
        const today = isoDateOf(new Date());

        // Employment status (5 and earlier) and job title openings (5 and earlier) start from the
        // sample values, so saved employees and job titles keep their edits.
        const employees = migrated.employees
            .map((employee) => (employee.status
                ? employee
                : { ...employee, status: employmentStatusFor(Number(String(employee.id).replace(/\D/g, '')) - 1001) }))
            // The signed-in employee's sample 201 file records (10 and earlier), only if none are saved.
            .map((employee) => (saved.version < 11 && employee.id === portalEmployeeId && !employee.documents
                ? { ...employee, documents: portalDocuments() }
                : employee));

        // Saved leave (9 and earlier) gets the signed-in employee's sample leave added once.
        const savedLeave = Array.isArray(migrated.leaveRequests) ? migrated.leaveRequests : null;
        const nextLeaveNumber = Math.max(migrated.nextLeaveNumber ?? 0, (savedLeave?.length ?? 0) + 1);
        const addsPortalLeave = saved.version < 10 && savedLeave && employees.some((employee) => employee.id === portalEmployeeId);
        const portalLeave = addsPortalLeave ? portalLeaveRequests(nextLeaveNumber, today) : [];

        return {
            ...migrated,
            employees,
            // Leave requests (7 and earlier) start from the sample, covering whoever was on leave.
            ...(savedLeave ? {} : seedLeaveState(employees, today)),
            ...(addsPortalLeave ? { leaveRequests: [...savedLeave, ...portalLeave], nextLeaveNumber: nextLeaveNumber + portalLeave.length } : {}),
            jobTitles: migrated.jobTitles.map((jobTitle) => (jobTitle.openings === undefined
                ? { ...jobTitle, openings: findById(seedJobTitles, jobTitle.id)?.openings ?? 0 }
                : jobTitle)),
            // Applications saved before the workflow (6 and earlier) get milestones that match their status.
            applications: migrated.applications.map((application) => (
                Array.isArray(application.milestones) ? application : progressForStatus(application, today)
            )),
        };
    } catch {
        // Missing, unreadable, or blocked storage falls through to fresh sample data.
    }

    return null;
}

let state = loadState();

if (!state) {
    state = createInitialState();
    saveState();
}

const changeListeners = [];

function commitChange() {
    saveState();
    changeListeners.forEach((listener) => listener());
}

// Keep other open tabs (for example, the Dashboard) in step with changes made here.
if (typeof window !== 'undefined') {
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) {
            state = loadState() ?? createInitialState();
            changeListeners.forEach((listener) => listener());
        }
    });
}

// Activity entries show salaries with centavos, e.g. "₱92,000.00".
function formatSalary(amount) {
    return `₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function logActivity(action, entity, detail, user = state.hrProfile.name) {
    state.activity.unshift({
        id: `l${state.nextActivityNumber}`,
        action,
        entity,
        detail,
        user,
        at: Date.now(),
    });
    state.nextActivityNumber += 1;
}

function activityWithDates(entries) {
    return entries.map((entry) => ({ ...entry, at: new Date(entry.at) }));
}

/**
 * Whether the employee has approved leave covering today. This is what "On Leave" means everywhere
 * (Employees, profiles, reports, and the Dashboard), so it always agrees with the Calendar.
 */
function isOnLeaveToday(employeeId) {
    const today = isoDateOf(new Date());

    return state.leaveRequests.some((leave) => (
        leave.employeeId === employeeId && leave.status === 'Approved' && leave.startDate <= today && today <= leave.endDate
    ));
}

function withNames(employee) {
    return {
        ...employee,
        jobTitle: findById(state.jobTitles, employee.jobTitleId)?.name ?? '—',
        department: findById(state.departments, employee.departmentId)?.name ?? '—',
        status: isOnLeaveToday(employee.id) ? 'On Leave' : 'Active',
    };
}

function countForStatus(status) {
    return state.applications.filter((application) => application.status === status).length;
}

/**
 * Runs the listener after every change (an added, edited, or deleted employee, department, or job title, or a reset).
 */
export function onMockDataChange(listener) {
    changeListeners.push(listener);
}

/**
 * The HR user as the header, the Dashboard greeting, and the profile page show them.
 */
function currentUserView() {
    const { name, email, role } = state.hrProfile;

    return { name, firstName: name.split(/\s+/)[0], initials: initialsOf(name), email, role };
}

/**
 * The HR user's account: name, initials, email, and role.
 */
export function getHrProfile() {
    return currentUserView();
}

/**
 * Saves the HR user's name and email. The role is not editable here. Shown everywhere the account
 * appears (the header menu, the Dashboard greeting) and logged as account activity.
 */
export function updateHrProfile({ name, email }) {
    state.hrProfile = { ...state.hrProfile, name: name.trim(), email: email.trim() };
    logActivity('Updated', `Account · ${state.hrProfile.name}`, 'Profile details updated');
    commitChange();

    return currentUserView();
}

/**
 * Records that the HR user changed their password. Nothing about the password is kept (the sign-in
 * here is a demo with no passwords to check or store); only the fact that it changed is logged.
 */
export function recordPasswordChange() {
    logActivity('Updated', `Account · ${state.hrProfile.name}`, 'Password changed');
    commitChange();
}

/**
 * Records that the signed-in employee changed their own password, under their own name. As with the HR
 * user's, nothing about the password is kept; only the fact that it changed is logged.
 */
export function recordPortalPasswordChange() {
    const record = findById(state.employees, portalEmployeeId);

    if (!record) {
        return;
    }

    logActivity('Updated', `Employee · ${record.name} (${record.id})`, 'Password changed by the employee', record.name);
    commitChange();
}

/**
 * Data every page inside the app shell (header and sidebar) needs.
 */
export function getShellData() {
    return {
        currentUser: currentUserView(),
        pendingCandidates: countForStatus('Applied'),
        notifications: activityWithDates(state.activity.slice(0, 4)),
    };
}

/**
 * The signed-in employee's own 201 file documents by category, for My Files. `hasContent` is true only
 * for a record that carries file content; records that hold just a filename are placeholders, and there
 * is nothing to preview or download. `employee` is false when that employee no longer exists.
 */
export function getPortalFiles() {
    const record = findById(state.employees, portalEmployeeId);

    if (!record) {
        return { employee: false, categories: [] };
    }

    return {
        employee: true,
        categories: documentCategories.map(([key, label]) => ({
            key,
            label,
            files: (record.documents?.[key] ?? []).map((file) => ({
                name: file.name,
                size: file.size,
                addedOn: file.addedOn,
                hasContent: Boolean(file.content),
            })),
        })),
    };
}

/**
 * The signed-in employee's own leave requests (filed, pending, and approved), and nothing of anyone
 * else's, for the Employee Calendar.
 */
export function getPortalLeave() {
    return state.leaveRequests
        .filter((leave) => leave.employeeId === portalEmployeeId)
        .map(({ id, type, startDate, endDate, status, filedOn }) => ({ id, type, startDate, endDate, status, filedOn }));
}

/**
 * What the Employee interface's Benefits page shows: every benefit with its description, the job
 * titles it is offered with (eligibility), and whether it is included for the signed-in employee.
 * `employee` is null when that employee no longer exists. Nothing about pay or other employees is
 * included.
 */
export function getEmployeeBenefitsData() {
    const record = findById(state.employees, portalEmployeeId);

    if (!record) {
        return { employee: null, benefits: [] };
    }

    const own = record.benefits ?? [];

    return {
        employee: { jobTitle: findById(state.jobTitles, record.jobTitleId)?.name ?? '—' },
        benefits: benefitNames.map((name) => {
            const eligibleJobTitles = state.jobTitles
                .filter((jobTitle) => jobTitle.benefits.includes(name))
                .map((jobTitle) => jobTitle.name)
                .sort((first, second) => first.localeCompare(second));

            return {
                name,
                description: benefitDescriptions[name] ?? '',
                eligibleJobTitles,
                isForAllJobTitles: eligibleJobTitles.length > 0 && eligibleJobTitles.length === state.jobTitles.length,
                isIncluded: own.includes(name),
            };
        }),
    };
}

/**
 * The Directory for the signed-in employee: everyone in the department on their profile, whatever their
 * job title, A to Z (the employee included). Employees of other departments are never returned. Only each
 * person's name, position, and work email are passed on, so addresses, phone numbers, government IDs, pay,
 * and 201 file records never reach the Directory page.
 *
 * `employee` is false when the employee's record no longer exists; `department` is the department's name,
 * or '' when the profile has no department (or one that no longer exists), in which case `people` is empty.
 */
export function getDirectoryData() {
    const record = findById(state.employees, portalEmployeeId);

    if (!record) {
        return { employee: false, department: '', people: [] };
    }

    const department = findById(state.departments, record.departmentId);

    if (!department) {
        return { employee: true, department: '', people: [] };
    }

    return {
        employee: true,
        department: department.name,
        people: state.employees
            .filter((employee) => employee.departmentId === department.id)
            .map(withNames)
            .map((employee) => ({
                name: employee.name,
                initials: initialsOf(employee.name),
                jobTitle: employee.jobTitle,
                email: employee.email,
            }))
            .sort((first, second) => first.name.localeCompare(second.name)),
    };
}

// The only parts of their own record the employee may change; everything else is HR's to edit.
const selfEditableFields = ['civilStatus', 'phone', 'address'];

/**
 * The signed-in employee's full record (with job title, department, and status names), or undefined
 * when HR has deleted it.
 */
export function getPortalProfile() {
    const record = findById(state.employees, portalEmployeeId);

    return record ? withNames(record) : undefined;
}

/**
 * Saves the signed-in employee's own edits. Only civil status, phone, and address are accepted; any
 * other field passed in is ignored. The change is logged under the employee's own name, and nothing is
 * logged when nothing changed.
 *
 * @returns the updated record, or undefined when the employee no longer exists
 */
export function updatePortalProfile(details) {
    const index = state.employees.findIndex((employee) => employee.id === portalEmployeeId);

    if (index === -1) {
        return undefined;
    }

    const current = state.employees[index];
    const changes = Object.fromEntries(
        selfEditableFields.filter((field) => field in details).map((field) => [field, String(details[field]).trim()]),
    );

    if (selfEditableFields.every((field) => !(field in changes) || changes[field] === (current[field] ?? ''))) {
        return withNames(current);
    }

    const employee = { ...current, ...changes };

    state.employees[index] = employee;
    logActivity('Updated', `Employee · ${employee.name} (${employee.id})`, 'Personal details updated by the employee', employee.name);
    commitChange();

    return withNames(employee);
}

// Whole calendar days from one ISO date to another, counting both ends ("2026-10-17" to "2026-10-19" is 3).
function inclusiveDays(startDate, endDate) {
    const toDay = (isoDate) => Date.UTC(...isoDate.split('-').map((part, index) => Number(part) - (index === 1 ? 1 : 0))) / 86400000;

    return Math.round(toDay(endDate) - toDay(startDate)) + 1;
}

// Days of a leave that fall inside a window of dates (0 when they do not overlap).
function leaveDaysWithin(leave, windowStart, windowEnd) {
    const first = leave.startDate > windowStart ? leave.startDate : windowStart;
    const last = leave.endDate < windowEnd ? leave.endDate : windowEnd;

    return first > last ? 0 : inclusiveDays(first, last);
}

/**
 * What the Employee interface shows for the signed-in employee: their details, leave summary,
 * approved leave still ahead (or under way), and benefits. `employee` is null when that employee
 * no longer exists (HR deleted the record).
 */
export function getEmployeePortalData() {
    const record = findById(state.employees, portalEmployeeId);

    if (!record) {
        return { employee: null };
    }

    const employee = withNames(record);
    const today = isoDateOf(new Date());
    const yearStart = `${today.slice(0, 4)}-01-01`;
    const leave = state.leaveRequests.filter((request) => request.employeeId === employee.id);
    const approved = leave.filter((request) => request.status === 'Approved');
    const ahead = approved
        .filter((request) => request.endDate >= today)
        .sort((first, second) => first.startDate.localeCompare(second.startDate));
    const underWay = ahead.find((request) => request.startDate <= today);
    const total = (days) => days.reduce((sum, count) => sum + count, 0);

    return {
        employee: {
            id: employee.id,
            name: employee.name,
            firstName: employee.name.split(/\s+/)[0],
            initials: initialsOf(employee.name),
            email: employee.email,
            phone: employee.phone,
            department: employee.department,
            jobTitle: employee.jobTitle,
            hiredOn: employee.hiredOn,
            status: employee.status,
        },
        benefits: [...(employee.benefits ?? [])],
        leave: {
            // Approved leave days so far this year, and the approved days still ahead (all calendar days).
            takenDays: total(approved.map((request) => leaveDaysWithin(request, yearStart, today))),
            aheadDays: total(ahead.map((request) => leaveDaysWithin(request, today, '9999-12-31'))),
            aheadCount: ahead.length,
            awaitingCount: leave.filter((request) => request.status === 'Filed' || request.status === 'Pending').length,
            onLeaveUntil: underWay?.endDate ?? '',
            year: Number(today.slice(0, 4)),
        },
        upcomingLeave: ahead.map((request) => ({
            id: request.id,
            type: request.type,
            startDate: request.startDate,
            endDate: request.endDate,
            days: inclusiveDays(request.startDate, request.endDate),
            isUnderWay: request.startDate <= today,
            startsInDays: request.startDate > today ? inclusiveDays(today, request.startDate) - 1 : 0,
        })),
    };
}

export function getDashboardData() {
    const hiresByYear = {};

    state.employees.forEach((employee) => {
        const year = Number(employee.hiredOn.slice(0, 4));

        hiresByYear[year] = (hiresByYear[year] ?? 0) + 1;
    });

    return {
        currentUser: currentUserView(),
        employeeCounts: {
            total: state.employees.length,
            active: state.employees.filter((employee) => !isOnLeaveToday(employee.id)).length,
            onLeave: state.employees.filter((employee) => isOnLeaveToday(employee.id)).length,
        },
        openPositions: {
            total: state.jobTitles.reduce((total, jobTitle) => total + Number(jobTitle.openings || 0), 0),
            jobTitleCount: state.jobTitles.filter((jobTitle) => Number(jobTitle.openings || 0) > 0).length,
        },
        departmentHeadcounts: state.departments
            .map(({ id, name }) => ({
                name,
                headcount: state.employees.filter((employee) => employee.departmentId === id).length,
            }))
            .sort((first, second) => second.headcount - first.headcount),
        candidateStages: applicationStatuses.map((stage) => ({ stage, count: countForStatus(stage) })),
        hiresByYear: Object.entries(hiresByYear)
            .map(([year, count]) => ({ year: Number(year), count }))
            .sort((first, second) => first.year - second.year),
        recentHires: [...state.employees]
            .sort((first, second) => second.hiredOn.localeCompare(first.hiredOn))
            .slice(0, 5)
            .map(withNames)
            .map(({ id, name, jobTitle, department, hiredOn }) => ({ id, name, title: jobTitle, department, hiredOn })),
        recentActivity: activityWithDates(state.activity.slice(0, 5)),
    };
}

export function getEmployeeFormOptions() {
    return {
        departments: [...state.departments]
            .sort((first, second) => first.name.localeCompare(second.name))
            .map(({ id, name }) => ({ id, name })),
        jobTitles: [...state.jobTitles]
            .sort((first, second) => first.name.localeCompare(second.name))
            .map(({ id, name, salary, benefits }) => ({ id, name, salary, benefits })),
        benefits: benefitNames,
        nextEmployeeId: `EMP-${state.nextEmployeeNumber}`,
    };
}

/**
 * Every employee with job title and department names.
 */
export function listEmployees() {
    return state.employees.map(withNames);
}

/**
 * A full employee record with its job title and department names, or undefined.
 */
export function findEmployee(id) {
    const employee = findById(state.employees, id);

    return employee ? withNames(employee) : undefined;
}

/**
 * The latest activity recorded against one employee, newest first.
 */
export function getEmployeeHistory(id, limit = 5) {
    return activityWithDates(state.activity.filter((entry) => entry.entity.includes(`(${id})`)).slice(0, limit));
}

export function addEmployee(details) {
    const employee = { id: `EMP-${state.nextEmployeeNumber}`, ...details };

    state.nextEmployeeNumber += 1;
    state.employees.push(employee);
    logActivity('Created', `Employee · ${employee.name} (${employee.id})`, 'Employee record created');
    commitChange();

    return employee;
}

export function updateEmployee(id, details) {
    const index = state.employees.findIndex((employee) => employee.id === id);

    if (index === -1) {
        return undefined;
    }

    const employee = { ...state.employees[index], ...details, id };

    state.employees[index] = employee;
    logActivity('Updated', `Employee · ${employee.name} (${id})`, 'Employee record updated');
    commitChange();

    return employee;
}

export function deleteEmployee(id) {
    const employee = findById(state.employees, id);

    if (!employee) {
        return undefined;
    }

    state.employees = state.employees.filter((candidate) => candidate.id !== id);
    state.leaveRequests = state.leaveRequests.filter((leave) => leave.employeeId !== id);
    logActivity('Deleted', `Employee · ${employee.name} (${id})`, 'Employee record deleted');
    commitChange();

    return employee;
}

function departmentUsage(id) {
    return {
        headcount: state.employees.filter((employee) => employee.departmentId === id).length,
        jobTitleCount: state.jobTitles.filter((jobTitle) => jobTitle.departmentId === id).length,
        applicationCount: state.applications.filter((application) => application.departmentId === id).length,
    };
}

/**
 * Every department with its headcount and number of job titles.
 */
export function listDepartments() {
    return state.departments.map((department) => ({ ...department, ...departmentUsage(department.id) }));
}

/**
 * A department with its headcount, job titles, and newest faculty, or undefined.
 */
export function findDepartment(id, facultyLimit = 5) {
    const department = findById(state.departments, id);

    if (!department) {
        return undefined;
    }

    return {
        ...department,
        ...departmentUsage(id),
        jobTitles: state.jobTitles
            .filter((jobTitle) => jobTitle.departmentId === id)
            .map(({ id: jobTitleId, name, salary }) => ({ id: jobTitleId, name, salary })),
        recentFaculty: state.employees
            .filter((employee) => employee.departmentId === id)
            .sort((first, second) => second.hiredOn.localeCompare(first.hiredOn))
            .slice(0, facultyLimit)
            .map(withNames)
            .map(({ id: employeeId, name, jobTitle, hiredOn }) => ({ id: employeeId, name, jobTitle, hiredOn })),
    };
}

/**
 * Whether another department already uses this name (ignoring case and surrounding spaces).
 */
export function isDepartmentNameTaken(name, exceptId = null) {
    const normalized = name.trim().toLowerCase();

    return state.departments.some((department) => department.id !== exceptId && department.name.toLowerCase() === normalized);
}

export function addDepartment(name) {
    const department = { id: `d${state.nextDepartmentNumber}`, name: name.trim() };

    state.nextDepartmentNumber += 1;
    state.departments.push(department);
    logActivity('Created', `Department · ${department.name}`, 'Department created');
    commitChange();

    return department;
}

export function updateDepartment(id, name) {
    const department = findById(state.departments, id);

    if (!department) {
        return undefined;
    }

    const previousName = department.name;

    department.name = name.trim();
    logActivity(
        'Updated',
        `Department · ${department.name}`,
        previousName === department.name ? 'Department saved' : `Renamed from ${previousName}`,
    );
    commitChange();

    return department;
}

/**
 * Removes a department that has no employees, job titles, or applications assigned.
 *
 * @returns {{ department?: object, headcount: number, jobTitleCount: number, applicationCount: number }} `department` is set only when it was deleted.
 */
export function deleteDepartment(id) {
    const department = findById(state.departments, id);

    if (!department) {
        return { headcount: 0, jobTitleCount: 0, applicationCount: 0 };
    }

    const usage = departmentUsage(id);

    if (usage.headcount || usage.jobTitleCount || usage.applicationCount) {
        return usage;
    }

    state.departments = state.departments.filter((candidate) => candidate.id !== id);
    logActivity('Deleted', `Department · ${department.name}`, 'Department removed');
    commitChange();

    return { department, ...usage };
}

function withDepartmentName(jobTitle) {
    return {
        ...jobTitle,
        benefits: [...jobTitle.benefits],
        department: findById(state.departments, jobTitle.departmentId)?.name ?? '—',
        headcount: state.employees.filter((employee) => employee.jobTitleId === jobTitle.id).length,
        applicationCount: state.applications.filter((application) => application.jobTitleId === jobTitle.id).length,
    };
}

/**
 * The benefit names a job title can offer, in display order.
 */
export function getBenefitNames() {
    return [...benefitNames];
}

/**
 * Every job title with its department name and the number of employees holding it.
 */
export function listJobTitles() {
    return state.jobTitles.map(withDepartmentName);
}

/**
 * A job title with its department name, headcount, and newest holders, or undefined.
 */
export function findJobTitle(id, employeeLimit = 5) {
    const jobTitle = findById(state.jobTitles, id);

    if (!jobTitle) {
        return undefined;
    }

    return {
        ...withDepartmentName(jobTitle),
        recentEmployees: state.employees
            .filter((employee) => employee.jobTitleId === id)
            .sort((first, second) => second.hiredOn.localeCompare(first.hiredOn))
            .slice(0, employeeLimit)
            .map(withNames)
            .map(({ id: employeeId, name, department, hiredOn }) => ({ id: employeeId, name, department, hiredOn })),
    };
}

/**
 * Whether another job title in the same department already uses this name (ignoring case and surrounding spaces).
 */
export function isJobTitleNameTaken(name, departmentId, exceptId = null) {
    const normalized = name.trim().toLowerCase();

    return state.jobTitles.some((jobTitle) => (
        jobTitle.id !== exceptId && jobTitle.departmentId === departmentId && jobTitle.name.toLowerCase() === normalized
    ));
}

/**
 * @param {{ name: string, departmentId: string, salary: number, benefits: string[] }} details
 */
export function addJobTitle(details) {
    const jobTitle = { id: `t${state.nextJobTitleNumber}`, ...details, name: details.name.trim() };

    state.nextJobTitleNumber += 1;
    state.jobTitles.push(jobTitle);
    logActivity('Created', `Job Title · ${jobTitle.name}`, `Job title created, base salary ${formatSalary(jobTitle.salary)}`);
    commitChange();

    return jobTitle;
}

/**
 * @param {{ name: string, departmentId: string, salary: number, benefits: string[] }} details
 */
export function updateJobTitle(id, details) {
    const index = state.jobTitles.findIndex((jobTitle) => jobTitle.id === id);

    if (index === -1) {
        return undefined;
    }

    const jobTitle = { ...state.jobTitles[index], ...details, name: details.name.trim(), id };

    state.jobTitles[index] = jobTitle;
    logActivity('Updated', `Job Title · ${jobTitle.name}`, `Job title updated, base salary ${formatSalary(jobTitle.salary)}`);
    commitChange();

    return jobTitle;
}

/**
 * Removes a job title that no employee holds and no application is for.
 *
 * @returns {{ jobTitle?: object, headcount: number, applicationCount: number }} `jobTitle` is set only when it was deleted.
 */
export function deleteJobTitle(id) {
    const jobTitle = findById(state.jobTitles, id);

    if (!jobTitle) {
        return { headcount: 0, applicationCount: 0 };
    }

    const { headcount, applicationCount } = withDepartmentName(jobTitle);

    if (headcount || applicationCount) {
        return { headcount, applicationCount };
    }

    state.jobTitles = state.jobTitles.filter((candidate) => candidate.id !== id);
    logActivity('Deleted', `Job Title · ${jobTitle.name}`, 'Job title removed');
    commitChange();

    return { jobTitle, headcount, applicationCount };
}

function withApplicationNames(application) {
    return {
        ...application,
        jobTitle: findById(state.jobTitles, application.jobTitleId)?.name ?? '—',
        department: findById(state.departments, application.departmentId)?.name ?? '—',
    };
}

function applicationEntity(application) {
    return `Candidate · ${application.name}`;
}

/**
 * Every application with its job title and department names.
 */
export function listApplications() {
    return state.applications.map(withApplicationNames);
}

/**
 * An application with its job title and department names, or undefined.
 */
export function findApplication(id) {
    const application = findById(state.applications, id);

    return application ? withApplicationNames(application) : undefined;
}

/**
 * The latest activity recorded against one applicant, newest first.
 */
export function getApplicationHistory(id, limit = 5) {
    const application = findById(state.applications, id);

    if (!application) {
        return [];
    }

    return activityWithDates(state.activity.filter((entry) => entry.entity === applicationEntity(application)).slice(0, limit));
}

/**
 * Whether another application from this email address is already open for the same job title.
 */
export function isDuplicateApplication(email, jobTitleId, exceptId = null) {
    const normalized = email.trim().toLowerCase();

    return state.applications.some((application) => (
        application.id !== exceptId && application.jobTitleId === jobTitleId && application.email.toLowerCase() === normalized
    ));
}

function todayIso() {
    return isoDateOf(new Date());
}

function failure(error, errors = {}) {
    return { ok: false, error, errors };
}

function success(application, extra = {}) {
    return { ok: true, application: withApplicationNames(application), ...extra };
}

/**
 * Field errors for a screening or interview slot (empty when valid): a date from today on, and a time.
 *
 * @returns {{ date?: string, time?: string }}
 */
export function validateActivitySlot({ date, time }) {
    const errors = {};

    if (!date) {
        errors.date = 'Choose a date.';
    } else if (date < todayIso()) {
        errors.date = 'Choose today or a later date.';
    }

    if (!time) {
        errors.time = 'Choose a time.';
    }

    return errors;
}

function slotText({ date, time }) {
    return `${formatDate(date)} at ${formatTime(time)}`;
}

function nextActivityId(application) {
    return `a${Math.max(0, ...application.activities.map((activity) => Number(activity.id.slice(1)) || 0)) + 1}`;
}

function addMilestone(application, key) {
    if (!application.milestones.some((milestone) => milestone.key === key)) {
        application.milestones.push({ key, at: todayIso() });
    }
}

/**
 * Moves an application to a new status and logs it.
 */
function moveTo(application, status, action, detail) {
    const previous = application.status;

    application.status = status;
    logActivity(action, applicationEntity(application), `${detail}; stage changed from ${previous} to ${status}`);
}

/**
 * New applications always start as Applied.
 *
 * @param {{ name: string, email: string, phone: string, jobTitleId: string, departmentId: string, appliedOn: string }} details
 */
export function addApplication(details) {
    const { name, email, phone, jobTitleId, departmentId, appliedOn } = details;
    const application = progressForStatus(
        { id: `c${state.nextApplicationNumber}`, name, email, phone, jobTitleId, departmentId, appliedOn, status: 'Applied' },
        todayIso(),
    );
    const jobTitle = findById(state.jobTitles, application.jobTitleId);

    state.nextApplicationNumber += 1;
    state.applications.push(application);
    logActivity(
        'Created',
        applicationEntity(application),
        `Added to Recruitment as Applied for ${jobTitle?.name ?? 'a position'}`,
    );
    commitChange();

    return application;
}

/**
 * Edits the applicant's details. The status, milestones, and activities are not editable here: they
 * change only through the workflow actions below.
 *
 * @param {{ name: string, email: string, phone: string, jobTitleId: string, departmentId: string, appliedOn: string }} details
 */
export function updateApplication(id, details) {
    const application = findById(state.applications, id);

    if (!application) {
        return undefined;
    }

    const { name, email, phone, jobTitleId, departmentId, appliedOn } = details;

    Object.assign(application, { name, email, phone, jobTitleId, departmentId, appliedOn });

    const received = application.milestones.find((milestone) => milestone.key === 'applied');

    if (received) {
        received.at = appliedOn;
    }

    logActivity('Updated', applicationEntity(application), 'Application updated');
    commitChange();

    return application;
}

/**
 * Puts a screening or interview on the calendar. This does not change the status: that happens when
 * the activity is completed.
 *
 * @param {{ type: string, date: string, time: string }} slot
 */
export function scheduleActivity(id, { type, date, time }) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    const workflow = workflowFor(application);

    if (workflow.scheduleType !== type) {
        return failure(workflow.pendingActivity
            ? `Complete or cancel the scheduled ${workflow.pendingActivity.type.toLowerCase()} first.`
            : `A ${type.toLowerCase()} cannot be scheduled while the application is ${application.status}.`);
    }

    const errors = validateActivitySlot({ date, time });

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    const activity = { id: nextActivityId(application), type, date, time, status: 'Scheduled' };

    application.activities.push(activity);
    logActivity('Updated', applicationEntity(application), `${type} scheduled for ${slotText(activity)}`);
    commitChange();

    return success(application, { activity });
}

/**
 * Changes the date and time of a scheduled (not yet completed) activity.
 */
export function rescheduleActivity(id, activityId, { date, time }) {
    const application = findById(state.applications, id);
    const activity = application?.activities.find((candidate) => candidate.id === activityId);

    if (!application || !activity) {
        return failure('That activity no longer exists.');
    }

    if (activity.status !== 'Scheduled') {
        return failure(`This ${activity.type.toLowerCase()} is already completed and cannot be rescheduled.`);
    }

    const errors = validateActivitySlot({ date, time });

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    Object.assign(activity, { date, time });
    logActivity('Updated', applicationEntity(application), `${activity.type} rescheduled to ${slotText(activity)}`);
    commitChange();

    return success(application, { activity });
}

/**
 * Removes a scheduled activity that has not happened, so the next one can be scheduled.
 */
export function cancelActivity(id, activityId) {
    const application = findById(state.applications, id);
    const activity = application?.activities.find((candidate) => candidate.id === activityId);

    if (!application || !activity) {
        return failure('That activity no longer exists.');
    }

    if (activity.status !== 'Scheduled') {
        return failure(`This ${activity.type.toLowerCase()} is already completed and cannot be cancelled.`);
    }

    application.activities = application.activities.filter((candidate) => candidate.id !== activityId);
    logActivity('Updated', applicationEntity(application), `${activity.type} on ${slotText(activity)} cancelled`);
    commitChange();

    return success(application);
}

/**
 * Marks a scheduled activity as having happened. This is the milestone that advances the status:
 * a completed screening moves Applied to Screening, a completed interview moves Screening to Interview.
 * It cannot be completed before its scheduled date.
 */
export function completeActivity(id, activityId) {
    const application = findById(state.applications, id);
    const activity = application?.activities.find((candidate) => candidate.id === activityId);

    if (!application || !activity) {
        return failure('That activity no longer exists.');
    }

    if (activity.status !== 'Scheduled') {
        return failure(`This ${activity.type.toLowerCase()} is already completed.`);
    }

    if (workflowFor(application).isFinal) {
        return failure(`A ${application.status.toLowerCase()} application cannot move forward.`);
    }

    if (activity.date > todayIso()) {
        return failure(`This ${activity.type.toLowerCase()} is scheduled for ${formatDate(activity.date)}. Reschedule it, or complete it on or after that date.`);
    }

    activity.status = 'Completed';
    activity.completedOn = todayIso();

    const nextStatus = statusAfterCompleting[activity.type];

    if (application.status === nextStatus) {
        logActivity('Updated', applicationEntity(application), `${activity.type} completed`);
    } else {
        addMilestone(application, activity.type.toLowerCase());
        moveTo(application, nextStatus, 'Updated', `${activity.type} completed`);
    }

    commitChange();

    return success(application, { activity });
}

/**
 * Issues a job offer after an interview has been completed: Interview to Offered, response pending.
 */
export function issueOffer(id) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    const workflow = workflowFor(application);

    if (!workflow.canIssueOffer) {
        return failure(workflow.pendingActivity
            ? `Complete or cancel the scheduled ${workflow.pendingActivity.type.toLowerCase()} first.`
            : 'An offer can be issued only after an interview has been completed.');
    }

    application.offerResponse = 'Pending';
    addMilestone(application, 'offered');
    moveTo(application, 'Offered', 'Updated', 'Offer issued');
    commitChange();

    return success(application);
}

/**
 * Records that the applicant accepted the offer. They stay Offered until hiring is completed.
 */
export function recordOfferAccepted(id) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    if (!workflowFor(application).canRecordAcceptance) {
        return failure('Only an offer that is waiting for a response can be accepted.');
    }

    application.offerResponse = 'Accepted';
    addMilestone(application, 'accepted');
    logActivity('Updated', applicationEntity(application), 'Offer accepted');
    commitChange();

    return success(application);
}

/**
 * Completes hiring after the offer was accepted: adds the applicant to Employees (position, department,
 * salary, and benefits from the job title, hired today) and moves the application to Hired.
 */
export function completeHiring(id) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    const workflow = workflowFor(application);

    if (!workflow.canCompleteHiring) {
        return failure(application.status === 'Offered'
            ? 'The offer must be accepted before hiring can be completed.'
            : 'Only an applicant with an accepted offer can be hired.');
    }

    const jobTitle = findById(state.jobTitles, application.jobTitleId);

    if (!jobTitle) {
        return failure('The position for this application no longer exists.');
    }

    const employee = addEmployee({
        name: application.name,
        email: application.email,
        phone: application.phone,
        address: '',
        birthdate: '',
        sex: '',
        civilStatus: '',
        departmentId: application.departmentId,
        jobTitleId: application.jobTitleId,
        hiredOn: todayIso(),
        salary: jobTitle.salary,
        benefits: [...jobTitle.benefits],
    });

    application.employeeId = employee.id;
    addMilestone(application, 'hired');
    moveTo(application, 'Hired', 'Hired', `Hired and added to Employees as ${employee.id}`);
    commitChange();

    return success(application, { employee });
}

/**
 * Rejects an application that is still open. Any scheduled activity is cancelled, and a rejected
 * application stays rejected.
 */
export function rejectApplication(id) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    if (!workflowFor(application).canReject) {
        return failure(`A ${application.status.toLowerCase()} application cannot be rejected.`);
    }

    application.activities = application.activities.filter((activity) => activity.status !== 'Scheduled');
    addMilestone(application, 'rejected');
    moveTo(application, 'Rejected', 'Updated', 'Application rejected');
    commitChange();

    return success(application);
}

export function deleteApplication(id) {
    const application = findById(state.applications, id);

    if (!application) {
        return undefined;
    }

    state.applications = state.applications.filter((candidate) => candidate.id !== id);
    logActivity('Deleted', applicationEntity(application), 'Application removed');
    commitChange();

    return application;
}

/**
 * Every activity (audit log) entry, newest first, with `at` as a Date.
 */
export function listActivity() {
    return activityWithDates(state.activity);
}

/**
 * One activity entry with up to `limit` other entries for the same record, or undefined.
 * Session entries are not tied to a record, so they have no related entries.
 */
export function findActivity(id, limit = 5) {
    const entry = findById(state.activity, id);

    if (!entry) {
        return undefined;
    }

    const related = entry.entity === 'Session'
        ? []
        : state.activity.filter((candidate) => candidate.entity === entry.entity && candidate.id !== id).slice(0, limit);

    return { ...activityWithDates([entry])[0], related: activityWithDates(related) };
}

/**
 * Records a sign-out (the demo has no real session to end).
 */
export function recordSignOut() {
    logActivity('Logout', 'Session', 'Signed out');
    commitChange();
}

function withEmployeeNames(leave) {
    const employee = findById(state.employees, leave.employeeId);

    return {
        ...leave,
        employeeName: employee?.name ?? '—',
        department: employee ? findById(state.departments, employee.departmentId)?.name ?? '—' : '—',
        jobTitle: employee ? findById(state.jobTitles, employee.jobTitleId)?.name ?? '—' : '—',
    };
}

/**
 * Every leave request with the employee's name, department, and job title.
 */
export function listLeaveRequests() {
    return state.leaveRequests.map(withEmployeeNames);
}

/**
 * One employee's leave requests, latest start first.
 */
export function getEmployeeLeave(employeeId) {
    return state.leaveRequests
        .filter((leave) => leave.employeeId === employeeId)
        .sort((first, second) => second.startDate.localeCompare(first.startDate))
        .map(withEmployeeNames);
}

/**
 * Approves a filed or pending leave request. If it covers today, the employee shows as On Leave.
 *
 * @returns {{ ok: boolean, error?: string, leave?: object }}
 */
export function approveLeave(id) {
    const leave = findById(state.leaveRequests, id);

    if (!leave) {
        return { ok: false, error: 'That leave request no longer exists.' };
    }

    if (leave.status === 'Approved') {
        return { ok: false, error: 'This leave request is already approved.' };
    }

    const employee = findById(state.employees, leave.employeeId);

    leave.status = 'Approved';
    logActivity(
        'Updated',
        `Leave · ${employee?.name ?? leave.employeeId}`,
        `${leave.type} from ${formatDate(leave.startDate)} to ${formatDate(leave.endDate)} approved`,
    );
    commitChange();

    return { ok: true, leave: withEmployeeNames(leave) };
}

export function resetMockData() {
    state = createInitialState();
    commitChange();
}
