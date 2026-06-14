<script setup lang="ts">
import type { AssignmentItem } from '@/types/dashboard';
import {
    CheckCircle2,
    CircleDashed,
    ClipboardCheck,
    PartyPopper,
} from 'lucide-vue-next';
import { computed } from 'vue';

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
    return 'text-zinc-500';
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
    <div class="rounded-xl border border-white/[0.06] bg-zinc-900/60">
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-5 py-3.5"
        >
            <h2 class="text-sm font-semibold text-zinc-200">
                Upcoming Assignments
            </h2>
            <span v-if="items.length > 0" class="text-xs text-zinc-500"
                >{{ items.length }} pending</span
            >
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-col items-center justify-center px-5 py-10 text-center"
        >
            <div
                class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-800/80 ring-1 ring-white/[0.06]"
            >
                <PartyPopper class="h-5 w-5 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-400">All caught up!</p>
            <p class="mt-1 text-xs text-zinc-600">
                No pending assignments right now.
            </p>
        </div>

        <div v-else class="divide-y divide-white/[0.04]">
            <div
                v-for="item in items"
                :key="item.id"
                class="px-5 py-3.5 transition-colors hover:bg-white/[0.02]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span
                                v-if="item.subjectCode"
                                class="shrink-0 rounded bg-zinc-800 px-1.5 py-0.5 text-[10px] font-semibold tracking-wider text-zinc-400 uppercase ring-1 ring-white/[0.04]"
                            >
                                {{ item.subjectCode }}
                            </span>
                            <p
                                class="truncate text-sm font-medium text-zinc-200"
                            >
                                {{ item.title }}
                            </p>
                        </div>
                        <div
                            class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs"
                        >
                            <span :class="dueDateColor(item.dueAt)">
                                {{ relativeDate(item.dueAt) }}
                            </span>
                            <span v-if="item.points" class="text-zinc-600">
                                {{ item.points }} pts
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 items-center gap-1.5"
                        :class="statusColor(item.submissionStatus)"
                    >
                        <component
                            :is="statusIcon(item.submissionStatus)"
                            class="h-3.5 w-3.5"
                        />
                        <span class="text-xs font-medium">
                            {{ statusLabel(item.submissionStatus) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
