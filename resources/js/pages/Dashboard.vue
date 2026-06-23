<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { BookOpen, Users, Clock, CheckCircle2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/campus';
import { type BreadcrumbItem } from '@/types';
import type { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard({ campus: page.props.currentCampus!.slug }).url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex w-full max-w-[1400px] flex-col gap-6 p-4 sm:p-6 lg:p-8"
        >
            <!-- Header (Full width of the container) -->
            <WelcomeHeader
                :student="props.student"
                :academic-context="props.academicContext"
                :enrollment="props.enrollment"
            />

            <!-- Main Bento Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                <!-- Stat Cards -->
                <StatCard
                    :value="props.stats.totalClasses"
                    label="Enrolled Classes"
                    accent-color="text-primary"
                    :icon="BookOpen"
                />
                <StatCard
                    :value="props.stats.totalUnits"
                    label="Total Units"
                    accent-color="text-blue-500"
                    :icon="Users"
                />
                <StatCard
                    :value="props.stats.pendingAssignments"
                    label="Pending Tasks"
                    accent-color="text-amber-500"
                    :icon="Clock"
                />
                <StatCard
                    :value="props.stats.unreadAnnouncements"
                    label="Announcements"
                    accent-color="text-emerald-500"
                    :icon="CheckCircle2"
                />

                <!-- Schedule & Assignments -->
                <div class="flex flex-col gap-6 md:col-span-2 xl:col-span-2">
                    <TodaySchedule :items="props.todaySchedule" />
                </div>
                <div class="flex flex-col gap-6 md:col-span-2 xl:col-span-2">
                    <UpcomingAssignments :items="props.upcomingAssignments" />
                </div>

                <!-- Grades & Feed -->
                <div class="flex flex-col gap-6 md:col-span-2 xl:col-span-2">
                    <GradeSummary :items="props.gradeSummary" />
                </div>
                <div class="flex flex-col gap-6 md:col-span-2 xl:col-span-2">
                    <RecentAnnouncements :items="props.recentAnnouncements" :campus="page.props.currentCampus!" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
