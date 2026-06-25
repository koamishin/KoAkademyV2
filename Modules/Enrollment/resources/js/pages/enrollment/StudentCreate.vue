<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, UserRoundPlus } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/admin/students';
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

defineProps<{ options: Options }>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Student Records', href: index.url({ campus: campusSlug.value }) },
    { title: 'New Student' },
];
</script>

<template>
    <Head title="New Student" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex gap-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <UserRoundPlus />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-primary">Registrar intake</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Create student record</h1>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Add the student profile, guardian links, academic intent, compliance details, and first document packet from one full-page workflow.
                        </p>
                    </div>
                </div>

                <Button as-child variant="outline" class="gap-2">
                    <Link :href="index.url({ campus: campusSlug })">
                        <ArrowLeft data-icon="inline-start" />
                        Back to records
                    </Link>
                </Button>
            </header>

            <StudentRecordForm
                :options="options"
                :submit-url="store.url({ campus: campusSlug })"
                method="post"
                mode="create"
                submit-label="Create student record"
            />
        </div>
    </AppLayout>
</template>
