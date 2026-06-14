<script setup lang="ts">
import type {
    StudentInfo,
    AcademicContext,
    EnrollmentSummary,
} from '@/types/dashboard';
import { GraduationCap } from 'lucide-vue-next';
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
            return 'bg-emerald-500/15 text-emerald-400 ring-emerald-500/30';
        case 'pending':
        case 'waitlisted':
            return 'bg-amber-500/15 text-amber-400 ring-amber-500/30';
        case 'cancelled':
            return 'bg-red-500/15 text-red-400 ring-red-500/30';
        default:
            return 'bg-zinc-500/15 text-zinc-400 ring-zinc-500/30';
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
        class="relative overflow-hidden rounded-2xl border border-white/[0.06] bg-gradient-to-br from-zinc-900/80 via-zinc-900/60 to-zinc-800/40 p-5 sm:p-7"
    >
        <!-- Decorative gradient orb -->
        <div
            class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full bg-gradient-to-br from-violet-600/20 to-indigo-600/10 blur-3xl"
        />
        <div
            class="pointer-events-none absolute -bottom-12 -left-12 h-36 w-36 rounded-full bg-gradient-to-tr from-emerald-600/10 to-cyan-600/5 blur-2xl"
        />

        <div
            class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 shadow-lg shadow-violet-500/20"
                    >
                        <GraduationCap class="h-5.5 w-5.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h1
                            class="truncate text-xl font-semibold tracking-tight text-zinc-100 sm:text-2xl"
                        >
                            {{ greeting }},
                            {{ student?.firstName ?? 'Student' }}!
                        </h1>
                        <p
                            v-if="academicContext"
                            class="mt-0.5 text-sm text-zinc-400"
                        >
                            {{ academicContext.academicYearName }}
                            <span
                                v-if="academicContext.termName"
                                class="text-zinc-500"
                            >
                                · {{ academicContext.termName }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <span
                    v-if="student?.studentNumber || enrollment?.studentNumber"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-800/80 px-3 py-1.5 text-xs font-medium tracking-wide text-zinc-300 ring-1 ring-white/[0.06]"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-violet-400" />
                    {{ enrollment?.studentNumber ?? student?.studentNumber }}
                </span>

                <span
                    class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold ring-1"
                    :class="enrollmentStatusColor"
                >
                    {{ enrollmentStatusLabel }}
                </span>
            </div>
        </div>

        <!-- Section & subjects info -->
        <div
            v-if="enrollment?.sectionName"
            class="relative mt-4 flex items-center gap-4 border-t border-white/[0.04] pt-4 text-sm text-zinc-400"
        >
            <span>
                Section:
                <span class="font-medium text-zinc-300">
                    {{ enrollment.sectionName }}
                </span>
            </span>
            <span class="text-zinc-600">•</span>
            <span>
                {{ enrollment.subjectsCount }}
                {{ enrollment.subjectsCount === 1 ? 'subject' : 'subjects' }}
                enrolled
            </span>
        </div>
    </div>
</template>
