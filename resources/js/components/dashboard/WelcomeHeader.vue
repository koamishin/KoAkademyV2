<script setup lang="ts">
import { GraduationCap, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';
import type {
    StudentInfo,
    AcademicContext,
    EnrollmentSummary,
} from '@/types/dashboard';

const props = defineProps<{
    student: StudentInfo | null;
    academicContext: AcademicContext | null;
    enrollment: EnrollmentSummary | null;
}>();

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) {
        return 'Good Morning';
    }
    if (hour < 17) {
        return 'Good Afternoon';
    }
    return 'Good Evening';
});

const enrollmentStatusColor = computed(() => {
    const status = props.enrollment?.status;
    switch (status) {
        case 'approved':
        case 'completed':
            return 'bg-emerald-500/10 text-emerald-500';
        case 'pending':
        case 'waitlisted':
            return 'bg-amber-500/10 text-amber-500';
        case 'cancelled':
            return 'bg-red-500/10 text-red-500';
        default:
            return 'bg-muted text-muted-foreground';
    }
});

const enrollmentStatusLabel = computed(() => {
    const status = props.enrollment?.status;
    if (!status) {
        return 'Not Enrolled';
    }
    return status.charAt(0).toUpperCase() + status.slice(1);
});
</script>

<template>
    <div class="py-8">
        <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
            <!-- Left: User Info & Greeting -->
            <div class="min-w-0 flex-1">
                <div class="min-w-0 flex items-start gap-4">
                    <!-- Avatar placeholder -->
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-accent">
                        <GraduationCap class="h-7 w-7 text-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            {{ greeting }}
                        </p>
                        <h1 class="mt-1 truncate text-2xl font-semibold text-foreground sm:text-3xl">
                            {{ student?.fullName ?? 'Student' }}
                        </h1>
                        <p
                            v-if="academicContext"
                            class="mt-2 text-sm text-muted-foreground"
                        >
                            {{ academicContext.academicYearName }}
                            <span
                                v-if="academicContext.termName"
                                class="px-1.5 text-border"
                            >·</span
                            >
                            <span v-if="academicContext.termName">
                                {{ academicContext.termName }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right: Status & Quick Actions -->
            <div class="flex flex-col items-start gap-3 md:items-end">
                <span
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wider"
                    :class="enrollmentStatusColor"
                >
                    <div class="h-1.5 w-1.5 rounded-full current" style="background-color: currentColor" />
                    {{ enrollmentStatusLabel }}
                </span>

                <div v-if="student?.studentNumber || enrollment?.studentNumber" class="flex flex-col items-start gap-1 md:items-end">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        Student ID
                    </span>
                    <span class="text-sm font-medium text-foreground">
                        {{ enrollment?.studentNumber ?? student?.studentNumber }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Context Row -->
        <div
            v-if="enrollment?.sectionName"
            class="mt-8 grid grid-cols-1 gap-6 border-t border-border/50 pt-8 md:grid-cols-3"
        >
            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider"
                    >Section</span
                >
                <span class="text-base font-medium text-foreground">
                    {{ enrollment.sectionName }}
                </span>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider"
                    >Enrolled Subjects</span
                >
                <span class="text-base font-medium text-foreground">
                    {{ enrollment.subjectsCount }}
                </span>
            </div>
            <div class="flex items-end justify-start md:justify-end">
                <button
                    class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                >
                    View Schedule
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
