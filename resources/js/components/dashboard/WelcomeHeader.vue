<script setup lang="ts">
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
        return 'Good morning';
    }
    if (hour < 17) {
        return 'Good afternoon';
    }
    return 'Good evening';
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
        class="relative overflow-hidden rounded-[2rem] border border-white/[0.08] bg-white/[0.02] p-6 backdrop-blur-2xl transition-all duration-500 sm:p-10"
    >
        <!-- Animated ambient light blobs -->
        <div
            class="pointer-events-none absolute -top-20 -right-20 h-64 w-64 animate-pulse rounded-full bg-violet-600/20 blur-[80px]"
            style="animation-duration: 4s"
        />
        <div
            class="pointer-events-none absolute -bottom-32 -left-20 h-80 w-80 animate-pulse rounded-full bg-indigo-600/10 blur-[100px]"
            style="animation-duration: 6s; animation-delay: 1s"
        />

        <div
            class="relative z-10 flex flex-col gap-8 md:flex-row md:items-end md:justify-between"
        >
            <div class="min-w-0 flex-1">
                <div class="min-w-0">
                    <p
                        class="text-xs font-semibold tracking-widest text-zinc-500 uppercase"
                    >
                        {{ greeting }}
                    </p>
                    <h1
                        class="mt-2 truncate text-4xl font-medium tracking-tighter text-zinc-100 sm:text-5xl"
                    >
                        {{ student?.fullName ?? 'Student' }}
                    </h1>
                    <p
                        v-if="academicContext"
                        class="mt-3 text-sm font-medium tracking-wide text-zinc-400"
                    >
                        {{ academicContext.academicYearName }}
                        <span
                            v-if="academicContext.termName"
                            class="px-1.5 text-zinc-600"
                            >·</span
                        >
                        <span v-if="academicContext.termName">{{
                            academicContext.termName
                        }}</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-start gap-3 md:items-end">
                <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold tracking-wider uppercase ring-1"
                    :class="enrollmentStatusColor"
                >
                    {{ enrollmentStatusLabel }}
                </span>

                <span
                    v-if="student?.studentNumber || enrollment?.studentNumber"
                    class="text-sm font-medium tracking-widest text-zinc-500"
                >
                    {{ enrollment?.studentNumber ?? student?.studentNumber }}
                </span>
            </div>
        </div>

        <!-- Meta / Context Row -->
        <div
            v-if="enrollment?.sectionName"
            class="relative z-10 mt-8 flex items-center gap-6 border-t border-white/[0.06] pt-6 text-sm font-medium text-zinc-400"
        >
            <div class="flex flex-col">
                <span
                    class="text-[10px] font-bold tracking-widest text-zinc-600 uppercase"
                    >Section</span
                >
                <span class="mt-0.5 text-zinc-200">{{
                    enrollment.sectionName
                }}</span>
            </div>
            <div class="h-8 w-px bg-white/[0.06]"></div>
            <div class="flex flex-col">
                <span
                    class="text-[10px] font-bold tracking-widest text-zinc-600 uppercase"
                    >Enrolled Subjects</span
                >
                <span class="mt-0.5 text-zinc-200">{{
                    enrollment.subjectsCount
                }}</span>
            </div>
        </div>
    </div>
</template>
