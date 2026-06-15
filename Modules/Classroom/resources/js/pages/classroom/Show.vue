<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/classroom';
import type { AppPageProps } from '@/types';
defineProps<{ classroom: any }>();
const page = usePage<AppPageProps>();
</script>

<template>
    <Head :title="classroom.name" />
    <AppLayout
        :breadcrumbs="[
            {
                title: 'My Classes',
                href: index({
                    campus: page.props.currentCampus!.slug,
                }).url,
            },
            { title: classroom.name },
        ]"
    >
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

                <div
                    class="relative z-10 flex flex-col items-start gap-4 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-widest text-zinc-500 uppercase"
                        >
                            {{ classroom.code }}
                        </p>
                        <h1
                            class="mt-2 text-4xl font-medium tracking-tighter text-zinc-100 sm:text-5xl"
                        >
                            {{ classroom.name }}
                        </h1>
                    </div>
                </div>
            </header>

            <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
                <!-- Left Sidebar: Schedule & Assignments -->
                <aside class="flex flex-col gap-6">
                    <!-- Schedule -->
                    <section
                        class="flex flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] backdrop-blur-xl"
                    >
                        <div class="border-b border-white/[0.04] px-8 py-6">
                            <h2
                                class="text-sm font-semibold tracking-wide text-zinc-100 uppercase"
                            >
                                Schedule
                            </h2>
                        </div>
                        <div
                            v-if="classroom.meetings.length === 0"
                            class="p-8 text-center"
                        >
                            <p
                                class="text-sm font-medium tracking-wide text-zinc-500"
                            >
                                No meetings configured.
                            </p>
                        </div>
                        <div v-else class="flex flex-col p-4">
                            <div
                                v-for="meeting in classroom.meetings"
                                :key="meeting.id"
                                class="rounded-2xl p-4 transition-colors hover:bg-white/[0.04]"
                            >
                                <p
                                    class="text-base font-medium tracking-tight text-zinc-100"
                                >
                                    {{
                                        meeting.meeting_date ||
                                        `Day ${meeting.day_of_week}`
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs font-medium tracking-wide text-zinc-500"
                                >
                                    {{ formatTime(meeting.starts_at) }} —
                                    {{ formatTime(meeting.ends_at) }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Assignments -->
                    <section
                        class="flex flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] backdrop-blur-xl"
                    >
                        <div class="border-b border-white/[0.04] px-8 py-6">
                            <h2
                                class="text-sm font-semibold tracking-wide text-zinc-100 uppercase"
                            >
                                Assignments
                            </h2>
                        </div>
                        <div
                            v-if="classroom.assignments.length === 0"
                            class="p-8 text-center"
                        >
                            <p
                                class="text-sm font-medium tracking-wide text-zinc-500"
                            >
                                No published assignments.
                            </p>
                        </div>
                        <div v-else class="flex flex-col p-4">
                            <article
                                v-for="assignment in classroom.assignments"
                                :key="assignment.id"
                                class="rounded-2xl p-4 transition-colors hover:bg-white/[0.04]"
                            >
                                <p
                                    class="text-base font-medium tracking-tight text-zinc-100"
                                >
                                    {{ assignment.title }}
                                </p>
                                <p
                                    class="mt-1 text-xs font-medium tracking-wide"
                                    :class="
                                        assignment.due_at
                                            ? 'text-amber-500/80'
                                            : 'text-zinc-500'
                                    "
                                >
                                    {{ formatDate(assignment.due_at) }}
                                </p>
                            </article>
                        </div>
                    </section>
                </aside>

                <!-- Right Main Area: Stream/Posts -->
                <main class="flex flex-col gap-6">
                    <div
                        v-if="classroom.posts.length === 0"
                        class="flex flex-col items-center justify-center rounded-[2rem] border border-white/[0.04] bg-white/[0.01] p-16 text-center backdrop-blur-sm"
                    >
                        <p
                            class="text-2xl font-semibold tracking-tighter text-zinc-300"
                        >
                            Stream Empty
                        </p>
                        <p
                            class="mt-3 text-sm font-medium tracking-wide text-zinc-500"
                        >
                            The class stream is quiet for now.
                        </p>
                    </div>

                    <article
                        v-for="post in classroom.posts"
                        :key="post.id"
                        class="flex flex-col overflow-hidden rounded-[1.5rem] border border-white/[0.08] bg-white/[0.02] p-8 backdrop-blur-xl transition-all duration-500 hover:bg-white/[0.03]"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <span
                                class="rounded bg-white/[0.06] px-2 py-1 text-[10px] font-bold tracking-widest text-zinc-300 uppercase"
                            >
                                {{ post.type }}
                            </span>
                            <time
                                class="text-xs font-medium tracking-wide text-zinc-500"
                            >
                                {{
                                    relativeTime(
                                        post.published_at || post.created_at,
                                    )
                                }}
                            </time>
                        </div>
                        <h2
                            v-if="post.title"
                            class="mt-5 text-xl font-semibold tracking-tight text-zinc-100"
                        >
                            {{ post.title }}
                        </h2>
                        <p
                            class="mt-4 text-sm leading-relaxed font-medium tracking-wide whitespace-pre-wrap text-zinc-400"
                        >
                            {{ post.body }}
                        </p>
                    </article>
                </main>
            </div>
        </div>
    </AppLayout>
</template>
