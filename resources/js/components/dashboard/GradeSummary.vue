<script setup lang="ts">
import type { GradeItem } from '@/types/dashboard';

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
const radius = 20;
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
            class="flex items-center justify-between border-b border-white/[0.04] px-8 py-6"
        >
            <h2
                class="text-sm font-semibold tracking-wide text-zinc-100 uppercase"
            >
                Grade Summary
            </h2>
        </div>

        <div
            v-if="items.length === 0"
            class="flex flex-1 flex-col items-center justify-center p-12 text-center"
        >
            <p class="text-2xl font-semibold tracking-tight text-zinc-300">
                No Grades Yet
            </p>
            <p class="mt-2 text-sm font-medium tracking-wide text-zinc-500">
                Keep up the good work!
            </p>
        </div>

        <div v-else class="flex-1 p-4">
            <div
                v-for="item in items"
                :key="item.classOfferingId"
                class="group flex items-center justify-between gap-5 rounded-2xl p-4 transition-colors hover:bg-white/[0.04]"
            >
                <div class="min-w-0 flex-1">
                    <p
                        class="truncate text-base font-medium tracking-tight text-zinc-100"
                    >
                        {{ item.className }}
                    </p>
                    <div
                        class="mt-2 flex items-center gap-3 text-xs font-medium tracking-wide text-zinc-500"
                    >
                        <span
                            v-if="item.subjectCode"
                            class="font-bold tracking-widest text-zinc-400 uppercase"
                        >
                            {{ item.subjectCode }}
                        </span>
                        <span
                            v-if="item.subjectCode"
                            class="h-1 w-1 rounded-full bg-zinc-700"
                        ></span>
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
                    class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                >
                    <!-- Background ring -->
                    <svg
                        class="absolute inset-0 h-full w-full -rotate-90 transform"
                        viewBox="0 0 48 48"
                    >
                        <circle
                            cx="24"
                            cy="24"
                            :r="radius"
                            fill="none"
                            class="text-white/[0.04]"
                            stroke="currentColor"
                            stroke-width="2.5"
                        />
                        <!-- Progress ring -->
                        <circle
                            cx="24"
                            cy="24"
                            :r="radius"
                            fill="none"
                            :class="ringColor(item.percentage)"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashoffset(item.percentage)"
                            class="drop-shadow-lg transition-all duration-1000 ease-out"
                        />
                    </svg>
                    <span
                        class="relative text-sm font-semibold tracking-tighter"
                        :class="gradeColor(item.percentage)"
                    >
                        {{ Math.round(item.percentage)
                        }}<span class="text-[10px] font-medium opacity-60"
                            >%</span
                        >
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
