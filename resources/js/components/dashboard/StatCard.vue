<script setup lang="ts">
import { BookOpen } from 'lucide-vue-next';

defineProps<{
    value: number | string;
    label: string;
    accentColor?: string;
    icon?: any;
}>();
</script>

<template>
    <div
        class="group relative flex h-full min-h-[140px] flex-col justify-between overflow-hidden rounded-[1.5rem] border border-border bg-card p-6 shadow-sm transition-all duration-700 hover:-translate-y-1 hover:border-primary/50 hover:bg-accent/50 hover:shadow-xl"
    >
        <div class="flex items-start justify-between">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/10 to-secondary/10"
            >
                <component :is="icon || BookOpen" class="h-6 w-6 text-primary" />
            </div>
            <div
                class="h-1.5 w-1.5 rounded-full bg-primary/50 transition-all duration-500 group-hover:bg-primary"
            ></div>
        </div>

        <div class="mt-auto min-w-0">
            <p
                class="text-4xl font-bold tracking-tighter text-foreground"
                :class="accentColor"
            >
                {{ value }}
            </p>
            <p
                class="mt-2 truncate text-sm font-medium tracking-wide text-muted-foreground"
            >
                {{ label }}
            </p>
        </div>
    </div>
</template>
