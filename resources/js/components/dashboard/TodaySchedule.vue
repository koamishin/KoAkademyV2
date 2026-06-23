<script setup lang="ts">
import { Clock, MapPin } from 'lucide-vue-next';
import type { ScheduleItem } from '@/types/dashboard';

defineProps<{
    items: ScheduleItem[];
}>();

function formatTime(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const suffix = hours >= 12 ? 'PM' : 'AM';
    const h = hours % 12 || 12;
    return `${h}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

function getStatus(
    startsAt: string,
    endsAt: string,
): 'done' | 'ongoing' | 'upcoming' {
    const now = new Date();
    const [startH, startM] = startsAt.split(':').map(Number);
    const [endH, endM] = endsAt.split(':').map(Number);

    const startMinutes = startH * 60 + startM;
    const endMinutes = endH * 60 + endM;
    const nowMinutes = now.getHours() * 60 + now.getMinutes();

    if (nowMinutes >= endMinutes) {
        return 'done';
    }
    if (nowMinutes >= startMinutes) {
        return 'ongoing';
    }
    return 'upcoming';
}

const statusStyles: Record<string, string> = {
    done: 'bg-muted-foreground/30',
    ongoing: 'bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.3)]',
    upcoming: 'bg-primary',
};

const statusTextStyles: Record<string, string> = {
    done: 'text-muted-foreground',
    ongoing: 'text-emerald-500',
    upcoming: 'text-primary',
};
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
                Today's Schedule
            </h2>
            <span
                class="text-xs font-bold tracking-[0.2em] text-muted-foreground uppercase"
            >
                {{
                    new Date().toLocaleDateString('en-US', {
                        weekday: 'long',
                        month: 'short',
                        day: 'numeric',
                    })
                }}
            </span>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-12 text-center"
        >
            <p class="text-2xl font-semibold tracking-tight text-foreground">
                Free Day
            </p>
            <p class="mt-2 text-sm font-medium text-muted-foreground">
                No classes scheduled for today.
            </p>
        </div>

        <div v-else class="flex-1 p-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="group relative flex items-start gap-5 rounded-2xl p-5 transition-all duration-300 hover:bg-accent/50"
            >
                <!-- Timeline indicator -->
                <div class="relative flex flex-col items-center pt-3">
                    <div
                        class="h-3 w-3 rounded-full transition-all duration-300"
                        :class="
                            statusStyles[getStatus(item.startsAt, item.endsAt)]
                        "
                    />
                    <div
                        class="absolute top-7 bottom-[-1.5rem] w-px bg-border group-last:hidden"
                    />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-4">
                        <p
                            class="truncate text-base font-semibold tracking-tight text-foreground"
                        >
                            {{ item.subjectName }}
                        </p>
                        <span
                            class="shrink-0 text-[10px] font-bold tracking-[0.2em] uppercase"
                            :class="statusTextStyles[getStatus(item.startsAt, item.endsAt)]"
                        >
                            {{ getStatus(item.startsAt, item.endsAt) }}
                        </span>
                    </div>

                    <div
                        class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-muted-foreground"
                    >
                        <span class="flex items-center gap-2">
                            <Clock class="h-3.5 w-3.5" />
                            {{ formatTime(item.startsAt) }} —
                            {{ formatTime(item.endsAt) }}
                        </span>
                        <span
                            v-if="item.roomName"
                            class="flex items-center gap-2"
                        >
                            <MapPin class="h-3.5 w-3.5" />
                            {{ item.roomName }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
