<script setup lang="ts">
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
            class="flex items-center justify-between border-b border-white/[0.04] px-8 py-6"
        >
            <h2
                class="text-sm font-semibold tracking-wide text-zinc-100 uppercase"
            >
                Today's Schedule
            </h2>
            <span
                class="text-xs font-bold tracking-widest text-zinc-500 uppercase"
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
            <p class="text-2xl font-semibold tracking-tight text-zinc-300">
                Free Day
            </p>
            <p class="mt-2 text-sm font-medium tracking-wide text-zinc-500">
                No classes scheduled for today.
            </p>
        </div>

        <div v-else class="flex-1 p-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="group relative flex items-start gap-5 rounded-2xl p-4 transition-colors hover:bg-white/[0.04]"
            >
                <!-- Timeline indicator -->
                <div class="relative flex flex-col items-center pt-2">
                    <div
                        class="h-2 w-2 rounded-full"
                        :class="
                            statusStyles[getStatus(item.startsAt, item.endsAt)]
                        "
                    />
                    <div
                        class="absolute top-5 bottom-[-1.5rem] w-px bg-white/[0.06] group-last:hidden"
                    />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-4">
                        <p
                            class="truncate text-base font-medium tracking-tight text-zinc-100"
                        >
                            {{ item.subjectName }}
                        </p>
                        <span
                            class="shrink-0 text-[10px] font-bold tracking-widest uppercase"
                            :class="{
                                'text-zinc-600':
                                    getStatus(item.startsAt, item.endsAt) ===
                                    'done',
                                'text-emerald-400':
                                    getStatus(item.startsAt, item.endsAt) ===
                                    'ongoing',
                                'text-blue-400':
                                    getStatus(item.startsAt, item.endsAt) ===
                                    'upcoming',
                            }"
                        >
                            {{ getStatus(item.startsAt, item.endsAt) }}
                        </span>
                    </div>

                    <div
                        class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs font-medium tracking-wide text-zinc-500"
                    >
                        <span class="text-zinc-400">
                            {{ formatTime(item.startsAt) }} —
                            {{ formatTime(item.endsAt) }}
                        </span>
                        <span
                            v-if="item.roomName"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="h-1 w-1 rounded-full bg-zinc-700"
                            ></span>
                            {{ item.roomName }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
