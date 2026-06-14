<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types/dashboard';
import { type BreadcrumbItem } from '@/types';
import { BookOpen, Calculator, ClipboardCheck, Bell } from 'lucide-vue-next';

import WelcomeHeader from '@/components/dashboard/WelcomeHeader.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import TodaySchedule from '@/components/dashboard/TodaySchedule.vue';
import UpcomingAssignments from '@/components/dashboard/UpcomingAssignments.vue';
import GradeSummary from '@/components/dashboard/GradeSummary.vue';
import RecentAnnouncements from '@/components/dashboard/RecentAnnouncements.vue';

const props = defineProps<DashboardProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-hidden p-4 sm:p-6 lg:p-8"
        >
            <!-- Section 1: Welcome Header -->
            <WelcomeHeader
                :student="props.student"
                :academic-context="props.academicContext"
                :enrollment="props.enrollment"
            />

            <!-- Section 2: Stats Row -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    :icon="BookOpen"
                    :value="props.stats.totalClasses"
                    label="Classes Enrolled"
                    accent-color="bg-gradient-to-b from-violet-500 to-indigo-600/60"
                />
                <StatCard
                    :icon="Calculator"
                    :value="props.stats.totalUnits"
                    label="Total Units"
                    accent-color="bg-gradient-to-b from-blue-500 to-cyan-600/60"
                />
                <StatCard
                    :icon="ClipboardCheck"
                    :value="props.stats.pendingAssignments"
                    label="Pending Tasks"
                    accent-color="bg-gradient-to-b from-amber-500 to-orange-600/60"
                />
                <StatCard
                    :icon="Bell"
                    :value="props.stats.unreadAnnouncements"
                    label="New Announcements"
                    accent-color="bg-gradient-to-b from-emerald-500 to-teal-600/60"
                />
            </div>

            <!-- Section 3: Main Content Grid -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-5">
                <!-- Left Column (col-span 3) -->
                <div class="flex flex-col gap-6 lg:col-span-3">
                    <TodaySchedule :items="props.todaySchedule" />
                    <UpcomingAssignments :items="props.upcomingAssignments" />
                </div>

                <!-- Right Column (col-span 2) -->
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <GradeSummary :items="props.gradeSummary" />
                    <RecentAnnouncements :items="props.recentAnnouncements" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
