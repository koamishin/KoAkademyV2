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
        class="group relative flex h-full min-h-[140px] flex-col justify-between overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] p-6 backdrop-blur-xl transition-all duration-500 hover:-translate-y-1 hover:border-white/[0.12] hover:bg-white/[0.04] hover:shadow-2xl hover:shadow-black/40"
    >
        <div class="flex items-start justify-between">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-[1.125rem] bg-white/[0.04] ring-1 ring-white/[0.08] transition-transform duration-500 group-hover:scale-110"
            >
                <component
                    :is="icon"
                    class="h-5.5 w-5.5"
                    :class="accentColor"
                />
            </div>
        </div>

        <div class="mt-6 min-w-0">
            <p class="text-3xl font-bold tracking-tight text-zinc-100">
                {{ value }}
            </p>
            <p class="mt-1 truncate text-sm font-medium text-zinc-400">
                {{ label }}
            </p>
        </div>
    </div>
</template>
