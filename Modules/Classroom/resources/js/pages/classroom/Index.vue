<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { show } from '@/routes/classroom';
import type { AppPageProps } from '@/types';
type Classroom = {
    id: number;
    name: string;
    code: string;
    status: string;
    students?: number;
    teacher?: { first_name: string; last_name: string };
};

defineProps<{ classes: Classroom[]; portalRole?: string }>();
const page = usePage<AppPageProps>();

const statusColor = (status: string) => {
    switch (status) {
        case 'active':
        case 'published':
            return 'bg-emerald-500/10 text-emerald-500';
        case 'draft':
            return 'bg-amber-500/10 text-amber-500';
        default:
            return 'bg-muted text-muted-foreground';
    }
};
</script>

<template>
    <Head title="My Classes" />
    <AppLayout :breadcrumbs="[{ title: 'My Classes' }]">
        <div
            class="mx-auto flex w-full max-w-[1400px] flex-col gap-6 p-4 sm:p-6 lg:p-8"
        >
            <!-- Header -->
            <header class="py-8">
                <p class="text-sm font-medium text-primary uppercase tracking-wider">
                    Academic workspace
                </p>
                <h1 class="mt-2 text-2xl font-semibold text-foreground sm:text-3xl">
                    {{ portalRole === 'admin' ? 'Classes' : 'My Classes' }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Schedules, announcements, learning materials,
                    assignments, and submissions in one organized place.
                </p>
            </header>

            <!-- Class Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="item in classes"
                    :key="item.id"
                    :href="
                        show({
                            campus: page.props.currentCampus!.slug,
                            classOffering: item.id,
                        })
                    "
                    class="group rounded-lg border border-border p-4 transition-colors hover:bg-accent/50"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p
                                class="text-xs font-semibold text-muted-foreground uppercase tracking-wider"
                            >
                                {{ item.code }}
                            </p>
                            <h2
                                class="mt-1 truncate text-lg font-semibold text-foreground transition-colors group-hover:text-primary"
                            >
                                {{ item.name }}
                            </h2>
                        </div>
                        <span
                            class="shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary uppercase"
                        >
                            {{ item.status }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span
                            v-if="item.teacher"
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-border"></span>
                            {{ item.teacher.first_name }}
                            {{ item.teacher.last_name }}
                        </span>
                        <span
                            v-if="item.students !== undefined"
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-border"></span>
                            {{ item.students }} students
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div
                v-if="classes.length === 0"
                class="py-12 text-center text-sm text-muted-foreground"
            >
                <p class="font-semibold text-foreground">Workspace Empty</p>
                <p class="mt-2">
                    Your assigned classes will appear here once enrollment is
                    finalized.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
