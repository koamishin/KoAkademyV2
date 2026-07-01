<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AcademicProgress from '@/components/dashboard/AcademicProgress.vue';
import StatsBar from '@/components/dashboard/StatsBar.vue';
import TodayFocus from '@/components/dashboard/TodayFocus.vue';
import WelcomeHeader from '@/components/dashboard/WelcomeHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/campus';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import type {
    AcademicContext,
    EnrollmentSummary,
    GradeSummaryItem,
    StudentInfo,
} from '@/types/dashboard';

type ScheduleItem = {
    id: number;
    subjectName: string;
    subjectCode?: string;
    startsAt: string;
    endsAt: string;
    roomName?: string | null;
};

type AssignmentItem = {
    id: number;
    title: string;
    subjectName: string;
    subjectCode?: string;
    dueAt?: string | null;
    points?: string | null;
    submissionStatus?: string | null;
    submissionScore?: string | null;
};

type AnnouncementItem = {
    id: number;
    title?: string | null;
    body: string;
    subjectName: string;
    subjectCode?: string | null;
    publishedAt: string;
    classOfferingId: number;
};

const props = defineProps<{
    student: StudentInfo | null;
    academicContext: AcademicContext | null;
    enrollment: EnrollmentSummary | null;
    todaySchedule: ScheduleItem[];
    upcomingAssignments: AssignmentItem[];
    gradeSummary: GradeSummaryItem[];
    recentAnnouncements: AnnouncementItem[];
    stats: {
        totalClasses: number;
        totalUnits: number;
        pendingAssignments: number;
        unreadAnnouncements: number;
    };
}>();

const page = usePage<AppPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard({ campus: page.props.currentCampus!.slug }).url,
    },
];
</script>

<template>
    <Head title="Student Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-8">
            <WelcomeHeader
                :student="props.student"
                :academic-context="props.academicContext"
                :enrollment="props.enrollment"
            />

            <StatsBar :stats="props.stats" />

            <TodayFocus
                :schedule="props.todaySchedule"
                :assignments="props.upcomingAssignments"
            />

            <AcademicProgress
                :grades="props.gradeSummary"
                :announcements="props.recentAnnouncements"
                :campus="page.props.currentCampus!.slug"
            />
        </div>
    </AppLayout>
</template>
