<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { show } from '@/routes/classroom';
type Classroom = {
    id: number;
    name: string;
    code: string;
    status: string;
    teacher?: { first_name: string; last_name: string };
};
defineProps<{ classes: Classroom[] }>();
</script>
<template>
    <Head title="My Classes" />
    <AppLayout :breadcrumbs="[{ title: 'My Classes' }]">
        <div class="p-4">
            <header
                class="rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 p-8 text-white shadow-sm"
            >
                <p class="text-sm font-medium text-white/80">
                    Academic workspace
                </p>
                <h1 class="mt-1 text-3xl font-semibold">My classes</h1>
                <p class="mt-2 max-w-xl text-sm text-white/85">
                    Schedules, announcements, learning materials, assignments,
                    and submissions in one place.
                </p>
            </header>
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="item in classes"
                    :key="item.id"
                    :href="show(item.id)"
                    class="group rounded-xl border bg-card p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-xs font-semibold tracking-wider text-primary uppercase"
                            >
                                {{ item.code }}
                            </p>
                            <h2 class="mt-1 text-lg font-semibold">
                                {{ item.name }}
                            </h2>
                        </div>
                        <span
                            class="rounded-full bg-muted px-2.5 py-1 text-xs capitalize"
                            >{{ item.status }}</span
                        >
                    </div>
                    <p
                        v-if="item.teacher"
                        class="mt-5 text-sm text-muted-foreground"
                    >
                        {{ item.teacher.first_name }}
                        {{ item.teacher.last_name }}
                    </p>
                </Link>
            </div>
            <p
                v-if="classes.length === 0"
                class="mt-6 rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                Your assigned classes will appear here.
            </p>
        </div>
    </AppLayout>
</template>
