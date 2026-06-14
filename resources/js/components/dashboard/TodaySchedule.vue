<script setup lang="ts">
import type { ScheduleItem } from '@/types/dashboard';
import { CalendarOff, Clock, MapPin } from 'lucide-vue-next';

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
    done: 'bg-zinc-700',
    ongoing: 'bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.5)]',
    upcoming: 'bg-blue-400',
};
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] backdrop-blur-xl transition-colors hover:bg-white/[0.03]"
    >
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-6 py-5"
        >
            <h2 class="text-base font-semibold text-zinc-100">
                Today's Schedule
            </h2>
            <span
                class="rounded-lg bg-white/[0.04] px-2.5 py-1 text-xs font-medium text-zinc-400 ring-1 ring-white/[0.08]"
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
            class="flex flex-1 flex-col items-center justify-center p-8 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-[1.125rem] bg-white/[0.02] ring-1 ring-white/[0.08]"
            >
                <CalendarOff class="h-6 w-6 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-300">No classes today</p>
            <p class="mt-1 text-xs text-zinc-500">Enjoy your free time!</p>
        </div>

        <div v-else class="flex-1 p-3">
            <div
                v-for="item in items"
                :key="item.id"
                class="group relative flex items-start gap-4 rounded-xl p-3 transition-colors hover:bg-white/[0.04]"
            >
                <!-- Timeline indicator -->
                <div class="relative flex flex-col items-center pt-1.5">
                    <div
                        class="h-2.5 w-2.5 rounded-full"
                        :class="
                            statusStyles[getStatus(item.startsAt, item.endsAt)]
                        "
                    />
                    <div
                        class="absolute top-4 bottom-[-1.5rem] w-px bg-white/[0.08] group-last:hidden"
                    />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-zinc-100">
                        {{ item.subjectName }}
                    </p>
                    <div
                        class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-zinc-400"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 rounded text-zinc-300"
                        >
                            <Clock class="h-3.5 w-3.5 text-zinc-500" />
                            {{ formatTime(item.startsAt) }} –
                            {{ formatTime(item.endsAt) }}
                        </span>
                        <span
                            v-if="item.roomName"
                            class="inline-flex items-center gap-1.5"
                        >
                            <MapPin class="h-3.5 w-3.5 text-zinc-500" />
                            {{ item.roomName }}
                        </span>
                    </div>
                </div>

                <span
                    class="shrink-0 rounded-lg px-2.5 py-1 text-[10px] font-bold tracking-wider uppercase ring-1 ring-inset"
                    :class="{
                        'bg-zinc-800/50 text-zinc-500 ring-zinc-700/50':
                            getStatus(item.startsAt, item.endsAt) === 'done',
                        'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20':
                            getStatus(item.startsAt, item.endsAt) === 'ongoing',
                        'bg-blue-500/10 text-blue-400 ring-blue-500/20':
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
