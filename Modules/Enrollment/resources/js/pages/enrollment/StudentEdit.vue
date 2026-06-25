<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArchiveRestore, FileCheck2, GraduationCap, ShieldCheck, UserRoundPen } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, update } from '@/routes/admin/students';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import StudentRecordForm from '../../components/StudentRecordForm.vue';

type Option = { id: number; name: string; code?: string | null };
type AcademicLevel = { id: number; name: string; code?: string | null; category?: string | null };
type ProgramOption = Option & { curricula: Option[]; educationLevel?: AcademicLevel | null };
type SelectOption = { value: string; label: string; required?: boolean };
type SectionOption = { id: number; programId?: number | null; name: string; code?: string | null; yearLevel?: number | null };
type AcademicStyle = { level: 'college' | 'high_school' | 'elementary'; label: string };
type Options = {
    academicStyle?: AcademicStyle;
    statuses: string[];
    classifications: SelectOption[];
    incomeBrackets: Record<string, string>;
    documentTypes: SelectOption[];
    programs: ProgramOption[];
    sections?: SectionOption[];
};
type Student = {
    id: number;
    fullName: string;
    studentNumber?: string | null;
    status: string;
    deletedAt?: string | null;
    documentSummary: {
        verifiedCount: number;
        requiredCount: number;
        ready: boolean;
    };
    guardians: unknown[];
};

const props = defineProps<{
    student: Student;
    form: Record<string, unknown>;
    documents: unknown[];
    transferCredits: unknown[];
    enrollments: unknown[];
    options: Options;
}>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Student Records', href: index.url({ campus: campusSlug.value }) },
    { title: props.student.fullName, href: show.url({ campus: campusSlug.value, student: props.student.id }) },
    { title: 'Edit' },
];
</script>

<template>
    <Head :title="`Edit ${student.fullName}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex gap-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <UserRoundPen />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-primary">Student lifecycle</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Edit {{ student.fullName }}</h1>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ student.studentNumber ?? 'No student number' }} / {{ student.status }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline" class="gap-2">
                        <Link :href="show.url({ campus: campusSlug, student: student.id })">
                            <ArrowLeft data-icon="inline-start" />
                            Profile
                        </Link>
                    </Button>
                    <Button as-child variant="outline" class="gap-2">
                        <Link :href="index.url({ campus: campusSlug })">Records</Link>
                    </Button>
                </div>
            </header>

            <section class="grid gap-4 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardDescription>Documents</CardDescription>
                        <CardTitle>{{ student.documentSummary.verifiedCount }}/{{ student.documentSummary.requiredCount }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Badge :variant="student.documentSummary.ready ? 'default' : 'secondary'">
                            <ShieldCheck class="mr-1 size-3" />
                            {{ student.documentSummary.ready ? 'Ready' : 'Needs review' }}
                        </Badge>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Guardians</CardDescription>
                        <CardTitle>{{ student.guardians.length }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Enrollments</CardDescription>
                        <CardTitle>{{ enrollments.length }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <GraduationCap class="text-muted-foreground" />
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Archive state</CardDescription>
                        <CardTitle>{{ student.deletedAt ? 'Archived' : 'Active' }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ArchiveRestore class="text-muted-foreground" />
                    </CardContent>
                </Card>
            </section>

            <StudentRecordForm
                :options="options"
                :initial="form"
                :submit-url="update.url({ campus: campusSlug, student: student.id })"
                method="patch"
                mode="edit"
                submit-label="Save student record"
            />

            <section class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Documents on file</CardTitle>
                        <CardDescription>{{ documents.length }} document records are preserved outside this profile form.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <FileCheck2 class="text-muted-foreground" />
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Transfer reviews</CardTitle>
                        <CardDescription>{{ transferCredits.length }} transfer credit evaluations remain available on the profile page.</CardDescription>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Enrollment history</CardTitle>
                        <CardDescription>{{ enrollments.length }} enrollment records stay linked while profile details are edited.</CardDescription>
                    </CardHeader>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
