<script setup lang="ts">
import { BookOpen, Layers, AlertCircle, Bell } from 'lucide-vue-next';

defineProps<{
    stats: {
        totalClasses: number;
        totalUnits: number;
        pendingAssignments: number;
        unreadAnnouncements: number;
    };
}>();
</script>

<template>
    <div class="flex items-center gap-4 overflow-x-auto border-b border-border/50 pb-6 scrollbar-hide sm:flex-wrap sm:gap-6">
        <div class="flex shrink-0 items-center gap-3">
            <BookOpen class="h-4 w-4 text-muted-foreground" />
            <span class="text-xl font-semibold text-foreground sm:text-2xl">{{ stats.totalClasses }}</span>
            <span class="text-sm text-muted-foreground">Classes</span>
        </div>
        
        <div class="hidden h-6 w-px bg-border sm:block" />
        
        <div class="flex shrink-0 items-center gap-3">
            <Layers class="h-4 w-4 text-muted-foreground" />
            <span class="text-xl font-semibold text-foreground sm:text-2xl">{{ stats.totalUnits }}</span>
            <span class="text-sm text-muted-foreground">Units</span>
        </div>
        
        <div class="hidden h-6 w-px bg-border sm:block" />
        
        <div class="flex shrink-0 items-center gap-3">
            <AlertCircle class="h-4 w-4 text-muted-foreground" />
            <span class="text-xl font-semibold text-foreground sm:text-2xl">{{ stats.pendingAssignments }}</span>
            <span class="text-sm text-muted-foreground">Pending</span>
        </div>
        
        <div class="hidden h-6 w-px bg-border sm:block" />
        
        <div class="flex shrink-0 items-center gap-3">
            <Bell class="h-4 w-4 text-muted-foreground" />
            <span class="text-xl font-semibold text-foreground sm:text-2xl">{{ stats.unreadAnnouncements }}</span>
            <span class="text-sm text-muted-foreground">New</span>
        </div>
    </div>
</template>
