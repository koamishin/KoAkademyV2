<script setup lang="ts">
import type { AnnouncementItem } from '@/types/dashboard';
import { Link } from '@inertiajs/vue3';
import { Bell, Megaphone } from 'lucide-vue-next';
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
    <div
        class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] backdrop-blur-xl transition-colors hover:bg-white/[0.03]"
    >
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-6 py-5"
        >
            <h2 class="text-base font-semibold text-zinc-100">
                Recent Announcements
            </h2>
            <div
                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/[0.04] ring-1 ring-white/[0.08]"
            >
                <Bell class="h-4 w-4 text-zinc-400" />
            </div>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-8 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-[1.125rem] bg-white/[0.02] ring-1 ring-white/[0.08]"
            >
                <Megaphone class="h-6 w-6 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-300">All quiet</p>
            <p class="mt-1 text-xs text-zinc-500">
                No recent announcements from your classes.
            </p>
        </div>

        <div v-else class="flex-1 p-3">
            <Link
                v-for="item in items"
                :key="item.id"
                :href="showClass({ classOffering: item.classOfferingId })"
                class="group block rounded-xl p-3 transition-colors hover:bg-white/[0.04]"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-[0.875rem] bg-white/[0.04] text-zinc-400 ring-1 ring-white/[0.08] transition-colors group-hover:bg-violet-500/10 group-hover:text-violet-400 group-hover:ring-violet-500/20"
                    >
                        <Megaphone class="h-4.5 w-4.5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p
                                class="truncate text-sm font-medium text-zinc-100 transition-colors group-hover:text-violet-300"
                            >
                                {{ item.title || 'Announcement' }}
                            </p>
                            <span
                                class="shrink-0 text-[11px] font-medium text-zinc-500"
                            >
                                {{ relativeTime(item.publishedAt) }}
                            </span>
                        </div>
                        <p
                            class="mt-1 line-clamp-2 text-xs leading-relaxed text-zinc-400"
                        >
                            {{ item.body }}
                        </p>
                        <div class="mt-2.5">
                            <span
                                class="inline-flex rounded bg-white/[0.06] px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-zinc-300 uppercase"
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
