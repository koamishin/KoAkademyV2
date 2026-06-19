<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Search, UserRoundPlus, UsersRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, store } from '@/routes/admin/students';
import type { AppPageProps, BreadcrumbItem } from '@/types';

type StudentRow = {
    id: number;
    fullName: string;
    studentNumber?: string | null;
    email?: string | null;
    phone?: string | null;
    status: string;
    enrollmentsCount: number;
    currentEnrollment?: {
        id: number;
        status: string;
        classification?: string | null;
        period?: string | null;
        term?: string | null;
        academicYear?: string | null;
        program?: string | null;
        curriculum?: string | null;
        section?: string | null;
    } | null;
};

type Option = { id: number; name: string; code?: string | null };

const props = defineProps<{
    students: {
        data: StudentRow[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | null>;
    summary: {
        total: number;
        activeEnrollments: number;
        waiting: number;
    };
    options: {
        statuses: string[];
        enrollmentStatuses: { value: string; label: string }[];
        programs: (Option & { curricula: Option[] })[];
        sections: (Option & { programId: number; termId: number })[];
        terms: Option[];
    };
}>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Student Records' }];
const createDialogOpen = ref(false);

const filterForm = ref({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    enrollment_status: props.filters.enrollment_status ?? '',
    term: props.filters.term ?? '',
    program: props.filters.program ?? '',
    section: props.filters.section ?? '',
});

const createForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    birth_date: '',
    sex: '',
    email: '',
    phone: '',
    address: '',
    status: 'active',
    student_number: '',
    metadata: {
        learner_reference_number: '',
        previous_school: '',
        emergency_contact: '',
    },
    guardians: [
        {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            relationship: '',
            is_primary: true,
            has_portal_access: true,
        },
    ],
});

