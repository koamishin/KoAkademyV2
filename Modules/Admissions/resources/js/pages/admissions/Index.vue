<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { store } from '@/routes/applications';
import type { AppPageProps } from '@/types';

type Period = { id: number; name: string };
type Application = {
    id: number;
    application_number: string;
    status: string;
    period: Period;
    program?: { name: string };
};

defineProps<{ applications: Application[]; periods: Period[] }>();

const page = usePage<AppPageProps>();
</script>

<template>
    <Head title="Applications" />
    <AppLayout :breadcrumbs="[{ title: 'Applications' }]">
        <div class="grid gap-6 p-4 lg:grid-cols-[1.2fr_0.8fr]">
            <section
                class="rounded-xl border bg-card p-6 text-card-foreground shadow-sm"
            >
                <div class="flex flex-col gap-1">
                    <p class="text-sm font-medium text-primary">Admissions</p>
                    <h1 class="text-2xl font-semibold">Your applications</h1>
                    <p class="text-sm text-muted-foreground">
                        Track every application and its current review status.
                    </p>
                </div>
                <div class="mt-6 grid gap-3">
                    <article
                        v-for="application in applications"
                        :key="application.id"
                        class="rounded-lg border p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-medium">
                                    {{ application.application_number }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ application.period.name
                                    }}<span v-if="application.program">
                                        · {{ application.program.name }}</span
                                    >
                                </p>
                            </div>
                            <span
                                class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary capitalize"
                                >{{
                                    application.status.replace('_', ' ')
                                }}</span
                            >
                        </div>
                    </article>
                    <p
                        v-if="applications.length === 0"
                        class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        No application has been submitted yet.
                    </p>
                </div>
            </section>

            <section
                class="rounded-xl border bg-card p-6 text-card-foreground shadow-sm"
            >
                <h2 class="text-lg font-semibold">Start an application</h2>
                <Form
                    v-bind="
                        store.form({
                            campus: page.props.currentCampus!.slug,
                        })
                    "
                    class="mt-5 grid gap-4"
                    #default="{ errors, processing }"
                >
                    <label class="grid gap-1 text-sm">
                        Admission period
                        <select
                            name="admission_period_id"
                            class="h-10 rounded-md border bg-background px-3"
                            required
                        >
                            <option value="">Choose a period</option>
                            <option
                                v-for="period in periods"
                                :key="period.id"
                                :value="period.id"
                            >
                                {{ period.name }}
                            </option>
                        </select>
                        <span class="text-xs text-destructive">{{
                            errors.admission_period_id
                        }}</span>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm"
                            >First name<input
                                name="first_name"
                                class="h-10 rounded-md border bg-background px-3"
                                required
                            /><span class="text-xs text-destructive">{{
                                errors.first_name
                            }}</span></label
                        >
                        <label class="grid gap-1 text-sm"
                            >Last name<input
                                name="last_name"
                                class="h-10 rounded-md border bg-background px-3"
                                required
                            /><span class="text-xs text-destructive">{{
                                errors.last_name
                            }}</span></label
                        >
                    </div>
                    <label class="grid gap-1 text-sm"
                        >Birth date<input
                            name="birth_date"
                            type="date"
                            class="h-10 rounded-md border bg-background px-3"
                    /></label>
                    <label class="grid gap-1 text-sm"
                        >Phone<input
                            name="phone"
                            class="h-10 rounded-md border bg-background px-3"
                    /></label>
                    <button
                        class="h-10 rounded-md bg-primary px-4 font-medium text-primary-foreground disabled:opacity-50"
                        :disabled="processing"
                    >
                        Submit application
                    </button>
                </Form>
            </section>
        </div>
    </AppLayout>
</template>
