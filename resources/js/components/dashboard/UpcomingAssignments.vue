<script setup lang="ts">
import type { AssignmentItem } from '@/types/dashboard';
import {
    CheckCircle2,
    CircleDashed,
    ClipboardCheck,
    PartyPopper,
} from 'lucide-vue-next';

const props = defineProps<{
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
        return `${Math.abs(diffDays)} day${Math.abs(diffDays) === 1 ? '' : 's'} overdue`;
    }
    if (diffDays === 0) {
        return 'Due today';
    }
    if (diffDays === 1) {
        return 'Due tomorrow';
    }
    return `Due in ${diffDays} days`;
}

function dueDateColor(dateStr: string | null): string {
    if (!dateStr) {
        return 'text-zinc-500';
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
        return 'Not started';
    }
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function statusIcon(status: string | null) {
    switch (status) {
        case 'submitted':
            return ClipboardCheck;
        case 'graded':
        case 'returned':
            return CheckCircle2;
        default:
            return CircleDashed;
    }
}

function statusColor(status: string | null): string {
    switch (status) {
        case 'submitted':
            return 'text-blue-400';
        case 'graded':
        case 'returned':
            return 'text-emerald-400';
        default:
            return 'text-zinc-500';
    }
}
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] backdrop-blur-xl transition-colors hover:bg-white/[0.03]"
    >
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-6 py-5"
        >
            <h2 class="text-base font-semibold text-zinc-100">
                Upcoming Assignments
            </h2>
            <span
                v-if="items.length > 0"
                class="rounded-lg bg-white/[0.04] px-2.5 py-1 text-xs font-medium text-zinc-400 ring-1 ring-white/[0.08]"
            >
                {{ items.length }} pending
            </span>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-8 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-[1.125rem] bg-white/[0.02] ring-1 ring-white/[0.08]"
            >
                <PartyPopper class="h-6 w-6 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-300">All caught up!</p>
            <p class="mt-1 text-xs text-zinc-500">
                No pending assignments right now.
            </p>
        </div>

        <div v-else class="flex-1 p-3">
            <div
                v-for="item in items"
                :key="item.id"
                class="group rounded-xl p-3 transition-colors hover:bg-white/[0.04]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2.5">
                            <span
                                v-if="item.subjectCode"
                                class="shrink-0 rounded bg-white/[0.06] px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-zinc-300 uppercase"
                            >
                                {{ item.subjectCode }}
                            </span>
                            <p
                                class="truncate text-sm font-medium text-zinc-100"
                            >
                                {{ item.title }}
                            </p>
                        </div>
                        <div
                            class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium"
                        >
                            <span :class="dueDateColor(item.dueAt)">
                                {{ relativeDate(item.dueAt) }}
                            </span>
                            <span v-if="item.points" class="text-zinc-500">
                                {{ item.points }} pts
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 items-center gap-1.5 rounded-lg bg-white/[0.02] px-2.5 py-1.5 ring-1 ring-white/[0.04]"
                        :class="statusColor(item.submissionStatus)"
                    >
                        <component
                            :is="statusIcon(item.submissionStatus)"
                            class="h-3.5 w-3.5"
                        />
                        <span class="text-[11px] font-semibold tracking-wide">
                            {{ statusLabel(item.submissionStatus) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
