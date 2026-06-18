<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

type QueueItem = {
    id: number;
    studentName?: string | null;
    studentNumber?: string | null;
    period?: string | null;
    curriculum?: string | null;
    status: string;
    classification?: string | null;
};

defineProps<{
    enrollments: { data: QueueItem[] };
    summary: {
        draft: number;
        pending: number;
        waitlisted: number;
    };
}>();
</script>

<template>
    <Head title="Enrollment Queue" />

    <AppLayout :breadcrumbs="[{ title: 'Enrollment Queue' }]">
        <div class="mx-auto flex w-full max-w-[1200px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="rounded-lg border bg-card p-6 shadow-sm">
                <p class="text-sm font-medium text-primary">Enrollment</p>
                <h1 class="mt-1 text-2xl font-semibold">Enrollment queue</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Follow up students who need enrollment review or approval.
                </p>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-lg border bg-background p-5">
                    <p class="text-3xl font-semibold">{{ summary.draft }}</p>
                    <p class="text-sm text-muted-foreground">Draft</p>
                </article>
                <article class="rounded-lg border bg-background p-5">
                    <p class="text-3xl font-semibold">{{ summary.pending }}</p>
                    <p class="text-sm text-muted-foreground">Pending</p>
                </article>
                <article class="rounded-lg border bg-background p-5">
                    <p class="text-3xl font-semibold">{{ summary.waitlisted }}</p>
                    <p class="text-sm text-muted-foreground">Waitlisted</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-lg border bg-card shadow-sm">
                <article
                    v-for="item in enrollments.data"
                    :key="item.id"
                    class="grid gap-3 border-b p-5 last:border-b-0 md:grid-cols-[1fr_auto]"
                >
                    <div>
                        <p class="font-medium">{{ item.studentName ?? item.studentNumber ?? 'Student' }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ item.period ?? 'Enrollment period' }}
                            <span v-if="item.curriculum"> · {{ item.curriculum }}</span>
                        </p>
                    </div>
                    <span class="w-fit rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary capitalize">
                        {{ item.status.replace('_', ' ') }}
                    </span>
                </article>
                <p v-if="enrollments.data.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No enrollments are waiting in the queue.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
