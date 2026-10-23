// Frontend-only sample data. Employees, departments, job titles, applications, leave requests, and
// activity are saved in localStorage so changes carry across pages; everything else is fixed for now. In a future phase, replace this
// module with backend calls that return the same shapes.

import { formatDate, formatTime, initialsOf } from './format.js';
import { milestoneKeyFor, progressForStatus, statusAfterCompleting, workflowFor } from './recruitment-workflow.js';
import {
    birthdateRule,
    choiceRule,
    civilStatusOptions,
    emergencyRelationshipOptions,
    idNumberRule,
    mobileNumberRule,
    pagibigRule,
    personNameRule,
    philhealthRule,
    sexOptions,
    sssRule,
    tinRule,
} from './validation-rules.js';

const storageKey = 'ems-mock-data';
const storageVersion = 24;

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

/// What each of the sample positions involves and asks for, as the Applicant interface shows it (job titles hold
// no description, employment type, location, or qualifications of their own). `summary` is the short description the
// position cards show; positions without one show their `description`. A position HR adds later has none
// until this lists it, and the Applicant interface shows "Not specified" for what is missing.
const mainCampus = '12-B Alcalde Jose, Pasig City';
const satelliteCampus = 'Hybrid (on-site at 12-B Alcalde Jose, Pasig City)';
const standardRequirements = ['Application letter', 'Curriculum vitae (CV)', 'Transcript of records', 'Two valid government-issued IDs'];

const positionDetails = {
    t1: {
        employmentType: 'Full-time',
        location: mainCampus,
        description: 'Leads the college: sets its academic direction, oversees faculty and programs, and represents the college within the university.',
        responsibilities: ['Set the academic direction and strategic plan of the college', 'Oversee faculty hiring, development, and evaluation', 'Approve curricula and program changes', 'Represent the college in university councils and with partners'],
        qualifications: ['Doctoral degree in a relevant field', 'At least 8 years of teaching experience, including academic leadership', 'A record of research or professional publications', 'Strong leadership and communication skills'],
        requirements: [...standardRequirements, 'Diploma of the highest degree earned', 'List of publications', 'Two letters of recommendation'],
    },
    t2: {
        employmentType: 'Full-time',
        location: mainCampus,
        description: 'Manages the department’s faculty, course offerings, and schedules, and supports students and faculty.',
        responsibilities: ['Prepare the department’s course offerings and teaching schedules', 'Coordinate and evaluate the department’s faculty', 'Handle student concerns and academic advising', 'Teach a reduced course load'],
        qualifications: ['Master’s degree required; doctoral degree preferred', 'At least 5 years of teaching experience', 'Experience coordinating academic programs'],
        requirements: [...standardRequirements, 'Diploma of the highest degree earned', 'Certificate of teaching experience'],
    },
    t3: {
        employmentType: 'Full-time',
        location: mainCampus,
        description: 'Teaches advanced courses, mentors junior faculty, and leads research in the department.',
        responsibilities: ['Teach advanced undergraduate and graduate courses', 'Lead research projects and publish findings', 'Mentor junior faculty and advise graduate students', 'Serve on department and university committees'],
        qualifications: ['Doctoral degree in the discipline', 'At least 8 years of teaching experience', 'Published research in peer-reviewed journals'],
        requirements: [...standardRequirements, 'Diploma of the highest degree earned', 'List of publications'],
    },
    t4: {
        employmentType: 'Full-time',
        location: mainCampus,
        description: 'Teaches undergraduate and graduate courses, conducts research, and serves on department committees.',
        responsibilities: ['Teach undergraduate and graduate courses', 'Conduct and publish research', 'Advise students on theses and projects', 'Serve on department committees'],
        qualifications: ['Doctoral degree in the discipline', 'At least 5 years of teaching experience', 'Research output in peer-reviewed publications'],
        requirements: [...standardRequirements, 'Diploma of the highest degree earned', 'List of publications'],
    },
    t5: {
        employmentType: 'Full-time',
        location: mainCampus,
        description: 'Teaches courses, advises students, and builds an independent research program.',
        responsibilities: ['Teach a full load of undergraduate courses', 'Advise and mentor students', 'Build an independent research program', 'Take part in curriculum development'],
        qualifications: ['Doctoral degree, or a master’s degree with relevant experience', 'Teaching experience or strong teaching potential', 'Evidence of research interest'],
        requirements: [...standardRequirements, 'Diploma of the highest degree earned'],
    },
    t6: {
        employmentType: 'Full-time',
        location: mainCampus,
        description: 'Teaches a full load of courses and helps coordinate the curriculum for the unit.',
        responsibilities: ['Teach a full load of courses', 'Coordinate course syllabi and materials across sections', 'Assess and report student performance', 'Support the unit’s curriculum review'],
        qualifications: ['Master’s degree in the subject', 'At least 5 years of teaching experience', 'Experience developing course materials'],
        requirements: [...standardRequirements, 'Certificate of teaching experience'],
    },
    t7: {
        employmentType: 'Full-time',
        location: satelliteCampus,
        description: 'Teaches introductory and general education courses and supports students in class.',
        responsibilities: ['Teach introductory and general education courses', 'Prepare lesson plans, activities, and assessments', 'Keep class records and submit grades on time', 'Hold consultation hours for students'],
        qualifications: ['Master’s degree in the subject, or a bachelor’s degree with relevant experience', 'Classroom teaching experience', 'Clear and engaging presentation skills'],
        requirements: [...standardRequirements, 'Professional license, if applicable'],
    },
    t8: {
        employmentType: 'Part-time',
        location: mainCampus,
        description: 'Teaches for a limited term and brings outside expertise into the program.',
        responsibilities: ['Teach assigned courses for the contract term', 'Give public lectures and seminars', 'Collaborate with faculty on research or industry projects'],
        qualifications: ['Doctoral degree or equivalent professional standing', 'An established teaching or industry record', 'Availability for a fixed term'],
        requirements: [...standardRequirements, 'Proposed course outline'],
    },
    t9: {
        employmentType: 'Part-time',
        location: satelliteCampus,
        description: 'Delivers lectures and assessments for assigned courses.',
        responsibilities: ['Deliver lectures for assigned courses', 'Prepare and check assessments', 'Submit grades and class records on time'],
        qualifications: ['Master’s degree in the subject', 'College-level teaching experience', 'Good communication skills'],
        requirements: [...standardRequirements],
    },
    t10: {
        employmentType: 'Full-time',
        location: mainCampus,
        description: 'Supports research projects by collecting and analyzing data and writing up findings.',
        responsibilities: ['Collect and clean research data', 'Run statistical analyses and summarize results', 'Draft reports and manuscripts with the research team', 'Keep project records organized'],
        qualifications: ['Master’s degree in a relevant field', 'Experience with research methods and data analysis', 'Attention to detail and good writing'],
        requirements: [...standardRequirements, 'Sample of written research work'],
    },
    t12: {
        employmentType: 'Full-time',
        location: mainCampus,
        summary: 'Runs the hiring process from job posting and demo interviews to interview scheduling and offers.',
        description: 'Owns the recruitment cycle for faculty and staff positions. You will work with hiring departments to define what each role needs, source and screen candidates, schedule demo interviews and interviews, and keep applicants informed at every stage.',
        responsibilities: ['Draft and publish job postings with hiring managers', 'Screen applications and conduct initial demo interviews', 'Schedule interviews and coordinate with department panels', 'Prepare offer documents and background checks', 'Keep the recruitment pipeline updated and report on hiring progress'],
        qualifications: ['Bachelor’s degree in Psychology, Human Resource Management, or a related field', 'At least 2 years of recruitment or talent acquisition experience', 'Strong interviewing and communication skills', 'Familiarity with applicant tracking and labor regulations'],
        requirements: [...standardRequirements, 'Certificate of employment showing recruitment experience'],
    },
    t13: {
        employmentType: 'Full-time',
        location: mainCampus,
        summary: 'Provides office support: correspondence, scheduling, filing, and coordination for an administrative unit.',
        description: 'Keeps an administrative unit organized and responsive. You will handle correspondence, manage calendars and meetings, prepare documents, and coordinate with other offices so that the unit’s work moves without delays.',
        responsibilities: ['Prepare, proofread, and route letters, memos, and reports', 'Manage the unit head’s calendar, meetings, and travel arrangements', 'Receive visitors and handle calls and emails', 'Maintain filing systems and office supplies', 'Take meeting minutes and follow up on action items'],
        qualifications: ['Associate or bachelor’s degree in Office Administration or a related field', 'At least 1 year of administrative support experience', 'Proficiency in word processing, spreadsheets, and email', 'Clear written and spoken English and Filipino'],
        requirements: [...standardRequirements, 'NBI clearance'],
    },
    t15: {
        employmentType: 'Full-time',
        location: mainCampus,
        summary: 'First-line technical support for staff and faculty: computers, accounts, printers, and networks.',
        description: 'Keeps staff and faculty productive by resolving hardware, software, and account problems quickly. You will staff the help desk, set up and maintain computers and peripherals, and document fixes so that common problems are solved faster the next time.',
        responsibilities: ['Respond to help desk requests and track them to resolution', 'Install, configure, and repair computers, printers, and peripherals', 'Create and manage user accounts and access', 'Support classroom and meeting-room equipment', 'Document solutions and keep the equipment inventory current'],
        qualifications: ['Bachelor’s degree in Information Technology, Computer Science, or a related field', 'At least 1 year of IT support experience', 'Knowledge of Windows, common office software, and basic networking', 'Patient and clear when helping non-technical users'],
        requirements: [...standardRequirements, 'IT certification (for example CompTIA A+), if any'],
    },
    t16: {
        employmentType: 'Full-time',
        location: satelliteCampus,
        summary: 'Builds and maintains the university’s internal web applications alongside a small development team.',
        description: 'Joins the IT team that builds and maintains the university’s internal systems. You will write and test features, fix bugs, and review code with senior developers while learning the team’s tools and practices.',
        responsibilities: ['Develop and test features for internal web applications', 'Fix bugs and improve the performance of existing code', 'Write clear documentation and automated tests', 'Take part in code reviews and sprint planning', 'Work with users to understand and refine requirements'],
        qualifications: ['Bachelor’s degree in Computer Science, Information Technology, or a related field', 'Working knowledge of PHP or another server-side language, plus HTML, CSS, and JavaScript', 'Familiarity with relational databases and Git', 'Fresh graduates with strong projects are welcome'],
        requirements: [...standardRequirements, 'Portfolio or link to sample projects'],
    },
    t19: {
        employmentType: 'Full-time',
        location: mainCampus,
        summary: 'Prepares financial records and reports, reconciliations, and tax filings for the university.',
        description: 'Maintains the accuracy of the university’s books. You will record and reconcile transactions, prepare monthly financial statements and tax filings, support audits, and recommend improvements to financial controls.',
        responsibilities: ['Record and reconcile financial transactions and accounts', 'Prepare monthly and annual financial statements', 'Compute and file taxes and statutory remittances', 'Support internal and external audits', 'Review vouchers and supporting documents for completeness'],
        qualifications: ['Bachelor’s degree in Accountancy', 'Certified Public Accountant (CPA) license', 'At least 2 years of accounting experience', 'Knowledge of accounting software and government accounting rules is an advantage'],
        requirements: [...standardRequirements, 'PRC license (CPA)', 'Certificate of employment showing accounting experience'],
    },
    t21: {
        employmentType: 'Full-time',
        location: mainCampus,
        summary: 'Plans and carries out campaigns that promote the university’s programs and events.',
        description: 'Helps the university reach prospective students and partners. You will plan and carry out campaigns, write promotional content, coordinate events and admissions drives, and report on what worked.',
        responsibilities: ['Plan and carry out marketing campaigns and admissions drives', 'Write copy for brochures, emails, and web pages', 'Coordinate events, fairs, and school visits', 'Track campaign results and report on them', 'Work with designers and the social media team on materials'],
        qualifications: ['Bachelor’s degree in Marketing, Communication, or a related field', 'At least 1 year of marketing or communications experience', 'Strong writing and presentation skills', 'Creative and comfortable working with deadlines'],
        requirements: [...standardRequirements, 'Writing or campaign samples'],
    },
    t23: {
        employmentType: 'Full-time',
        location: mainCampus,
        summary: 'Helps students, parents, and visitors by phone, email, and in person.',
        description: 'The friendly first contact for students, parents, and visitors. You will answer inquiries about admissions, enrollment, and services, resolve concerns, and pass issues to the right office with clear notes.',
        responsibilities: ['Answer inquiries by phone, email, chat, and in person', 'Explain admissions, enrollment, and payment procedures', 'Log concerns and follow up until they are resolved', 'Route cases to the right office with clear notes', 'Keep an up-to-date list of frequently asked questions'],
        qualifications: ['At least 2 years of college; a bachelor’s degree is preferred', 'At least 6 months of customer service experience is an advantage', 'Clear, polite communication in English and Filipino', 'Patient and calm under pressure'],
        requirements: [...standardRequirements, 'Barangay clearance'],
    },
};

// The employment types a job posting can have.
export const employmentTypes = ['Full-time', 'Part-time'];

/**
 * A job title kept with an employment type that is no longer offered (Contractual or Internship) takes its sample
 * position's type, or Full-time when it has none.
 */
function withOfferedEmploymentType(jobTitle) {
    if (employmentTypes.includes(jobTitle.employmentType)) {
        return jobTitle;
    }

    const seedType = positionDetails[jobTitle.id]?.employmentType;

    return { ...jobTitle, employmentType: employmentTypes.includes(seedType) ? seedType : jobTitle.employmentType ? 'Full-time' : '' };
}

/**
 * The posting content of a job title: what the Open Job Positions page edits and the public Careers website shows. It is
 * kept on the job title itself, so HR and the applicant-facing pages always read the same record. The sample titles start
 * from the details above; a title HR adds starts blank.
 */
function postingContentFor(id) {
    const seed = positionDetails[id] ?? {};

    return {
        employmentType: seed.employmentType ?? '',
        location: seed.location ?? '',
        summary: seed.summary ?? '',
        description: seed.description ?? '',
        responsibilities: [...(seed.responsibilities ?? [])],
        qualifications: [...(seed.qualifications ?? [])],
        requirements: [...(seed.requirements ?? [])],
    };
}

// Which updates the HR header's notifications menu shows (Account Settings). Everything is on until turned off.
const defaultHrSettings = { notifications: { leave: true, recruitment: true, employees: true } };

// The signed-in HR user's account. Kept apart from the employee and applicant records; `state.hrProfile`
// starts from this and holds the user's edits.
const seedHrProfile = {
    name: 'Maria Santos',
    email: 'maria.santos@company.com',
    role: 'Admin',
};

/**
 * The colleges of the university: the only ones a job title, employee, or application can belong to. They are kept in
 * the `departments` records (with `departmentId` on the others), so only the names shown changed.
 */
export const approvedColleges = [
    'College of Business and Accountancy (CBA)',
    'College of Nursing (CON)',
    'College of Engineering (COE)',
    'College of Arts and Sciences (CAS)',
    'College of Computer Studies (CCS)',
    'College of International Hospitality Management (CIHM)',
    'College of Education (COED)',
];

