<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as showClass } from '@/routes/classroom';
import type { AnnouncementItem } from '@/types/dashboard';

defineProps<{
    items: AnnouncementItem[];
}>();

function relativeTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMins < 60) {
        return diffMins <= 1 ? 'Just now' : `${diffMins}m ago`;
    }
    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }
    if (diffDays === 1) {
        return 'Yesterday';
    }
    if (diffDays < 7) {
        return `${diffDays}d ago`;
    }
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
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
                Recent Announcements
            </h2>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-12 text-center"
        >
            <p class="text-2xl font-semibold tracking-tight text-zinc-300">
                All Quiet
            </p>
            <p class="mt-2 text-sm font-medium tracking-wide text-zinc-500">
                No recent announcements from your classes.
            </p>
        </div>

        <div v-else class="flex-1 p-4">
            <Link
                v-for="item in items"
                :key="item.id"
                :href="showClass({ classOffering: item.classOfferingId })"
                class="group block rounded-2xl p-4 transition-colors hover:bg-white/[0.04]"
            >
                <div class="flex flex-col gap-2">
                    <div class="flex items-start justify-between gap-4">
                        <p
                            class="truncate text-base font-medium tracking-tight text-zinc-100 transition-colors group-hover:text-violet-300"
                        >
                            {{ item.title || 'Announcement' }}
                        </p>
                        <span
                            class="shrink-0 text-[10px] font-bold tracking-widest text-zinc-500 uppercase"
                        >
                            {{ relativeTime(item.publishedAt) }}
                        </span>
                    </div>
                    <p
                        class="line-clamp-2 text-sm leading-relaxed font-medium tracking-wide text-zinc-400"
                    >
                        {{ item.body }}
                    </p>
                    <div class="mt-1">
                        <span
                            class="text-xs font-bold tracking-widest text-zinc-600 uppercase transition-colors group-hover:text-violet-400/70"
                        >
                            {{ item.subjectCode || item.subjectName }}
                        </span>
                    </div>
                </div>
            </Link>
        </div>
    </div>
</template>
