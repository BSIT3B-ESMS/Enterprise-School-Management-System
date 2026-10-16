// Frontend-only sample data. Employees, departments, job titles, applications, and activity are
// saved in localStorage so changes carry across pages; everything else is fixed for now. In a future phase, replace this
// module with backend calls that return the same shapes.

const storageKey = 'ems-mock-data';
const storageVersion = 5;

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

const currentUser = {
    name: 'Maria Santos',
    firstName: 'Maria',
    initials: 'MS',
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

const seedJobTitles = [
    { id: 't1', name: 'College Dean', departmentId: 'd5', salary: 110000, benefits: benefitNames.slice(0, 8) },
    { id: 't2', name: 'Department Chair', departmentId: 'd2', salary: 92000, benefits: benefitNames.slice(0, 8) },
    { id: 't3', name: 'Professor', departmentId: 'd6', salary: 88000, benefits: benefitNames.slice(0, 7) },
    { id: 't4', name: 'Associate Professor', departmentId: 'd1', salary: 72000, benefits: benefitNames.slice(0, 7) },
    { id: 't5', name: 'Assistant Professor', departmentId: 'd1', salary: 58000, benefits: benefitNames.slice(0, 6) },
    { id: 't6', name: 'Senior Lecturer', departmentId: 'd3', salary: 48000, benefits: benefitNames.slice(0, 6) },
    { id: 't7', name: 'Instructor', departmentId: 'd4', salary: 36000, benefits: benefitNames.slice(0, 5) },
    { id: 't8', name: 'Visiting Professor', departmentId: 'd7', salary: 65000, benefits: benefitNames.slice(0, 5) },
    { id: 't9', name: 'Lecturer', departmentId: 'd8', salary: 40000, benefits: benefitNames.slice(0, 5) },
    { id: 't10', name: 'Research Associate', departmentId: 'd2', salary: 45000, benefits: benefitNames.slice(0, 6) },
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

        return {
            id: `EMP-${1001 + index}`,
            ...employee,
            salary: employee.salary ?? jobTitle.salary,
            benefits: [...jobTitle.benefits],
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
    const applications = seedApplications.map(([name, jobTitleId, status, daysAgo], index) => {
        const appliedOn = new Date();
        const nameParts = name.toLowerCase().split(' ');

        appliedOn.setDate(appliedOn.getDate() - daysAgo);

        return {
            id: `c${index + 1}`,
            name,
            email: `${nameParts[0]}.${nameParts[nameParts.length - 1]}@mail.com`,
            phone: `+63 9${17 + index} 555 ${1000 + index * 37}`,
            jobTitleId,
            departmentId: findById(seedJobTitles, jobTitleId).departmentId,
            appliedOn: isoDateOf(appliedOn),
            status,
        };
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

function createInitialState() {
    const now = Date.now();
    const hoursAgo = (hours) => Math.round(now - hours * 3600000);
    const entry = (action, entity, detail, hours) => ({ action, entity, detail, user: currentUser.name, at: hoursAgo(hours) });
    const employees = generateRoster();

    return {
        version: storageVersion,
        employees,
        nextEmployeeNumber: 1001 + employees.length,
        ...seedDepartmentState(),
        ...seedJobTitleState(),
        ...seedApplicationState(),
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
        return {
            ...saved,
            ...(Array.isArray(saved.departments) ? {} : seedDepartmentState()),
            ...(Array.isArray(saved.jobTitles) ? {} : seedJobTitleState()),
            ...(Array.isArray(saved.applications) ? {} : seedApplicationState()),
            ...withActivityIds(saved),
            version: storageVersion,
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

function logActivity(action, entity, detail) {
    state.activity.unshift({
        id: `l${state.nextActivityNumber}`,
        action,
        entity,
        detail,
        user: currentUser.name,
        at: Date.now(),
    });
    state.nextActivityNumber += 1;
}

function activityWithDates(entries) {
    return entries.map((entry) => ({ ...entry, at: new Date(entry.at) }));
}

function withNames(employee) {
    return {
        ...employee,
        jobTitle: findById(state.jobTitles, employee.jobTitleId)?.name ?? '—',
        department: findById(state.departments, employee.departmentId)?.name ?? '—',
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
 * Data every page inside the app shell (header and sidebar) needs.
 */
export function getShellData() {
    return {
        currentUser,
        pendingCandidates: countForStatus('Applied'),
        notifications: activityWithDates(state.activity.slice(0, 4)),
    };
}

export function getDashboardData() {
    const hiresByYear = {};

    state.employees.forEach((employee) => {
        const year = Number(employee.hiredOn.slice(0, 4));

        hiresByYear[year] = (hiresByYear[year] ?? 0) + 1;
    });

    return {
        currentUser,
        monthlyPayroll: state.employees.reduce((total, employee) => total + Number(employee.salary || 0), 0),
        jobTitleSalaries: state.jobTitles.map((jobTitle) => jobTitle.salary),
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

/**
 * @param {{ name: string, email: string, phone: string, jobTitleId: string, departmentId: string, appliedOn: string, status: string }} details
 */
export function addApplication(details) {
    const application = { id: `c${state.nextApplicationNumber}`, ...details };
    const jobTitle = findById(state.jobTitles, application.jobTitleId);

    state.nextApplicationNumber += 1;
    state.applications.push(application);
    logActivity(
        'Created',
        applicationEntity(application),
        `Added to Recruitment as ${application.status} for ${jobTitle?.name ?? 'a position'}`,
    );
    commitChange();

    return application;
}

/**
 * @param {{ name: string, email: string, phone: string, jobTitleId: string, departmentId: string, appliedOn: string, status: string }} details
 */
export function updateApplication(id, details) {
    const index = state.applications.findIndex((application) => application.id === id);

    if (index === -1) {
        return undefined;
    }

    const previous = state.applications[index];
    const application = { ...previous, ...details, id };

    state.applications[index] = application;
    logActivity(
        'Updated',
        applicationEntity(application),
        previous.status === application.status
            ? 'Application updated'
            : `Application updated, stage changed from ${previous.status} to ${application.status}`,
    );
    commitChange();

    return application;
}

/**
 * Moves an application to another pipeline status. Returns undefined when it no longer exists.
 */
export function setApplicationStatus(id, status) {
    const application = findById(state.applications, id);

    if (!application || !applicationStatuses.includes(status)) {
        return undefined;
    }

    if (application.status !== status) {
        logActivity('Updated', applicationEntity(application), `Stage changed from ${application.status} to ${status}`);
        application.status = status;
        commitChange();
    }

    return application;
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

export function resetMockData() {
    state = createInitialState();
    commitChange();
}
