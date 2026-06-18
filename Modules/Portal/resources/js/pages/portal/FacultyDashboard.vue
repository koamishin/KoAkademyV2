<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpenCheck, Clock3, FileCheck2, UsersRound } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showClassroom } from '@/routes/classroom';
import type { AppPageProps, BreadcrumbItem } from '@/types';

type FacultyClass = {
    id: number;
    name: string;
    code: string;
    section?: string | null;
    status: string;
    students: number;
};

defineProps<{
    faculty: { fullName: string; firstName: string } | null;
    stats: {
        classes: number;
        students: number;
        pendingGrading: number;
        publishedAssignments: number;
    };
    todaySchedule: {
        id: number;
        className: string;
        classCode?: string;
        startsAt: string;
        endsAt: string;
        roomName?: string | null;
    }[];
    classes: FacultyClass[];
    recentActivity: {
        id: number;
        title: string;
        type: string;
        className?: string | null;
        createdAt?: string | null;
    }[];
}>();

const page = usePage<AppPageProps>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard' }];

const statCards = [
    { key: 'classes', label: 'Teaching Load', icon: BookOpenCheck },
    { key: 'students', label: 'Active Students', icon: UsersRound },
    { key: 'pendingGrading', label: 'To Grade', icon: FileCheck2 },
    { key: 'publishedAssignments', label: 'Assignments', icon: Clock3 },
] as const;
</script>

<template>
    <Head title="Faculty Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1400px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="rounded-lg border bg-card p-6 text-card-foreground shadow-sm">
                <p class="text-sm font-medium text-primary">Faculty workspace</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                    Good day, {{ faculty?.firstName ?? 'teacher' }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    Review today’s schedule, class activity, and work that needs feedback.
                </p>
            </header>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in statCards"
                    :key="card.key"
                    class="rounded-lg border bg-background p-5 shadow-sm"
                >
                    <component :is="card.icon" class="size-5 text-primary" />
                    <p class="mt-4 text-3xl font-semibold">{{ stats[card.key] }}</p>
                    <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-lg border bg-card p-5">
                    <h2 class="text-lg font-semibold">Assigned classes</h2>
                    <div class="mt-4 grid gap-3">
                        <Link
                            v-for="item in classes"
                            :key="item.id"
                            :href="showClassroom({ campus: page.props.currentCampus!.slug, classOffering: item.id })"
                            class="rounded-md border p-4 transition hover:border-primary/50 hover:bg-muted/50"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium">{{ item.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ item.code }}<span v-if="item.section"> · {{ item.section }}</span>
                                    </p>
                                </div>
                                <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                    {{ item.students }} students
                                </span>
                            </div>
                        </Link>
                        <p v-if="classes.length === 0" class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
                            No classes are assigned yet.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5">
                    <section class="rounded-lg border bg-card p-5">
                        <h2 class="text-lg font-semibold">Today</h2>
                        <div class="mt-4 grid gap-3">
                            <article v-for="item in todaySchedule" :key="item.id" class="rounded-md bg-muted/50 p-3">
                                <p class="font-medium">{{ item.className }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.startsAt }} - {{ item.endsAt }}
                                    <span v-if="item.roomName"> · {{ item.roomName }}</span>
                                </p>
                            </article>
                            <p v-if="todaySchedule.length === 0" class="text-sm text-muted-foreground">
                                No meetings scheduled today.
                            </p>
                        </div>
                    </section>

                    <section class="rounded-lg border bg-card p-5">
                        <h2 class="text-lg font-semibold">Recent class activity</h2>
                        <div class="mt-4 grid gap-3">
                            <article v-for="item in recentActivity" :key="item.id" class="rounded-md bg-muted/50 p-3">
                                <p class="font-medium">{{ item.title }}</p>
                                <p class="text-xs text-muted-foreground capitalize">
                                    {{ item.type }}<span v-if="item.className"> · {{ item.className }}</span>
                                </p>
                            </article>
                            <p v-if="recentActivity.length === 0" class="text-sm text-muted-foreground">
                                Class activity will appear here.
                            </p>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
