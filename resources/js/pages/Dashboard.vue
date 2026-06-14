<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types/dashboard';
import { type BreadcrumbItem } from '@/types';

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
            class="mx-auto flex w-full max-w-[1400px] flex-col gap-5 p-4 sm:p-6 lg:p-8"
        >
            <!-- Header (Full width of the container) -->
            <WelcomeHeader
                :student="props.student"
                :academic-context="props.academicContext"
                :enrollment="props.enrollment"
            />

            <!-- Main Bento Grid -->
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <!-- Stat Cards -->
                <StatCard
                    :value="props.stats.totalClasses"
                    label="Enrolled Classes"
                    accent-color="text-violet-400"
                />
                <StatCard
                    :value="props.stats.totalUnits"
                    label="Total Units"
                    accent-color="text-blue-400"
                />
                <StatCard
                    :value="props.stats.pendingAssignments"
                    label="Pending Tasks"
                    accent-color="text-amber-400"
                />
                <StatCard
                    :value="props.stats.unreadAnnouncements"
                    label="Announcements"
                    accent-color="text-emerald-400"
                />

                <!-- Schedule & Assignments -->
                <div class="flex flex-col gap-5 md:col-span-2 xl:col-span-2">
                    <TodaySchedule :items="props.todaySchedule" />
                </div>
                <div class="flex flex-col gap-5 md:col-span-2 xl:col-span-2">
                    <UpcomingAssignments :items="props.upcomingAssignments" />
                </div>

                <!-- Grades & Feed -->
                <div class="flex flex-col gap-5 md:col-span-2 xl:col-span-2">
                    <GradeSummary :items="props.gradeSummary" />
                </div>
                <div class="flex flex-col gap-5 md:col-span-2 xl:col-span-2">
                    <RecentAnnouncements :items="props.recentAnnouncements" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