const statusClass = (status: string) => {
    if (['approved', 'active'].includes(status)) {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (['pending', 'waitlisted', 'draft'].includes(status)) {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-muted text-muted-foreground';
};

function applyFilters() {
    router.get(index.url({ campus: campusSlug.value }), filterForm.value, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    filterForm.value = {
        search: '',
        status: '',
        enrollment_status: '',
        term: '',
        program: '',
        section: '',
    };
    applyFilters();
}

function addGuardian() {
    createForm.guardians.push({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        relationship: '',
        is_primary: createForm.guardians.length === 0,
        has_portal_access: true,
    });
}

function removeGuardian(index: number) {
    createForm.guardians.splice(index, 1);
}

function submitCreate() {
    createForm.post(store.url({ campus: campusSlug.value }), {
        preserveScroll: true,
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Student Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-primary">Registrar workspace</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Student records</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ students.total }} students in view
                    </p>
                </div>

                <Dialog v-model:open="createDialogOpen">
                    <DialogTrigger as-child>
                        <Button class="gap-2">
                            <UserRoundPlus class="size-4" />
                            New student
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Create student record</DialogTitle>
                            <DialogDescription>
                                Create the learner profile, campus student role, and guardian links.
                            </DialogDescription>
                        </DialogHeader>

                        <form class="grid gap-6 py-4" @submit.prevent="submitCreate">
                            <section class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="first-name">First name</Label>
                                    <Input id="first-name" v-model="createForm.first_name" />
                                    <p v-if="createForm.errors.first_name" class="text-sm text-destructive">
                                        {{ createForm.errors.first_name }}
                                    </p>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="last-name">Last name</Label>
                                    <Input id="last-name" v-model="createForm.last_name" />
                                    <p v-if="createForm.errors.last_name" class="text-sm text-destructive">
                                        {{ createForm.errors.last_name }}
                                    </p>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="middle-name">Middle name</Label>
                                    <Input id="middle-name" v-model="createForm.middle_name" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="suffix">Suffix</Label>
                                    <Input id="suffix" v-model="createForm.suffix" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="student-number">Student number</Label>
                                    <Input id="student-number" v-model="createForm.student_number" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="birth-date">Birth date</Label>
                                    <Input id="birth-date" v-model="createForm.birth_date" type="date" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="sex">Sex</Label>
                                    <Input id="sex" v-model="createForm.sex" placeholder="Female, male, or school-defined value" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="status">Status</Label>
                                    <select id="status" v-model="createForm.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                                        <option v-for="status in options.statuses" :key="status" :value="status">
                                            {{ status }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="email">Email</Label>
                                    <Input id="email" v-model="createForm.email" type="email" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="phone">Phone</Label>
                                    <Input id="phone" v-model="createForm.phone" />
                                </div>
                                <div class="grid gap-2 md:col-span-2">
                                    <Label for="address">Address</Label>
                                    <Input id="address" v-model="createForm.address" />
                                </div>
                            </section>

                            <section class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label for="lrn">Learner reference number</Label>
                                    <Input id="lrn" v-model="createForm.metadata.learner_reference_number" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="previous-school">Previous school</Label>
                                    <Input id="previous-school" v-model="createForm.metadata.previous_school" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="emergency-contact">Emergency contact</Label>
                                    <Input id="emergency-contact" v-model="createForm.metadata.emergency_contact" />
                                </div>
                            </section>

                            <section class="grid gap-4">
                                <div class="flex items-center justify-between gap-4">
                                    <h2 class="text-sm font-semibold">Guardians</h2>
                                    <Button type="button" variant="outline" size="sm" class="gap-2" @click="addGuardian">
                                        <Plus class="size-4" />
                                        Add guardian
                                    </Button>
                                </div>

                                <article
                                    v-for="(guardian, guardianIndex) in createForm.guardians"
                                    :key="guardianIndex"
                                    class="grid gap-3 rounded-md border bg-muted/30 p-4 md:grid-cols-2"
                                >
                                    <Input v-model="guardian.first_name" placeholder="First name" />
                                    <Input v-model="guardian.last_name" placeholder="Last name" />
                                    <Input v-model="guardian.relationship" placeholder="Relationship" />
                                    <Input v-model="guardian.phone" placeholder="Phone" />
                                    <Input v-model="guardian.email" type="email" placeholder="Email" />
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="flex items-center gap-2 text-sm">
                                            <input v-model="guardian.is_primary" type="checkbox" class="size-4" />
                                            Primary
                                        </label>
                                        <Button type="button" variant="ghost" size="sm" @click="removeGuardian(guardianIndex)">
                                            Remove
                                        </Button>
                                    </div>
                                </article>
                            </section>

                            <DialogFooter>
                                <Button type="submit" :disabled="createForm.processing">Create student</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-lg border bg-card p-5">
                    <UsersRound class="size-5 text-primary" />
                    <p class="mt-4 text-3xl font-semibold">{{ summary.total }}</p>
                    <p class="text-sm text-muted-foreground">Active student records</p>
                </article>
                <article class="rounded-lg border bg-card p-5">
                    <p class="text-3xl font-semibold">{{ summary.activeEnrollments }}</p>
                    <p class="text-sm text-muted-foreground">Pending or approved enrollments</p>
                </article>
                <article class="rounded-lg border bg-card p-5">
                    <p class="text-3xl font-semibold">{{ summary.waiting }}</p>
                    <p class="text-sm text-muted-foreground">Draft or waitlisted</p>
                </article>
            </section>

            <section class="rounded-lg border bg-card p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-[1.4fr_repeat(5,minmax(0,1fr))_auto]">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
                        <Input v-model="filterForm.search" class="pl-9" placeholder="Search name, number, email, phone" @keyup.enter="applyFilters" />
                    </div>
                    <select v-model="filterForm.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any profile</option>
                        <option v-for="status in options.statuses" :key="status" :value="status">{{ status }}</option>
                    </select>
                    <select v-model="filterForm.enrollment_status" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any enrollment</option>
                        <option v-for="status in options.enrollmentStatuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <select v-model="filterForm.term" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any term</option>
                        <option v-for="term in options.terms" :key="term.id" :value="String(term.id)">
                            {{ term.name }}
                        </option>
                    </select>
                    <select v-model="filterForm.program" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any program</option>
                        <option v-for="program in options.programs" :key="program.id" :value="String(program.id)">
                            {{ program.code ?? program.name }}
                        </option>
                    </select>
                    <select v-model="filterForm.section" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any section</option>
                        <option v-for="section in options.sections" :key="section.id" :value="String(section.id)">
                            {{ section.code ?? section.name }}
                        </option>
                    </select>
                    <div class="flex gap-2">
                        <Button type="button" size="sm" @click="applyFilters">Apply</Button>
                        <Button type="button" size="sm" variant="ghost" @click="clearFilters">Reset</Button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border bg-card shadow-sm">
                <article
                    v-for="student in students.data"
                    :key="student.id"
                    class="grid gap-4 border-b p-5 last:border-b-0 xl:grid-cols-[1.1fr_1.5fr_auto]"
                >
                    <div>
                        <Link
                            :href="show.url({ campus: campusSlug, student: student.id })"
                            class="font-medium tracking-tight hover:text-primary"
                        >
                            {{ student.fullName }}
                        </Link>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ student.studentNumber ?? 'No student number' }}
                            <span v-if="student.email"> / {{ student.email }}</span>
                        </p>
                    </div>
                    <div class="text-sm text-muted-foreground">
                        <p class="font-medium text-foreground">
                            {{ student.currentEnrollment?.program ?? 'No current enrollment' }}
                        </p>
                        <p>
                            {{ student.currentEnrollment?.period ?? 'No period' }}
                            <span v-if="student.currentEnrollment?.section"> / {{ student.currentEnrollment.section }}</span>
                            <span v-if="student.currentEnrollment?.curriculum"> / {{ student.currentEnrollment.curriculum }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 xl:justify-end">
                        <Badge :class="statusClass(student.status)" class="capitalize">{{ student.status }}</Badge>
                        <Badge v-if="student.currentEnrollment" :class="statusClass(student.currentEnrollment.status)" class="capitalize">
                            {{ student.currentEnrollment.status.replace('_', ' ') }}
                        </Badge>
                    </div>
                </article>
                <p v-if="students.data.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No student records match the current filters.
                </p>
            </section>

            <nav v-if="students.last_page > 1" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in students.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="[
                        link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </div>
    </AppLayout>
</template>
