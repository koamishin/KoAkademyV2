<script setup lang="ts">
import type { GradeItem } from '@/types/dashboard';
import { Award, Target } from 'lucide-vue-next';
import { computed } from 'vue';

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

// Simple circular progress calculation
const radius = 16;
const circumference = 2 * Math.PI * radius;
function dashoffset(percentage: number): number {
    return circumference - (percentage / 100) * circumference;
}
</script>

<template>
    <div class="rounded-xl border border-white/[0.06] bg-zinc-900/60">
        <div
            class="flex items-center justify-between border-b border-white/[0.04] px-5 py-3.5"
        >
            <h2 class="text-sm font-semibold text-zinc-200">Grade Summary</h2>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-col items-center justify-center px-5 py-10 text-center"
        >
            <div
                class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-800/80 ring-1 ring-white/[0.06]"
            >
                <Target class="h-5 w-5 text-zinc-500" />
            </div>
            <p class="text-sm font-medium text-zinc-400">No grades yet</p>
            <p class="mt-1 text-xs text-zinc-600">Keep up the good work!</p>
        </div>

        <div v-else class="divide-y divide-white/[0.04]">
            <div
                v-for="item in items"
                :key="item.classOfferingId"
                class="flex items-center justify-between gap-4 px-5 py-3.5 transition-colors hover:bg-white/[0.02]"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-zinc-200">
                        {{ item.className }}
                    </p>
                    <div
                        class="mt-1 flex items-center gap-2 text-xs text-zinc-500"
                    >
                        <span v-if="item.subjectCode" class="font-medium">
                            {{ item.subjectCode }}
                        </span>
                        <span v-if="item.subjectCode" class="text-zinc-700"
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
                    class="relative flex h-10 w-10 shrink-0 items-center justify-center"
                >
                    <!-- Background ring -->
                    <svg
                        class="absolute inset-0 h-full w-full -rotate-90 transform"
                        viewBox="0 0 36 36"
                    >
                        <circle
                            cx="18"
                            cy="18"
                            :r="radius"
                            fill="none"
                            class="text-zinc-800"
                            stroke="currentColor"
                            stroke-width="3"
                        />
                        <!-- Progress ring -->
                        <circle
                            cx="18"
                            cy="18"
                            :r="radius"
                            fill="none"
                            :class="ringColor(item.percentage)"
                            stroke="currentColor"
                            stroke-width="3"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashoffset(item.percentage)"
                            class="transition-all duration-1000 ease-out"
                        />
                    </svg>
                    <span
                        class="relative text-[10px] font-bold"
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
