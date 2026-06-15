<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Building2, Clock3 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    {
        title: 'Campus Assignment',
        href: '/campus-assignment-required',
    },
];
</script>

<template>
    <Head title="Campus Assignment Required" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[70vh] items-center justify-center p-6">
            <div
                class="w-full max-w-xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <Building2 class="size-7" />
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Campus assignment required
                    </h1>
                    <p class="text-sm leading-6 text-muted-foreground">
                        Your account is active, but it has not yet been assigned
                        to a campus. Please contact your school administrator.
                    </p>
                </div>

                <div
                    class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-muted/60 px-4 py-3 text-sm text-muted-foreground"
                >
                    <Clock3 class="size-4" />
                    You can sign in again after your campus is assigned.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
