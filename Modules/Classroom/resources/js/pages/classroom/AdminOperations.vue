<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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

const statusColor = (status: string) => {
    switch (status) {
        case 'active':
        case 'published':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
        case 'draft':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};
</script>

<template>
    <Head title="Class Operations" />

    <AppLayout :breadcrumbs="[{ title: 'Class Operations' }]">
        <div class="mx-auto flex w-full max-w-[1200px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="rounded-lg border bg-card p-6 shadow-sm">
                <p class="text-sm font-medium text-primary">Classroom</p>
                <h1 class="mt-1 text-2xl font-semibold">Class operations</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Monitor classes, assigned faculty, and roster size at a glance.
                </p>
            </header>

            <section class="overflow-hidden rounded-lg border bg-card shadow-sm">
                <article
                    v-for="item in classes.data"
                    :key="item.id"
                    class="grid gap-3 border-b p-5 last:border-b-0 lg:grid-cols-[1fr_auto_auto]"
                >
                    <div>
                        <p class="font-medium">{{ item.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ item.code }}<span v-if="item.section"> · {{ item.section }}</span>
                        </p>
                    </div>
                    <p class="text-sm text-muted-foreground lg:text-right">
                        {{ item.teacher ?? 'No teacher assigned' }} · {{ item.students }} students
                    </p>
                    <span class="w-fit rounded-full px-3 py-1 text-xs font-medium capitalize" :class="statusColor(item.status)">
                        {{ item.status }}
                    </span>
                </article>
                <p v-if="classes.data.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No class offerings are available yet.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
