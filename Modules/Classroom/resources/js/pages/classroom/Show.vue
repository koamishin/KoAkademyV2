<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/classroom';
defineProps<{ classroom: any }>();
</script>
<template>
    <Head :title="classroom.name" />
    <AppLayout
        :breadcrumbs="[
            { title: 'My Classes', href: index().url },
            { title: classroom.name },
        ]"
    >
        <div class="grid gap-6 p-4 lg:grid-cols-[0.75fr_1.5fr]">
            <aside class="grid content-start gap-4">
                <section class="rounded-xl border bg-card p-5 shadow-sm">
                    <p
                        class="text-xs font-semibold tracking-wider text-primary uppercase"
                    >
                        {{ classroom.code }}
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold">
                        {{ classroom.name }}
                    </h1>
                </section>
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="font-semibold">Schedule</h2>
                    <div class="mt-3 grid gap-2 text-sm text-muted-foreground">
                        <p
                            v-for="meeting in classroom.meetings"
                            :key="meeting.id"
                        >
                            {{
                                meeting.meeting_date ||
                                `Day ${meeting.day_of_week}`
                            }}
                            · {{ meeting.starts_at }}–{{ meeting.ends_at }}
                        </p>
                        <p v-if="!classroom.meetings.length">
                            No meetings configured.
                        </p>
                    </div>
                </section>
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="font-semibold">Assignments</h2>
                    <div class="mt-3 grid gap-3">
                        <article
                            v-for="assignment in classroom.assignments"
                            :key="assignment.id"
                            class="rounded-lg bg-muted/50 p-3"
                        >
                            <p class="font-medium">{{ assignment.title }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    assignment.due_at
                                        ? `Due ${assignment.due_at}`
                                        : 'No due date'
                                }}
                            </p>
                        </article>
                        <p
                            v-if="!classroom.assignments.length"
                            class="text-sm text-muted-foreground"
                        >
                            No published assignments.
                        </p>
                    </div>
                </section>
            </aside>
            <main class="grid content-start gap-4">
                <article
                    v-for="post in classroom.posts"
                    :key="post.id"
                    class="rounded-xl border bg-card p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <span
                            class="text-xs font-semibold tracking-wider text-primary uppercase"
                            >{{ post.type }}</span
                        ><time class="text-xs text-muted-foreground">{{
                            post.published_at || post.created_at
                        }}</time>
                    </div>
                    <h2 v-if="post.title" class="mt-3 text-lg font-semibold">
                        {{ post.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ post.body }}
                    </p>
                </article>
                <p
                    v-if="!classroom.posts.length"
                    class="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground"
                >
                    The class stream is quiet for now.
                </p>
            </main>
        </div>
    </AppLayout>
</template>
