export type StudentInfo = {
    firstName: string;
    lastName: string;
    fullName: string;
    studentNumber: string | null;
};

export type AcademicContext = {
    academicYearName: string;
    termName: string | null;
    termId: number | null;
};

export type EnrollmentSummary = {
    status: string;
    sectionName: string | null;
    subjectsCount: number;
    studentNumber: string | null;
};

export type ScheduleItem = {
    id: number;
    subjectName: string;
    subjectCode: string;
    startsAt: string;
    endsAt: string;
    roomName: string | null;
};

export type AssignmentItem = {
    id: number;
    title: string;
    subjectName: string;
    subjectCode: string | null;
    dueAt: string | null;
    points: number | null;
    submissionStatus: string | null;
    submissionScore: number | null;
};

export type GradeItem = {
    classOfferingId: number;
    className: string;
    subjectCode: string | null;
    gradedCount: number;
    totalScore: number;
    totalPoints: number;
    percentage: number;
};

export type AnnouncementItem = {
    id: number;
    title: string | null;
    body: string;
    subjectName: string;
    subjectCode: string | null;
    publishedAt: string;
    classOfferingId: number;
};

export type DashboardStats = {
    totalClasses: number;
    totalUnits: number;
    pendingAssignments: number;
    unreadAnnouncements: number;
};

export type DashboardProps = {
    student: StudentInfo | null;
    academicContext: AcademicContext | null;
    enrollment: EnrollmentSummary | null;
    todaySchedule: ScheduleItem[];
    upcomingAssignments: AssignmentItem[];
    gradeSummary: GradeItem[];
    recentAnnouncements: AnnouncementItem[];
    stats: DashboardStats;
};
