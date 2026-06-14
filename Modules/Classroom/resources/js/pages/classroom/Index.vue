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

function statusColor(status: string): string {
    switch (status.toLowerCase()) {
        case 'active':
            return 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20';
        case 'completed':
            return 'bg-blue-500/10 text-blue-400 ring-blue-500/20';
        default:
            return 'bg-white/5 text-zinc-400 ring-white/10';
    }
}
</script>

<template>
    <Head title="My Classes" />
    <AppLayout :breadcrumbs="[{ title: 'My Classes' }]">
        <div
            class="mx-auto flex w-full max-w-[1400px] flex-col gap-6 p-4 sm:p-6 lg:p-8"
        >
            <!-- Glassmorphic Banner -->
            <header
                class="relative overflow-hidden rounded-[2rem] border border-white/[0.08] bg-white/[0.02] p-8 backdrop-blur-2xl transition-all duration-500 sm:p-12"
            >
                <!-- Animated ambient light blobs (Amber/Orange tint) -->
                <div
                    class="pointer-events-none absolute -top-20 -right-20 h-80 w-80 animate-pulse rounded-full bg-amber-600/15 blur-[100px]"
                    style="animation-duration: 5s"
                />
                <div
                    class="pointer-events-none absolute -bottom-32 -left-20 h-[400px] w-[400px] animate-pulse rounded-full bg-orange-600/10 blur-[120px]"
                    style="animation-duration: 7s; animation-delay: 2s"
                />

                <div class="relative z-10">
                    <p
                        class="text-xs font-semibold tracking-widest text-zinc-500 uppercase"
                    >
                        Academic workspace
                    </p>
                    <h1
                        class="mt-2 text-4xl font-medium tracking-tighter text-zinc-100 sm:text-5xl"
                    >
                        My Classes
                    </h1>
                    <p
                        class="mt-4 max-w-2xl text-sm font-medium tracking-wide text-zinc-400"
                    >
                        Schedules, announcements, learning materials,
                        assignments, and submissions in one beautifully
                        organized place.
                    </p>
                </div>
            </header>

            <!-- Bento Grid -->
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="item in classes"
                    :key="item.id"
                    :href="show({ classOffering: item.id })"
                    class="group relative flex min-h-[160px] flex-col justify-between overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] p-6 backdrop-blur-xl transition-all duration-500 hover:-translate-y-1 hover:border-white/[0.12] hover:bg-white/[0.04] hover:shadow-2xl hover:shadow-black/40"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-bold tracking-widest text-zinc-500 uppercase transition-colors group-hover:text-amber-500/70"
                            >
                                {{ item.code }}
                            </p>
                            <h2
                                class="mt-1.5 truncate text-xl font-semibold tracking-tight text-zinc-100 transition-colors group-hover:text-amber-300"
                            >
                                {{ item.name }}
                            </h2>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold tracking-widest uppercase ring-1"
                            :class="statusColor(item.status)"
                        >
                            {{ item.status }}
                        </span>
                    </div>

                    <div class="mt-8 flex items-center gap-3">
                        <span
                            v-if="item.teacher"
                            class="flex items-center gap-2 text-xs font-medium tracking-wide text-zinc-400"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-zinc-600"
                            ></span>
                            {{ item.teacher.first_name }}
                            {{ item.teacher.last_name }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div
                v-if="classes.length === 0"
                class="mt-4 flex flex-col items-center justify-center rounded-[2rem] border border-white/[0.04] bg-white/[0.01] p-16 text-center backdrop-blur-sm"
            >
                <p
                    class="text-3xl font-semibold tracking-tighter text-zinc-300"
                >
                    Workspace Empty
                </p>
                <p class="mt-3 text-sm font-medium tracking-wide text-zinc-500">
                    Your assigned classes will appear here once enrollment is
                    finalized.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