const seedDepartments = approvedColleges.map((name, index) => ({ id: `d${index + 1}`, name }));

// The college each department of version 21 and earlier belongs to (by its ID), for saved data. A department HR added
// is matched by name, and otherwise goes to the College of Arts and Sciences.
const collegeForLegacyDepartment = {
    d1: 'd4', d2: 'd5', d3: 'd4', d4: 'd7', d5: 'd1', d6: 'd3', d7: 'd2',
    d8: 'd4', d9: 'd1', d10: 'd5', d11: 'd1', d12: 'd1', d13: 'd1', d14: 'd6',
};
const fallbackCollegeId = 'd4';

// How many departments and job titles the earlier versions of the sample data had (the openings added later follow).
const firstOpeningsDepartment = 8;
const firstOpeningsJobTitle = 10;

// Openings that version 14 added and version 15 took out again to keep the open positions at 15. A saved one that an
// application or employee already uses is closed instead of removed.
const retiredOpeningIds = ['t11', 't14', 't17', 't18', 't20', 't22', 't24', 't25', 't26', 't27', 't28'];

// `openings` is the number of open vacancies for the position.
const seedJobTitles = [
    { id: 't1', name: 'College Dean', departmentId: 'd1', salary: 110000, benefits: benefitNames.slice(0, 8), openings: 0 },
    { id: 't2', name: 'Department Chair', departmentId: 'd5', salary: 92000, benefits: benefitNames.slice(0, 8), openings: 1 },
    { id: 't3', name: 'Professor', departmentId: 'd3', salary: 88000, benefits: benefitNames.slice(0, 7), openings: 3 },
    { id: 't4', name: 'Associate Professor', departmentId: 'd4', salary: 72000, benefits: benefitNames.slice(0, 7), openings: 2 },
    { id: 't5', name: 'Assistant Professor', departmentId: 'd4', salary: 58000, benefits: benefitNames.slice(0, 6), openings: 4 },
    { id: 't6', name: 'Senior Lecturer', departmentId: 'd4', salary: 48000, benefits: benefitNames.slice(0, 6), openings: 1 },
    { id: 't7', name: 'Instructor', departmentId: 'd7', salary: 36000, benefits: benefitNames.slice(0, 5), openings: 2 },
    { id: 't8', name: 'Visiting Professor', departmentId: 'd2', salary: 65000, benefits: benefitNames.slice(0, 5), openings: 0 },
    { id: 't9', name: 'Lecturer', departmentId: 'd4', salary: 40000, benefits: benefitNames.slice(0, 5), openings: 1 },
    { id: 't10', name: 'Research Associate', departmentId: 'd5', salary: 45000, benefits: benefitNames.slice(0, 6), openings: 2 },
    // Non-teaching openings (t12 on), all open for applications.
    { id: 't12', name: 'Recruitment Specialist', departmentId: 'd1', salary: 38000, benefits: benefitNames.slice(0, 7), openings: 1 },
    { id: 't13', name: 'Administrative Assistant', departmentId: 'd1', salary: 26000, benefits: benefitNames.slice(0, 6), openings: 2 },
    { id: 't15', name: 'IT Support Specialist', departmentId: 'd5', salary: 30000, benefits: benefitNames.slice(0, 6), openings: 2 },
    { id: 't16', name: 'Junior Software Developer', departmentId: 'd5', salary: 40000, benefits: benefitNames.slice(0, 7), openings: 2 },
    { id: 't19', name: 'Accountant', departmentId: 'd1', salary: 45000, benefits: benefitNames.slice(0, 7), openings: 1 },
    { id: 't21', name: 'Marketing Associate', departmentId: 'd1', salary: 32000, benefits: benefitNames.slice(0, 6), openings: 1 },
    { id: 't23', name: 'Customer Service Representative', departmentId: 'd6', salary: 24000, benefits: benefitNames.slice(0, 6), openings: 3 },
];

// The top of each sample job title's base salary range. The bottom is the salary the title had before ranges existed, so
// no current value changes; a title HR added before ranges gets a maximum a quarter above its salary.
const seedSalaryMaximums = {
    t1: 140000, t2: 115000, t3: 110000, t4: 90000, t5: 72000, t6: 60000, t7: 46000, t8: 80000, t9: 52000, t10: 58000,
    t12: 50000, t13: 34000, t15: 40000, t16: 55000, t19: 60000, t21: 42000, t23: 32000,
};

function salaryRangeFor(id, salary) {
    return {
        salaryMin: Number(salary),
        salaryMax: seedSalaryMaximums[id] ?? Math.round((Number(salary) * 1.25) / 500) * 500,
    };
}

// A job title saved before salary ranges (20 and earlier) has one salary: it becomes the minimum.
function withSalaryRange(jobTitle) {
    if (jobTitle.salaryMin !== undefined) {
        return jobTitle;
    }

    const { salary, ...rest } = jobTitle;

    return { ...rest, ...salaryRangeFor(jobTitle.id, salary ?? 0) };
}

// The leave credits (days per year) every sample job title starts with. A placeholder, not a policy: HR sets each job
// title's own value in Job Titles.
export const defaultLeaveCredits = 15;

// A job title saved before leave credits (23 and earlier) starts with the default.
function withLeaveCredits(jobTitle) {
    return jobTitle.leaveCredits === undefined ? { ...jobTitle, leaveCredits: defaultLeaveCredits } : jobTitle;
}

// Every action an activity (audit log) entry can record.
export const activityActions = ['Login', 'Logout', 'Created', 'Updated', 'Deleted', 'Hired'];

// Recruitment pipeline order; every application is in exactly one of these.
export const applicationStatuses = ['Applied', 'Demo Interview', 'Final Interview', 'Offered', 'Hired', 'Rejected'];

// The reference's ten applicants: name, job title, status, and days before today they applied.
const seedApplications = [
    ['Juan Dela Cruz', 't5', 'Applied', 2],
    ['Patricia Lumbao', 't4', 'Demo Interview', 5],
    ['Rafael Ignacio', 't10', 'Final Interview', 9],
    ['Isabel Hernandez', 't2', 'Offered', 14],
    ['Daniel Perez', 't7', 'Applied', 1],
    ['Liza Maglaya', 't9', 'Rejected', 21],
    ['Gabriel Ocampo', 't3', 'Final Interview', 11],
    ['Teresa Villar', 't8', 'Demo Interview', 6],
    ['Eduardo Panganiban', 't6', 'Hired', 30],
    ['Bianca Aguilar', 't5', 'Applied', 0],
];

// The newest employees, with full records (salary and benefits default from the job title).
const featuredEmployees = [
    {
        name: 'Maria Clara Santos', departmentId: 'd5', jobTitleId: 't4', hiredOn: '2024-10-24', salary: 95000,
        birthdate: '1992-05-14', sex: 'Female', civilStatus: 'Single', email: 'mariaclara.santos@company.com',
        phone: '+63 917 123 4567', address: '123 Ayala Avenue, Makati City, Metro Manila',
    },
    {
        name: 'Juan Carlos Reyes', departmentId: 'd4', jobTitleId: 't5', hiredOn: '2024-10-22',
        birthdate: '1988-11-03', sex: 'Male', civilStatus: 'Married', email: 'juancarlos.reyes@company.com',
        phone: '+63 918 234 5678', address: '45 Katipunan Avenue, Quezon City',
    },
    {
        name: 'Angelica De la Cruz', departmentId: 'd1', jobTitleId: 't3', hiredOn: '2024-10-18',
        birthdate: '1979-02-21', sex: 'Female', civilStatus: 'Married', email: 'angelica.delacruz@company.com',
        phone: '+63 919 345 6789', address: '210 Ortigas Avenue, Pasig City',
    },
    {
        name: 'Mark Anthony Bautista', departmentId: 'd4', jobTitleId: 't2', hiredOn: '2024-10-15',
        birthdate: '1975-07-09', sex: 'Male', civilStatus: 'Married', email: 'markanthony.bautista@company.com',
        phone: '+63 920 456 7890', address: '88 España Boulevard, Manila',
    },
    {
        name: 'Kristine Joy Mendoza', departmentId: 'd7', jobTitleId: 't9', hiredOn: '2024-10-10',
        birthdate: '1995-09-30', sex: 'Female', civilStatus: 'Single', email: 'kristinejoy.mendoza@company.com',
        phone: '+63 921 567 8901', address: '17 Shaw Boulevard, Mandaluyong City',
    },
    {
        name: 'Paolo Luis Villanueva', departmentId: 'd3', jobTitleId: 't4', hiredOn: '2024-09-28',
        birthdate: '1986-04-17', sex: 'Male', civilStatus: 'Married', email: 'paololuis.villanueva@company.com',
        phone: '+63 922 678 9012', address: '302 Marcos Highway, Antipolo, Rizal',
    },
    {
        name: 'Catherine Rose Ramos', departmentId: 'd4', jobTitleId: 't7', hiredOn: '2024-09-15',
        birthdate: '1997-12-05', sex: 'Female', civilStatus: 'Single', email: 'catherinerose.ramos@company.com',
        phone: '+63 923 789 0123', address: '64 Roxas Boulevard, Pasay City',
    },
    {
        name: 'Jose Mari Alcantara', departmentId: 'd4', jobTitleId: 't5', hiredOn: '2024-08-30',
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
    const remainingHeadcounts = { d1: 18, d2: 19, d3: 25, d4: 26, d5: 17, d6: 12, d7: 23 };
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
        jobTitles: seedJobTitles.map(({ salary, ...jobTitle }) => ({
            ...jobTitle,
            ...salaryRangeFor(jobTitle.id, salary),
            leaveCredits: defaultLeaveCredits,
            benefits: [...jobTitle.benefits],
            ...postingContentFor(jobTitle.id),
        })),
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
            ...splitPersonName(name),
            source: 'hr',
            email: `${nameParts[0]}.${nameParts[nameParts.length - 1]}@mail.com`,
            phone: `+63 9${17 + index} 555 ${1000 + index * 37}`,
            jobTitleId,
            departmentId: findById(seedJobTitles, jobTitleId).departmentId,
            appliedOn: isoDateOf(appliedOn),
            status,
        }, today);
    });

    // Upcoming activities (scheduled, not completed, so the statuses above stay as they are).
    [['c1', 'Demo Interview', 2, '10:00'], ['c10', 'Demo Interview', 1, '09:00'], ['c2', 'Final Interview', 3, '14:00'], ['c8', 'Final Interview', 6, '10:30']]
        .forEach(([id, type, days, time]) => {
            const application = findById(applications, id);

            application.activities.push({ id: `a${application.activities.length + 1}`, type, date: addDaysIso(today, days), time, status: 'Scheduled' });
        });

    // The sample offers: the bottom of the position's base salary range, starting in a month.
    applications.filter((application) => ['Offered', 'Hired'].includes(application.status)).forEach((application) => {
        application.offer = sampleOfferFor(application, addDaysIso(today, 30), seedJobTitles);
    });

    return { applications, nextApplicationNumber: applications.length + 1, ...seedApplicantAccountState(applications) };
}

// An offer for an application that has none (the samples, and offers issued before offers had details).
function sampleOfferFor(application, startDate, jobTitles) {
    const jobTitle = findById(jobTitles, application.jobTitleId);

    return {
        salary: Number(jobTitle?.salaryMin ?? jobTitle?.salary ?? 0),
        startDate,
        notes: '',
        issuedOn: application.milestones?.find((milestone) => milestone.key === 'offered')?.at ?? application.appliedOn,
        updatedOn: '',
    };
}

// The 201 file details an applicant gives. They use the employee record's field names, so they carry over to the employee
// record when the applicant is hired. The personal details come with the account and the job application; the
// government and tax numbers and the emergency contact are pre-employment information, asked for only once the
// applicant has accepted an offer. Applications HR adds by hand, and the sample ones, have none of them (they read as blank).
const applicantPersonalFields = ['birthdate', 'sex', 'civilStatus', 'address'];
const applicantGovernmentFields = ['sssNumber', 'philhealthNumber', 'pagibigNumber', 'tin', 'birNumber'];
const applicantDetailFields = [...applicantPersonalFields, ...applicantGovernmentFields];

// The emergency contact, asked for with the pre-employment information.
const applicantEmergencyFields = ['emergencyContactName', 'emergencyContactNumber', 'emergencyContactRelationship'];

// Words that start a Filipino or Spanish surname ("Dela Cruz", "De los Santos"), for splitting a full name saved before
// names had parts.
const surnameParticles = ['de', 'del', 'dela', 'delos', 'de la', 'de los', 'san', 'santa', 'santo', 'sta.', 'sta', 'la', 'las', 'los', 'van', 'von', 'di', 'da', 'dos'];

/**
 * "Juan Dela Cruz" → { firstName: 'Juan', middleName: '', lastName: 'Dela Cruz' }. The last word (with any surname particles
 * before it) is the last name and the rest the first name; a middle name cannot be told apart, so it is left blank.
 */
export function splitPersonName(name) {
    const words = String(name ?? '').trim().split(/\s+/).filter(Boolean);

    if (words.length < 2) {
        return { firstName: words[0] ?? '', middleName: '', lastName: '' };
    }

    let start = words.length - 1;

    while (start > 1 && surnameParticles.includes(words[start - 1].toLowerCase())) {
        start -= 1;
    }

    return { firstName: words.slice(0, start).join(' '), middleName: '', lastName: words.slice(start).join(' ') };
}

/**
 * A person's name as it is shown everywhere: first, middle, and last name.
 */
export function fullNameOf({ firstName, middleName, lastName }) {
    return [firstName, middleName, lastName].map((part) => String(part ?? '').trim()).filter(Boolean).join(' ');
}

// The name parts of a record that has them, or of its full name otherwise.
function namePartsOf(record) {
    return record.lastName !== undefined
        ? { firstName: String(record.firstName ?? ''), middleName: String(record.middleName ?? ''), lastName: String(record.lastName ?? '') }
        : splitPersonName(record.name);
}

// The 201 file documents that apply at the application stage (the others belong to employment).
const applicantDocumentKeys = ['hiringRequirements', 'education', 'workHistory'];

// The password on every sample applicant account (mock accounts only; nothing signs in with it yet).
const sampleApplicantPassword = 'Applicant123';

/**
 * Mock applicant accounts for applications that have none: one per email address (any case), named and filled in
 * from that address's first application, with the sample password. Each application is linked to its account by
 * `accountId`. Used for the sample data and for applications saved before accounts existed (12 and earlier).
 */
function seedApplicantAccountState(applications, accounts = [], nextNumber = 1) {
    let next = nextNumber;

    applications.filter((application) => !application.accountId).forEach((application) => {
        const email = application.email.toLowerCase();
        let account = accounts.find((candidate) => candidate.email.toLowerCase() === email);

        if (!account) {
            account = {
                id: `u${next++}`,
                name: application.name,
                ...namePartsOf(application),
                email: application.email,
                password: sampleApplicantPassword,
                phone: application.phone ?? '',
                ...Object.fromEntries(applicantDetailFields.map((field) => [field, String(application[field] ?? '')])),
                documents: Object.fromEntries(applicantDocumentKeys.map((key) => [key, (application.documents?.[key] ?? []).map((file) => ({ ...file }))])),
                createdOn: application.appliedOn,
            };
            accounts.push(account);
        }

        application.accountId = account.id;
    });

    return { applicantAccounts: accounts, nextApplicantAccountNumber: next };
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

// A leave request moves from Pending (filed, waiting for HR) to Approved or Rejected by HR, or to Cancelled by the
// employee while it is still Pending. Nothing changes a status on its own, whatever the dates.
export const leaveStatuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];

