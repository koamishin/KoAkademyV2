<script setup lang="ts">
import type { ScheduleItem } from '@/types/dashboard';
import { CalendarOff, Clock, MapPin } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
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
    done: 'bg-zinc-600',
    ongoing: 'bg-emerald-500 animate-pulse',
    upcoming: 'bg-blue-500',
};
</script>

<template>
    <div class="rounded-xl border border-white/[0.06] bg-zinc-900/60">
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-5 py-3.5"
        >
            <h2 class="text-sm font-semibold text-zinc-200">
                Today's Schedule
            </h2>
            <span class="text-xs text-zinc-500">
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
            class="flex flex-col items-center justify-center px-5 py-10 text-center"
        >
            <div
                class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-800/80 ring-1 ring-white/[0.06]"
            >
                <CalendarOff class="h-5 w-5 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-400">
                No classes scheduled for today
            </p>
            <p class="mt-1 text-xs text-zinc-600">Enjoy your free time!</p>
        </div>

        <div v-else class="divide-y divide-white/[0.04]">
            <div
                v-for="item in items"
                :key="item.id"
                class="flex items-start gap-3.5 px-5 py-3.5 transition-colors hover:bg-white/[0.02]"
            >
                <!-- Status indicator -->
                <div class="flex flex-col items-center pt-0.5">
                    <div
                        class="h-2.5 w-2.5 rounded-full"
                        :class="
                            statusStyles[getStatus(item.startsAt, item.endsAt)]
                        "
                    />
                    <div class="mt-1 h-full w-px bg-zinc-800" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-zinc-200">
                        {{ item.subjectName }}
                    </p>
                    <div
                        class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500"
                    >
                        <span class="inline-flex items-center gap-1">
                            <Clock class="h-3 w-3" />
                            {{ formatTime(item.startsAt) }} –
                            {{ formatTime(item.endsAt) }}
                        </span>
                        <span
                            v-if="item.roomName"
                            class="inline-flex items-center gap-1"
                        >
                            <MapPin class="h-3 w-3" />
                            {{ item.roomName }}
                        </span>
                    </div>
                </div>

                <span
                    class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase"
                    :class="{
                        'bg-zinc-800 text-zinc-500':
                            getStatus(item.startsAt, item.endsAt) === 'done',
                        'bg-emerald-500/15 text-emerald-400':
                            getStatus(item.startsAt, item.endsAt) === 'ongoing',
                        'bg-blue-500/15 text-blue-400':
                            getStatus(item.startsAt, item.endsAt) ===
                            'upcoming',
                    }"
                >
                    {{ getStatus(item.startsAt, item.endsAt) }}
                </span>
            </div>
        </div>
    </div>
</template>
