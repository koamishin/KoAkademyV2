<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, ClipboardCheck, GraduationCap, UsersRound } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminApplicationsIndex } from '@/routes/admin/applications';
import { index as adminClassesIndex } from '@/routes/admin/classes';
import { index as adminEnrollmentsIndex } from '@/routes/admin/enrollments';
import type { AppPageProps, BreadcrumbItem } from '@/types';

defineProps<{
    admin: { name: string; campusName: string };
    stats: {
        applicationsInReview: number;
        pendingEnrollments: number;
        activeClasses: number;
        meetingsToday: number;
    };
    applicationQueue: {
        id: number;
        number: string;
        studentName?: string | null;
        period?: string | null;
        program?: string | null;
        status: string;
        submittedAt?: string | null;
    }[];
    enrollmentQueue: {
        id: number;
        studentName?: string | null;
        studentNumber?: string | null;
        period?: string | null;
        curriculum?: string | null;
        status: string;
    }[];
    classes: {
        id: number;
        name: string;
        code: string;
        teacher?: string | null;
        status: string;
        students: number;
    }[];
}>();

const page = usePage<AppPageProps>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard' }];

const statCards = [
    { label: 'Applications', value: 'applicationsInReview', icon: ClipboardCheck, href: adminApplicationsIndex },
    { label: 'Enrollments', value: 'pendingEnrollments', icon: UsersRound, href: adminEnrollmentsIndex },
    { label: 'Active Classes', value: 'activeClasses', icon: GraduationCap, href: adminClassesIndex },
    { label: 'Meetings Today', value: 'meetingsToday', icon: CalendarDays, href: null },
] as const;
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1400px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="rounded-lg border bg-card p-6 text-card-foreground shadow-sm">
                <p class="text-sm font-medium text-primary">Daily operations</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ admin.campusName }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    A focused admin view for queues, classes, and today’s campus movement.
                </p>
            </header>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <component
                    :is="card.href ? Link : 'article'"
                    v-for="card in statCards"
                    :key="card.label"
                    :href="card.href ? card.href({ campus: page.props.currentCampus!.slug }) : undefined"
                    class="rounded-lg border bg-background p-5 shadow-sm transition hover:border-primary/50"
                >
                    <component :is="card.icon" class="size-5 text-primary" />
                    <p class="mt-4 text-3xl font-semibold">{{ stats[card.value] }}</p>
                    <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                </component>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <div class="rounded-lg border bg-card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-semibold">Applications queue</h2>
                        <Link :href="adminApplicationsIndex({ campus: page.props.currentCampus!.slug })" class="text-sm text-primary">
                            Open
                        </Link>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <article v-for="item in applicationQueue" :key="item.id" class="rounded-md bg-muted/50 p-3">
                            <p class="font-medium">{{ item.studentName ?? item.number }}</p>
                            <p class="text-xs text-muted-foreground capitalize">{{ item.status.replace('_', ' ') }}</p>
                        </article>
                        <p v-if="applicationQueue.length === 0" class="text-sm text-muted-foreground">No applications need attention.</p>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-semibold">Enrollment queue</h2>
                        <Link :href="adminEnrollmentsIndex({ campus: page.props.currentCampus!.slug })" class="text-sm text-primary">
                            Open
                        </Link>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <article v-for="item in enrollmentQueue" :key="item.id" class="rounded-md bg-muted/50 p-3">
                            <p class="font-medium">{{ item.studentName ?? item.studentNumber ?? 'Student' }}</p>
                            <p class="text-xs text-muted-foreground capitalize">{{ item.status.replace('_', ' ') }}</p>
                        </article>
                        <p v-if="enrollmentQueue.length === 0" class="text-sm text-muted-foreground">No pending enrollments.</p>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-semibold">Class operations</h2>
                        <Link :href="adminClassesIndex({ campus: page.props.currentCampus!.slug })" class="text-sm text-primary">
                            Open
                        </Link>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <article v-for="item in classes" :key="item.id" class="rounded-md bg-muted/50 p-3">
                            <p class="font-medium">{{ item.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.students }} students<span v-if="item.teacher"> · {{ item.teacher }}</span>
                            </p>
                        </article>
                        <p v-if="classes.length === 0" class="text-sm text-muted-foreground">No classes are active yet.</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
