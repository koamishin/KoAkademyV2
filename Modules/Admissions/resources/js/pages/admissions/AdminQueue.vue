<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ClipboardCheck, Clock, CheckCircle2, AlertCircle } from 'lucide-vue-next';
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
            <header class="flex items-center justify-between rounded-2xl border border-border bg-card p-4 sm:p-6">
                <div>
                    <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <ClipboardCheck class="size-4" />
                        Admissions
                    </p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Applications queue</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Review applications that need admissions attention.
                    </p>
                </div>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article class="group relative overflow-hidden rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="absolute left-0 top-0 h-1 w-full bg-gradient-to-r" :style="{ background: 'linear-gradient(90deg, var(--color-chart-1), var(--color-accent))' }"></div>
                    <div class="flex items-start justify-between">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl" :style="{ background: 'linear-gradient(135deg, var(--color-chart-1), var(--color-accent))' }">
                            <CheckCircle2 class="size-6 text-white" />
                        </div>
                    </div>
                    <p class="mt-5 text-3xl font-bold tracking-tight">{{ summary.submitted }}</p>
                    <p class="mt-1 text-sm font-medium text-muted-foreground">Submitted</p>
                </article>
                
                <article class="group relative overflow-hidden rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="absolute left-0 top-0 h-1 w-full bg-gradient-to-r" :style="{ background: 'linear-gradient(90deg, var(--color-chart-2), var(--color-accent))' }"></div>
                    <div class="flex items-start justify-between">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl" :style="{ background: 'linear-gradient(135deg, var(--color-chart-2), var(--color-accent))' }">
                            <Clock class="size-6 text-white" />
                        </div>
                    </div>
                    <p class="mt-5 text-3xl font-bold tracking-tight">{{ summary.underReview }}</p>
                    <p class="mt-1 text-sm font-medium text-muted-foreground">Under review</p>
                </article>
                
                <article class="group relative overflow-hidden rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="absolute left-0 top-0 h-1 w-full bg-gradient-to-r" :style="{ background: 'linear-gradient(90deg, var(--color-chart-3), var(--color-accent))' }"></div>
                    <div class="flex items-start justify-between">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl" :style="{ background: 'linear-gradient(135deg, var(--color-chart-3), var(--color-accent))' }">
                            <AlertCircle class="size-6 text-white" />
                        </div>
                    </div>
                    <p class="mt-5 text-3xl font-bold tracking-tight">{{ summary.needsInformation }}</p>
                    <p class="mt-1 text-sm font-medium text-muted-foreground">Needs information</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-border bg-card text-card-foreground shadow-sm">
                <article
                    v-for="item in applications.data"
                    :key="item.id"
                    class="grid gap-3 border-b border-border p-5 last:border-b-0"
                >
                    <div class="flex items-start gap-3">
                        <div class="mt-1">
                            <CheckCircle2 class="size-5" :style="{ color: 'var(--color-chart-2)' }" />
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">{{ item.studentName ?? item.number }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ item.period ?? 'Admission period' }}
                                <span v-if="item.program"> · {{ item.program }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium capitalize" :style="{ background: 'color-mix(in srgb, var(--color-primary) 10%, transparent)', color: 'var(--color-primary)' }">
                            {{ item.status.replace('_', ' ') }}
                        </span>
                        <span class="text-xs text-muted-foreground">{{ item.submittedAt }}</span>
                    </div>
                </article>
                <div
                    v-if="applications.data.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center"
                >
                    <AlertCircle class="mb-3 size-10 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">No applications are waiting in the queue.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