// The reason given on the sample requests, by leave type.
const sampleLeaveReasons = {
    'Vacation Leave': 'Personal time off.',
    'Sick Leave': 'Medical rest as advised by the doctor.',
    'Emergency Leave': 'A family emergency that needs my attention.',
};

// Other sample leave, by order: days from today it starts, how many days, and its status. Only
// future requests are still Pending.
const otherLeavePlan = [
    [-20, 2, 'Approved'], [-9, 3, 'Approved'], [4, 2, 'Pending'], [9, 3, 'Pending'], [13, 1, 'Approved'],
    [17, 2, 'Pending'], [22, 3, 'Pending'], [27, 2, 'Approved'], [-3, 2, 'Approved'],
];

// The employee the Employee interface is signed in as (Juan Carlos Reyes). The leave below is his: days
// from today it starts, how many days, type, status, his reason, and HR's remarks. HR sees it too, on Leave Requests,
// the Calendar, and his profile.
const portalEmployeeId = 'EMP-1002';
const portalLeavePlan = [
    [-35, 2, 'Sick Leave', 'Approved', 'Flu; the clinic advised two days of rest.', ''],
    [10, 3, 'Vacation Leave', 'Approved', 'Family trip to Baguio.', 'Approved. Please endorse your classes before you leave.'],
    [32, 2, 'Vacation Leave', 'Approved', 'Personal time off.', ''],
    [18, 1, 'Emergency Leave', 'Pending', 'I need to accompany a family member to a hospital procedure.', ''],
    [50, 2, 'Vacation Leave', 'Pending', 'Attending a relative’s wedding in the province.', ''],
    [24, 2, 'Vacation Leave', 'Rejected', 'Out-of-town seminar.', 'The college holds its midterm examinations on these days. Please file for other dates.'],
    [40, 1, 'Sick Leave', 'Cancelled', 'Medical check-up.', ''],
];

// A timestamp (milliseconds) at an hour of an ISO date, for the review and cancellation times of the sample leave.
function timestampOn(isoDate, hour = 10) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return new Date(year, month - 1, day, hour).getTime();
}

/**
 * A leave request with every field it keeps: the employee's reason, the supporting documents (file names and sizes
 * only), HR's remarks (the reason, when rejected), who reviewed it and when, and when the employee cancelled it.
 */
function leaveRecord(fields) {
    return {
        reason: '',
        supportingDocuments: [],
        hrRemarks: '',
        reviewedBy: '',
        reviewedAt: null,
        cancelledAt: null,
        ...fields,
    };
}

// Review details for a sample request HR already decided (the day after it was filed), or the employee cancelled.
function sampleReview(status, filedOn, hrRemarks = '') {
    if (status === 'Approved' || status === 'Rejected') {
        return { hrRemarks, reviewedBy: seedHrProfile.name, reviewedAt: timestampOn(addDaysIso(filedOn, 1)) };
    }

    return status === 'Cancelled' ? { cancelledAt: timestampOn(addDaysIso(filedOn, 2), 15) } : {};
}

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
    return portalLeavePlan.map(([startOffset, days, type, status, reason, hrRemarks], index) => {
        const startDate = addDaysIso(today, startOffset);
        const filedOn = addDaysIso(today, Math.min(startOffset, 0) - 7);

        return leaveRecord({
            id: `lv${firstNumber + index}`,
            employeeId: portalEmployeeId,
            type,
            startDate,
            endDate: addDaysIso(startDate, days - 1),
            status,
            filedOn,
            reason,
            ...sampleReview(status, filedOn, hrRemarks),
        });
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
        const filedOn = addDaysIso(today, Math.min(startOffset, 0) - 7);

        leaveRequests.push(leaveRecord({
            id: `lv${leaveRequests.length + 1}`,
            employeeId: employee.id,
            type,
            startDate,
            endDate: addDaysIso(startDate, days - 1),
            status,
            filedOn,
            reason: sampleLeaveReasons[type] ?? '',
            ...sampleReview(status, filedOn),
        }));
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
        hrSettings: structuredClone(defaultHrSettings),
        employees,
        nextEmployeeNumber: 1001 + employees.length,
        ...seedDepartmentState(),
        ...seedJobTitleState(),
        ...seedApplicationState(),
        // Job applications an applicant has saved but not submitted (see saveApplicationDraft).
        applicationDrafts: [],
        nextDraftNumber: 1,
        // Employees HR has archived (see archiveEmployee), kept apart from the active list until restored or deleted.
        archivedEmployees: [],
        // Events HR adds on the Calendar (see addCalendarEvent).
        calendarEvents: [],
        nextCalendarEventNumber: 1,
        ...seedLeaveState(employees, today),
        // Newest first; `at` is a timestamp in milliseconds.
        ...withActivityIds({ activity: [
            entry('Login', 'Session', 'Signed in', 0.2),
            entry('Created', 'Candidate · Bianca Aguilar', 'Added to Recruitment as Applied', 3),
            entry('Created', 'Candidate · Juan Dela Cruz', 'Added to Recruitment as Applied', 27),
            entry('Updated', 'Candidate · Isabel Hernandez', 'Stage changed from Final Interview to Offered', 31),
            entry('Updated', 'Job Title · Department Chair', 'Base salary updated to ₱92,000.00', 52),
            entry('Created', 'College · College of Nursing (CON)', 'College created', 75),
            entry('Created', 'Employee · Maria Clara Santos (EMP-1001)', 'Employee record created', 96),
            entry('Updated', 'Employee · Juan Carlos Reyes (EMP-1002)', 'Job title changed to Assistant Professor', 120),
            entry('Hired', 'Candidate · Eduardo Panganiban', 'Hired and added to Employees', 170),
            entry('Deleted', 'Job Title · Teaching Assistant', 'Job title removed', 190),
            entry('Updated', 'College · College of Education (COED)', 'College updated', 215),
            entry('Login', 'Session', 'Signed in', 240),
        ] }),
    };
}

/**
 * Adds the non-teaching openings (version 13 and earlier did not have them) to saved departments and job titles. A
 * department that already exists by name is reused; a job title whose ID is already taken by one HR added is skipped.
 */
function withSeededOpenings({ departments, jobTitles, nextDepartmentNumber, nextJobTitleNumber }) {
    const allDepartments = [...departments];
    const departmentIds = {};
    let nextDepartment = Math.max(nextDepartmentNumber ?? 0, departments.length + 1);

    seedDepartments.slice(firstOpeningsDepartment).forEach((seed) => {
        let department = allDepartments.find((candidate) => candidate.name.toLowerCase() === seed.name.toLowerCase());

        if (!department) {
            department = { id: `d${nextDepartment}`, name: seed.name };
            nextDepartment += 1;
            allDepartments.push(department);
        }

        departmentIds[seed.id] = department.id;
    });

    const allJobTitles = [...jobTitles];

    seedJobTitles.slice(firstOpeningsJobTitle).forEach((seed) => {
        if (!allJobTitles.some((candidate) => candidate.id === seed.id)) {
            allJobTitles.push({ ...seed, departmentId: departmentIds[seed.departmentId] ?? seed.departmentId, benefits: [...seed.benefits] });
        }
    });

    return {
        departments: allDepartments,
        jobTitles: allJobTitles,
        nextDepartmentNumber: nextDepartment,
        nextJobTitleNumber: Math.max(nextJobTitleNumber ?? 0, seedJobTitles.length + 1),
    };
}

// Earlier versions called the first interview stage "Screening". Saved applications, schedules, milestones, audit
// log text, and job descriptions are renamed on load.
const screeningWords = [[/Screenings/g, 'Demo Interviews'], [/Screening/g, 'Demo Interview'], [/screenings/g, 'demo interviews'], [/screening/g, 'demo interview']];

function renameScreening(text) {
    return typeof text === 'string' ? screeningWords.reduce((value, [pattern, replacement]) => value.replace(pattern, replacement), text) : text;
}

function withDemoInterview(application) {
    const renamed = { ...application, status: application.status === 'Screening' ? 'Demo Interview' : application.status };

    if (Array.isArray(application.milestones)) {
        renamed.milestones = application.milestones.map((milestone) => (milestone.key === 'screening' ? { ...milestone, key: 'demoInterview' } : milestone));
    }

    if (Array.isArray(application.activities)) {
        renamed.activities = application.activities.map((activity) => (activity.type === 'Screening' ? { ...activity, type: 'Demo Interview' } : activity));
    }

    return renamed;
}

function withDemoInterviewWording(jobTitle) {
    return {
        ...jobTitle,
        summary: renameScreening(jobTitle.summary),
        description: renameScreening(jobTitle.description),
        responsibilities: (jobTitle.responsibilities ?? []).map(renameScreening),
        qualifications: (jobTitle.qualifications ?? []).map(renameScreening),
        requirements: (jobTitle.requirements ?? []).map(renameScreening),
    };
}

/**
 * Takes the retired openings (version 14 and earlier) out of the saved job titles. One that an application or an employee
 * uses stays, with no openings, so those records keep their position.
 */
function withoutRetiredOpenings(jobTitles, applications, employees) {
    const isUsed = (id) => applications.some((application) => application.jobTitleId === id)
        || employees.some((employee) => employee.jobTitleId === id);

    return jobTitles
        .filter((jobTitle) => !retiredOpeningIds.includes(jobTitle.id) || isUsed(jobTitle.id))
        .map((jobTitle) => (retiredOpeningIds.includes(jobTitle.id) ? { ...jobTitle, openings: 0 } : jobTitle));
}

/**
 * Moves saved data (21 and earlier) from departments to the approved colleges: every record's `departmentId` points to
 * its college, the college list is replaced, and audit log entries name colleges.
 */
function withColleges(saved) {
    const collegeIds = {};

    saved.departments.forEach((department) => {
        const byName = approvedColleges.findIndex((college) => college.toLowerCase().includes(String(department.name).toLowerCase()));

        collegeIds[department.id] = collegeForLegacyDepartment[department.id] ?? (byName >= 0 ? `d${byName + 1}` : fallbackCollegeId);
    });

    const toCollege = (record) => (record && 'departmentId' in record
        ? { ...record, departmentId: collegeIds[record.departmentId] ?? collegeForLegacyDepartment[record.departmentId] ?? fallbackCollegeId }
        : record);
    const collegeEntity = (entity) => String(entity).replace(/^Department · .*$/, (text) => {
        const name = text.slice('Department · '.length);
        const legacy = saved.departments.find((department) => department.name === name);

        return `College · ${legacy ? findById(seedDepartments, collegeIds[legacy.id])?.name ?? name : name}`;
    });

    return {
        ...saved,
        ...seedDepartmentState(),
        jobTitles: saved.jobTitles.map(toCollege),
        employees: saved.employees.map(toCollege),
        archivedEmployees: (saved.archivedEmployees ?? []).map(toCollege),
        applications: saved.applications.map(toCollege),
        applicationDrafts: (saved.applicationDrafts ?? []).map(toCollege),
        activity: saved.activity.map((entry) => ({
            ...entry,
            entity: collegeEntity(entry.entity),
            detail: String(entry.detail).replace(/^Department /, 'College '),
        })),
    };
}

/**
 * Saved data from 21 and earlier: names get their parts, an offer that was issued gets details, and each application
 * notes whether the applicant submitted it (it then has their own details or documents) or HR added it.
 */
function withApplicantRecordsV22(saved) {
    const isApplicantSubmitted = (application) => applicantPersonalFields.some((field) => String(application[field] ?? '').trim())
        || applicantDocumentKeys.some((key) => (application.documents?.[key] ?? []).length > 0);

    return {
        ...saved,
        applicantAccounts: saved.applicantAccounts.map((account) => ({ ...account, ...namePartsOf(account) })),
        applications: saved.applications.map((application) => ({
            ...application,
            ...namePartsOf(application),
            source: application.source ?? (isApplicantSubmitted(application) ? 'applicant' : 'hr'),
            ...(['Offered', 'Hired'].includes(application.status) && !application.offer
                ? { offer: sampleOfferFor(application, '', saved.jobTitles) }
                : {}),
        })),
    };
}

/**
 * Saved data from 22 and earlier: the final interview stage and schedule are named "Final Interview" (they were
 * "Interview"), and leave requests that were Filed are Pending, with the reason, documents, remarks, and review
 * details every request now keeps.
 */
