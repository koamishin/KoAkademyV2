<script setup lang="ts">
import type { GradeItem } from '@/types/dashboard';
import { Target } from 'lucide-vue-next';

const props = defineProps<{
    items: GradeItem[];
}>();

function gradeColor(percentage: number): string {
    if (percentage >= 90) return 'text-emerald-400';
    if (percentage >= 75) return 'text-blue-400';
    if (percentage >= 60) return 'text-amber-400';
    return 'text-red-400';
}

function ringColor(percentage: number): string {
    if (percentage >= 90) return 'text-emerald-500';
    if (percentage >= 75) return 'text-blue-500';
    if (percentage >= 60) return 'text-amber-500';
    return 'text-red-500';
}

// Circular progress calculation
const radius = 18; // slightly larger for bento style
const circumference = 2 * Math.PI * radius;
function dashoffset(percentage: number): number {
    return circumference - (percentage / 100) * circumference;
}
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] backdrop-blur-xl transition-colors hover:bg-white/[0.03]"
    >
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-6 py-5"
        >
            <h2 class="text-base font-semibold text-zinc-100">Grade Summary</h2>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-8 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-[1.125rem] bg-white/[0.02] ring-1 ring-white/[0.08]"
            >
                <Target class="h-6 w-6 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-300">No grades yet</p>
            <p class="mt-1 text-xs text-zinc-500">Keep up the good work!</p>
        </div>

        <div v-else class="flex-1 p-3">
            <div
                v-for="item in items"
                :key="item.classOfferingId"
                class="group flex items-center justify-between gap-4 rounded-xl p-3 transition-colors hover:bg-white/[0.04]"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-zinc-100">
                        {{ item.className }}
                    </p>
                    <div
                        class="mt-1.5 flex items-center gap-2 text-xs font-medium text-zinc-400"
                    >
                        <span
                            v-if="item.subjectCode"
                            class="rounded bg-white/[0.06] px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-zinc-300 uppercase"
                        >
                            {{ item.subjectCode }}
                        </span>
                        <span v-if="item.subjectCode" class="text-zinc-600"
                            >•</span
                        >
                        <span
                            >{{ item.gradedCount }}
                            {{
                                item.gradedCount === 1 ? 'item' : 'items'
                            }}
                            graded</span
                        >
                    </div>
                </div>

                <div
                    class="relative flex h-12 w-12 shrink-0 items-center justify-center"
                >
                    <!-- Background ring -->
                    <svg
                        class="absolute inset-0 h-full w-full -rotate-90 transform"
                        viewBox="0 0 44 44"
                    >
                        <circle
                            cx="22"
                            cy="22"
                            :r="radius"
                            fill="none"
                            class="text-white/[0.06]"
                            stroke="currentColor"
                            stroke-width="3"
                        />
                        <!-- Progress ring -->
                        <circle
                            cx="22"
                            cy="22"
                            :r="radius"
                            fill="none"
                            :class="ringColor(item.percentage)"
                            stroke="currentColor"
                            stroke-width="3"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashoffset(item.percentage)"
                            class="drop-shadow-md transition-all duration-1000 ease-out"
                        />
                    </svg>
                    <span
                        class="relative text-[11px] font-bold tracking-tight"
                        :class="gradeColor(item.percentage)"
                    >
                        {{ Math.round(item.percentage)
                        }}<span class="text-[8px] opacity-70">%</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
