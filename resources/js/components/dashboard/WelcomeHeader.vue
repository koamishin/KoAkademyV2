<script setup lang="ts">
import type {
    StudentInfo,
    AcademicContext,
    EnrollmentSummary,
} from '@/types/dashboard';
import { Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

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
        class="relative overflow-hidden rounded-[2rem] border border-white/[0.08] bg-white/[0.02] p-6 backdrop-blur-2xl transition-all duration-500 sm:p-8"
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
            class="relative z-10 flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
        >
            <div class="min-w-0 flex-1">
                <div class="flex items-start gap-4 sm:items-center">
                    <!-- Icon / Avatar Placeholder -->
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.125rem] bg-gradient-to-br from-violet-500/20 to-indigo-500/20 ring-1 ring-white/[0.08]"
                    >
                        <Sparkles class="h-6 w-6 text-violet-300" />
                    </div>
                    <div class="min-w-0">
                        <h1
                            class="truncate text-2xl font-semibold tracking-tight text-zinc-100 sm:text-3xl"
                        >
                            {{ greeting }},
                            <span
                                class="bg-gradient-to-r from-zinc-100 to-zinc-400 bg-clip-text text-transparent"
                                >{{ student?.firstName ?? 'Student' }}</span
                            >
                        </h1>
                        <p
                            v-if="academicContext"
                            class="mt-1 text-sm font-medium text-zinc-400"
                        >
                            {{ academicContext.academicYearName }}
                            <span
                                v-if="academicContext.termName"
                                class="px-1 text-zinc-600"
                                >·</span
                            >
                            <span v-if="academicContext.termName">{{
                                academicContext.termName
                            }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span
                    v-if="student?.studentNumber || enrollment?.studentNumber"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-white/[0.04] px-4 py-2 text-xs font-semibold tracking-wide text-zinc-300 ring-1 ring-white/[0.08]"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-violet-400" />
                    {{ enrollment?.studentNumber ?? student?.studentNumber }}
                </span>

                <span
                    class="inline-flex items-center rounded-xl px-4 py-2 text-xs font-bold tracking-wide ring-1"
                    :class="enrollmentStatusColor"
                >
                    {{ enrollmentStatusLabel }}
                </span>
            </div>
        </div>

        <!-- Meta / Context Row -->
        <div
            v-if="enrollment?.sectionName"
            class="relative z-10 mt-6 flex items-center gap-4 border-t border-white/[0.08] pt-5 text-sm font-medium text-zinc-400"
        >
            <div class="flex items-center gap-2">
                <span
                    class="rounded bg-white/10 px-1.5 py-0.5 text-xs text-zinc-300"
                    >Section</span
                >
                <span class="text-zinc-200">{{ enrollment.sectionName }}</span>
            </div>
            <span class="h-1 w-1 rounded-full bg-zinc-700"></span>
            <span>
                <strong class="font-semibold text-zinc-200">{{
                    enrollment.subjectsCount
                }}</strong>
                {{ enrollment.subjectsCount === 1 ? 'subject' : 'subjects' }}
            </span>
        </div>
    </div>
</template>
