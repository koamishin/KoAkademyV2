<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

type QueueItem = {
    id: number;
    number: string;
    studentName?: string | null;
    period?: string | null;
    program?: string | null;
    status: string;
    submittedAt?: string | null;
};

defineProps<{
    applications: { data: QueueItem[] };
    summary: {
        submitted: number;
        underReview: number;
        needsInformation: number;
    };
}>();
</script>

<template>
    <Head title="Applications Queue" />

    <AppLayout :breadcrumbs="[{ title: 'Applications Queue' }]">
        <div class="mx-auto flex w-full max-w-[1200px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="rounded-lg border bg-card p-6 shadow-sm">
                <p class="text-sm font-medium text-primary">Admissions</p>
                <h1 class="mt-1 text-2xl font-semibold">Applications queue</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Review applications that need admissions attention.
                </p>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-lg border bg-background p-5">
                    <p class="text-3xl font-semibold">{{ summary.submitted }}</p>
                    <p class="text-sm text-muted-foreground">Submitted</p>
                </article>
                <article class="rounded-lg border bg-background p-5">
                    <p class="text-3xl font-semibold">{{ summary.underReview }}</p>
                    <p class="text-sm text-muted-foreground">Under review</p>
                </article>
                <article class="rounded-lg border bg-background p-5">
                    <p class="text-3xl font-semibold">{{ summary.needsInformation }}</p>
                    <p class="text-sm text-muted-foreground">Needs information</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-lg border bg-card shadow-sm">
                <article
                    v-for="item in applications.data"
                    :key="item.id"
                    class="grid gap-3 border-b p-5 last:border-b-0 md:grid-cols-[1fr_auto]"
                >
                    <div>
                        <p class="font-medium">{{ item.studentName ?? item.number }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ item.period ?? 'Admission period' }}
                            <span v-if="item.program"> · {{ item.program }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 md:justify-end">
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary capitalize">
                            {{ item.status.replace('_', ' ') }}
                        </span>
                        <span class="text-xs text-muted-foreground">{{ item.submittedAt }}</span>
                    </div>
                </article>
                <p v-if="applications.data.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No applications are waiting in the queue.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