function withV23Records(saved) {
    const finalInterview = (value) => (value === 'Interview' ? 'Final Interview' : value);
    const leave = (request) => leaveRecord({ ...request, status: request.status === 'Filed' ? 'Pending' : request.status });

    return {
        ...saved,
        applications: saved.applications.map((application) => ({
            ...application,
            status: finalInterview(application.status),
            activities: (application.activities ?? []).map((activity) => ({ ...activity, type: finalInterview(activity.type) })),
        })),
        leaveRequests: saved.leaveRequests.map(leave),
        archivedEmployees: (saved.archivedEmployees ?? []).map((employee) => (Array.isArray(employee.archivedLeave)
            ? { ...employee, archivedLeave: employee.archivedLeave.map(leave) }
            : employee)),
        calendarEvents: (saved.calendarEvents ?? []).map((event) => (event.status === 'Filed' ? { ...event, status: 'Pending' } : event)),
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

        // Versions 13 and earlier predate the openings and the colleges; they start from fresh sample data.
        if (!(saved.version >= 14 && saved.version <= storageVersion)) {
            return null;
        }

        // Earlier versions kept departments (1), job titles (2), and applications (3) fixed, and
        // activity had no IDs (4); carry the saved records over and fill in what is missing.
        const migrated = {
            ...saved,
            ...(Array.isArray(saved.departments) ? {} : seedDepartmentState()),
            ...(Array.isArray(saved.jobTitles) ? {} : seedJobTitleState()),
            ...(Array.isArray(saved.applications) ? {} : seedApplicationState()),
            // Application drafts (15 and earlier) start empty.
            ...(Array.isArray(saved.applicationDrafts) ? {} : { applicationDrafts: [], nextDraftNumber: 1 }),
            // Archived employees (16 and earlier) start empty.
            ...(Array.isArray(saved.archivedEmployees) ? {} : { archivedEmployees: [] }),
            // Calendar events (19 and earlier) start empty.
            ...(Array.isArray(saved.calendarEvents) ? {} : { calendarEvents: [], nextCalendarEventNumber: 1 }),
            ...withActivityIds(saved),
            // The HR profile (8 and earlier) starts from the sample account.
            hrProfile: saved.hrProfile?.name ? saved.hrProfile : { ...seedHrProfile },
            // The HR settings (11 and earlier) start with every notification on.
            hrSettings: { notifications: { ...defaultHrSettings.notifications, ...saved.hrSettings?.notifications } },
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

        // The non-teaching openings (13 and earlier) are added to the saved departments and job titles.
        const seeded = saved.version < 14 ? withSeededOpenings(migrated) : {};

        // Saved leave (9 and earlier) gets the signed-in employee's sample leave added once.
        const savedLeave = Array.isArray(migrated.leaveRequests) ? migrated.leaveRequests : null;
        const nextLeaveNumber = Math.max(migrated.nextLeaveNumber ?? 0, (savedLeave?.length ?? 0) + 1);
        const addsPortalLeave = saved.version < 10 && savedLeave && employees.some((employee) => employee.id === portalEmployeeId);
        const portalLeave = addsPortalLeave ? portalLeaveRequests(nextLeaveNumber, today) : [];

        // Applications saved before the workflow (6 and earlier) get milestones that match their status.
        const applications = migrated.applications.map(withDemoInterview).map((application) => (
            Array.isArray(application.milestones) ? application : progressForStatus(application, today)
        ));

        const loaded = {
            ...migrated,
            ...seeded,
            // The audit log's text uses the new stage name too.
            activity: migrated.activity.map((entry) => ({ ...entry, detail: renameScreening(entry.detail) })),
            employees,
            // Leave requests (7 and earlier) start from the sample, covering whoever was on leave.
            ...(savedLeave ? {} : seedLeaveState(employees, today)),
            ...(addsPortalLeave ? { leaveRequests: [...savedLeave, ...portalLeave], nextLeaveNumber: nextLeaveNumber + portalLeave.length } : {}),
            // Job titles saved before postings had their own content (16 and earlier) take the sample details.
            jobTitles: (saved.version < 15
                ? withoutRetiredOpenings(seeded.jobTitles ?? migrated.jobTitles, applications, employees)
                : seeded.jobTitles ?? migrated.jobTitles).map((jobTitle) => ('employmentType' in jobTitle ? jobTitle : { ...postingContentFor(jobTitle.id), ...jobTitle })).map(withOfferedEmploymentType).map(withDemoInterviewWording).map(withSalaryRange).map((jobTitle) => (jobTitle.openings === undefined
                ? { ...jobTitle, openings: findById(seedJobTitles, jobTitle.id)?.openings ?? 0 }
                : jobTitle)),
            applications,
            // Applicant accounts (12 and earlier): every saved application gets one, by email address.
            ...(Array.isArray(migrated.applicantAccounts)
                ? {}
                : seedApplicantAccountState(applications)),
        };

        const upToV22 = saved.version < 22 ? withApplicantRecordsV22(withColleges(loaded)) : loaded;

        const upToV23 = saved.version < 23 ? withV23Records(upToV22) : upToV22;

        return saved.version < 24 ? { ...upToV23, jobTitles: upToV23.jobTitles.map(withLeaveCredits) } : upToV23;
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
function salaryRangeText(jobTitle) {
    return `${formatSalary(jobTitle.salaryMin)} to ${formatSalary(jobTitle.salaryMax)}`;
}

function leaveCreditsText(jobTitle) {
    const credits = Number(jobTitle.leaveCredits ?? 0);

    return `${credits} leave credit${credits === 1 ? '' : 's'}`;
}

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
    const { notifications: shown } = state.hrSettings;

    return {
        currentUser: currentUserView(),
        // The waiting-candidates banner and bell dot are recruitment updates.
        pendingCandidates: shown.recruitment ? countForStatus('Applied') : 0,
        notifications: activityWithDates(state.activity.filter((entry) => isNotificationShown(entry, shown)).slice(0, 4)),
    };
}

// The kind of update an activity entry is, from the record it is about; entries about the session or the
// account itself are not a kind the settings can turn off.
const notificationKinds = { Leave: 'leave', Candidate: 'recruitment', 'Applicant Account': 'recruitment', Employee: 'employees', Department: 'employees', College: 'employees', 'Job Title': 'employees' };

function isNotificationShown(entry, shown) {
    const kind = notificationKinds[entry.entity.split(' · ')[0]];

    return !kind || shown[kind];
}

/**
 * The HR user's Account Settings: which kinds of updates the notifications menu shows
 * (leave requests, recruitment updates, and employee-related updates).
 */
export function getHrSettings() {
    return { notifications: { ...state.hrSettings.notifications } };
}

/**
 * Turns notification kinds on or off. Unknown kinds and non-boolean values are ignored. The header's
 * notifications menu follows right away.
 *
 * @param {{ notifications?: { leave?: boolean, recruitment?: boolean, employees?: boolean } }} changes
 */
export function updateHrSettings(changes) {
    Object.entries(changes.notifications ?? {}).forEach(([kind, value]) => {
        if (kind in defaultHrSettings.notifications && typeof value === 'boolean') {
            state.hrSettings.notifications[kind] = value;
        }
    });
    commitChange();

    return getHrSettings();
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
 * The signed-in employee's own leave requests (every status, with their reason, documents, and HR's remarks), newest
 * filing first, and nothing of anyone else's: the Employee Calendar and Leave Request page. Matched by employee ID.
 */
export function getPortalLeave() {
    return state.leaveRequests
        .filter((leave) => leave.employeeId === portalEmployeeId)
        .sort((first, second) => second.filedOn.localeCompare(first.filedOn) || Number(second.id.slice(2)) - Number(first.id.slice(2)))
        .map((leave) => structuredClone(leave));
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
    const jobTitle = record ? findById(state.jobTitles, record.jobTitleId) : undefined;

    // Base Salary and Leave Credits come from the employee's job title (HR manages them in Job Titles); read-only here.
    return record
        ? { ...withNames(record), salaryMin: jobTitle?.salaryMin, salaryMax: jobTitle?.salaryMax, leaveCredits: jobTitle ? Number(jobTitle.leaveCredits ?? 0) : undefined }
        : undefined;
}

/**
 * The signed-in employee's leave credits for a year: the credits their job title grants, the days of Approved leave in
 * that year (calendar days, any leave type), and what is left. Pending, Rejected, and Cancelled requests take nothing.
 * `credits` is null when the employee or their job title no longer exists.
 *
 * @returns {{ year: number, credits: number|null, used: number, remaining: number|null }}
 */
export function getLeaveBalance(year = Number(todayIso().slice(0, 4))) {
    const employee = findById(state.employees, portalEmployeeId);
    const jobTitle = employee ? findById(state.jobTitles, employee.jobTitleId) : undefined;
    const used = state.leaveRequests
        .filter((leave) => leave.employeeId === portalEmployeeId && leave.status === 'Approved')
        .reduce((total, leave) => total + leaveDaysWithin(leave, `${year}-01-01`, `${year}-12-31`), 0);

    if (!jobTitle) {
        return { year, credits: null, used, remaining: null };
    }

    const credits = Number(jobTitle.leaveCredits ?? 0);

    return { year, credits, used, remaining: credits - used };
}

/**
 * What a leave request would mean for the balance of the year it starts in: the days requested (calendar days, counted
 * within that year) and the credits that would remain if HR approved it. Nothing is deducted until it is approved.
 * `days` is 0 until both dates are valid.
 *
 * @returns {{ days: number, balance: object, remainingAfter: number|null }}
 */
export function estimateLeaveBalance({ startDate, endDate }) {
    const isValid = /^\d{4}-\d{2}-\d{2}$/.test(startDate ?? '') && /^\d{4}-\d{2}-\d{2}$/.test(endDate ?? '') && endDate >= startDate;
    const year = isValid ? Number(startDate.slice(0, 4)) : undefined;
    const balance = getLeaveBalance(year);
    const days = isValid ? leaveDaysWithin({ startDate, endDate }, `${balance.year}-01-01`, `${balance.year}-12-31`) : 0;

    return { days, balance, remainingAfter: balance.remaining === null ? null : balance.remaining - days };
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
            awaitingCount: leave.filter((request) => request.status === 'Pending').length,
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
            // `salary` is what a new employee in the job title starts with: the bottom of its range.
            .map(({ id, name, salaryMin, salaryMax, benefits }) => ({ id, name, salary: salaryMin, salaryMin, salaryMax, benefits })),
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

/**
 * An archived employee as the Archived Employees page shows them: the whole record, with the department and job title
 * names and when they were archived (not the leave requests kept with them).
 */
function archivedView(record) {
    const employee = { ...record };

    delete employee.archivedLeave;

    return {
        ...employee,
        jobTitle: findById(state.jobTitles, employee.jobTitleId)?.name ?? '—',
        department: findById(state.departments, employee.departmentId)?.name ?? '—',
    };
}

/**
 * Moves an employee out of the active list into the archive. Nothing is lost: the record and the leave requests go
 * with them, so Restore brings back exactly what was archived. While archived they are in no list, count, report, or
 * calendar.
 */
export function archiveEmployee(id) {
    const employee = findById(state.employees, id);

    if (!employee) {
        return undefined;
    }

    state.archivedEmployees.push({
        ...employee,
        archivedAt: Date.now(),
        archivedLeave: state.leaveRequests.filter((leave) => leave.employeeId === id),
    });
    state.employees = state.employees.filter((candidate) => candidate.id !== id);
    state.leaveRequests = state.leaveRequests.filter((leave) => leave.employeeId !== id);
    logActivity('Updated', `Employee · ${employee.name} (${id})`, 'Employee archived');
    commitChange();

    return employee;
}

/**
 * Archived employees, most recently archived first.
 */
export function listArchivedEmployees() {
    return [...state.archivedEmployees]
        .sort((first, second) => second.archivedAt - first.archivedAt)
        .map(archivedView);
}

/**
 * Brings an archived employee back to the active list, with the record and leave requests they were archived with.
 */
export function restoreEmployee(id) {
    const record = findById(state.archivedEmployees, id);

    if (!record) {
        return undefined;
    }

    const employee = { ...record };
    const leave = employee.archivedLeave ?? [];

    delete employee.archivedAt;
    delete employee.archivedLeave;

    state.archivedEmployees = state.archivedEmployees.filter((candidate) => candidate.id !== id);
    state.employees.push(employee);
    state.leaveRequests.push(...leave);
    logActivity('Updated', `Employee · ${employee.name} (${id})`, 'Employee restored from the archive');
    commitChange();

    return employee;
}

/**
 * Permanently removes an archived employee. An employee who is still active cannot be deleted: archive them first.
 */
export function deleteArchivedEmployee(id) {
    const record = findById(state.archivedEmployees, id);

    if (!record) {
        return undefined;
    }

    state.archivedEmployees = state.archivedEmployees.filter((candidate) => candidate.id !== id);
    logActivity('Deleted', `Employee · ${record.name} (${id})`, 'Archived employee record deleted');
    commitChange();

    return record;
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
            .map(({ id: jobTitleId, name, salaryMin, salaryMax }) => ({ id: jobTitleId, name, salaryMin, salaryMax })),
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

/**
 * Why a college choice is not valid, or '' when it is: it must be one of the listed (approved) colleges.
 */
export function collegeChoiceError(departmentId) {
    if (!departmentId) {
        return 'Select a college.';
    }

    return findById(state.departments, departmentId) ? '' : 'Choose one of the listed colleges.';
}

/**
 * The approved colleges not yet listed (plus the one being edited, if any), in the approved order.
 */
export function availableColleges(exceptId = null) {
    return approvedColleges.filter((college) => !isDepartmentNameTaken(college, exceptId));
}

/**
 * Why a college name cannot be saved, or '' when it can: it must be one of the approved colleges, listed only once.
 */
export function collegeNameError(name, exceptId = null) {
    if (!String(name ?? '').trim()) {
        return 'Select a college.';
    }

    if (!approvedColleges.includes(name.trim())) {
        return 'Choose one of the approved colleges.';
    }

    return isDepartmentNameTaken(name, exceptId) ? 'This college is already listed.' : '';
}

export function addDepartment(name) {
    if (collegeNameError(name)) {
        return undefined;
    }

    const department = { id: `d${state.nextDepartmentNumber}`, name: name.trim() };

    state.nextDepartmentNumber += 1;
    state.departments.push(department);
    logActivity('Created', `College · ${department.name}`, 'College created');
    commitChange();

    return department;
}

export function updateDepartment(id, name) {
    const department = findById(state.departments, id);

    if (!department || collegeNameError(name, id)) {
        return undefined;
    }

    const previousName = department.name;

    department.name = name.trim();
    logActivity(
        'Updated',
        `College · ${department.name}`,
        previousName === department.name ? 'College updated' : `Renamed from ${previousName}`,
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
    logActivity('Deleted', `College · ${department.name}`, 'College removed');
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
        isOpen: Number(jobTitle.openings || 0) > 0,
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
 * @param {{ name: string, departmentId: string, salaryMin: number, salaryMax: number, leaveCredits?: number, benefits: string[] }} details
 */
export function addJobTitle(details) {
    const jobTitle = { id: `t${state.nextJobTitleNumber}`, leaveCredits: defaultLeaveCredits, ...details, name: details.name.trim() };

    state.nextJobTitleNumber += 1;
    state.jobTitles.push(jobTitle);
    logActivity('Created', `Job Title · ${jobTitle.name}`, `Job title created, base salary ${salaryRangeText(jobTitle)}, ${leaveCreditsText(jobTitle)}`);
    commitChange();

    return jobTitle;
}

/**
 * @param {{ name: string, departmentId: string, salaryMin: number, salaryMax: number, leaveCredits?: number, benefits: string[] }} details
 */
export function updateJobTitle(id, details) {
    const index = state.jobTitles.findIndex((jobTitle) => jobTitle.id === id);

    if (index === -1) {
        return undefined;
    }

    const jobTitle = { ...state.jobTitles[index], ...details, name: details.name.trim(), id };

    state.jobTitles[index] = jobTitle;
    logActivity('Updated', `Job Title · ${jobTitle.name}`, `Job title updated, base salary ${salaryRangeText(jobTitle)}, ${leaveCreditsText(jobTitle)}`);
    commitChange();

    return jobTitle;
}

/**
 * Opens or closes a job posting. A posting is open while its job title has openings, so closing sets them to none (and
 * remembers the number) and opening restores that number, or one opening when there was none. Applications already
 * received are not touched.
 *
 * @param {'Open'|'Closed'} status
 * @returns {{ ok: true, jobTitle: object } | { ok: false, error: string }}
 */
export function setJobPostingStatus(id, status) {
    const jobTitle = findById(state.jobTitles, id);

    if (!jobTitle) {
        return { ok: false, error: 'That position no longer exists.' };
    }

    if (!['Open', 'Closed'].includes(status)) {
        return { ok: false, error: 'A job posting can only be Open or Closed.' };
    }

    const isOpen = Number(jobTitle.openings || 0) > 0;

    if (status === 'Open' && isOpen) {
        return { ok: false, error: `${jobTitle.name} is already open.` };
    }

    if (status === 'Closed' && !isOpen) {
        return { ok: false, error: `${jobTitle.name} is already closed.` };
    }

    if (status === 'Closed') {
        jobTitle.lastOpenings = Number(jobTitle.openings);
        jobTitle.openings = 0;
    } else {
        jobTitle.openings = Math.max(1, Number(jobTitle.lastOpenings || 1));
    }

    logActivity('Updated', `Job Title · ${jobTitle.name}`, status === 'Open' ? `Job posting opened with ${jobTitle.openings} opening${jobTitle.openings === 1 ? '' : 's'}` : 'Job posting closed');
    commitChange();

    return { ok: true, jobTitle: withDepartmentName(jobTitle) };
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
 * Field errors for a demo interview or final interview slot (empty when valid): a date from today on, and a time.
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

function slotText({ date, time, endTime }) {
    return `${formatDate(date)} at ${formatTime(time)}${endTime ? ` to ${formatTime(endTime)}` : ''}`;
}

// ---- HR Calendar scheduling

// The interview types the HR Calendar offers: Demo Interview, Final Interview, and Other is any other on-site meeting and is
// not a recruitment step.
export const interviewTypes = [
    { value: 'Demo Interview', label: 'Demo Interview' },
    { value: 'Final Interview', label: 'Final Interview' },
    { value: 'Other', label: 'Other' },
];

export function interviewTypeLabel(type) {
    return interviewTypes.find((candidate) => candidate.value === type)?.label ?? type;
}

export const rescheduleReasons = [
    'Applicant Requested a Change',
    'Interviewer Unavailable',
    'Schedule Conflict',
    'Emergency/Unforeseen Circumstances',
    'Other',
];

// A schedule set before end times existed is taken to last an hour.
const defaultActivityMinutes = 60;

function minutesOf(time) {
    const [hours, minutes] = String(time).split(':').map(Number);

    return hours * 60 + minutes;
}

function timeOf(minutes) {
    const clamped = Math.min(Math.max(minutes, 0), 23 * 60 + 59);

    return `${String(Math.floor(clamped / 60)).padStart(2, '0')}:${String(clamped % 60).padStart(2, '0')}`;
}

export function activityEndTime(activity) {
    return activity.endTime || timeOf(minutesOf(activity.time) + defaultActivityMinutes);
}

function currentTime() {
    const now = new Date();

    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

/**
 * Why a time range clashes with another scheduled event on the same day, or '' when it does not: the same venue (when
 * `checkVenue`), or the same applicant (by email address), at overlapping times. Applicant schedules and the calendar
 * events HR adds are both looked at; leave events and finished events are not. The event being changed is left out.
 */
function scheduleConflict({ applicationId = null, activityId = null, eventId = null, date, time, endTime, venue, checkVenue = true }) {
    const start = minutesOf(time);
    const end = minutesOf(endTime);
    const place = checkVenue ? String(venue ?? '').trim().toLowerCase() : '';
    const email = findById(state.applications, applicationId ?? '')?.email.toLowerCase() ?? '';

    for (const application of state.applications) {
        for (const activity of application.activities) {
            const isSame = application.id === applicationId && activity.id === activityId;

            if (isSame || activity.status !== 'Scheduled' || activity.date !== date) {
                continue;
            }

            const otherEnd = activityEndTime(activity);

            if (start >= minutesOf(otherEnd) || end <= minutesOf(activity.time)) {
                continue;
            }

            const samePlace = Boolean(place) && String(activity.venue ?? '').trim().toLowerCase() === place;
            const samePerson = Boolean(email) && application.email.toLowerCase() === email;

            if (samePlace || samePerson) {
                const where = samePlace ? ` at ${activity.venue}` : '';
                const range = `${formatTime(activity.time)} to ${formatTime(otherEnd)}`;

                return `This overlaps ${application.name}'s ${interviewTypeLabel(activity.type).toLowerCase()}${where} (${range}). Choose another time${samePlace ? ' or venue' : ''}.`;
            }
        }
    }

    for (const event of state.calendarEvents) {
        if (event.id === eventId || event.type === 'leave' || event.status !== 'Scheduled' || event.date !== date) {
            continue;
        }

        const samePlace = Boolean(place) && String(event.venue).trim().toLowerCase() === place;

        if (samePlace && start < minutesOf(event.endTime) && end > minutesOf(event.time)) {
            return `This overlaps "${event.title}" at ${event.venue} (${formatTime(event.time)} to ${formatTime(event.endTime)}). Choose another time or venue.`;
        }
    }

    return '';
}

// A date and start time must not be in the past; the end time must come after the start time.
function slotErrors({ date, time, endTime }) {
    const errors = {};

    if (!date) {
        errors.date = 'Choose a date.';
    } else if (date < todayIso()) {
        errors.date = 'Choose today or a later date.';
    }

    if (!time) {
        errors.time = 'Choose a start time.';
    } else if (date === todayIso() && time <= currentTime()) {
        errors.time = 'Choose a start time later than now.';
    }

    if (!endTime) {
        errors.endTime = 'Choose an end time.';
    } else if (time && endTime <= time) {
        errors.endTime = 'End Time must be later than Start Time.';
    }

    return errors;
}

/**
 * Field errors for a new HR Calendar schedule (empty when it can be saved): an applicant whose next step allows the
 * interview type, a date and start time from now on, an end time after the start time, an on-site venue that is not
 * blank, and no conflict with another schedule at that venue or for that applicant.
 *
 * @returns {{ applicationId?: string, type?: string, date?: string, time?: string, endTime?: string, venue?: string }}
 */
export function validateCalendarSchedule({ applicationId, type, date, time, endTime, venue }) {
    const errors = {};
    const application = findById(state.applications, applicationId ?? '');

    if (!application) {
        errors.applicationId = 'Select an applicant.';
    }

    if (!type) {
        errors.type = 'Select the interview type.';
    } else if (application) {
        const workflow = workflowFor(application);

        if (workflow.isFinal) {
            errors.type = `${application.name}'s application is ${application.status} and cannot be scheduled.`;
        } else if (type !== 'Other' && workflow.pendingActivity) {
            errors.type = `${application.name} already has a ${interviewTypeLabel(workflow.pendingActivity.type).toLowerCase()} scheduled.`;
        } else if (type !== 'Other' && workflow.scheduleType !== type) {
            errors.type = workflow.scheduleType
                ? `${application.name}'s next step is a ${interviewTypeLabel(workflow.scheduleType).toLowerCase()}.`
                : `${application.name} is not waiting for an interview.`;
        }
    }

    Object.assign(errors, slotErrors({ date, time, endTime }));

    if (!String(venue ?? '').trim()) {
        errors.venue = 'Enter the venue.';
    }

    if (application && !errors.date && !errors.time && !errors.endTime) {
        const conflict = scheduleConflict({ applicationId, date, time, endTime, venue });

        if (conflict) {
            errors.time = conflict;
        }
    }

    return errors;
}

/**
 * Field errors for rescheduling an HR Calendar event (empty when it can be saved): a new date and start time from now
 * on, an end time after the start time, a schedule different from the current one, a reason (with details for Other),
 * and no conflict at the event's venue or for its applicant.
 *
 * @returns {{ date?: string, time?: string, endTime?: string, reason?: string, reasonDetail?: string }}
 */
export function validateRescheduleSlot(applicationId, activityId, { date, time, endTime, reason, reasonDetail }) {
    const application = findById(state.applications, applicationId ?? '');
    const activity = application?.activities.find((candidate) => candidate.id === activityId);

    if (!activity) {
        return { date: 'That schedule no longer exists.' };
    }

    const errors = rescheduleFieldErrors({ date: activity.date, time: activity.time, endTime: activityEndTime(activity) }, { date, time, endTime, reason, reasonDetail });

    if (!errors.date && !errors.time && !errors.endTime) {
        const conflict = scheduleConflict({ applicationId, activityId, date, time, endTime, venue: activity.venue });

        if (conflict) {
            errors.time = conflict;
        }
    }

    return errors;
}

// The checks rescheduling shares: a valid future slot that differs from the current one, and a reason.
function rescheduleFieldErrors(current, { date, time, endTime, reason, reasonDetail }) {
    const errors = slotErrors({ date, time, endTime });

    if (!errors.date && !errors.time && !errors.endTime && date === current.date && time === current.time && endTime === current.endTime) {
        errors.date = 'Choose a different date or time than the current schedule.';
    }

    if (!rescheduleReasons.includes(reason)) {
        errors.reason = 'Select a reason for rescheduling.';
    } else if (reason === 'Other' && !String(reasonDetail ?? '').trim()) {
        errors.reasonDetail = 'Describe the reason.';
    }

    return errors;
}

// ---- Calendar events HR adds

// The kinds of event the HR Calendar can hold, with the statuses each uses (the same ones the calendar already filters by):
// leave is Pending or Approved; everything else is Scheduled or Completed.
export const calendarEventTypes = [
    { value: 'leave', label: 'Employee Leave', statuses: ['Pending', 'Approved'] },
    { value: 'demo-interview', label: 'Applicant Demo Interview', statuses: ['Scheduled', 'Completed'] },
    { value: 'interview', label: 'Applicant Final Interview', statuses: ['Scheduled', 'Completed'] },
    { value: 'other', label: 'Other Applicant Event', statuses: ['Scheduled', 'Completed'] },
];

const timePattern = /^([01]\d|2[0-3]):[0-5]\d$/;

function isRealDate(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(String(value))) {
        return false;
    }

    const [year, month, day] = String(value).split('-').map(Number);
    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
}

/**
 * Field errors for a calendar event HR adds or edits (empty when it can be saved): a title, a type, a real date, start and
 * end times with the end later than the start, a venue that is not blank, a status that fits the type, and (for events
 * that are Scheduled and not leave) no clash with another event at the same venue. Past dates are fine, to record
 * events that already happened.
 *
 * @returns {{ title?: string, type?: string, date?: string, time?: string, endTime?: string, venue?: string, status?: string }}
 */
export function validateCalendarEvent({ title, type, date, time, endTime, venue, status }, exceptId = null) {
    const errors = {};
    const kind = calendarEventTypes.find((candidate) => candidate.value === type);

    if (!String(title ?? '').trim()) {
        errors.title = 'Enter an event title.';
    } else if (String(title).trim().length > 120) {
        errors.title = 'Keep the title to 120 characters or fewer.';
    }

    if (!kind) {
        errors.type = 'Select an event type.';
    }

    if (!date) {
        errors.date = 'Choose a date.';
    } else if (!isRealDate(date)) {
        errors.date = 'Enter a valid date.';
    }

    if (!time) {
        errors.time = 'Choose a start time.';
    } else if (!timePattern.test(time)) {
        errors.time = 'Enter a valid start time.';
    }

    if (!endTime) {
        errors.endTime = 'Choose an end time.';
    } else if (!timePattern.test(endTime)) {
        errors.endTime = 'Enter a valid end time.';
    } else if (!errors.time && endTime <= time) {
        errors.endTime = 'End Time must be later than Start Time.';
    }

    if (!String(venue ?? '').trim()) {
        errors.venue = 'Enter the venue.';
    }

    if (kind && !kind.statuses.includes(status)) {
        errors.status = 'Select a status.';
    }

    if (kind && kind.value !== 'leave' && status === 'Scheduled' && !errors.date && !errors.time && !errors.endTime && !errors.venue) {
        const conflict = scheduleConflict({ eventId: exceptId, date, time, endTime, venue });

        if (conflict) {
            errors.time = conflict;
        }
    }

    return errors;
}

function eventView(event) {
    return { ...event, history: (event.history ?? []).map((entry) => ({ ...entry })) };
}

/**
 * The calendar events HR added, in the order they were added.
 */
export function listCalendarEvents() {
    return state.calendarEvents.map(eventView);
}

/**
 * Adds an event to the HR Calendar. It is free-standing (not tied to an applicant or a leave request), so it changes no
 * recruitment status.
 *
 * @returns {{ ok: true, event: object } | { ok: false, error: string, errors: object }}
 */
export function addCalendarEvent(details) {
    const errors = validateCalendarEvent(details);

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    const event = {
        id: `ev${state.nextCalendarEventNumber}`,
        title: String(details.title).trim(),
        type: details.type,
        date: details.date,
        time: details.time,
        endTime: details.endTime,
        venue: String(details.venue).trim(),
        status: details.status,
        notes: String(details.notes ?? '').trim(),
        history: [],
    };

    state.nextCalendarEventNumber += 1;
    state.calendarEvents.push(event);
    logActivity('Created', `Calendar Event · ${event.title}`, `Event added for ${slotText(event)} at ${event.venue}`);
    commitChange();

    return { ok: true, event: eventView(event) };
}

/**
 * Edits an event's details. The rescheduling history is kept as it is; use rescheduleCalendarEvent to move an event and
 * record why.
 */
export function updateCalendarEvent(id, details) {
    const event = findById(state.calendarEvents, id);

    if (!event) {
        return failure('That event no longer exists.');
    }

    const errors = validateCalendarEvent(details, id);

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    Object.assign(event, {
        title: String(details.title).trim(),
        type: details.type,
        date: details.date,
        time: details.time,
        endTime: details.endTime,
        venue: String(details.venue).trim(),
        status: details.status,
        notes: String(details.notes ?? '').trim(),
    });
    logActivity('Updated', `Calendar Event · ${event.title}`, `Event updated: ${slotText(event)} at ${event.venue}`);
    commitChange();

    return { ok: true, event: eventView(event) };
}

/**
 * Field errors for rescheduling one of HR's calendar events (empty when it can be saved): the same checks as rescheduling
 * an interview.
 */
export function validateEventReschedule(id, values) {
    const event = findById(state.calendarEvents, id);

    if (!event) {
        return { date: 'That event no longer exists.' };
    }

    const errors = rescheduleFieldErrors({ date: event.date, time: event.time, endTime: event.endTime }, values);

    if (!errors.date && !errors.time && !errors.endTime && event.type !== 'leave' && event.status === 'Scheduled') {
        const conflict = scheduleConflict({ eventId: id, date: values.date, time: values.time, endTime: values.endTime, venue: event.venue });

        if (conflict) {
            errors.time = conflict;
        }
    }

    return errors;
}

/**
 * Moves one of HR's calendar events, keeping the previous schedule and the reason in its history.
 */
export function rescheduleCalendarEvent(id, values) {
    const event = findById(state.calendarEvents, id);

    if (!event) {
        return failure('That event no longer exists.');
    }

    if (event.status === 'Completed' || event.status === 'Approved') {
        return failure(`This event is already ${event.status.toLowerCase()} and cannot be rescheduled.`);
    }

    const errors = validateEventReschedule(id, values);

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    event.history = [...(event.history ?? []), {
        fromDate: event.date,
        fromTime: event.time,
        fromEndTime: event.endTime,
        toDate: values.date,
        toTime: values.time,
        toEndTime: values.endTime,
        reason: values.reason,
        reasonDetail: String(values.reasonDetail ?? '').trim(),
        changedAt: Date.now(),
    }];
    Object.assign(event, { date: values.date, time: values.time, endTime: values.endTime });
    logActivity('Updated', `Calendar Event · ${event.title}`, `Event rescheduled to ${slotText(event)} (${values.reason === 'Other' ? String(values.reasonDetail).trim() : values.reason})`);
    commitChange();

    return { ok: true, event: eventView(event) };
}

/**
 * Applicants still waiting for their next interview (Applied, or done with the demo interview) with nothing scheduled
 * for it yet, oldest application first. Scheduling one takes them off this list; cancelling their schedule puts them back.
 */
export function listUnscheduledApplicants() {
    return state.applications
        .filter((application) => ['Applied', 'Demo Interview'].includes(application.status) && workflowFor(application).scheduleType)
        .map(withApplicationNames)
        .map((application) => ({
            id: application.id,
            name: application.name,
            jobTitle: application.jobTitle,
            department: application.department,
            status: application.status,
            appliedOn: application.appliedOn,
            nextType: workflowFor(application).scheduleType,
        }))
        .sort((first, second) => first.appliedOn.localeCompare(second.appliedOn) || first.name.localeCompare(second.name));
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
// `user` names who made the change in the audit log (the HR user when omitted).
function moveTo(application, status, action, detail, user = undefined) {
    const previous = application.status;

    application.status = status;
    logActivity(action, applicationEntity(application), `${detail}; stage changed from ${previous} to ${status}`, user);
}

/**
 * The positions an applicant can apply for: job titles with at least one opening, by department then
 * name. Nothing about pay or benefits is included.
 */
export function listOpenPositions() {
    return state.jobTitles
        .filter((jobTitle) => Number(jobTitle.openings || 0) > 0)
        .map((jobTitle) => ({
            id: jobTitle.id,
            name: jobTitle.name,
            departmentId: jobTitle.departmentId,
            department: findById(state.departments, jobTitle.departmentId)?.name ?? '—',
            openings: Number(jobTitle.openings),
        }))
        .sort((first, second) => first.department.localeCompare(second.department) || first.name.localeCompare(second.name));
}

/**
 * Every position for the Applicant interface and the public Careers website, open (at least one opening, as HR's Job
 * Titles set) and closed (none), by department then name, with what each involves and asks for. `salaryMin` and
 * `salaryMax` are the job title's monthly base salary range, the same figures HR manages; benefits are listed separately.
 */
export function listPositions() {
    return state.jobTitles
        .map((jobTitle) => ({
            id: jobTitle.id,
            name: jobTitle.name,
            departmentId: jobTitle.departmentId,
            department: findById(state.departments, jobTitle.departmentId)?.name ?? '—',
            openings: Number(jobTitle.openings || 0),
            isOpen: Number(jobTitle.openings || 0) > 0,
            employmentType: jobTitle.employmentType ?? '',
            location: jobTitle.location ?? '',
            salaryMin: Number(jobTitle.salaryMin || 0),
            salaryMax: Number(jobTitle.salaryMax || 0),
            summary: jobTitle.summary || jobTitle.description || '',
            description: jobTitle.description ?? '',
            responsibilities: [...(jobTitle.responsibilities ?? [])],
            qualifications: [...(jobTitle.qualifications ?? [])],
            requirements: [...(jobTitle.requirements ?? [])],
            benefits: [...jobTitle.benefits],
        }))
        .sort((first, second) => first.department.localeCompare(second.department) || first.name.localeCompare(second.name));
}

/**
 * One position as the Applicant interface shows it (see listPositions), open or closed, or undefined.
 */
export function findPosition(id) {
    return listPositions().find((position) => position.id === id);
}

function findAccount(accountId) {
    return findById(state.applicantAccounts, accountId ?? '');
}

function normalizedEmail(email) {
    return String(email).trim().toLowerCase();
}

/**
 * The applicant's own account as the Applicant interface shows it, without the password, or undefined when there
 * is no such account.
 */
export function getApplicantAccount(accountId) {
    const account = findAccount(accountId);

    if (!account) {
        return undefined;
    }

    return {
        id: account.id,
        name: account.name,
        ...namePartsOf(account),
        email: account.email,
        phone: account.phone ?? '',
        createdOn: account.createdOn,
        ...submittedDetails(account),
    };
}

/**
 * Whether another applicant account already uses this email address (ignoring case and surrounding spaces).
 */
export function isApplicantAccountEmailTaken(email, exceptAccountId = null) {
    const normalized = normalizedEmail(email);

    return state.applicantAccounts.some((account) => account.id !== exceptAccountId && account.email.toLowerCase() === normalized);
}

/**
 * Field errors for the applicant's own personal details (empty when valid): last and first name, an optional middle
 * name, and a birthdate, sex, and civil status from the listed options. Values are trimmed before they are checked.
 *
 * @returns {{ lastName?: string, firstName?: string, middleName?: string, birthdate?: string, sex?: string, civilStatus?: string }}
 */
export function validateApplicantPersonal(details) {
    const rules = {
        lastName: personNameRule('Enter your last name.'),
        firstName: personNameRule('Enter your first name.'),
        middleName: (value) => (String(value).trim() ? personNameRule('')(value) : ''),
        birthdate: birthdateRule('Enter your birthdate.'),
        sex: choiceRule(sexOptions, 'Select your sex.'),
        civilStatus: choiceRule(civilStatusOptions, 'Select your civil status.'),
    };

    return Object.fromEntries(Object.entries(rules)
        .filter(([field]) => field in details)
        .map(([field, rule]) => [field, rule(String(details[field] ?? ''))])
        .filter(([, message]) => message));
}

/**
 * Creates a mock applicant account with the applicant's name (in parts) and personal details. The password is kept
 * only in this frontend mock data, never sent anywhere.
 *
 * @param {{ lastName: string, firstName: string, middleName: string, birthdate: string, sex: string, civilStatus: string, email: string, password: string }} details
 * @returns {{ ok: true, account: object } | { ok: false, error: string, errors: object }}
 */
export function registerApplicant({ lastName, firstName, middleName = '', birthdate, sex, civilStatus, email, password }) {
    const personal = { lastName, firstName, middleName, birthdate, sex, civilStatus };
    const personalErrors = validateApplicantPersonal(personal);

    if (Object.keys(personalErrors).length) {
        return { ok: false, error: Object.values(personalErrors)[0], errors: personalErrors };
    }

    if (isApplicantAccountEmailTaken(email)) {
        const error = 'An account with this email address already exists. Log in instead.';

        return { ok: false, error, errors: { email: error } };
    }

    const nameParts = {
        firstName: String(firstName).trim(),
        middleName: String(middleName ?? '').trim(),
        lastName: String(lastName).trim(),
    };
    const account = {
        id: `u${state.nextApplicantAccountNumber}`,
        name: fullNameOf(nameParts),
        ...nameParts,
        email: String(email).trim(),
        password: String(password),
        phone: '',
        ...Object.fromEntries(applicantDetailFields.map((field) => [field, ''])),
        birthdate: String(birthdate).trim(),
        sex: String(sex).trim(),
        civilStatus: String(civilStatus).trim(),
        documents: applicantDocuments({}),
        createdOn: todayIso(),
    };

    state.nextApplicantAccountNumber += 1;
    state.applicantAccounts.push(account);
    logActivity('Created', `Applicant Account · ${account.name}`, 'Applicant account created', account.name);
    commitChange();

    return { ok: true, account: getApplicantAccount(account.id) };
}

/**
 * Checks an email and password against the mock accounts (this is not real authentication: the passwords are sample
 * data in this browser). The email is matched ignoring case and surrounding spaces.
 *
 * @returns {{ ok: true, account: object } | { ok: false, error: string }}
 */
export function authenticateApplicant(email, password) {
    const normalized = normalizedEmail(email);
    const account = state.applicantAccounts.find((candidate) => candidate.email.toLowerCase() === normalized);

    if (!account) {
        return { ok: false, error: 'No account was found for this email address.' };
    }

    return account.password === password
        ? { ok: true, account: getApplicantAccount(account.id) }
        : { ok: false, error: 'The email or password is incorrect.' };
}

function applicationsOfAccount(accountId) {
    return state.applications.filter((application) => application.accountId === accountId);
}

/**
 * Whether the email address would clash with an application from someone else for a position this applicant has
 * applied to (one application per email per position). The applicant's own applications do not count.
 */
function isEmailUsedOnOthersApplications(accountId, email) {
    const own = applicationsOfAccount(accountId);
    const jobTitleIds = new Set(own.map((application) => application.jobTitleId));
    const normalized = normalizedEmail(email);

    return state.applications.some((application) => (
        application.accountId !== accountId && jobTitleIds.has(application.jobTitleId) && application.email.toLowerCase() === normalized
    ));
}

/**
 * Field errors for the applicant's own email address (empty when it can be used): it must not belong to another
 * account or clash with someone else's application for a position they applied to.
 *
 * @returns {{ email?: string }}
 */
export function validateApplicantEmail(accountId, email) {
    if (isApplicantAccountEmailTaken(email, accountId)) {
        return { email: 'Another account already uses this email address.' };
    }

    if (isEmailUsedOnOthersApplications(accountId, email)) {
        return { email: 'An application from this email address already exists for a position you applied to.' };
    }

    return {};
}

/**
 * Saves the applicant's edits to their own profile: email, phone, address, birthdate, sex, and civil status. The name
 * stays as it was registered. The email address and phone number are how HR reaches them, so they also change on all of
 * their applications; the other details apply to their next application (submitted applications keep what was sent).
 * Nothing is logged when nothing changed.
 *
 * @returns {{ ok: true, account: object } | { ok: false, error: string, errors: object }}
 */
export function updateApplicantAccount(accountId, details) {
    const account = findAccount(accountId);

    if (!account) {
        return { ok: false, error: 'Your account no longer exists.', errors: {} };
    }

    const errors = {
        ...validateApplicantPersonal({ birthdate: details.birthdate, sex: details.sex, civilStatus: details.civilStatus }),
        ...validateApplicantEmail(accountId, details.email),
    };

    if (Object.keys(errors).length) {
        return { ok: false, error: Object.values(errors)[0], errors };
    }

    const changes = {
        email: String(details.email).trim(),
        phone: String(details.phone).trim(),
        ...Object.fromEntries(applicantPersonalFields.map((field) => [field, String(details[field] ?? '').trim()])),
    };
    const hasChanges = Object.entries(changes).some(([field, value]) => String(account[field] ?? '') !== value);

    if (!hasChanges) {
        return { ok: true, account: getApplicantAccount(accountId) };
    }

    Object.assign(account, changes);
    logActivity('Updated', `Applicant Account · ${account.name}`, 'Profile updated by the applicant', account.name);

    applicationsOfAccount(accountId)
        .filter((application) => application.email !== changes.email || application.phone !== changes.phone)
        .forEach((application) => {
            Object.assign(application, { email: changes.email, phone: changes.phone });
            logActivity('Updated', applicationEntity(application), 'Contact details updated by the applicant', account.name);
        });

    commitChange();

    return { ok: true, account: getApplicantAccount(accountId) };
}

function scheduledActivitiesOf(application) {
    return application.activities
        .filter((activity) => activity.status === 'Scheduled')
        .sort((first, second) => `${first.date} ${first.time}`.localeCompare(`${second.date} ${second.time}`));
}

/**
 * One application as the Applicant interface shows it: what was submitted, the position, where it stands, and the
 * demo interview and final interview schedule HR set. Recruitment's internal fields (such as the offer response or a linked
 * employee record) are not passed on.
 */
function applicantView(application) {
    const named = withApplicationNames(application);
    const position = findById(state.jobTitles, application.jobTitleId) ?? {};
    const next = scheduledActivitiesOf(application)[0];
    const workflow = workflowFor(application);

    return {
        id: named.id,
        name: named.name,
        ...namePartsOf(named),
        email: named.email,
        phone: named.phone,
        jobTitleId: named.jobTitleId,
        jobTitle: named.jobTitle,
        department: named.department,
        employmentType: position.employmentType ?? '',
        location: position.location ?? '',
        appliedOn: named.appliedOn,
        status: named.status,
        ...submittedDetails(named),
        milestones: named.milestones.map(({ key, at }) => ({ key, at })),
        activities: named.activities.map(({ id: activityId, type, date, time, endTime, venue, status, completedOn }) => ({
            id: activityId, type, date, time, endTime: endTime ?? '', venue: venue ?? '', status, completedOn,
        })),
        next: next ? { type: next.type, date: next.date, time: next.time } : null,
        // The offer HR issued, and the applicant's own steps: answering it, then giving the pre-employment information.
        offer: named.offer ? { ...named.offer } : null,
        offerResponse: named.offerResponse ?? '',
        preEmploymentSubmittedAt: named.preEmploymentSubmittedAt ?? null,
        canRespondToOffer: workflow.canRespondToOffer,
        canSubmitPreEmployment: workflow.canSubmitPreEmployment,
    };
}

/**
 * The applicant's applications, newest first (see applicantView). Empty when the account does not exist.
 */
export function listApplicantApplications(accountId) {
    return applicationsOfAccount(accountId ?? '')
        .map(applicantView)
        .sort((first, second) => second.appliedOn.localeCompare(first.appliedOn) || second.id.localeCompare(first.id, undefined, { numeric: true }));
}

/**
 * One of the applicant's own applications (see applicantView), or undefined when it does not exist or belongs to
 * someone else.
 */
export function getApplicantApplication(accountId, applicationId) {
    const application = findById(state.applications, applicationId ?? '');

    return application && application.accountId === (accountId ?? '') ? applicantView(application) : undefined;
}

/**
 * Whether this applicant can apply for the position: it must be open, and they (or someone using their email
 * address) must not have applied for it already. Returns an error message, or '' when they can.
 */
export function applicationBlockFor(accountId, jobTitleId) {
    const account = findAccount(accountId);
    const jobTitle = findById(state.jobTitles, jobTitleId ?? '');

    if (!account) {
        return 'Your account no longer exists.';
    }

    if (!jobTitle || Number(jobTitle.openings || 0) <= 0) {
        return 'This position is closed and is not accepting applications.';
    }

    if (applicationsOfAccount(accountId).some((application) => application.jobTitleId === jobTitleId)) {
        return 'You have already applied for this position.';
    }

    return isDuplicateApplication(account.email, jobTitleId)
        ? 'An application from your email address already exists for this position.'
        : '';
}

/**
 * Submits the signed-in applicant's job application: a new Recruitment application (status Applied) linked to their
 * account and the position, with their name and email from the account. The personal details and documents they give
 * are kept on the application and become their profile's details, so the next application starts from them. The
 * government and tax numbers and the emergency contact are not part of it: they come later, as pre-employment information.
 *
 * @param {{ jobTitleId: string, phone: string, documents: object }} details plus the personal detail fields
 * @returns {{ ok: true, application: object } | { ok: false, error: string, errors?: object }}
 */
export function submitApplication(accountId, details) {
    const error = applicationBlockFor(accountId, details.jobTitleId);

    if (error) {
        return { ok: false, error };
    }

    const personalErrors = validateApplicantPersonal({ birthdate: details.birthdate, sex: details.sex, civilStatus: details.civilStatus });

    if (Object.keys(personalErrors).length) {
        return { ok: false, error: Object.values(personalErrors)[0], errors: personalErrors };
    }

    const account = findAccount(accountId);
    const jobTitle = findById(state.jobTitles, details.jobTitleId);
    const given = {
        phone: String(details.phone).trim(),
        ...Object.fromEntries(applicantPersonalFields.map((field) => [field, String(details[field] ?? '').trim()])),
        documents: applicantDocuments(details.documents),
    };

    Object.assign(account, given, { documents: applicantDocuments(given.documents) });

    // Submitting turns the draft into the application, so the draft goes away (before the change is announced).
    state.applicationDrafts = state.applicationDrafts.filter((draft) => !(
        draft.accountId === accountId && (draft.id === details.draftId || draft.jobTitleId === jobTitle.id)
    ));

    const application = addApplication({
        ...given,
        accountId,
        source: 'applicant',
        name: account.name,
        ...namePartsOf(account),
        email: account.email,
        jobTitleId: jobTitle.id,
        departmentId: jobTitle.departmentId,
        appliedOn: todayIso(),
    }, account.name);

    return { ok: true, application: applicantView(application) };
}

/**
 * A saved draft as the Applicant interface shows it: what was filled in, the position it is for (blank when none was
 * chosen yet), and when it was saved. A draft is not an application: it has no status and HR never sees it.
 */
function draftView(draft) {
    const jobTitle = findById(state.jobTitles, draft.jobTitleId);

    return {
        ...draft,
        documents: applicantDocuments(draft.documents),
        jobTitle: jobTitle?.name ?? '',
        department: findById(state.departments, jobTitle?.departmentId)?.name ?? '',
    };
}

/**
 * The applicant's saved drafts, most recently saved first.
 */
export function listApplicantDrafts(accountId) {
    return state.applicationDrafts
        .filter((draft) => draft.accountId === (accountId ?? ''))
        .sort((first, second) => second.savedAt - first.savedAt)
        .map(draftView);
}

/**
 * One of the applicant's drafts, by its ID or else the one for a position, or undefined.
 *
 * @param {{ id?: string, jobTitleId?: string }} which
 */
export function getApplicationDraft(accountId, { id = '', jobTitleId = '' } = {}) {
    const draft = state.applicationDrafts.find((candidate) => (
        candidate.accountId === (accountId ?? '') && (id ? candidate.id === id : Boolean(jobTitleId) && candidate.jobTitleId === jobTitleId)
    ));

    return draft ? draftView(draft) : undefined;
}

/**
 * Saves what the applicant has filled in so far, to reopen and edit later. Nothing is validated except the position
 * (when one is chosen, it must still be open and not already applied for), and nothing else changes: no application
 * is created, so there is no status, HR Recruitment does not show it, and the applicant's profile is left alone.
 * Passing `draftId` updates that draft (even if its position changed); otherwise a position keeps one draft.
 *
 * @param {{ draftId?: string, jobTitleId: string, phone: string, documents: object }} details plus the personal detail fields
 * @returns {{ ok: true, draft: object } | { ok: false, error: string, errors: object }}
 */
export function saveApplicationDraft(accountId, details) {
    if (!findAccount(accountId)) {
        return failure('Your account no longer exists.');
    }

    const jobTitleId = String(details.jobTitleId ?? '');
    const block = jobTitleId ? applicationBlockFor(accountId, jobTitleId) : '';

    if (block) {
        return failure(block, { jobTitleId: block });
    }

    let draft = state.applicationDrafts.find((candidate) => candidate.id === details.draftId && candidate.accountId === accountId);

    // Only one draft per position: saving for a position replaces any other draft of this applicant for it.
    state.applicationDrafts = state.applicationDrafts.filter((candidate) => (
        candidate === draft || !(jobTitleId && candidate.accountId === accountId && candidate.jobTitleId === jobTitleId)
    ));

    if (!draft) {
        draft = { id: `dr${state.nextDraftNumber}`, accountId };
        state.nextDraftNumber += 1;
        state.applicationDrafts.push(draft);
    }

    Object.assign(draft, {
        jobTitleId,
        phone: String(details.phone ?? '').trim(),
        ...Object.fromEntries(applicantPersonalFields.map((field) => [field, String(details[field] ?? '').trim()])),
        documents: applicantDocuments(details.documents),
        savedAt: Date.now(),
    });
    commitChange();

    return { ok: true, draft: draftView(draft) };
}

/**
 * An applicant's submitted documents: their lists of file records (name, size, date added), copied, with every
 * list present.
 */
function applicantDocuments(source) {
    return Object.fromEntries(applicantDocumentKeys.map((key) => [key, (source?.[key] ?? []).map((file) => ({ ...file }))]));
}

/**
 * The 201 file details and documents on an application, as plain values (blank when not given).
 */
function submittedDetails(source) {
    return {
        ...Object.fromEntries([...applicantDetailFields, ...applicantEmergencyFields].map((field) => [field, String(source[field] ?? '').trim()])),
        documents: applicantDocuments(source.documents),
    };
}

/**
 * New applications always start as Applied. `loggedBy` names who made the entry in the audit log (the
 * HR user when omitted; an applicant's own name when they apply themselves). `accountId` links an application an
 * applicant submitted to their account. One HR adds by hand is linked to the applicant account with its email address
 * (one is made, with the sample password, when there is none), so only the applicant can answer an offer.
 *
 * @param {{ lastName?: string, firstName?: string, middleName?: string, name?: string, email: string, phone: string, jobTitleId: string, departmentId: string, appliedOn: string, accountId?: string, source?: string }} details
 */
export function addApplication(details, loggedBy = undefined) {
    const { email, phone, jobTitleId, departmentId, appliedOn, accountId = '', source = 'hr' } = details;
    const nameParts = namePartsOf(details);
    const name = fullNameOf(nameParts) || String(details.name ?? '').trim();
    const application = progressForStatus(
        {
            id: `c${state.nextApplicationNumber}`,
            name,
            ...nameParts,
            source,
            email,
            phone,
            jobTitleId,
            departmentId,
            appliedOn,
            status: 'Applied',
            accountId,
            ...submittedDetails(details),
        },
        todayIso(),
    );
    const jobTitle = findById(state.jobTitles, application.jobTitleId);

    state.nextApplicationNumber += 1;
    state.applications.push(application);

    if (!application.accountId) {
        Object.assign(state, seedApplicantAccountState([application], state.applicantAccounts, state.nextApplicantAccountNumber));
    }
    logActivity(
        'Created',
        applicationEntity(application),
        `Added to Recruitment as Applied for ${jobTitle?.name ?? 'a position'}`,
        loggedBy,
    );
    commitChange();

    return application;
}

/**
 * Whether HR may edit an application: only one HR added by hand. What an applicant submitted is theirs, so HR can read it
 * but not change it.
 */
export function canHrEditApplication(application) {
    return Boolean(application) && (application.source ?? 'hr') !== 'applicant';
}

/**
 * Edits the details of an application HR added. The status, milestones, and activities are not editable here: they
 * change only through the workflow actions below. An application the applicant submitted is left unchanged (undefined).
 *
 * @param {{ lastName: string, firstName: string, middleName: string, email: string, phone: string, jobTitleId: string, departmentId: string, appliedOn: string }} details
 */
export function updateApplication(id, details) {
    const application = findById(state.applications, id);

    if (!application || !canHrEditApplication(application)) {
        return undefined;
    }

    const { email, phone, jobTitleId, departmentId, appliedOn } = details;
    const nameParts = namePartsOf(details);

    Object.assign(application, { name: fullNameOf(nameParts), ...nameParts, email, phone, jobTitleId, departmentId, appliedOn });

    // The applicant account made for this application (it has no other) keeps the same name and contact details, so the
    // applicant signs in with the email HR has on file.
    const account = findAccount(application.accountId);

    if (account && applicationsOfAccount(account.id).length === 1 && !isApplicantAccountEmailTaken(email, account.id)) {
        Object.assign(account, { name: application.name, ...nameParts, email: String(email).trim(), phone: String(phone).trim() });
    }

    const received = application.milestones.find((milestone) => milestone.key === 'applied');

    if (received) {
        received.at = appliedOn;
    }

    logActivity('Updated', applicationEntity(application), 'Application updated');
    commitChange();

    return application;
}

/**
 * Puts a demo interview, final interview, or other schedule on the calendar. This does not change the status: that
 * happens when a demo interview or final interview is completed. The HR Calendar also sends an end time, an on-site venue,
 * and notes, and those are checked (including for conflicts); Recruitment sends only the date and start time.
 *
 * @param {{ type: string, date: string, time: string, endTime?: string, venue?: string, notes?: string }} slot
 */
export function scheduleActivity(id, { type, date, time, endTime, venue, notes }) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    const workflow = workflowFor(application);
    const isCalendarSchedule = endTime !== undefined;

    if (type === 'Other' ? workflow.isFinal : workflow.scheduleType !== type) {
        const message = workflow.pendingActivity
            ? `Complete or cancel the scheduled ${workflow.pendingActivity.type.toLowerCase()} first.`
            : `A ${interviewTypeLabel(type).toLowerCase()} cannot be scheduled while the application is ${application.status}.`;

        return failure(message, { type: message });
    }

    const errors = isCalendarSchedule
        ? validateCalendarSchedule({ applicationId: id, type, date, time, endTime, venue })
        : validateActivitySlot({ date, time });

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    const activity = {
        id: nextActivityId(application),
        type,
        date,
        time,
        status: 'Scheduled',
        ...(isCalendarSchedule ? { endTime, venue: String(venue).trim(), notes: String(notes ?? '').trim(), history: [] } : {}),
    };

    application.activities.push(activity);
    logActivity('Updated', applicationEntity(application), `${interviewTypeLabel(type)} scheduled for ${slotText(activity)}${activity.venue ? ` at ${activity.venue}` : ''}`);
    commitChange();

    return success(application, { activity });
}

/**
 * Changes the date and time of a scheduled (not yet completed) activity. The previous schedule, and the reason when one
 * is given, are kept in the activity's history. The HR Calendar sends an end time and a reason (both checked, with
 * conflicts); Recruitment sends only the date and start time, and an end time then keeps its length.
 *
 * @param {{ date: string, time: string, endTime?: string, reason?: string, reasonDetail?: string }} slot
 */
export function rescheduleActivity(id, activityId, { date, time, endTime, reason, reasonDetail }) {
    const application = findById(state.applications, id);
    const activity = application?.activities.find((candidate) => candidate.id === activityId);

    if (!application || !activity) {
        return failure('That activity no longer exists.');
    }

    if (activity.status !== 'Scheduled') {
        return failure(`This ${activity.type.toLowerCase()} is already completed and cannot be rescheduled.`);
    }

    const isCalendarChange = reason !== undefined;
    const errors = isCalendarChange
        ? validateRescheduleSlot(id, activityId, { date, time, endTime, reason, reasonDetail })
        : validateActivitySlot({ date, time });

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    const keptLength = activity.endTime ? minutesOf(activity.endTime) - minutesOf(activity.time) : null;
    const newEndTime = endTime || (keptLength === null ? '' : timeOf(minutesOf(time) + keptLength));

    activity.history = [...(activity.history ?? []), {
        fromDate: activity.date,
        fromTime: activity.time,
        fromEndTime: activity.endTime ?? '',
        toDate: date,
        toTime: time,
        toEndTime: newEndTime,
        reason: reason ?? '',
        reasonDetail: String(reasonDetail ?? '').trim(),
        changedAt: Date.now(),
    }];
    Object.assign(activity, { date, time }, newEndTime ? { endTime: newEndTime } : {});
    logActivity('Updated', applicationEntity(application), `${interviewTypeLabel(activity.type)} rescheduled to ${slotText(activity)}${reason ? ` (${reason === 'Other' ? String(reasonDetail).trim() : reason})` : ''}`);
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
 * a completed demo interview moves Applied to Demo Interview, a completed interview moves Demo Interview to Interview.
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

    // An "Other" schedule is not a recruitment step, so completing it moves nothing.
    if (!nextStatus || application.status === nextStatus) {
        logActivity('Updated', applicationEntity(application), `${activity.type} completed`);
    } else {
        addMilestone(application, milestoneKeyFor[activity.type]);
        moveTo(application, nextStatus, 'Updated', `${activity.type} completed`);
    }

    commitChange();

    return success(application, { activity });
}

/**
 * Field errors for an offer's details (empty when valid): a monthly salary within the position's base salary range, a
 * start date from today on, and optional notes.
 *
 * @returns {{ salary?: string, startDate?: string, notes?: string }}
 */
export function validateOffer(applicationId, { salary, startDate, notes = '' }) {
    const application = findById(state.applications, applicationId ?? '');
    const jobTitle = findById(state.jobTitles, application?.jobTitleId ?? '');
    const errors = {};
    const amount = String(salary ?? '').trim() === '' ? Number.NaN : Number(salary);

    if (String(salary ?? '').trim() === '') {
        errors.salary = 'Enter the monthly salary offered.';
    } else if (!Number.isFinite(amount) || amount <= 0) {
        errors.salary = 'The salary must be an amount greater than 0.';
    } else if (jobTitle && (amount < Number(jobTitle.salaryMin) || amount > Number(jobTitle.salaryMax))) {
        errors.salary = `The salary must be within the position's base salary range: ${salaryRangeText(jobTitle)}.`;
    }

    if (!startDate) {
        errors.startDate = 'Choose the start date.';
    } else if (!isRealDate(startDate)) {
        errors.startDate = 'Enter a valid date.';
    } else if (startDate < todayIso()) {
        errors.startDate = 'Choose today or a later date.';
    }

    if (String(notes ?? '').trim().length > 500) {
        errors.notes = 'Keep the notes to 500 characters or fewer.';
    }

    return errors;
}

function offerDetails({ salary, startDate, notes }) {
    return { salary: Number(salary), startDate, notes: String(notes ?? '').trim() };
}

/**
 * Issues a job offer after the final interview has been completed: Interview to Offered, waiting for the applicant's
 * response. The offer's salary, start date, and notes are checked first (see validateOffer).
 */
export function issueOffer(id, details = {}) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    const workflow = workflowFor(application);

    if (!workflow.canIssueOffer) {
        return failure(workflow.pendingActivity
            ? `Complete or cancel the scheduled ${workflow.pendingActivity.type.toLowerCase()} first.`
            : 'An offer can be issued only after the final interview has been completed.');
    }

    const errors = validateOffer(id, details);

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    application.offer = { ...offerDetails(details), issuedOn: todayIso(), updatedOn: '' };
    application.offerResponse = 'Pending';
    addMilestone(application, 'offered');
    moveTo(application, 'Offered', 'Updated', `Offer issued: ${formatSalary(application.offer.salary)} a month, starting ${formatDate(application.offer.startDate)}`);
    commitChange();

    return success(application);
}

/**
 * Changes an offer's details while the applicant has not answered it yet. The status stays Offered.
 */
export function updateOffer(id, details) {
    const application = findById(state.applications, id);

    if (!application) {
        return failure('That application no longer exists.');
    }

    if (!workflowFor(application).canUpdateOffer) {
        return failure('Only an offer that is still waiting for the applicant\'s response can be changed.');
    }

    const errors = validateOffer(id, details);

    if (Object.keys(errors).length) {
        return failure(Object.values(errors)[0], errors);
    }

    application.offer = { ...application.offer, ...offerDetails(details), updatedOn: todayIso() };
    logActivity('Updated', applicationEntity(application), `Offer updated: ${formatSalary(application.offer.salary)} a month, starting ${formatDate(application.offer.startDate)}`);
    commitChange();

    return success(application);
}

/**
 * The applicant's own answer to their offer: Accepted keeps the application Offered until the pre-employment
 * information is given and HR completes hiring; Declined closes it as Rejected. Only the signed-in applicant's own
 * application can be answered, so HR cannot answer for them.
 *
 * @param {'Accepted'|'Declined'} response
 * @returns {{ ok: true, application: object } | { ok: false, error: string }}
 */
export function respondToOffer(accountId, applicationId, response) {
    const application = findById(state.applications, applicationId ?? '');
    const account = findAccount(accountId);

    if (!application || !account || application.accountId !== account.id) {
        return { ok: false, error: 'This application does not exist or is not one of yours.' };
    }

    if (!['Accepted', 'Declined'].includes(response)) {
        return { ok: false, error: 'Choose whether to accept or decline the offer.' };
    }

    if (!workflowFor(application).canRespondToOffer) {
        return { ok: false, error: 'This offer is not waiting for your response.' };
    }

    application.offerResponse = response;

    if (response === 'Accepted') {
        addMilestone(application, 'accepted');
        logActivity('Updated', applicationEntity(application), 'Offer accepted by the applicant', account.name);
    } else {
        application.activities = application.activities.filter((activity) => activity.status !== 'Scheduled');
        addMilestone(application, 'declined');
        moveTo(application, 'Rejected', 'Updated', 'Offer declined by the applicant', account.name);
    }

    commitChange();

    return { ok: true, application: applicantView(application) };
}

/**
 * Field errors for the pre-employment information (empty when valid): the emergency contact (all required) and the
 * government and tax numbers (optional, in their usual formats when given).
 */
export function validatePreEmployment(details) {
    const rules = {
        emergencyContactName: personNameRule('Enter your emergency contact’s full name.'),
        emergencyContactNumber: mobileNumberRule('Enter your emergency contact’s mobile number.'),
        emergencyContactRelationship: choiceRule(emergencyRelationshipOptions, 'Select your emergency contact’s relationship to you.'),
        sssNumber: sssRule,
        philhealthNumber: philhealthRule,
        pagibigNumber: pagibigRule,
        tin: tinRule,
        birNumber: idNumberRule,
    };

    return Object.fromEntries(Object.entries(rules)
        .map(([field, rule]) => [field, rule(String(details[field] ?? ''))])
        .filter(([, message]) => message));
}

/**
 * The applicant's pre-employment information, given after they accept an offer: the emergency contact and government
 * and tax numbers, kept on the application (and on their account, to fill in a later form). It can be corrected until
 * hiring is completed. HR completes hiring only once it is in.
 *
 * @returns {{ ok: true, application: object } | { ok: false, error: string, errors?: object }}
 */
export function submitPreEmployment(accountId, applicationId, details) {
    const application = findById(state.applications, applicationId ?? '');
    const account = findAccount(accountId);

    if (!application || !account || application.accountId !== account.id) {
        return { ok: false, error: 'This application does not exist or is not one of yours.' };
    }

    if (!workflowFor(application).canSubmitPreEmployment) {
        return { ok: false, error: 'Pre-employment information is asked for once you have accepted an offer.' };
    }

    const errors = validatePreEmployment(details);

    if (Object.keys(errors).length) {
        return { ok: false, error: Object.values(errors)[0], errors };
    }

    const given = Object.fromEntries([...applicantEmergencyFields, ...applicantGovernmentFields].map((field) => [field, String(details[field] ?? '').trim()]));
    const isUpdate = Boolean(application.preEmploymentSubmittedAt);

    Object.assign(application, given, { preEmploymentSubmittedAt: Date.now() });
    Object.assign(account, given);

    if (!isUpdate) {
        addMilestone(application, 'preEmployment');
    }

    logActivity('Updated', applicationEntity(application), isUpdate ? 'Pre-employment information updated by the applicant' : 'Pre-employment information submitted by the applicant', account.name);
    commitChange();

    return { ok: true, application: applicantView(application) };
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
        let reason = 'Only an applicant who accepted an offer can be hired.';

        if (application.status === 'Offered' && application.offerResponse !== 'Accepted') {
            reason = 'The applicant must accept the offer before hiring can be completed.';
        } else if (workflow.canSubmitPreEmployment && !workflow.hasPreEmployment) {
            reason = 'The applicant must submit their pre-employment information before hiring can be completed.';
        } else if (workflow.pendingActivity) {
            reason = `Complete or cancel the scheduled ${workflow.pendingActivity.type.toLowerCase()} first.`;
        }

        return failure(reason);
    }

    const jobTitle = findById(state.jobTitles, application.jobTitleId);

    if (!jobTitle) {
        return failure('The position for this application no longer exists.');
    }

    // What the applicant already submitted carries over, so nothing is asked for twice; the employee-only
    // document lists (BIR 2316, performance, and so on) start empty.
    const submitted = submittedDetails(application);
    const existing = state.employees.find((candidate) => candidate.email.toLowerCase() === application.email.toLowerCase());
    let employee;

    if (existing) {
        // The same person is already an employee: reuse that record (no second copy), fill in only what it is
        // missing, and move it to the position they were hired for.
        [...applicantDetailFields, 'phone'].forEach((field) => {
            existing[field] = existing[field] || (field === 'phone' ? application.phone : submitted[field]);
        });
        existing.documents = Object.fromEntries(documentCategories.map(([key]) => {
            const kept = existing.documents?.[key] ?? [];
            const added = (submitted.documents[key] ?? []).filter((file) => !kept.some((listed) => listed.name === file.name && listed.size === file.size));

            return [key, [...kept, ...added]];
        }));
        existing.departmentId = application.departmentId;
        existing.jobTitleId = application.jobTitleId;
        logActivity('Updated', `Employee · ${existing.name} (${existing.id})`, `Hired as ${jobTitle.name}; details carried over from the application`);
        employee = existing;
    } else {
        employee = addEmployee({
            ...submitted,
            documents: Object.fromEntries(documentCategories.map(([key]) => [key, submitted.documents[key] ?? []])),
            name: application.name,
            email: application.email,
            phone: application.phone,
            departmentId: application.departmentId,
            jobTitleId: application.jobTitleId,
            hiredOn: todayIso(),
            salary: application.offer?.salary || jobTitle.salaryMin,
            benefits: [...jobTitle.benefits],
        });
    }

    // Filling the position uses up one of its openings, so Open Positions (HR's Dashboard and Job Titles, and the
    // applicants' view) agree with the roster.
    jobTitle.openings = Math.max(0, Number(jobTitle.openings || 0) - 1);
    logActivity('Updated', `Job Title · ${jobTitle.name}`, `Openings reduced to ${jobTitle.openings} after a hire`);

    application.employeeId = employee.id;
    addMilestone(application, 'hired');
    moveTo(
        application,
        'Hired',
        'Hired',
        existing ? `Hired and linked to existing employee ${employee.id}` : `Hired and added to Employees as ${employee.id}`,
    );
    commitChange();

    return success(application, { employee, linked: Boolean(existing) });
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

// The leave types an employee can file (the same ones HR sees on the Calendar).
const filableLeaveTypes = ['Vacation Leave', 'Sick Leave', 'Emergency Leave'];

// The longest reason an employee, or rejection reason HR, can give.
export const leaveReasonMaxLength = 500;

export function getLeaveTypes() {
    return [...filableLeaveTypes];
}

// Leave that still holds its days: Pending (waiting for HR) or Approved. Rejected and cancelled requests free them.
const isActiveLeave = (leave) => leave.status === 'Pending' || leave.status === 'Approved';

/**
 * One leave request with the employee's name, department, and job title, or undefined when it no longer exists.
 */
export function findLeaveRequest(id) {
    const leave = findById(state.leaveRequests, id);

    return leave ? withEmployeeNames(leave) : undefined;
}

/**
 * Field errors for a leave request from the signed-in employee (empty when valid): a leave type, a first day from
 * today on, a last day that is not before the first, a reason, and no overlap with their Pending or Approved leave
 * (which also stops the same request being filed twice).
 *
 * @returns {{ type?: string, startDate?: string, endDate?: string, reason?: string }}
 */
export function validateLeaveRequest({ type, startDate, endDate, reason = '' }) {
    const errors = {};
    const trimmedReason = String(reason).trim();

    if (!filableLeaveTypes.includes(type)) {
        errors.type = 'Choose a leave type.';
    }

    if (!startDate) {
        errors.startDate = 'Choose the start date.';
    } else if (startDate < todayIso()) {
        errors.startDate = 'Choose today or a later date.';
    }

    if (!endDate) {
        errors.endDate = 'Choose the end date.';
    } else if (startDate && endDate < startDate) {
        errors.endDate = 'The end date cannot be before the start date.';
    }

    if (!trimmedReason) {
        errors.reason = 'Enter the reason for your leave.';
    } else if (trimmedReason.length > leaveReasonMaxLength) {
        errors.reason = `Keep the reason within ${leaveReasonMaxLength} characters.`;
    }

    const overlapping = !errors.startDate && !errors.endDate && state.leaveRequests.find((leave) => (
        leave.employeeId === portalEmployeeId && isActiveLeave(leave) && leave.startDate <= endDate && leave.endDate >= startDate
    ));

    if (overlapping) {
        errors.startDate = `You already have ${overlapping.status.toLowerCase()} ${overlapping.type.toLowerCase()} from ${formatDate(overlapping.startDate)} to ${formatDate(overlapping.endDate)} on some of these days.`;
    }

    return errors;
}

/**
 * Files a leave request for the signed-in employee. It starts Pending (waiting for HR) and appears at once on HR's
 * Leave Requests and Calendar, the employee's Leave Request page and Calendar, and HR's notifications; only HR can
 * approve or reject it. Supporting documents keep only each file's name, size, and the date it was added.
 *
 * @param {{ type: string, startDate: string, endDate: string, reason: string, supportingDocuments?: Array<{ name: string, size: number, addedOn: string }> }} details
 * @returns {{ ok: boolean, error?: string, errors?: object, leave?: object }}
 */
export function fileLeaveRequest(details) {
    const employee = findById(state.employees, portalEmployeeId);

    if (!employee) {
        return { ok: false, error: 'Your employee record no longer exists.', errors: {} };
    }

    const errors = validateLeaveRequest(details);

    if (Object.keys(errors).length) {
        return { ok: false, error: '', errors };
    }

    const { type, startDate, endDate } = details;
    const leave = leaveRecord({
        id: `lv${state.nextLeaveNumber}`,
        employeeId: employee.id,
        type,
        startDate,
        endDate,
        status: 'Pending',
        filedOn: todayIso(),
        reason: String(details.reason).trim(),
        supportingDocuments: (details.supportingDocuments ?? []).map(({ name, size, addedOn }) => ({ name, size, addedOn })),
    });

    state.leaveRequests.push(leave);
    state.nextLeaveNumber += 1;
    logActivity(
        'Created',
        `Leave · ${employee.name}`,
        `${type} from ${formatDate(startDate)} to ${formatDate(endDate)} filed`,
        employee.name,
    );
    commitChange();

    return { ok: true, leave: withEmployeeNames(leave) };
}

// Why a request can no longer be decided or cancelled: it is gone, or it is not Pending any more.
function notPendingError(leave) {
    if (!leave) {
        return 'That leave request no longer exists.';
    }

    return leave.status === 'Pending' ? '' : `This leave request is already ${leave.status.toLowerCase()}.`;
}

/**
 * HR approves a Pending leave request, with optional remarks; who approved it and when are recorded. If it covers
 * today, the employee shows as On Leave. A request HR already decided, or the employee cancelled, cannot be approved.
 *
 * @returns {{ ok: boolean, error?: string, errors?: { remarks?: string }, leave?: object }}
 */
export function approveLeave(id, { remarks = '' } = {}) {
    const leave = findById(state.leaveRequests, id);
    const error = notPendingError(leave);

    if (error) {
        return { ok: false, error };
    }

    const trimmedRemarks = String(remarks).trim();

    if (trimmedRemarks.length > leaveReasonMaxLength) {
        return { ok: false, error: '', errors: { remarks: `Keep the remarks within ${leaveReasonMaxLength} characters.` } };
    }

    const employee = findById(state.employees, leave.employeeId);

    Object.assign(leave, { status: 'Approved', hrRemarks: trimmedRemarks, reviewedBy: state.hrProfile.name, reviewedAt: Date.now() });
    logActivity(
        'Updated',
        `Leave · ${employee?.name ?? leave.employeeId}`,
        `${leave.type} from ${formatDate(leave.startDate)} to ${formatDate(leave.endDate)} approved`,
    );
    commitChange();

    return { ok: true, leave: withEmployeeNames(leave) };
}

/**
 * HR rejects a Pending leave request. The reason is required; the employee sees it as HR's remarks. The request stays
 * on record as Rejected.
 *
 * @returns {{ ok: boolean, error?: string, errors?: { reason?: string }, leave?: object }}
 */
export function rejectLeave(id, { reason = '' } = {}) {
    const leave = findById(state.leaveRequests, id);
    const error = notPendingError(leave);

    if (error) {
        return { ok: false, error };
    }

    const trimmedReason = String(reason).trim();

    if (!trimmedReason) {
        return { ok: false, error: '', errors: { reason: 'Enter the reason for rejecting this request.' } };
    }

    if (trimmedReason.length > leaveReasonMaxLength) {
        return { ok: false, error: '', errors: { reason: `Keep the reason within ${leaveReasonMaxLength} characters.` } };
    }

    const employee = findById(state.employees, leave.employeeId);

    Object.assign(leave, { status: 'Rejected', hrRemarks: trimmedReason, reviewedBy: state.hrProfile.name, reviewedAt: Date.now() });
    logActivity(
        'Updated',
        `Leave · ${employee?.name ?? leave.employeeId}`,
        `${leave.type} from ${formatDate(leave.startDate)} to ${formatDate(leave.endDate)} rejected: ${trimmedReason}`,
    );
    commitChange();

    return { ok: true, leave: withEmployeeNames(leave) };
}

/**
 * The signed-in employee cancels one of their own Pending requests. It stays on record as Cancelled; requests of other
 * employees, and requests HR already decided, cannot be cancelled here.
 *
 * @returns {{ ok: boolean, error?: string, leave?: object }}
 */
export function cancelLeaveRequest(id) {
    const leave = findById(state.leaveRequests, id);
    const ownLeave = leave?.employeeId === portalEmployeeId ? leave : undefined;
    const error = notPendingError(ownLeave);

    if (error) {
        return { ok: false, error };
    }

    const employee = findById(state.employees, ownLeave.employeeId);

    Object.assign(ownLeave, { status: 'Cancelled', cancelledAt: Date.now() });
    logActivity(
        'Updated',
        `Leave · ${employee?.name ?? ownLeave.employeeId}`,
        `${ownLeave.type} from ${formatDate(ownLeave.startDate)} to ${formatDate(ownLeave.endDate)} cancelled`,
        employee?.name,
    );
    commitChange();

    return { ok: true, leave: withEmployeeNames(ownLeave) };
}

export function resetMockData() {
    state = createInitialState();
    commitChange();
}
