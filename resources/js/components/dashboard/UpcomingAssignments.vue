<script setup lang="ts">
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
        return 'text-zinc-600';
    }

    const due = new Date(dateStr);
    const now = new Date();
    const diffMs = due.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return 'text-red-400';
    }
    if (diffDays <= 1) {
        return 'text-amber-400';
    }
    return 'text-zinc-400';
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
            return 'bg-blue-400 shadow-[0_0_8px_rgba(96,165,250,0.5)]';
        case 'graded':
        case 'returned':
            return 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]';
        default:
            return 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]';
    }
}
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] backdrop-blur-xl transition-colors hover:bg-white/[0.03]"
    >
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-8 py-6"
        >
            <h2
                class="text-sm font-semibold tracking-wide text-zinc-100 uppercase"
            >
                Upcoming Tasks
            </h2>
            <span
                v-if="items.length > 0"
                class="text-xs font-bold tracking-widest text-zinc-500 uppercase"
            >
                {{ items.length }} Pending
            </span>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-12 text-center"
        >
            <p class="text-2xl font-semibold tracking-tight text-zinc-300">
                All Clear
            </p>
            <p class="mt-2 text-sm font-medium tracking-wide text-zinc-500">
                No pending assignments right now.
            </p>
        </div>

        <div v-else class="flex-1 p-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="group rounded-2xl p-4 transition-colors hover:bg-white/[0.04]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <span
                                v-if="item.subjectCode"
                                class="shrink-0 text-xs font-bold tracking-widest text-zinc-500 uppercase"
                            >
                                {{ item.subjectCode }}
                            </span>
                            <span
                                v-if="item.subjectCode"
                                class="h-1 w-1 rounded-full bg-zinc-700"
                            ></span>
                            <p
                                class="truncate text-base font-medium tracking-tight text-zinc-100"
                            >
                                {{ item.title }}
                            </p>
                        </div>
                        <div
                            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs font-medium tracking-wide"
                        >
                            <span :class="dueDateColor(item.dueAt)">
                                {{ relativeDate(item.dueAt) }}
                            </span>
                            <span
                                v-if="item.points"
                                class="flex items-center gap-2 text-zinc-600"
                            >
                                <span
                                    class="h-1 w-1 rounded-full bg-zinc-700"
                                ></span>
                                {{ item.points }} pts
                            </span>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="statusDotColor(item.submissionStatus)"
                        ></span>
                        <span
                            class="text-[10px] font-bold tracking-widest text-zinc-400 uppercase"
                        >
                            {{ statusLabel(item.submissionStatus) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
