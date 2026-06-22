<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import GradeSummary from '@/components/dashboard/GradeSummary.vue';
import RecentAnnouncements from '@/components/dashboard/RecentAnnouncements.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import TodaySchedule from '@/components/dashboard/TodaySchedule.vue';
import UpcomingAssignments from '@/components/dashboard/UpcomingAssignments.vue';
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
        <div class="mx-auto flex w-full max-w-[1400px] flex-col gap-5 p-4 sm:p-6 lg:p-8">
            <WelcomeHeader
                :student="props.student"
                :academic-context="props.academicContext"
                :enrollment="props.enrollment"
            />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <StatCard :value="props.stats.totalClasses" label="Enrolled Classes" accent-color="text-cyan-400" />
                <StatCard :value="props.stats.totalUnits" label="Total Units" accent-color="text-blue-400" />
                <StatCard :value="props.stats.pendingAssignments" label="Pending Tasks" accent-color="text-amber-400" />
                <StatCard :value="props.stats.unreadAnnouncements" label="Announcements" accent-color="text-emerald-400" />

                <div class="flex flex-col gap-5 md:col-span-2">
                    <TodaySchedule :items="props.todaySchedule" />
                </div>
                <div class="flex flex-col gap-5 md:col-span-2">
                    <UpcomingAssignments :items="props.upcomingAssignments" />
                </div>
                <div class="flex flex-col gap-5 md:col-span-2">
                    <GradeSummary :items="props.gradeSummary" />
                </div>
                <div class="flex flex-col gap-5 md:col-span-2">
                    <RecentAnnouncements :items="props.recentAnnouncements" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
