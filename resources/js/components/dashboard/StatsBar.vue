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
    <div class="flex flex-wrap items-center gap-6 border-b border-border/50 pb-6">
        <div class="flex items-center gap-3">
            <BookOpen class="h-4 w-4 text-muted-foreground" />
            <span class="text-2xl font-semibold text-foreground">{{ stats.totalClasses }}</span>
            <span class="text-sm text-muted-foreground">Classes</span>
        </div>
        
        <div class="h-6 w-px bg-border" />
        
        <div class="flex items-center gap-3">
            <Layers class="h-4 w-4 text-muted-foreground" />
            <span class="text-2xl font-semibold text-foreground">{{ stats.totalUnits }}</span>
            <span class="text-sm text-muted-foreground">Units</span>
        </div>
        
        <div class="h-6 w-px bg-border" />
        
        <div class="flex items-center gap-3">
            <AlertCircle class="h-4 w-4 text-muted-foreground" />
            <span class="text-2xl font-semibold text-foreground">{{ stats.pendingAssignments }}</span>
            <span class="text-sm text-muted-foreground">Pending</span>
        </div>
        
        <div class="h-6 w-px bg-border" />
        
        <div class="flex items-center gap-3">
            <Bell class="h-4 w-4 text-muted-foreground" />
            <span class="text-2xl font-semibold text-foreground">{{ stats.unreadAnnouncements }}</span>
            <span class="text-sm text-muted-foreground">New</span>
        </div>
    </div>
</template>
