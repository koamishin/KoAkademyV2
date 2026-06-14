<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    value: number | string;
    label: string;
    accentColor?: string;
}>();
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border border-white/[0.06] bg-zinc-900/60 p-4 transition-all duration-300 hover:border-white/[0.1] hover:bg-zinc-900/80 sm:p-5"
    >
        <!-- Accent bar -->
        <div
            class="absolute top-0 left-0 h-full w-1 rounded-l-xl transition-all duration-300 group-hover:w-1.5"
            :class="
                accentColor ??
                'bg-gradient-to-b from-violet-500 to-violet-600/60'
            "
        />

        <div class="flex items-center gap-3.5 pl-2.5">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-800/80 ring-1 ring-white/[0.06] transition-transform duration-300 group-hover:scale-105"
            >
                <component :is="icon" class="h-5 w-5 text-zinc-400" />
            </div>

            <div class="min-w-0">
                <p class="text-2xl font-bold tracking-tight text-zinc-100">
                    {{ value }}
                </p>
                <p class="truncate text-xs font-medium text-zinc-500">
                    {{ label }}
                </p>
            </div>
        </div>
    </div>
</template>
