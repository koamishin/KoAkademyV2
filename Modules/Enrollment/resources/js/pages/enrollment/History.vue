<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

type EnrollmentSubject = {
    id: number;
    status: string;
    final_result: string | null;
    curriculum_item: {
        credit_units: string;
        subject: {
            code: string;
            name: string;
        };
    };
};

type Enrollment = {
    id: number;
    student_number: string | null;
    classification: string;
    status: string;
    period: {
        name: string;
    };
    curriculum: {
        name: string;
        code: string;
        program: {
            name: string;
            code: string;
        };
    };
    subjects: EnrollmentSubject[];
};

defineProps<{ enrollments: Enrollment[] }>();
</script>

<template>
    <Head title="Academic History" />

    <AppLayout :breadcrumbs="[{ title: 'Academic History' }]">
        <div class="px-4 py-6 sm:px-4 sm:py-8">
            <header class="py-6 sm:py-8">
                <p class="text-sm font-medium text-primary">Student records</p>
                <h1 class="mt-1 text-xl font-semibold sm:text-2xl">Academic history</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Curriculum, enrollment, and subject results for your
                    assigned campus.
                </p>
            </header>

            <section
                v-for="enrollment in enrollments"
                :key="enrollment.id"
                class="py-4 border-t border-border/50 first:border-t-0 sm:py-6"
            >
                <div
                    class="flex flex-col gap-3 pb-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-primary">
                            {{ enrollment.period.name }}
                        </p>
                        <h2 class="mt-1 text-base font-semibold sm:text-lg">
                            {{ enrollment.curriculum.program.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ enrollment.curriculum.name }}
                            · {{ enrollment.curriculum.code }}
                        </p>
                    </div>
                    <span
                        class="w-fit rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary capitalize"
                    >
                        {{ enrollment.status.replace('_', ' ') }}
                    </span>
                </div>

                <div class="divide-y divide-border/50">
                    <article
                        v-for="subject in enrollment.subjects"
                        :key="subject.id"
                        class="grid gap-2 py-3 sm:grid-cols-[1fr_auto_auto] sm:items-center"
                    >
                        <div>
                            <p class="text-sm font-medium sm:text-base">
                                {{ subject.curriculum_item.subject.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ subject.curriculum_item.subject.code }}
                            </p>
                        </div>
                        <p class="text-xs text-muted-foreground sm:text-sm">
                            {{ subject.curriculum_item.credit_units }} units
                        </p>
                        <p class="text-xs font-medium sm:text-sm">
                            {{ subject.final_result ?? subject.status }}
                        </p>
                    </article>
                </div>
            </section>

            <p
                v-if="enrollments.length === 0"
                class="py-12 text-center text-sm text-muted-foreground"
            >
                No academic history is available for your assigned campus.
            </p>
        </div>
    </AppLayout>
</template>
