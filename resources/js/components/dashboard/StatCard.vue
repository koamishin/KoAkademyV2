<script setup lang="ts">
defineProps<{
    value: number | string;
    label: string;
    accentColor?: string;
}>();
</script>

<template>
    <div
        class="group relative flex h-full min-h-[140px] flex-col justify-between overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] p-6 backdrop-blur-xl transition-all duration-500 hover:-translate-y-1 hover:border-white/[0.12] hover:bg-white/[0.04] hover:shadow-2xl hover:shadow-black/40"
    >
        <div class="mt-auto min-w-0">
            <p
                class="text-4xl font-semibold tracking-tighter text-zinc-100"
                :class="accentColor"
            >
                {{ value }}
            </p>
            <p
                class="mt-2 truncate text-sm font-medium tracking-wide text-zinc-500"
            >
                {{ label }}
            </p>
        </div>
    </div>
</template>
