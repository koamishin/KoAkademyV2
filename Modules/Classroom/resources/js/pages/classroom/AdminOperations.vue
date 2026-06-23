<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { GraduationCap, UsersRound, AlertCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

type ClassRow = {
    id: number;
    name: string;
    code: string;
    section?: string | null;
    teacher?: string | null;
    status: string;
    students: number;
};

defineProps<{
    classes: { data: ClassRow[] };
}>();
</script>

<template>
    <Head title="Class Operations" />

    <AppLayout :breadcrumbs="[{ title: 'Class Operations' }]">
        <div class="mx-auto flex w-full max-w-[1200px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex items-center justify-between rounded-2xl border border-border bg-card p-4 sm:p-6">
                <div>
                    <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <GraduationCap class="size-4" />
                        Classroom
                    </p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Class operations</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Monitor classes, assigned faculty, and roster size at a glance.
                    </p>
                </div>
            </header>

            <section class="overflow-hidden rounded-2xl border border-border bg-card text-card-foreground shadow-sm">
                <article
                    v-for="item in classes.data"
                    :key="item.id"
                    class="grid gap-3 border-b border-border p-5 last:border-b-0 lg:grid-cols-[1fr_auto_auto]"
                >
                    <div class="flex items-start gap-3">
                        <div class="mt-1">
                            <GraduationCap class="size-5" :style="{ color: 'var(--color-chart-1)' }" />
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">{{ item.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ item.code }}<span v-if="item.section"> · {{ item.section }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <UsersRound class="size-4 text-muted-foreground" />
                            <span class="text-sm text-muted-foreground lg:text-right">
                                {{ item.teacher ?? 'No teacher assigned' }} · {{ item.students }} students
                            </span>
                        </div>
                        <span class="w-fit rounded-full px-3 py-1 text-xs font-medium capitalize" :style="{ background: item.status === 'active' || item.status === 'published' ? 'color-mix(in srgb, var(--color-chart-2) 10%, transparent)' : 'color-mix(in srgb, var(--color-muted) 10%, transparent)', color: item.status === 'active' || item.status === 'published' ? 'var(--color-chart-2)' : 'var(--color-muted-foreground)' }">
                            {{ item.status }}
                        </span>
                    </div>
                </article>
                <div
                    v-if="classes.data.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center"
                >
                    <AlertCircle class="mb-3 size-10 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">No class offerings are available yet.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
