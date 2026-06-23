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
            return 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20';
        case 'pending':
        case 'waitlisted':
            return 'bg-amber-500/10 text-amber-400 ring-amber-500/20';
        case 'cancelled':
            return 'bg-red-500/10 text-red-400 ring-red-500/20';
        default:
            return 'bg-white/5 text-zinc-400 ring-white/10';
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
    <div
        class="group relative overflow-hidden rounded-[2rem] border border-border bg-card p-6 shadow-sm transition-all duration-700 hover:shadow-xl hover:-translate-y-0.5"
    >
        <!-- Decorative gradient blobs -->
        <div
            class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-gradient-to-br from-blue-500/10 to-purple-500/10 blur-[100px]"
        />
        <div
            class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-gradient-to-tr from-indigo-500/10 to-emerald-500/10 blur-[120px]"
        />

        <div
            class="relative z-10 flex flex-col gap-8 md:flex-row md:items-start md:justify-between"
        >
            <!-- Left: User Info & Greeting -->
            <div class="min-w-0 flex-1">
                <div class="min-w-0 flex items-start gap-6">
                    <!-- Avatar placeholder -->
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 p-0.5">
                        <div class="flex h-full w-full items-center justify-center rounded-full bg-card">
                            <GraduationCap class="h-10 w-10 text-primary" />
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-xs font-semibold tracking-[0.3em] text-muted-foreground uppercase"
                        >
                            {{ greeting }}
                        </p>
                        <h1
                            class="mt-2 truncate text-4xl font-semibold tracking-tighter text-foreground sm:text-5xl"
                        >
                            {{ student?.fullName ?? 'Student' }}
                        </h1>
                        <p
                            v-if="academicContext"
                            class="mt-3 text-sm font-medium text-muted-foreground"
                        >
                            {{ academicContext.academicYearName }}
                            <span
                                v-if="academicContext.termName"
                                class="px-1.5 text-zinc-500"
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
            <div class="flex flex-col items-start gap-4 md:items-end">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-semibold tracking-[0.2em] uppercase ring-1 transition-all duration-300"
                        :class="enrollmentStatusColor"
                    >
                        <div class="h-1.5 w-1.5 rounded-full current" style="background-color: currentColor" />
                        {{ enrollmentStatusLabel }}
                    </span>
                </div>

                <div class="flex flex-col items-start gap-1">
                    <span
                        v-if="student?.studentNumber || enrollment?.studentNumber"
                        class="text-xs font-semibold tracking-[0.3em] text-muted-foreground uppercase"
                    >
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
            class="relative z-10 mt-10 grid grid-cols-1 gap-6 border-t border-border/50 pt-8 md:grid-cols-3"
        >
            <div class="flex flex-col gap-2">
                <span class="text-[10px] font-bold tracking-[0.3em] text-muted-foreground uppercase"
                    >Section</span
                >
                <span class="text-lg font-semibold text-foreground">
                    {{ enrollment.sectionName }}
                </span>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-[10px] font-bold tracking-[0.3em] text-muted-foreground uppercase"
                    >Enrolled Subjects</span
                >
                <span class="text-lg font-semibold text-foreground">
                    {{ enrollment.subjectsCount }}
                </span>
            </div>
            <div class="flex items-end justify-start md:justify-end">
                <button
                    class="group inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-xs font-semibold text-foreground transition-all duration-300 hover:bg-accent hover:text-accent-foreground hover:border-transparent"
                >
                    View Schedule
                    <ChevronRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                </button>
            </div>
        </div>
    </div>
</template>
