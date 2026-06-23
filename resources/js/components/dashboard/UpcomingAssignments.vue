<script setup lang="ts">
import { Calendar, Award } from 'lucide-vue-next';
import type { AssignmentItem } from '@/types/dashboard';

defineProps<{
    items: AssignmentItem[];
}>();

function relativeDate(dateStr: string | null): string {
    if (!dateStr) {
        return 'No due date';
    }

    const due = new Date(dateStr);
    const now = new Date();
    const diffMs = due.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return `${Math.abs(diffDays)}d overdue`;
    }
    if (diffDays === 0) {
        return 'Today';
    }
    if (diffDays === 1) {
        return 'Tomorrow';
    }
    return `In ${diffDays}d`;
}

function dueDateColor(dateStr: string | null): string {
    if (!dateStr) {
        return 'text-muted-foreground';
    }

    const due = new Date(dateStr);
    const now = new Date();
    const diffMs = due.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return 'text-red-500';
    }
    if (diffDays <= 1) {
        return 'text-amber-500';
    }
    return 'text-muted-foreground';
}

function statusLabel(status: string | null): string {
    if (!status) {
        return 'Pending';
    }
    return status;
}

function statusDotColor(status: string | null): string {
    switch (status) {
        case 'submitted':
            return 'bg-blue-500';
        case 'graded':
        case 'returned':
            return 'bg-emerald-500';
        default:
            return 'bg-amber-500';
    }
}
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-border bg-card shadow-sm transition-all duration-500 hover:shadow-xl"
    >
        <div
            class="flex items-center justify-between border-b border-border/50 px-8 py-6"
        >
            <h2
                class="text-sm font-semibold tracking-[0.2em] text-foreground uppercase"
            >
                Upcoming Tasks
            </h2>
            <span
                v-if="items.length > 0"
                class="text-xs font-bold tracking-[0.2em] text-muted-foreground uppercase"
            >
                {{ items.length }} Pending
            </span>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-12 text-center"
        >
            <p class="text-2xl font-semibold tracking-tight text-foreground">
                All Clear
            </p>
            <p class="mt-2 text-sm font-medium text-muted-foreground">
                No pending assignments right now.
            </p>
        </div>

        <div v-else class="flex-1 p-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="group rounded-2xl p-5 transition-all duration-300 hover:bg-accent/50"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <span
                                v-if="item.subjectCode"
                                class="shrink-0 text-[10px] font-bold tracking-[0.2em] text-muted-foreground uppercase"
                            >
                                {{ item.subjectCode }}
                            </span>
                            <span
                                v-if="item.subjectCode"
                                class="h-1 w-1 rounded-full bg-border"
                            ></span>
                            <p
                                class="truncate text-base font-semibold tracking-tight text-foreground"
                            >
                                {{ item.title }}
                            </p>
                        </div>
                        <div
                            class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium"
                        >
                            <span class="flex items-center gap-2" :class="dueDateColor(item.dueAt)">
                                <Calendar class="h-3.5 w-3.5" />
                                {{ relativeDate(item.dueAt) }}
                            </span>
                            <span
                                v-if="item.points"
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Award class="h-3.5 w-3.5" />
                                {{ item.points }} pts
                            </span>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :class="statusDotColor(item.submissionStatus)"
                        ></span>
                        <span
                            class="text-[10px] font-bold tracking-[0.2em] text-muted-foreground uppercase"
                        >
                            {{ statusLabel(item.submissionStatus) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
