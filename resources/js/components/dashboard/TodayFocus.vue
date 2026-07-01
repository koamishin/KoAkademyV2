<script setup lang="ts">
import { Clock, MapPin, Calendar, Award } from 'lucide-vue-next';

type ScheduleItem = {
    id: number;
    subjectName: string;
    subjectCode?: string;
    startsAt: string;
    endsAt: string;
    roomName?: string | null;
};

type AssignmentItem = {
    id: number;
    title: string;
    subjectName: string;
    subjectCode?: string;
    dueAt?: string | null;
    points?: string | null;
    submissionStatus?: string | null;
    submissionScore?: string | null;
};

defineProps<{
    schedule: ScheduleItem[];
    assignments: AssignmentItem[];
}>();

function formatTime(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const suffix = hours >= 12 ? 'PM' : 'AM';
    const h = hours % 12 || 12;
    return `${h}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

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
    <div class="py-8">
        <h2 class="text-lg font-semibold text-foreground">Today's Focus</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            {{ new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }) }}
        </p>

        <div v-if="schedule.length === 0 && assignments.length === 0" class="mt-6 py-12 text-center">
            <p class="text-lg font-medium text-foreground">Free day</p>
            <p class="mt-1 text-sm text-muted-foreground">No classes or tasks scheduled for today</p>
        </div>

        <div v-else class="mt-6 space-y-6">
            <!-- Schedule Section -->
            <div v-if="schedule.length > 0">
                <h3 class="mb-4 text-sm font-medium text-muted-foreground">Classes</h3>
                <div class="space-y-4">
                    <div
                        v-for="item in schedule"
                        :key="item.id"
                        class="flex items-start gap-4 rounded-lg p-4 transition-colors hover:bg-accent/30"
                    >
                        <div class="flex shrink-0 flex-col items-center">
                            <div class="h-2 w-2 rounded-full bg-primary" />
                            <div class="mt-2 h-8 w-px bg-border" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-foreground">{{ item.subjectName }}</p>
                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1.5">
                                    <Clock class="h-3.5 w-3.5" />
                                    {{ formatTime(item.startsAt) }} — {{ formatTime(item.endsAt) }}
                                </span>
                                <span v-if="item.roomName" class="flex items-center gap-1.5">
                                    <MapPin class="h-3.5 w-3.5" />
                                    {{ item.roomName }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignments Section -->
            <div v-if="assignments.length > 0">
                <h3 class="mb-4 text-sm font-medium text-muted-foreground">Tasks Due Soon</h3>
                <div class="space-y-3">
                    <div
                        v-for="item in assignments"
                        :key="item.id"
                        class="flex items-start gap-4 rounded-lg p-4 transition-colors hover:bg-accent/30"
                    >
                        <div class="flex shrink-0 flex-col items-center">
                            <div
                                class="h-2 w-2 rounded-full"
                                :class="statusDotColor(item.submissionStatus)"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="item.subjectCode"
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    {{ item.subjectCode }}
                                </span>
                                <p class="font-medium text-foreground">{{ item.title }}</p>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1.5" :class="dueDateColor(item.dueAt)">
                                    <Calendar class="h-3.5 w-3.5" />
                                    {{ relativeDate(item.dueAt) }}
                                </span>
                                <span v-if="item.points" class="flex items-center gap-1.5">
                                    <Award class="h-3.5 w-3.5" />
                                    {{ item.points }} pts
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
