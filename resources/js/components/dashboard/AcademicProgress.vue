<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as showClass } from '@/routes/classroom';

type GradeItem = {
    classOfferingId: number;
    className: string;
    subjectCode?: string | null;
    gradedCount: number;
    totalScore: number;
    totalPoints: number;
    percentage: number;
};

type AnnouncementItem = {
    id: number;
    title?: string | null;
    body: string;
    subjectName: string;
    subjectCode?: string | null;
    publishedAt: string;
    classOfferingId: number;
};

defineProps<{
    grades: GradeItem[];
    announcements: AnnouncementItem[];
    campus: { slug: string } | string;
}>();

function gradeColor(percentage: number): string {
    if (percentage >= 90) return 'text-emerald-500';
    if (percentage >= 75) return 'text-blue-500';
    if (percentage >= 60) return 'text-amber-500';
    return 'text-red-500';
}

function ringColor(percentage: number): string {
    if (percentage >= 90) return 'stroke-emerald-500';
    if (percentage >= 75) return 'stroke-blue-500';
    if (percentage >= 60) return 'stroke-amber-500';
    return 'stroke-red-500';
}

const radius = 20;
const circumference = 2 * Math.PI * radius;
function dashoffset(percentage: number): number {
    return circumference - (percentage / 100) * circumference;
}

function relativeTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMins < 60) {
        return diffMins <= 1 ? 'Just now' : `${diffMins}m ago`;
    }
    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }
    if (diffDays === 1) {
        return 'Yesterday';
    }
    if (diffDays < 7) {
        return `${diffDays}d ago`;
    }
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <div class="py-6 border-t border-border/50 sm:py-8">
        <h2 class="text-base font-semibold text-foreground sm:text-lg">Academic Progress</h2>
        
        <!-- Grades Section -->
        <div class="mt-6">
            <h3 class="mb-4 text-sm font-medium text-muted-foreground">Grades</h3>
            
            <div v-if="grades.length === 0" class="py-8 text-center">
                <p class="text-sm text-muted-foreground">No grades yet</p>
            </div>
            
            <div v-else class="space-y-4">
                <div
                    v-for="item in grades"
                    :key="item.classOfferingId"
                    class="flex items-center justify-between gap-3 rounded-lg p-3 sm:gap-4 sm:p-4 transition-colors hover:bg-accent/30"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-foreground sm:text-base">{{ item.className }}</p>
                        <div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                            <span v-if="item.subjectCode" class="font-medium uppercase">{{ item.subjectCode }}</span>
                            <span v-if="item.subjectCode" class="text-border">·</span>
                            <span>{{ item.gradedCount }} {{ item.gradedCount === 1 ? 'item' : 'items' }} graded</span>
                        </div>
                    </div>
                    
                    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center sm:h-12 sm:w-12">
                        <svg class="absolute inset-0 h-full w-full -rotate-90 transform" viewBox="0 0 48 48">
                            <circle
                                cx="24"
                                cy="24"
                                :r="radius"
                                fill="none"
                                class="text-border"
                                stroke="currentColor"
                                stroke-width="3"
                            />
                            <circle
                                cx="24"
                                cy="24"
                                :r="radius"
                                fill="none"
                                :class="ringColor(item.percentage)"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="dashoffset(item.percentage)"
                                class="transition-all duration-700 ease-out"
                            />
                        </svg>
                        <span class="relative text-[10px] font-bold sm:text-xs" :class="gradeColor(item.percentage)">
                            {{ Math.round(item.percentage) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements Section -->
        <div class="mt-6 sm:mt-8">
            <h3 class="mb-4 text-sm font-medium text-muted-foreground">Recent Announcements</h3>
            
            <div v-if="announcements.length === 0" class="py-8 text-center">
                <p class="text-sm text-muted-foreground">No recent announcements</p>
            </div>
            
            <div v-else class="space-y-3">
                <Link
                    v-for="item in announcements"
                    :key="item.id"
                    :href="showClass({ campus: campus, classOffering: item.classOfferingId })"
                    class="block rounded-lg p-3 transition-colors hover:bg-accent/30 sm:p-4"
                >
                    <div class="flex items-start justify-between gap-3 sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-foreground transition-colors hover:text-primary sm:text-base">
                                {{ item.title || 'Announcement' }}
                            </p>
                            <p class="mt-1 line-clamp-2 text-xs text-muted-foreground sm:text-sm">
                                {{ item.body }}
                            </p>
                            <div class="mt-2 flex items-center gap-2 text-[10px] text-muted-foreground sm:text-xs">
                                <span class="font-medium uppercase">{{ item.subjectCode || item.subjectName }}</span>
                                <span class="text-border">·</span>
                                <span>{{ relativeTime(item.publishedAt) }}</span>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
