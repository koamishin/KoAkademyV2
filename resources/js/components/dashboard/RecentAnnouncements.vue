<script setup lang="ts">
import type { AnnouncementItem } from '@/types/dashboard';
import { Link } from '@inertiajs/vue3';
import { Bell, Megaphone } from 'lucide-vue-next';
import { computed } from 'vue';
import { show as showClass } from '@/routes/classroom';

const props = defineProps<{
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
        return diffMins <= 1 ? 'Just now' : `${diffMins} mins ago`;
    }
    if (diffHours < 24) {
        return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    }
    if (diffDays === 1) {
        return 'Yesterday';
    }
    if (diffDays < 7) {
        return `${diffDays} days ago`;
    }
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <div class="rounded-xl border border-white/[0.06] bg-zinc-900/60">
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-5 py-3.5"
        >
            <h2 class="text-sm font-semibold text-zinc-200">
                Recent Announcements
            </h2>
            <Bell class="h-4 w-4 text-zinc-500" />
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-col items-center justify-center px-5 py-10 text-center"
        >
            <div
                class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-800/80 ring-1 ring-white/[0.06]"
            >
                <Megaphone class="h-5 w-5 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-400">All quiet</p>
            <p class="mt-1 text-xs text-zinc-600">
                No recent announcements from your classes.
            </p>
        </div>

        <div v-else class="divide-y divide-white/[0.04]">
            <Link
                v-for="item in items"
                :key="item.id"
                :href="showClass({ classOffering: item.classOfferingId })"
                class="block px-5 py-4 transition-colors hover:bg-white/[0.02]"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-800 text-zinc-400"
                    >
                        <Megaphone class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p
                                class="truncate text-sm font-medium text-zinc-200"
                            >
                                {{ item.title || 'Announcement' }}
                            </p>
                            <span class="shrink-0 text-xs text-zinc-500">
                                {{ relativeTime(item.publishedAt) }}
                            </span>
                        </div>
                        <p class="mt-1 line-clamp-2 text-xs text-zinc-400">
                            {{ item.body }}
                        </p>
                        <div class="mt-2 flex items-center gap-1.5">
                            <span
                                class="inline-flex rounded-md bg-zinc-800 px-1.5 py-0.5 text-[10px] font-medium text-zinc-300 ring-1 ring-white/[0.04]"
                            >
                                {{ item.subjectCode || item.subjectName }}
                            </span>
                        </div>
                    </div>
                </div>
            </Link>
        </div>
    </div>
</template>
